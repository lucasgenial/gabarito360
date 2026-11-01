<?php
namespace App\Services;

use App\Models\Usuario;
use App\Support\{ContextoInterno, TokenInterno};
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\{Rule, ValidationException};

class GestaoAcessosInternos
{
    public function autorizar(Usuario $ator): void
    {
        abort_unless(TokenInterno::habilitado() && ContextoInterno::pode($ator,'acesso_interno.gerir'),403);
    }

    private function bloquear(): object
    {
        $controle = DB::table('acessos_internos_controle')->where('id',1)->lockForUpdate()->first();
        abort_unless($controle,503);
        return $controle;
    }

    private function motivo(array $dados): array
    {
        if (is_string($dados['motivo'] ?? null)) $dados['motivo'] = trim($dados['motivo']);
        return Validator::make($dados, ['motivo'=>'required|string|min:5|max:200'])->validate();
    }

    private function bloquearAtor(Usuario $ator): void
    {
        Usuario::query()->whereKey($ator->id)->lockForUpdate()->firstOrFail();
        DB::table('usuario_acessos_internos')->where('id',$ator->acessoInternoContextoId())->lockForUpdate()->first();
        $this->autorizar($ator);
    }

    private function alvo(Usuario $ator, int $id): Usuario
    {
        $u = Usuario::query()->lockForUpdate()->findOrFail($id);
        abort_if($u->id === $ator->id || DB::table('usuario_acessos_internos')->where('usuario_id',$id)->where('perfil','desenvolvedor')->exists(),403);
        return $u;
    }

    public function conceder(Usuario $ator, array $dados): object
    {
        $this->autorizar($ator);
        if (array_diff(array_keys($dados),['email','perfil','fim_em','motivo'])) throw ValidationException::withMessages(['acesso'=>'Campos não permitidos.']);
        $v = Validator::make($dados, ['email'=>'required|email|max:254','perfil'=>['required',Rule::in(['suporte','financeiro'])],
            'fim_em'=>'nullable|date|after:now'])->validate() + $this->motivo($dados);
        return DB::transaction(function () use ($ator,$v) {
            $this->bloquear(); $this->bloquearAtor($ator);
            $u = Usuario::porEmail($v['email'])->first();
            if (!$u) throw ValidationException::withMessages(['email'=>'Conta elegível não encontrada.']);
            $u = $this->alvo($ator,$u->id);
            if (!$u->ativo || $u->forcar_troca_senha) throw ValidationException::withMessages(['email'=>'Conta elegível não encontrada.']);
            if (DB::table('usuario_acessos_internos')->where('usuario_id',$u->id)->where('perfil',$v['perfil'])->exists()) {
                throw ValidationException::withMessages(['perfil'=>'A atribuição já existe. Edite sua situação e vigência.']);
            }
            $id = DB::table('usuario_acessos_internos')->insertGetId(['usuario_id'=>$u->id,'perfil'=>$v['perfil'],
                'situacao'=>'ativo','inicio_em'=>now(),'fim_em'=>$this->data($v['fim_em'] ?? null),'versao_autorizacao'=>1,
                'criado_por'=>$ator->id,'created_at'=>now(),'updated_at'=>now()]);
            $atual = DB::table('usuario_acessos_internos')->find($id);
            $this->evento($atual,$ator->id,'concessao',$v['motivo'],null);
            return $atual;
        });
    }

    public function atualizar(Usuario $ator, int $id, array $dados): object
    {
        $this->autorizar($ator);
        if (array_diff(array_keys($dados),['situacao','fim_em','versao_autorizacao','motivo'])) throw ValidationException::withMessages(['acesso'=>'Pessoa e perfil não podem ser alterados.']);
        $v = Validator::make($dados,['situacao'=>['required',Rule::in(['ativo','suspenso'])],
            'fim_em'=>'present|nullable|date','versao_autorizacao'=>'required|integer|min:1'])->validate() + $this->motivo($dados);
        return DB::transaction(function () use ($ator,$id,$v) {
            $this->bloquear(); $this->bloquearAtor($ator);
            $a = DB::table('usuario_acessos_internos')->where('id',$id)->lockForUpdate()->first();
            abort_unless($a,404);
            abort_unless(in_array($a->perfil,['suporte','financeiro'],true),403);
            $u = $this->alvo($ator,$a->usuario_id);
            if ($v['situacao']==='ativo' && (!$u->ativo || $u->forcar_troca_senha)) throw ValidationException::withMessages(['situacao'=>'A conta não pode ser ativada.']);
            abort_unless((int)$a->versao_autorizacao === (int)$v['versao_autorizacao'],409,'Este acesso mudou. Atualize a página antes de editar.');
            DB::table('usuario_acessos_internos')->where('id',$id)->update(['situacao'=>$v['situacao'],
                'fim_em'=>$this->data($v['fim_em']), 'versao_autorizacao'=>$a->versao_autorizacao+1,'updated_at'=>now()]);
            $atual = DB::table('usuario_acessos_internos')->find($id);
            $this->evento($atual,$ator->id,'alteracao',$v['motivo'],$a);
            return $atual;
        });
    }

    private function data(?string $valor): ?string { return $valor ? \Illuminate\Support\Carbon::parse($valor)->utc()->format('Y-m-d H:i:s') : null; }

    private function estado(object $a): array
    {
        return array_intersect_key((array)$a,array_flip(['usuario_id','perfil','situacao','inicio_em','fim_em','versao_autorizacao']));
    }

    private function evento(object $a, ?int $ator, string $acao, string $motivo, ?object $anterior): void
    {
        DB::table('acesso_interno_eventos')->insert(['acesso_id'=>$a->id,'ator_id'=>$ator,'acao'=>$acao,
            'origem'=>$ator ? 'aplicacao' : 'console','motivo'=>$motivo,
            'anterior'=>$anterior ? json_encode($this->estado($anterior),JSON_THROW_ON_ERROR) : null,
            'atual'=>json_encode($this->estado($a),JSON_THROW_ON_ERROR),'created_at'=>now()]);
    }

    public function iniciarDesenvolvedor(int $id, bool $aplicar, string $motivo): array
    {
        if ($aplicar) $this->motivo(['motivo'=>$motivo]);
        return DB::transaction(function () use ($id,$aplicar,$motivo) {
            $controle = $this->bloquear();
            abort_if($controle->bootstrap_em || DB::table('usuario_acessos_internos')->where('perfil','desenvolvedor')->exists(),409,'Bootstrap já encerrado ou Desenvolvedor existente.');
            $u = Usuario::query()->lockForUpdate()->findOrFail($id);
            abort_unless($u->ativo && !$u->forcar_troca_senha,422,'A conta deve estar ativa e com a senha inicial trocada.');
            if ($aplicar) {
                abort_unless(app()->isDownForMaintenance() && !config('acessos_internos.ativo'),409,'Aplicação exige manutenção e acesso interno desabilitado.');
                $aid = DB::table('usuario_acessos_internos')->insertGetId(['usuario_id'=>$id,'perfil'=>'desenvolvedor',
                    'situacao'=>'ativo','inicio_em'=>now(),'versao_autorizacao'=>1,'created_at'=>now(),'updated_at'=>now()]);
                $this->evento(DB::table('usuario_acessos_internos')->find($aid),null,'bootstrap',$motivo,null);
                DB::table('acessos_internos_controle')->where('id',1)->update(['bootstrap_em'=>now()]);
            }
            return ['usuario_id'=>$id,'aplicado'=>$aplicar];
        });
    }
}

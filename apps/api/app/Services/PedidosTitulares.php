<?php
namespace App\Services;

use App\Models\Usuario;
use App\Support\{ContextoInterno, TokenInterno, EstadoPedidoTitular, DadoProtegido};
use Illuminate\Support\Facades\{DB, Crypt, Validator};
use Illuminate\Support\Str;
use Illuminate\Validation\{Rule, ValidationException};
use DomainException;

final class PedidosTitulares
{
    private const TIPOS = ['acesso','correcao','anonimizacao','eliminacao','informacao'];

    private function ator(Usuario $u): object
    {
        abort_unless(config('pedidos_titulares.ativo') && TokenInterno::habilitado(), 403);
        $a = ContextoInterno::resolver($u);
        abort_unless(in_array($a->perfil, ['desenvolvedor','suporte'], true), 403);
        abort_unless(DB::table('usuarios')->where('id',$u->id)->where('forcar_troca_senha',false)->exists(),403);
        return $a;
    }

    private function bloquear(Usuario $u): object
    {
        // Mesma ordem usada pela gestão dos acessos: revogação serializa com escrita.
        abort_unless(DB::table('acessos_internos_controle')->where('id',1)->lockForUpdate()->first(), 503);
        return $this->ator($u);
    }

    private function validar(array $dados, array $regras): array
    {
        if (array_diff(array_keys($dados), array_keys($regras))) {
            throw ValidationException::withMessages(['pedido'=>'Campos não permitidos.']);
        }
        foreach ($dados as $chave => $valor) {
            if (is_string($valor)) $dados[$chave] = trim($valor);
        }
        return Validator::make($dados, $regras)->validate();
    }

    private function escopo(int $cliente, int $espaco): void
    {
        abort_unless(DB::table('clientes')->where('id',$cliente)->where('situacao','ativo')->lockForUpdate()->first(), 404);
        abort_unless(DB::table('espacos')->where('id',$espaco)->where('cliente_id',$cliente)->where('situacao','ativo')->lockForUpdate()->first(), 404);
    }

    private function responsavel(int $id): object
    {
        $a = DB::table('usuario_acessos_internos as a')->join('usuarios as u','u.id','=','a.usuario_id')
            ->where('a.id',$id)->where('u.ativo',true)->where('u.forcar_troca_senha',false)
            ->where('a.situacao','ativo')->whereIn('a.perfil',['desenvolvedor','suporte'])
            ->where('a.inicio_em','<=',now())->where(fn ($q) => $q->whereNull('a.fim_em')->orWhere('a.fim_em','>',now()))
            ->select('a.*')->lockForUpdate()->first();
        abort_unless($a, 404);
        return $a;
    }

    private function atribuicoes(object $a)
    {
        return DB::table('pedidos_titulares')->where('responsavel_acesso_id',$a->id)
            ->where('responsavel_versao',$a->versao_autorizacao)
            ->where(fn ($q) => $q->whereNull('atribuicao_fim_em')->orWhere('atribuicao_fim_em','>',now()))
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('clientes')->whereColumn('clientes.id','pedidos_titulares.cliente_id')->where('clientes.situacao','ativo'))
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('espacos')->whereColumn('espacos.id','pedidos_titulares.espaco_id')->whereColumn('espacos.cliente_id','pedidos_titulares.cliente_id')->where('espacos.situacao','ativo'));
    }

    private function recibo(object $a, string $acao, array $dados): ?array
    {
        $r = DB::table('pedido_titular_recibos')->where('acesso_id',$a->id)->where('acesso_versao',$a->versao_autorizacao)
            ->where('acao',$acao)->where('chave',$dados['chave'])->first();
        if (!$r) return null;
        abort_unless(hash_equals($r->payload_hash, $this->hash($dados)), 409, 'Chave já utilizada para outro pedido.');
        return json_decode($r->resposta,true,512,JSON_THROW_ON_ERROR);
    }

    private function hash(array $dados): string
    {
        ksort($dados);
        return DadoProtegido::hash(json_encode($dados,JSON_THROW_ON_ERROR), 'pedido_titular_payload');
    }

    private function gravarRecibo(object $a, string $acao, array $dados, object $p): array
    {
        $resposta = ['referencia'=>$p->id,'situacao'=>$p->situacao,'versao'=>(int)$p->versao];
        DB::table('pedido_titular_recibos')->insert(['acesso_id'=>$a->id,'acesso_versao'=>$a->versao_autorizacao,
            'acao'=>$acao,'chave'=>$dados['chave'],'payload_hash'=>$this->hash($dados),'pedido_id'=>$p->id,
            'resposta'=>json_encode($resposta,JSON_THROW_ON_ERROR),'created_at'=>now()]);
        return $resposta;
    }

    private function evento(Usuario $u, object $a, object $p, string $acao, ?string $anterior, string $motivo): void
    {
        DB::table('pedido_titular_eventos')->insert(['pedido_id'=>$p->id,'ator_id'=>$u->id,'acesso_id'=>$a->id,
            'acao'=>$acao,'anterior'=>$anterior,'atual'=>$p->situacao,'motivo'=>Crypt::encryptString($motivo),'ocorrido_em'=>now()]);
    }

    public function abrir(Usuario $u, array $dados): array
    {
        abort_unless($this->ator($u)->perfil === 'desenvolvedor',403);
        $v = $this->validar($dados, ['cliente_id'=>'required|integer|min:1','espaco_id'=>'required|integer|min:1',
            'responsavel_acesso_id'=>'required|integer|min:1','tipo'=>['required',Rule::in(self::TIPOS)],
            'identificacao_declarada'=>'required|string|max:200','relato'=>'required|string|max:2000',
            'motivo'=>'required|string|min:5|max:500','atribuicao_fim_em'=>'nullable|date','chave'=>'required|uuid']);
        return DB::transaction(function () use ($u,$v) {
            $a = $this->bloquear($u); abort_unless($a->perfil === 'desenvolvedor',403);
            $this->escopo((int)$v['cliente_id'],(int)$v['espaco_id']);
            $responsavel = $this->responsavel((int)$v['responsavel_acesso_id']);
            if ($resposta = $this->recibo($a,'abrir',$v)) return $resposta;
            if (!empty($v['atribuicao_fim_em'])) Validator::make($v,['atribuicao_fim_em'=>'date|after:now'])->validate();
            $id = (string) Str::uuid();
            DB::table('pedidos_titulares')->insert(['id'=>$id,'cliente_id'=>$v['cliente_id'],'espaco_id'=>$v['espaco_id'],
                'responsavel_acesso_id'=>$responsavel->id,'responsavel_versao'=>$responsavel->versao_autorizacao,
                'atribuicao_fim_em'=>empty($v['atribuicao_fim_em']) ? null : \Illuminate\Support\Carbon::parse($v['atribuicao_fim_em'])->utc()->format('Y-m-d H:i:s'),
                'criado_por'=>$u->id,'tipo'=>$v['tipo'],'situacao'=>'recebida','versao'=>1,
                'identificacao_declarada'=>Crypt::encryptString($v['identificacao_declarada']), 'relato'=>Crypt::encryptString($v['relato']),
                'created_at'=>now(),'updated_at'=>now()]);
            $p = DB::table('pedidos_titulares')->where('id',$id)->first();
            $this->evento($u,$a,$p,'abertura',null,$v['motivo']);
            return $this->gravarRecibo($a,'abrir',$v,$p);
        });
    }

    public function listar(Usuario $u, int $pagina = 1)
    {
        $a = $this->ator($u);
        return $this->atribuicoes($a)->select('id as referencia','cliente_id','espaco_id','tipo','situacao','versao','created_at','updated_at')
            ->selectSub(DB::table('clientes')->select('nome')->whereColumn('clientes.id','pedidos_titulares.cliente_id'),'cliente_nome')
            ->selectSub(DB::table('espacos')->select('nome')->whereColumn('espacos.id','pedidos_titulares.espaco_id'),'espaco_nome')
            ->orderByDesc('created_at')->orderBy('id')->paginate(20,['*'],'page',$pagina);
    }

    public function opcoes(Usuario $u, array $dados): array
    {
        abort_unless($this->ator($u)->perfil === 'desenvolvedor',403);
        $v = $this->validar($dados,['cliente_id'=>'nullable|integer|min:1','clientes_page'=>'sometimes|integer|min:1',
            'espacos_page'=>'sometimes|integer|min:1','responsaveis_page'=>'sometimes|integer|min:1']);
        $cliente = empty($v['cliente_id']) ? null : DB::table('clientes')->where('id',$v['cliente_id'])->where('situacao','ativo')->first(['id','nome']);
        abort_if(!empty($v['cliente_id']) && !$cliente,404);
        $responsaveis = DB::table('usuario_acessos_internos as a')->join('usuarios as u','u.id','=','a.usuario_id')
            ->whereIn('a.perfil',['desenvolvedor','suporte'])->where('a.situacao','ativo')->where('u.ativo',true)
            ->where('u.forcar_troca_senha',false)->where('a.inicio_em','<=',now())
            ->where(fn ($q) => $q->whereNull('a.fim_em')->orWhere('a.fim_em','>',now()))
            ->select('a.id','a.perfil','u.nome')->orderBy('a.id')->paginate(20,['*'],'responsaveis_page',$v['responsaveis_page'] ?? 1);
        return ['cliente'=>$cliente,
            'clientes'=>DB::table('clientes')->where('situacao','ativo')->select('id','nome')->orderBy('nome')->orderBy('id')->paginate(20,['*'],'clientes_page',$v['clientes_page'] ?? 1),
            'espacos'=>$cliente ? DB::table('espacos')->where('cliente_id',$cliente->id)->where('situacao','ativo')->select('id','nome','tipo')->orderBy('id')->paginate(20,['*'],'espacos_page',$v['espacos_page'] ?? 1) : null,
            'responsaveis'=>$responsaveis];
    }

    public function consultar(Usuario $u, string $id): array
    {
        $this->ator($u);
        return DB::transaction(function () use ($u,$id) {
            $a = $this->bloquear($u);
            $p = $this->atribuicoes($a)->where('id',$id)->lockForUpdate()->first(); abort_unless($p,404);
            $this->escopo((int)$p->cliente_id,(int)$p->espaco_id);
            $this->evento($u,$a,$p,'consulta',$p->situacao,'Consulta do protocolo atribuído.');
            return ['referencia'=>$p->id,'tipo'=>$p->tipo,'situacao'=>$p->situacao,'versao'=>(int)$p->versao,
                'cliente_id'=>(int)$p->cliente_id,'espaco_id'=>(int)$p->espaco_id,
                'cliente_nome'=>DB::table('clientes')->where('id',$p->cliente_id)->value('nome'),
                'espaco_nome'=>DB::table('espacos')->where('id',$p->espaco_id)->value('nome'),
                'identificacao_declarada'=>Crypt::decryptString($p->identificacao_declarada),
                'relato'=>Crypt::decryptString($p->relato),'resultado'=>$p->resultado,
                'ciclo_verificacao'=>(int)$p->ciclo_verificacao,
                'aluno_id'=>$p->aluno_id === null ? null : (int)$p->aluno_id,
                'sujeito_versao'=>(int)$p->sujeito_versao,
                'resposta_referencia'=>$p->resposta_referencia === null ? null : Crypt::decryptString($p->resposta_referencia),
                'verificacoes'=>$this->verificacoes($p)->orderBy('tipo')->get()->map(fn ($v) => [
                    'tipo'=>$v->tipo,'metodo'=>$v->metodo,'registrada_em'=>$v->registrada_em,'valida_ate'=>$v->valida_ate,
                    'vigente'=>\Illuminate\Support\Carbon::parse($v->valida_ate)->isFuture(),
                    'evidencia_referencia'=>Crypt::decryptString($v->evidencia_referencia),
                ])->all()];
        });
    }

    public function triar(Usuario $u, string $id, array $dados): array
    {
        $this->ator($u);
        $v = $this->validar($dados, ['situacao'=>['required',Rule::in(['em_verificacao','aguardando_informacao','cancelada'])],
            'versao'=>'required|integer|min:1','motivo'=>'required|string|min:5|max:500',
            'resultado'=>['nullable',Rule::in(['cancelado_pelo_solicitante','duplicado','inviavel_verificar'])],'chave'=>'required|uuid']);
        return DB::transaction(function () use ($u,$id,$v) {
            $a = $this->bloquear($u);
            $p = $this->atribuicoes($a)->where('id',$id)->lockForUpdate()->first(); abort_unless($p,404);
            $this->escopo((int)$p->cliente_id,(int)$p->espaco_id);
            if ($resposta = $this->recibo($a,'triar:'.$id,$v)) return $resposta;
            abort_unless((int)$p->versao === (int)$v['versao'],409,'Protocolo alterado. Atualize a consulta.');
            try { $destino = EstadoPedidoTitular::from($p->situacao)->transicionar(EstadoPedidoTitular::from($v['situacao']), resultado: $v['resultado'] ?? null); }
            catch (DomainException) { throw ValidationException::withMessages(['situacao'=>'Transição de protocolo indisponível.']); }
            $anterior = $p->situacao; $p->situacao = $destino->value; $p->versao++;
            DB::table('pedidos_titulares')->where('id',$id)->update(['situacao'=>$p->situacao,'versao'=>$p->versao,
                'ciclo_verificacao'=>$p->ciclo_verificacao + ($destino === EstadoPedidoTitular::AguardandoInformacao ? 1 : 0),
                'resultado'=>$v['resultado'] ?? null,'concluido_em'=>$destino->terminal() ? now() : null,'updated_at'=>now()]);
            $this->evento($u,$a,$p,'triagem',$anterior,$v['motivo']);
            return $this->gravarRecibo($a,'triar:'.$id,$v,$p);
        });
    }

    public function vincularSujeito(Usuario $u, string $id, array $dados): array
    {
        $this->ator($u);
        abort_unless(\App\Support\Espacos::ativo(),403);
        $v = $this->validar($dados, ['aluno_id'=>'required|integer|min:1','versao'=>'required|integer|min:1',
            'evidencia_referencia'=>'required|string|min:5|max:500','motivo'=>'required|string|min:5|max:500','chave'=>'required|uuid']);
        return DB::transaction(function () use ($u,$id,$v) {
            $a = $this->bloquear($u);
            $p = $this->atribuicoes($a)->where('id',$id)->lockForUpdate()->first(); abort_unless($p,404);
            $this->escopo((int)$p->cliente_id,(int)$p->espaco_id);
            if ($resposta = $this->recibo($a,'sujeito:'.$id,$v)) return $resposta;
            abort_unless((int)$p->versao === (int)$v['versao'],409,'Protocolo alterado. Atualize a consulta.');
            abort_unless(in_array($p->situacao,['recebida','em_verificacao','aguardando_informacao'],true),422,'Vinculação indisponível nesta etapa.');
            $e = DB::table('espacos')->where('id',$p->espaco_id)->first();
            abort_unless($e && \App\Support\ContextoVinculo::espacoValido($e),404);
            abort_if($e->tipo === 'pessoal' && !config('espacos.pessoal',false),403);
            $aluno = DB::table('alunos')->where('id',$v['aluno_id'])->where('espaco_id',$p->espaco_id)->lockForUpdate()->first();
            abort_unless($aluno,404);
            $turma = DB::table('turmas')->where('id',$aluno->turma_id)->where('espaco_id',$p->espaco_id)->lockForUpdate()->first();
            abort_unless($turma && (($e->tipo === 'pessoal' && $turma->escola_id === null)
                || ($e->tipo === 'escola' && (int)$turma->escola_id === (int)$e->escola_id)),404);
            $evidencia = Crypt::encryptString($v['evidencia_referencia']);
            DB::table('pedido_titular_sujeitos')->insert(['pedido_id'=>$id,'versao'=>$p->sujeito_versao+1,
                'aluno_id'=>$aluno->id,'ator_id'=>$u->id,'evidencia'=>$evidencia,'registrado_em'=>now()]);
            $anterior=$p->situacao;$p->situacao='em_verificacao';$p->versao++;
            DB::table('pedidos_titulares')->where('id',$id)->update(['aluno_id'=>$aluno->id,
                'sujeito_versao'=>$p->sujeito_versao+1,'sujeito_evidencia'=>$evidencia,
                'ciclo_verificacao'=>$p->ciclo_verificacao+1,'situacao'=>$p->situacao,'versao'=>$p->versao,'updated_at'=>now()]);
            $this->evento($u,$a,$p,'vincular_sujeito',$anterior,$v['motivo']);
            return $this->gravarRecibo($a,'sujeito:'.$id,$v,$p);
        });
    }

    private function verificacoes(object $p)
    {
        return DB::table('pedido_titular_verificacoes')->where('pedido_id',$p->id)->where('ciclo',$p->ciclo_verificacao)
            ->where('acesso_id',$p->responsavel_acesso_id)->where('acesso_versao',$p->responsavel_versao);
    }

    public function verificar(Usuario $u, string $id, array $dados): array
    {
        $this->ator($u);
        $v = $this->validar($dados, ['tipo'=>['required',Rule::in(['identidade','autoridade'])],
            'metodo'=>['required',Rule::in(['conferencia_documental','canal_autenticado','confirmacao_institucional','instrumento_representacao'])],
            'evidencia_referencia'=>'required|string|min:5|max:500','valida_ate'=>'required|date',
            'versao'=>'required|integer|min:1','motivo'=>'required|string|min:5|max:500','chave'=>'required|uuid']);
        $metodos = $v['tipo'] === 'identidade' ? ['conferencia_documental','canal_autenticado'] : ['confirmacao_institucional','instrumento_representacao'];
        if (!in_array($v['metodo'],$metodos,true)) throw ValidationException::withMessages(['metodo'=>'Método incompatível com a conferência.']);
        return DB::transaction(function () use ($u,$id,$v) {
            $a = $this->bloquear($u);
            $p = $this->atribuicoes($a)->where('id',$id)->lockForUpdate()->first(); abort_unless($p,404);
            $this->escopo((int)$p->cliente_id,(int)$p->espaco_id);
            if ($resposta = $this->recibo($a,'verificar:'.$id,$v)) return $resposta;
            abort_unless((int)$p->versao === (int)$v['versao'],409,'Protocolo alterado. Atualize a consulta.');
            if ($p->situacao !== 'em_verificacao') throw ValidationException::withMessages(['pedido'=>'Protocolo fora da etapa de verificação.']);
            Validator::make($v,['valida_ate'=>'date|after:now'])->validate();
            abort_if($this->verificacoes($p)->where('tipo',$v['tipo'])->exists(),409,'Conferência já registrada neste ciclo.');
            DB::table('pedido_titular_verificacoes')->insert(['pedido_id'=>$id,'ciclo'=>$p->ciclo_verificacao,
                'tipo'=>$v['tipo'],'metodo'=>$v['metodo'],'ator_id'=>$u->id,'acesso_id'=>$a->id,'acesso_versao'=>$a->versao_autorizacao,
                'evidencia_referencia'=>Crypt::encryptString($v['evidencia_referencia']),'registrada_em'=>now(),
                'valida_ate'=>\Illuminate\Support\Carbon::parse($v['valida_ate'])->utc()->format('Y-m-d H:i:s')]);
            $p->versao++;
            DB::table('pedidos_titulares')->where('id',$id)->update(['versao'=>$p->versao,'updated_at'=>now()]);
            $this->evento($u,$a,$p,'verificar_'.$v['tipo'],$p->situacao,$v['motivo']);
            return $this->gravarRecibo($a,'verificar:'.$id,$v,$p);
        });
    }

    public function decidir(Usuario $u, string $id, array $dados): array
    {
        $this->ator($u);
        $v = $this->validar($dados, ['situacao'=>['required',Rule::in(['em_analise','concluida'])],
            'versao'=>'required|integer|min:1','motivo'=>'required|string|min:5|max:500','chave'=>'required|uuid',
            'resultado'=>['required_if:situacao,concluida','prohibited_unless:situacao,concluida',Rule::in(['atendido','parcialmente_atendido','indeferido'])],
            'resposta_referencia'=>'required_if:situacao,concluida|prohibited_unless:situacao,concluida|string|min:5|max:500']);
        return DB::transaction(function () use ($u,$id,$v) {
            $a = $this->bloquear($u);
            $p = $this->atribuicoes($a)->where('id',$id)->lockForUpdate()->first(); abort_unless($p,404);
            $this->escopo((int)$p->cliente_id,(int)$p->espaco_id);
            if ($resposta = $this->recibo($a,'decidir:'.$id,$v)) return $resposta;
            abort_unless((int)$p->versao === (int)$v['versao'],409,'Protocolo alterado. Atualize a consulta.');
            $tipos = $this->verificacoes($p)->where('valida_ate','>',now())->pluck('tipo')->all();
            try {
                $destino = EstadoPedidoTitular::from($p->situacao)->transicionar(EstadoPedidoTitular::from($v['situacao']),
                    in_array('identidade',$tipos,true),in_array('autoridade',$tipos,true),$v['resultado'] ?? null);
            } catch (DomainException) { throw ValidationException::withMessages(['pedido'=>'Transição indisponível ou conferências pendentes.']); }
            $anterior = $p->situacao; $p->situacao = $destino->value; $p->versao++;
            DB::table('pedidos_titulares')->where('id',$id)->update(['situacao'=>$p->situacao,'versao'=>$p->versao,
                'resultado'=>$v['resultado'] ?? null,
                'resposta_referencia'=>isset($v['resposta_referencia']) ? Crypt::encryptString($v['resposta_referencia']) : null,
                'concluido_em'=>$destino->terminal() ? now() : null,'updated_at'=>now()]);
            $this->evento($u,$a,$p,'decisao',$anterior,$v['motivo']);
            return $this->gravarRecibo($a,'decidir:'.$id,$v,$p);
        });
    }
}

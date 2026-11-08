<?php
namespace App\Services;

use App\Models\Usuario;
use App\Support\{CatalogoPaineis, CatalogoWidgetsPainel, ContextoVinculo, OrganizacaoPainel, PadroesPainel, Permissao};
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\ValidationException;

/** Personalização não contém dados nem altera autorização. Nenhum contexto vem do cliente. */
final class PreferenciasPainel
{
    use \Illuminate\Database\DetectsConcurrencyErrors;

    private function contexto(Usuario $ator, string $painel, ?int $turmaId, bool $travar = false): array
    {
        abort_unless(ContextoVinculo::presente($ator), 403, 'Selecione um vínculo válido.');
        if ($travar) DB::table('usuario_vinculos')->where('id', $ator->vinculoContextoId())->where('usuario_id', $ator->id)->lockForUpdate()->first();
        $v = ContextoVinculo::resolver($ator);
        if ($travar) DB::table('espacos')->where('id', $v->espaco_id)->lockForUpdate()->first();
        Permissao::limparCache();
        $v = ContextoVinculo::resolver($ator);
        abort_unless(Permissao::pode($ator, 'turma.ver'), 403);
        $recurso=CatalogoPaineis::recurso($ator,$painel,$turmaId,$travar);
        $dados = ['usuario_id'=>(int)$ator->id, 'vinculo_id'=>(int)$v->id, 'espaco_id'=>(int)$v->espaco_id, 'perfil'=>$v->perfil]+$recurso;
        return $dados + ['contexto_hash'=>hash('sha256', json_encode($dados, JSON_THROW_ON_ERROR))];
    }

    private function falhar(string $campo, string $mensagem = 'Configuração de painel inválida.'): never
    {
        throw ValidationException::withMessages([$campo=>$mensagem]);
    }

    public function validar(array $pedido, array $catalogo, string $painel=''): array
    {
        if (array_diff(array_keys($pedido), ['versao','chave','acao','apresentacao','layouts','alvos'])) $this->falhar('preferencias');
        Validator::make($pedido, ['versao'=>'required|integer|min:0|max:2147483646', 'chave'=>'required|uuid', 'acao'=>'required|in:salvar,restaurar'])->validate();
        if (!is_int($pedido['versao'])) $this->falhar('versao');
        $pedido['chave'] = strtolower($pedido['chave']);
        if ($pedido['acao'] === 'restaurar') {
            if (array_key_exists('layouts', $pedido) || array_key_exists('apresentacao', $pedido)) $this->falhar('alvos');
            $alvos = $pedido['alvos'] ?? null;
            if (!is_array($alvos) || !array_is_list($alvos) || !$alvos || count($alvos)>3 || count(array_unique($alvos, SORT_REGULAR))!==count($alvos)) $this->falhar('alvos');
            foreach ($alvos as $alvo) if (!is_string($alvo) || !in_array($alvo, ['desktop','mobile','apresentacao'], true)) $this->falhar('alvos');
            sort($pedido['alvos'], SORT_STRING);
            return $pedido;
        }
        if (array_key_exists('alvos', $pedido)) $this->falhar('alvos');
        if (!array_key_exists('apresentacao', $pedido) && !array_key_exists('layouts', $pedido)) $this->falhar('preferencias');
        if (array_key_exists('apresentacao', $pedido) && !in_array($pedido['apresentacao'], ['tabela','cards'], true)) $this->falhar('apresentacao');
        if (array_key_exists('layouts', $pedido)) {
            if (!is_array($pedido['layouts']) || !$pedido['layouts'] || array_diff(array_keys($pedido['layouts']), ['desktop','mobile'])) $this->falhar('layouts');
            $porId = array_column($catalogo, null, 'id');
            foreach ($pedido['layouts'] as $dispositivo=>$widgets) {
                if (!is_array($widgets) || !array_is_list($widgets) || count($widgets)>64) $this->falhar('layouts.'.$dispositivo);
                $vistos = []; $caixas = [];
                foreach ($widgets as $i=>$widget) {
                    $campo = 'layouts.'.$dispositivo.'.'.$i;
                    if (!is_array($widget) || (count($widget)<6 || count($widget)>12)
                        || array_diff(array_keys($widget), ['id','section','x','y','w','h','altura_desejada','fixo','altura_livre','orientacao','ordem_card','ordem_secao'])) $this->falhar($campo);
                    if (!is_string($widget['id'] ?? null) || !isset($porId[$widget['id']]) || isset($vistos[$widget['id']])) $this->falhar($campo.'.id');
                    $item = $porId[$widget['id']]; $limite = $item[$dispositivo];
                    if (!is_string($widget['section']??null))$this->falhar($campo.'.section');
                    $widget['section']=OrganizacaoPainel::secao($widget['id'],$widget['section'],$porId);
                    if ($widget['section']!==$item['section']) $this->falhar($campo.'.section');
                    foreach (['x','y','w','h'] as $numero) if (!is_int($widget[$numero] ?? null)) $this->falhar($campo.'.'.$numero);
                    if ($widget['x']<0 || $widget['y']<0 || $widget['x']+$widget['w']>12 || $widget['y']+$widget['h']>200
                        || $widget['w']<$limite['minW'] || $widget['w']>$limite['maxW'] || $widget['h']<$limite['minH'] || $widget['h']>$limite['maxH']) $this->falhar($campo);
                    // h é a altura efetiva da linha; a intenção individual é técnica e opcional.
                    // Clientes anteriores continuam com seis campos, sem reescrita ou coerção.
                    if (array_key_exists('altura_desejada', $widget) && (!is_int($widget['altura_desejada'])
                        || $widget['altura_desejada']<$limite['minH'] || $widget['altura_desejada']>$widget['h'])) $this->falhar($campo.'.altura_desejada');
                    // Opções visuais não ampliam limites, acesso ou capacidades.
                    foreach (['fixo','altura_livre'] as $opcao) if (array_key_exists($opcao,$widget) && !is_bool($widget[$opcao])) $this->falhar($campo.'.'.$opcao);
                    if (array_key_exists('orientacao',$widget) && (!in_array($widget['id'],['distribuicao-acertos','resultados-avaliacao','periodos-escolares','habilidades','reavaliacoes'],true)
                        || !in_array($widget['orientacao'],['horizontal','vertical'],true))) $this->falhar($campo.'.orientacao');
                    foreach ($caixas as $outra) if ($outra['section']===$widget['section'] && $widget['x']<$outra['x']+$outra['w'] && $widget['x']+$widget['w']>$outra['x'] && $widget['y']<$outra['y']+$outra['h'] && $widget['y']+$widget['h']>$outra['y']) $this->falhar($campo, 'Os componentes não podem se sobrepor.');
                    $vistos[$widget['id']] = true; $caixas[] = $widget;
                }
                if(!OrganizacaoPainel::valida(OrganizacaoPainel::projetar($widgets,$catalogo),$painel))$this->falhar('layouts.'.$dispositivo.'.organizacao');
                $pedido['layouts'][$dispositivo] = $widgets;
            }
        }
        return $pedido;
    }

    private function estado(?object $registro): array
    {
        $layouts = ['desktop'=>[], 'mobile'=>[]];
        if ($registro) foreach (array_keys($layouts) as $d) $layouts[$d] = json_decode($registro->$d, true, 64, JSON_THROW_ON_ERROR);
        return ['versao'=>$registro ? (int)$registro->versao : 0, 'apresentacao'=>$registro?->apresentacao ?? 'tabela', 'layouts'=>$layouts];
    }

    private function filtrar(array $resultado, array $catalogo, string $perfil, string $painel): array
    {
        $ids = array_fill_keys(array_column($catalogo, 'id'), true);
        foreach (['desktop','mobile'] as $d) $resultado['layouts'][$d] = OrganizacaoPainel::projetar(array_values(array_filter($resultado['layouts'][$d], fn ($w)=>isset($ids[$w['id']]))),$catalogo);
        return $resultado + ['catalogo'=>$catalogo, 'repetido'=>false,
            'padrao'=>['desktop'=>PadroesPainel::desktop($perfil,$painel,$catalogo)],
            'origem_disposicao'=>$resultado['layouts']['desktop'] ? 'usuario' : 'perfil'];
    }

    public function mostrar(Usuario $ator, string $painel, ?int $turmaId=null): array
    {
        $contexto = $this->contexto($ator,$painel,$turmaId);
        $registro = DB::table('preferencias_paineis')->where($contexto)->where('painel', $painel)->first();
        $this->contexto($ator,$painel,$turmaId);
        return $this->filtrar($this->estado($registro), CatalogoWidgetsPainel::para($ator, $painel),$contexto['perfil'],$painel);
    }

    private function canonico(mixed $valor): mixed
    {
        if (!is_array($valor)) return $valor;
        if (!array_is_list($valor)) ksort($valor, SORT_STRING);
        return array_map(fn ($v)=>$this->canonico($v), $valor);
    }

    public function gravar(Usuario $ator, string $painel, array $pedido, ?int $turmaId=null): array
    {
        $acao = function () use ($ator,$painel,$pedido,$turmaId) {
            $contexto = $this->contexto($ator,$painel,$turmaId,true);
            $catalogo = CatalogoWidgetsPainel::para($ator, $painel);
            $pedido = $this->validar($pedido, $catalogo, $painel);
            $q = DB::table('preferencias_paineis')->where($contexto)->where('painel', $painel);
            $registro = (clone $q)->lockForUpdate()->first();
            if (!$registro) {
                $id = DB::table('preferencias_paineis')->insertGetId($contexto + ['painel'=>$painel, 'desktop'=>'[]', 'mobile'=>'[]', 'atualizado_em'=>now()->utc()]);
                $registro = DB::table('preferencias_paineis')->where('id', $id)->first();
            }
            $hash = hash('sha256', json_encode($this->canonico($pedido), JSON_THROW_ON_ERROR));
            $anterior = DB::table('preferencias_painel_eventos')->where('preferencia_id', $registro->id)->where('chave', $pedido['chave'])->first();
            if ($anterior) {
                abort_unless(hash_equals($anterior->pedido_hash, $hash), 409, 'Identificador já usado em outra configuração.');
                return array_replace($this->filtrar(json_decode($anterior->resultado, true, 64, JSON_THROW_ON_ERROR), $catalogo,$contexto['perfil'],$painel), ['repetido'=>true]);
            }
            abort_unless((int)$registro->versao===$pedido['versao'], 409, 'A organização do painel mudou. Atualize antes de salvar.');
            // Omitir um dispositivo preserva até as caixas temporariamente não autorizadas
            // no armazenamento; elas jamais são projetadas sem a capacidade atual.
            $resultado = $this->estado($registro);
            if ($pedido['acao']==='salvar') {
                if (isset($pedido['apresentacao'])) $resultado['apresentacao'] = $pedido['apresentacao'];
                foreach ($pedido['layouts'] ?? [] as $d=>$widgets) {
                    $anteriores=array_column($resultado['layouts'][$d],null,'id');
                    // Um cliente anterior que ajusta geometria não apaga a ordem dos grupos.
                    foreach($widgets as &$w)if(!array_intersect(OrganizacaoPainel::CAMPOS,array_keys($w)))
                        foreach(OrganizacaoPainel::CAMPOS as $k)if(array_key_exists($k,$anteriores[$w['id']]??[]))$w[$k]=$anteriores[$w['id']][$k];
                    unset($w);
                    $widgets=OrganizacaoPainel::projetar($widgets,$catalogo);
                    if(!OrganizacaoPainel::valida($widgets,$painel))$this->falhar('layouts.'.$d.'.organizacao');
                    $resultado['layouts'][$d] = $widgets;
                }
            } else foreach ($pedido['alvos'] as $alvo) {
                if ($alvo==='apresentacao') $resultado['apresentacao']='tabela';
                else $resultado['layouts'][$alvo]=[];
            }
            $resultado['versao']++;
            $this->contexto($ator,$painel,$turmaId);
            DB::table('preferencias_paineis')->where('id', $registro->id)->update(['versao'=>$resultado['versao'], 'apresentacao'=>$resultado['apresentacao'], 'desktop'=>json_encode($resultado['layouts']['desktop'], JSON_THROW_ON_ERROR), 'mobile'=>json_encode($resultado['layouts']['mobile'], JSON_THROW_ON_ERROR), 'atualizado_em'=>now()->utc()]);
            $auditado = array_intersect_key($resultado, array_flip(['versao','apresentacao','layouts']));
            DB::table('preferencias_painel_eventos')->insert(['preferencia_id'=>$registro->id, 'chave'=>$pedido['chave'], 'pedido_hash'=>$hash, 'acao'=>$pedido['acao'], 'versao'=>$resultado['versao'], 'resultado'=>json_encode($auditado, JSON_THROW_ON_ERROR), 'ocorrido_em'=>now()->utc()]);
            return $this->filtrar($resultado, $catalogo,$contexto['perfil'],$painel);
        };
        $c = DB::connection();
        if (!in_array($c->getDriverName(), ['mysql','mariadb'], true)) return $c->transaction($acao, 3);
        if ($c->transactionLevel()!==0) throw new \LogicException('Preferência exige transação própria.');
        for ($n=1;;$n++) {
            $c->statement('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            try { return $c->transaction($acao, 1); }
            catch (\Throwable $e) { if ($n>=3 || !$this->causedByConcurrencyError($e)) throw $e; }
        }
    }
}

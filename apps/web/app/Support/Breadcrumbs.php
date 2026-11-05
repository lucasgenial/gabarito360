<?php

namespace App\Support;

use Illuminate\Http\Request;

/** Navegação por rotas e dados já autorizados; não consulta entidades por parâmetros arbitrários. */
final class Breadcrumbs
{
    public static function itens(Request $r, array $dados = [], array $prova = []): ?array
    {
        $nome = $r->route()?->getName();
        $p = $dados['prova'] ?? $prova;
        $aluno = $dados['aluno'] ?? [];
        $itens = [['rotulo' => 'Início', 'url' => self::url($r, 'painel')]];
        if ($nome === 'resultados.show') {
            $origem = $r->query('from');
            if ($origem === 'correcao' && Acesso::pode('correcao.acompanhar') && ! empty($p['id'])) {
                $itens[] = ['rotulo' => 'Correção da prova', 'url' => self::url($r, 'correcao.show', [$p['id']])];
            } elseif ($origem === 'prova' && Acesso::pode('resultado.ver') && ! empty($p['id'])) {
                $itens[] = ['rotulo' => 'Relatório da prova', 'url' => self::url($r, 'relatorios.prova', [$p['id']])];
            } elseif ($origem === 'rel-turma' && Acesso::pode('resultado.ver') && ! empty($aluno['turma_id']) && ! empty($p['id'])) {
                $itens[] = ['rotulo' => 'Relatório da turma', 'url' => self::url($r, 'relatorios.turmaProva', [$aluno['turma_id'], $p['id']])];
            } elseif (! empty($aluno['turma_id']) && Acesso::pode('turma.ver')) {
                $itens[] = ['rotulo' => ($aluno['turma_nome'] ?? null) ?: 'Turma', 'url' => self::url($r, 'turmas.show', [$aluno['turma_id']])];
            }
            $itens[] = ['rotulo' => 'Resultado', 'url' => null];
        } elseif (in_array($nome, ['correcao.show', 'correcao.revisao'], true) && ! empty($p['id'])) {
            if ($nome === 'correcao.revisao') {
                $itens[] = ['rotulo' => 'Correção da prova', 'url' => self::url($r, 'correcao.show', [$p['id']])];
            }
            $itens[] = ['rotulo' => $nome === 'correcao.show' ? 'Correção da prova' : 'Revisar leituras', 'url' => null];
        } elseif ($nome === 'provas.index') {
            $itens[] = ['rotulo' => 'Provas', 'url' => null];
        } elseif ($nome === 'provas.show' && ! empty($p['id'])) {
            $itens[] = ['rotulo' => 'Provas', 'url' => self::url($r, 'provas.index')];
            $itens[] = ['rotulo' => $p['titulo'], 'url' => null];
        } else {
            return null;
        }

        return $itens;
    }

    private static function url(Request $r, string $nome, array $parametros = []): string
    {
        // Só rotas internas nomeadas. Documentos assinados não passam por este gerador.
        if ($ctx = $r->attributes->get('vinculo_handle')) {
            $parametros['_ctx'] = $ctx;
        }

        return route($nome,$parametros);
    }
}

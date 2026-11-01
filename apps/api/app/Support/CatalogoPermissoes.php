<?php

namespace App\Support;

/**
 * Arquivo: apps/api/app/Support/CatalogoPermissoes.php
 * Função: Catálogo de permissões de docs/03-perfis-e-permissoes.md (v2), em código.
 *         Fonte da sincronização das tabelas `permissoes` e `perfil_permissoes`.
 *         Cada linha: [chave, módulo, descrição, mapa de 8 caracteres na ordem PERFIS]
 *         onde "v" = padrão do perfil, "o" = extra concedível, "-" = nunca.
 */
class CatalogoPermissoes
{
    public const PERFIS = ['secretario_educacao', 'admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor', 'aplicador', 'aluno'];

    /** T3: política pessoal independente, sem herdar poderes institucionais do professor. */
    public const AUTONOMO = [
        'turma.ver', 'turma.gerir', 'turma.desativar',
        'aluno.ver', 'aluno.gerir', 'aluno.desativar',
        'prova.ver', 'prova.criar', 'prova.editar', 'gabarito.ver', 'gabarito.editar',
        'cartao.imprimir', 'cartao.enviar', 'correcao.acompanhar', 'correcao.alterar',
        'correcao.fechar', 'resultado.ver', 'resultado.compartilhar',
        'relatorio.prova', 'relatorio.turma',
    ];

    public const LISTA = [
        // 4.1 Rede, núcleos e escolas
        ['rede.configurar',          'escolas',   'Parâmetros da rede (metas etc.)',                        '-v------'],
        ['rede.logo',                'escolas',   'Enviar logo da rede ou da mantenedora',                 '-vvo----'],
        ['nucleo.ver',               'escolas',   'Ver núcleos',                                            'vvv-----'],
        ['nucleo.gerir',             'escolas',   'Criar/editar/excluir núcleo',                            '-vo-----'],
        ['escola.ver',               'escolas',   'Ver dados da escola',                                    'vvvvvvv-'],
        ['escola.criar',             'escolas',   'Cadastrar escola e anexos',                              '-vv-----'],
        ['escola.editar',            'escolas',   'Editar dados da escola',                                 '-vvvo---'],
        ['escola.desativar',         'escolas',   'Ativar/desativar escola',                                '-vv-----'],
        ['escola.logo',              'escolas',   'Enviar logo da escola',                                  '-vvvv---'],
        // 4.2 Equipe e usuários
        ['usuario.ver',              'equipe',    'Ver a equipe do escopo',                                 '-vvvvo--'],
        ['usuario.gerir',            'equipe',    'Cadastrar/editar/desativar usuários (quem cadastra quem)', '-vvvv---'],
        ['usuario.permissoes',       'equipe',    'Conceder/retirar permissões',                            '-vvvo---'],
        // 4.3 Turmas e alunos
        ['turma.ver',                'turmas',    'Ver turmas (professor: as suas)',                        '-vvvvv--'],
        ['turma.gerir',              'turmas',    'Criar/editar turma',                                     '-v-vvo--'],
        ['turma.desativar',          'turmas',    'Ativar/desativar turma',                                 '-v-vo---'],
        ['turma.vincular_professor', 'turmas',    'Vincular/desvincular professor à turma',                 '-v-vv---'],
        ['aluno.ver',                'turmas',    'Ver alunos (professor: das suas turmas)',                '-vvvvv-o'],
        ['aluno.gerir',              'turmas',    'Cadastrar/editar aluno',                                 '-v-vvo--'],
        ['aluno.importar',           'turmas',    'Importar alunos por planilha',                           '-v-vvo--'],
        ['aluno.desativar',          'turmas',    'Ativar/desativar aluno',                                 '-v-vv---'],
        // 4.4 Provas e gabaritos
        ['prova.ver',                'provas',    'Ver provas',                                             '-vvvvvv-'],
        ['prova.criar',              'provas',    'Criar prova',                                            '-v-ovv--'],
        ['prova.editar',             'provas',    'Editar, publicar e excluir rascunho de prova',           '-v-ovv--'],
        ['gabarito.ver',             'provas',    'Ver gabarito',                                           '-vvvvv--'],
        ['gabarito.editar',          'provas',    'Editar/publicar gabarito',                               '-v-ovv--'],
        // 4.5 Cartões e correção
        ['cartao.imprimir',          'correcao',  'Gerar PDF dos cartões-resposta',                         '-v-vvvo-'],
        ['cartao.enviar',            'correcao',  'Fotografar/enviar cartões',                              '-v-vvvv-'],
        ['correcao.acompanhar',      'correcao',  'Acompanhar correção (prova e turma)',                    '-vvvvv--'],
        ['correcao.alterar',         'correcao',  'Resolver ambíguos, vincular, revisar e excluir correção', '-v-vvv--'],
        ['correcao.fechar',          'correcao',  'Fechar a correção',                                      '-v-vvv--'],
        ['correcao.reabrir',         'correcao',  'Reabrir a correção e alterar com ela fechada',           '-v-vvo--'],
        // 4.6 Resultados e relatórios
        ['resultado.ver',            'relatorios', 'Ver acertos e erros do aluno',                          '-vvvvv-v'],
        ['resultado.compartilhar',   'relatorios', 'PDF/WhatsApp do resultado e boletim',                   '-vvvvv-v'],
        ['relatorio.prova',          'relatorios', 'Relatório da prova e da turma na prova',                '-vvvvv--'],
        ['relatorio.turma',          'relatorios', 'Relatório consolidado da turma e boletim',              '-vvvvv--'],
        ['relatorio.escola',         'relatorios', 'Relatório da escola',                                   '-vvvvo--'],
        ['relatorio.nucleo',         'relatorios', 'Relatório do núcleo',                                   '-vv-----'],
        ['relatorio.rede',           'relatorios', 'Relatório da rede',                                     'vv------'],
        ['relatorio.secretaria',     'relatorios', 'Consolidado da secretaria',                             'v-------'],
    ];

    /** [perfil => ['padrao' => [...chaves], 'extra' => [...chaves]]] */
    public static function porPerfil(): array
    {
        $out = [];
        foreach (self::PERFIS as $i => $perfil) {
            $out[$perfil] = ['padrao' => [], 'extra' => []];
            foreach (self::LISTA as [$chave, , , $mapa]) {
                if ($mapa[$i] === 'v') {
                    $out[$perfil]['padrao'][] = $chave;
                } elseif ($mapa[$i] === 'o') {
                    $out[$perfil]['extra'][] = $chave;
                }
            }
        }

        $out['professor_autonomo'] = ['padrao' => self::AUTONOMO, 'extra' => []];
        return $out;
    }
}

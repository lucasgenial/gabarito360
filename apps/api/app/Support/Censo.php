<?php

namespace App\Support;

/**
 * Arquivo: apps/api/app/Support/Censo.php
 * Função: Censo Escolar (INEP) — mapeamento da Tabela_Escola para a base `censo_escolas`
 *         e da base para os campos da escola. Mesmos códigos do ECOAR-MQA (config/censo.php de lá):
 *         TP_DEPENDENCIA 1 Federal, 2 Estadual, 3 Municipal, 4 Privada; TP_LOCALIZACAO 1 Urbana, 2 Rural;
 *         TP_SITUACAO_FUNCIONAMENTO 1 Ativa, 2 Paralisada, 3 Extinta; CO_ORGAO_REGIONAL = nº do NTE (BA).
 */
class Censo
{
    /** Coluna da base => coluna do CSV (casada pelo nome no cabeçalho, não pela posição). */
    public const COLUNAS = [
        'ano_censo'      => 'NU_ANO_CENSO',
        'codigo_inep'    => 'CO_ENTIDADE',
        'nome'           => 'NO_ENTIDADE',
        'sg_uf'          => 'SG_UF',
        'co_uf'          => 'CO_UF',
        'co_municipio'   => 'CO_MUNICIPIO',
        'no_municipio'   => 'NO_MUNICIPIO',
        'tp_dependencia' => 'TP_DEPENDENCIA',
        'tp_localizacao' => 'TP_LOCALIZACAO',
        'tp_situacao'    => 'TP_SITUACAO_FUNCIONAMENTO',
        'orgao_regional' => 'CO_ORGAO_REGIONAL',
        'endereco'       => 'DS_ENDERECO',
        'numero'         => 'NU_ENDERECO',
        'complemento'    => 'DS_COMPLEMENTO',
        'bairro'         => 'NO_BAIRRO',
        'cep'            => 'CO_CEP',
        'ddd'            => 'NU_DDD',
        'telefone'       => 'NU_TELEFONE',
        'latitude'       => 'LATITUDE',
        'longitude'      => 'LONGITUDE',
    ];

    public const ESFERAS     = [1 => 'federal', 2 => 'estadual', 3 => 'municipal', 4 => 'privada'];
    public const LOCALIZACAO = [1 => 'urbana', 2 => 'rural'];
    public const SITUACAO    = [1 => 'ativa', 2 => 'paralisada', 3 => 'extinta'];

    /** Linha do CSV (já em UTF-8, indexada pelo cabeçalho) => linha de `censo_escolas`. */
    public static function linhaParaBase(array $l): ?array
    {
        $inep = preg_replace('/\D/', '', (string) ($l['CO_ENTIDADE'] ?? ''));
        if (strlen($inep) !== 8) {
            return null;
        }
        $v = fn (string $col) => ($x = trim((string) ($l[self::COLUNAS[$col]] ?? ''))) === '' ? null : $x;
        $num = fn (string $col) => ($x = $v($col)) === null ? null : (float) str_replace(',', '.', $x);
        $cep = preg_replace('/\D/', '', (string) $v('cep'));

        return [
            'codigo_inep'    => $inep,
            'ano_censo'      => (int) $v('ano_censo'),
            'nome'           => mb_substr((string) $v('nome'), 0, 200),
            'sg_uf'          => (string) $v('sg_uf'),
            'co_uf'          => (int) $v('co_uf'),
            'co_municipio'   => (int) $v('co_municipio'),
            'no_municipio'   => mb_substr((string) $v('no_municipio'), 0, 100),
            'tp_dependencia' => (int) $v('tp_dependencia'),
            'tp_localizacao' => $v('tp_localizacao') !== null ? (int) $v('tp_localizacao') : null,
            'tp_situacao'    => $v('tp_situacao') !== null ? (int) $v('tp_situacao') : null,
            'orgao_regional' => $v('orgao_regional'),
            'endereco'       => mb_substr((string) $v('endereco'), 0, 255) ?: null,
            'numero'         => mb_substr((string) $v('numero'), 0, 20) ?: null,
            'complemento'    => mb_substr((string) $v('complemento'), 0, 100) ?: null,
            'bairro'         => mb_substr((string) $v('bairro'), 0, 100) ?: null,
            'cep'            => strlen($cep) === 8 ? $cep : null,
            'ddd'            => $v('ddd'),
            'telefone'       => $v('telefone'),
            'latitude'       => $num('latitude'),
            'longitude'      => $num('longitude'),
        ];
    }

    /** Linha de `censo_escolas` => campos da escola (o que o cadastro preenche). */
    public static function paraEscola(object|array $c): array
    {
        $c = (object) $c;
        $tel = $c->telefone ? ($c->ddd ? "({$c->ddd}) " : '') . $c->telefone : null;

        return [
            'inep'                   => $c->codigo_inep,
            'nome'                   => self::titulo($c->nome),
            'tipo_rede'              => self::ESFERAS[$c->tp_dependencia] ?? null,
            'situacao_funcionamento' => self::SITUACAO[$c->tp_situacao] ?? null,
            'localizacao'            => self::LOCALIZACAO[$c->tp_localizacao] ?? null,
            'orgao_regional'         => $c->orgao_regional,
            'logradouro'             => $c->endereco ? self::titulo($c->endereco) : null,
            'numero'                 => $c->numero,
            'complemento'            => $c->complemento ? self::titulo($c->complemento) : null,
            'bairro'                 => $c->bairro ? self::titulo($c->bairro) : null,
            'cep'                    => $c->cep,
            'cidade'                 => self::titulo($c->no_municipio),
            'co_municipio'           => $c->co_municipio,
            'uf'                     => $c->sg_uf,
            'telefone'               => $tel ? mb_substr($tel, 0, 20) : null,
            'latitude'               => $c->latitude,
            'longitude'              => $c->longitude,
            'ano_censo'              => $c->ano_censo,
        ];
    }

    /** "COLEGIO ESTADUAL DO CAMPO" => "Colégio Estadual do Campo" (sem acento novo: só caixa). */
    public static function titulo(?string $s): ?string
    {
        if ($s === null || $s === '') {
            return $s;
        }
        $menores = ['de', 'da', 'do', 'das', 'dos', 'e', 'em', 'na', 'no', 'nas', 'nos', 'a', 'o', 'à'];
        $palavras = explode(' ', mb_strtolower(preg_replace('/\s+/', ' ', trim($s))));
        foreach ($palavras as $i => $p) {
            if ($i === 0 || !in_array($p, $menores, true)) {
                $palavras[$i] = mb_strtoupper(mb_substr($p, 0, 1)) . mb_substr($p, 1);
            }
        }

        return implode(' ', $palavras);
    }
}

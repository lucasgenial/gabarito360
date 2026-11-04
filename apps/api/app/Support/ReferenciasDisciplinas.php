<?php

namespace App\Support;

/** Nomenclatura de referência, não matriz obrigatória nem código oficial BNCC. */
final class ReferenciasDisciplinas
{
    public const EDICAO = 'bncc-2018-v1';
    public const FONTE = 'https://basenacionalcomum.mec.gov.br/images/BNCC_EI_EF_110518_versaofinal_site.pdf';

    public static function todas(): array
    {
        $fundamental = ['fundamental_iniciais', 'fundamental_finais'];
        $todos = [...$fundamental, 'medio'];
        $itens = [
            ['portugues', 'Língua Portuguesa', 'Linguagens', $todos],
            ['arte', 'Arte', 'Linguagens', $todos],
            ['educacao-fisica', 'Educação Física', 'Linguagens', $todos],
            ['ingles', 'Língua Inglesa', 'Linguagens', ['fundamental_finais', 'medio']],
            ['matematica', 'Matemática', 'Matemática', $todos],
            ['ciencias', 'Ciências', 'Ciências da Natureza', $fundamental],
            ['historia', 'História', 'Ciências Humanas', $todos],
            ['geografia', 'Geografia', 'Ciências Humanas', $todos],
            ['ensino-religioso', 'Ensino Religioso', 'Ensino Religioso', $fundamental],
            ['biologia', 'Biologia', 'Ciências da Natureza', ['medio']],
            ['fisica', 'Física', 'Ciências da Natureza', ['medio']],
            ['quimica', 'Química', 'Ciências da Natureza', ['medio']],
            ['filosofia', 'Filosofia', 'Ciências Humanas', ['medio']],
            ['sociologia', 'Sociologia', 'Ciências Humanas', ['medio']],
        ];
        return array_map(fn ($r) => ['chave' => $r[0], 'nome' => $r[1], 'area' => $r[2], 'etapas' => $r[3], 'edicao' => self::EDICAO, 'fonte' => self::FONTE], $itens);
    }
}

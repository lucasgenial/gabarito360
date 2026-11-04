<?php

namespace App\Services;

use App\Support\DocumentoIdentidade;
use Illuminate\Support\Facades\{Crypt, DB};
use InvalidArgumentException;
use RuntimeException;

/** Diagnóstico administrativo local: nunca vincula, atualiza ou expõe pessoas. */
final class InventarioIdentidades
{
    public function executar(int $limite = 100000): array
    {
        if ($limite < 1 || $limite > 100000) throw new RuntimeException('Limite de inventário inválido.');
        $fontes = ['usuarios', 'alunos'];
        $contagens = [];
        foreach ($fontes as $fonte) $contagens[$fonte] = DB::table($fonte)->count();
        if (array_sum($contagens) > $limite) throw new RuntimeException('Volume exige inventário particionado.');
        // Validar chave mesmo se a base não possuir CPF válido.
        DocumentoIdentidade::indiceCpf('00000000191');
        $grupos = [];
        $total = 0;
        $saida = ['natureza' => 'diagnostico_sem_vinculacao', 'somente_leitura' => true, 'fontes' => []];
        foreach ($fontes as $fonte) {
            $resumo = ['registros' => 0, 'sem_documento' => 0, 'invalidos' => 0, 'ilegiveis' => 0, 'digitos_validos' => 0];
            DB::table($fonte)->select(['id', 'cpf'])->orderBy('id')->chunkById(500, function ($linhas) use (&$resumo, &$grupos, &$total, $limite, $fonte) {
                foreach ($linhas as $linha) {
                    if (++$total > $limite) throw new RuntimeException('Volume exige inventário particionado.');
                    $resumo['registros']++;
                    if ($linha->cpf === null || $linha->cpf === '') { $resumo['sem_documento']++; continue; }
                    try {
                        $valor = Crypt::decryptString(str_starts_with($linha->cpf, 'g360:v1:') ? substr($linha->cpf, 8) : $linha->cpf);
                    } catch (\Throwable) { $resumo['ilegiveis']++; continue; }
                    try { $indice = DocumentoIdentidade::indiceCpf($valor); }
                    catch (InvalidArgumentException) { $resumo['invalidos']++; continue; }
                    $resumo['digitos_validos']++;
                    $grupos[$indice][$fonte] = ($grupos[$indice][$fonte] ?? 0) + 1;
                }
            });
            $saida['fontes'][$fonte] = $resumo;
        }
        $saida['grupos_repetidos'] = count(array_filter($grupos, fn ($g) => array_sum($g) > 1));
        $saida['grupos_entre_conta_e_aluno'] = count(array_filter($grupos, fn ($g) => isset($g['usuarios'], $g['alunos'])));
        $saida['grupos_em_multiplos_alunos'] = count(array_filter($grupos, fn ($g) => ($g['alunos'] ?? 0) > 1));
        return $saida;
    }
}

<?php

namespace App\Services\Omr;

/**
 * Arquivo: apps/api/app/Services/Omr/OmrIdentificacaoInterface.php
 * Função: Contrato opcional para drivers que, além das respostas, identificam
 *         o cartão (prova, aluno e língua estrangeira) — ex.: pelo QR impresso.
 */
interface OmrIdentificacaoInterface
{
    /**
     * Dados da última leitura feita por processar():
     * ['prova_id' => ?int, 'aluno_id' => ?int, 'lingua' => ?string ('ING'|'ESP'), 'erro' => ?string]
     */
    public function identificacao(): array;
}

<?php

namespace App\Services\ModelosCartao;

use App\Enums\ModeloCartaoOrigemCodigo;
use App\Enums\ModeloCartaoTipoCodigo;
use Illuminate\Validation\ValidationException;

class ModeloCartaoConfigurationValidator
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, list<string>>
     */
    public function errors(array $attributes, bool $forApproval = false): array
    {
        $errors = [];
        $configuration = $attributes['configuracao_omr'] ?? null;
        $alternatives = $attributes['alternativas'] ?? null;
        $questionCount = $attributes['quantidade_questoes'] ?? null;
        $alternativeCount = $attributes['quantidade_alternativas'] ?? null;
        $codeType = ModeloCartaoTipoCodigo::tryFrom((string) ($attributes['tipo_codigo'] ?? ''));
        $codeOrigin = ModeloCartaoOrigemCodigo::tryFrom((string) ($attributes['origem_codigo'] ?? ''));

        if (! is_array($alternatives) || ! array_is_list($alternatives)) {
            $this->add($errors, 'alternativas', 'As alternativas devem formar uma lista ordenada.');
        } else {
            $normalized = array_map(fn (mixed $value): string => (string) $value, $alternatives);

            if (count($normalized) !== (int) $alternativeCount || count(array_unique($normalized)) !== count($normalized)) {
                $this->add($errors, 'alternativas', 'As alternativas devem ser unicas e corresponder a quantidade informada.');
            }

            foreach ($normalized as $alternative) {
                if (preg_match('/^[A-Z0-9]$/', $alternative) !== 1) {
                    $this->add($errors, 'alternativas', 'Cada alternativa deve possuir um unico caractere A-Z ou 0-9.');
                    break;
                }
            }
        }

        if (
            ($codeType === ModeloCartaoTipoCodigo::WITHOUT_CODE && $codeOrigin !== ModeloCartaoOrigemCodigo::NONE)
            || ($codeType !== null && $codeType !== ModeloCartaoTipoCodigo::WITHOUT_CODE && $codeOrigin === ModeloCartaoOrigemCodigo::NONE)
        ) {
            $this->add($errors, 'origem_codigo', 'A origem semantica deve corresponder ao tipo de codigo configurado.');
        }

        if (! is_array($configuration)) {
            $this->add($errors, 'configuracao_omr', 'A configuracao OMR deve ser um objeto.');

            return $errors;
        }

        foreach (['spec_id', 'spec_version'] as $field) {
            if (! is_string(data_get($configuration, $field)) || trim((string) data_get($configuration, $field)) === '') {
                $this->add($errors, "configuracao_omr.{$field}", 'O identificador e a versao da especificacao sao obrigatorios.');
            }
        }

        foreach (['canvas.width', 'canvas.height'] as $field) {
            if (! is_int(data_get($configuration, $field)) || data_get($configuration, $field) <= 0) {
                $this->add($errors, "configuracao_omr.{$field}", 'A dimensao canonica deve ser um inteiro positivo.');
            }
        }

        $this->validateMarkers($configuration, $errors);
        $this->validatePrintedCode($configuration, $codeType, $codeOrigin, $errors);
        $this->validateAnswers($configuration, $questionCount, $alternatives, $errors);
        $this->validateThresholds($configuration, $forApproval, $errors);

        if ($forApproval) {
            $checksum = $attributes['artefato_checksum_sha256'] ?? null;

            if (! is_string($checksum) || preg_match('/^[0-9a-f]{64}$/', $checksum) !== 1) {
                $this->add($errors, 'artefato_checksum_sha256', 'A homologacao exige o checksum SHA-256 do artefato de impressao.');
            }

            if ($this->containsPlaceholder($configuration)) {
                $this->add($errors, 'configuracao_omr', 'A configuracao homologada nao pode conter placeholders PREENCHER_.');
            }
        }

        return $errors;
    }

    /** @param array<string, mixed> $attributes */
    public function assertValid(array $attributes, bool $forApproval = false): void
    {
        $errors = $this->errors($attributes, $forApproval);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<string, list<string>>  $errors
     */
    private function validateMarkers(array $configuration, array &$errors): void
    {
        if (! is_string(data_get($configuration, 'markers.dictionary')) || trim((string) data_get($configuration, 'markers.dictionary')) === '') {
            $this->add($errors, 'configuracao_omr.markers.dictionary', 'O dicionario dos marcadores e obrigatorio.');
        }

        $items = data_get($configuration, 'markers.items');

        if (! is_array($items) || array_diff(['TL', 'TR', 'BR', 'BL'], array_keys($items)) !== []) {
            $this->add($errors, 'configuracao_omr.markers.items', 'Os marcadores TL, TR, BR e BL sao obrigatorios.');

            return;
        }

        $ids = [];

        foreach (['TL', 'TR', 'BR', 'BL'] as $position) {
            $id = data_get($items, "{$position}.id");

            if (! is_int($id) || $id < 0) {
                $this->add($errors, "configuracao_omr.markers.items.{$position}.id", 'O identificador do marcador deve ser inteiro e nao negativo.');
            } else {
                $ids[] = $id;
            }

            if (! $this->isRegionWithinCanvas(data_get($items, "{$position}.region"), $configuration)) {
                $this->add($errors, "configuracao_omr.markers.items.{$position}.region", 'A regiao deve usar [x, y, largura, altura] e permanecer dentro do canvas.');
            }
        }

        if (count(array_unique($ids)) !== count($ids)) {
            $this->add($errors, 'configuracao_omr.markers.items', 'Os identificadores dos marcadores devem ser unicos.');
        }
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<string, list<string>>  $errors
     */
    private function validatePrintedCode(
        array $configuration,
        ?ModeloCartaoTipoCodigo $codeType,
        ?ModeloCartaoOrigemCodigo $codeOrigin,
        array &$errors,
    ): void {
        if (! $this->isRegionWithinCanvas(data_get($configuration, 'printed_code.region'), $configuration)) {
            $this->add($errors, 'configuracao_omr.printed_code.region', 'A regiao reservada do codigo fisico deve permanecer dentro do canvas, inclusive para sem_codigo.');
        }

        if (data_get($configuration, 'printed_code.type') !== $codeType?->value) {
            $this->add($errors, 'configuracao_omr.printed_code.type', 'O tipo do codigo na configuracao deve corresponder ao modelo.');
        }

        if (data_get($configuration, 'printed_code.semantic_source') !== $codeOrigin?->value) {
            $this->add($errors, 'configuracao_omr.printed_code.semantic_source', 'A origem semantica deve distinguir codigo externo, sistema afixado ou ausencia.');
        }

        $normalization = data_get($configuration, 'printed_code.normalization');

        if (! is_array($normalization) || $normalization === []) {
            $this->add($errors, 'configuracao_omr.printed_code.normalization', 'As regras explicitas de normalizacao do codigo fisico sao obrigatorias.');
        } elseif (
            $codeType === ModeloCartaoTipoCodigo::WITHOUT_CODE
            && data_get($normalization, 'strategy') !== 'none'
        ) {
            $this->add($errors, 'configuracao_omr.printed_code.normalization.strategy', 'Modelos sem codigo devem declarar estrategia de normalizacao none.');
        } elseif (
            $codeType !== null
            && $codeType !== ModeloCartaoTipoCodigo::WITHOUT_CODE
            && (! is_string(data_get($normalization, 'strategy')) || data_get($normalization, 'strategy') === 'none')
        ) {
            $this->add($errors, 'configuracao_omr.printed_code.normalization.strategy', 'Modelos com codigo devem declarar uma estrategia explicita de normalizacao.');
        }
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<string, list<string>>  $errors
     */
    private function validateAnswers(
        array $configuration,
        mixed $questionCount,
        mixed $alternatives,
        array &$errors,
    ): void {
        if (data_get($configuration, 'answers.questions') !== (int) $questionCount) {
            $this->add($errors, 'configuracao_omr.answers.questions', 'A quantidade de questoes da grade deve corresponder ao modelo.');
        }

        if (data_get($configuration, 'answers.alternatives') !== $alternatives) {
            $this->add($errors, 'configuracao_omr.answers.alternatives', 'As alternativas da grade devem corresponder ao modelo.');
        }

        $answersRegion = data_get($configuration, 'answers.region');

        if (! $this->isRegionWithinCanvas($answersRegion, $configuration)) {
            $this->add($errors, 'configuracao_omr.answers.region', 'A regiao geral das respostas deve permanecer dentro do canvas.');
        }

        foreach (['first_center_y', 'row_step'] as $field) {
            if (! $this->isNumber(data_get($configuration, "answers.{$field}")) || data_get($configuration, "answers.{$field}") <= 0) {
                $this->add($errors, "configuracao_omr.answers.{$field}", 'O posicionamento da grade deve ser numerico e positivo.');
            }
        }

        $centers = data_get($configuration, 'answers.centers_x');

        if (
            ! is_array($centers)
            || ! is_array($alternatives)
            || count($centers) !== count($alternatives)
            || array_diff($alternatives, array_keys($centers)) !== []
            || ! collect($centers)->every(fn (mixed $value): bool => $this->isNumber($value) && $value >= 0)
        ) {
            $this->add($errors, 'configuracao_omr.answers.centers_x', 'Cada alternativa deve possuir um centro horizontal explicito.');
        } elseif ($this->isRegion($answersRegion)) {
            [$x, , $width] = $answersRegion;

            if (! collect($centers)->every(fn (mixed $value): bool => $value >= $x && $value <= $x + $width)) {
                $this->add($errors, 'configuracao_omr.answers.centers_x', 'Os centros horizontais devem permanecer dentro da regiao de respostas.');
            }
        }

        $firstCenterY = data_get($configuration, 'answers.first_center_y');
        $rowStep = data_get($configuration, 'answers.row_step');

        if (
            $this->isRegion($answersRegion)
            && $this->isNumber($firstCenterY)
            && $this->isNumber($rowStep)
            && $this->isNumber($questionCount)
        ) {
            [, $y, , $height] = $answersRegion;
            $lastCenterY = $firstCenterY + ($rowStep * max(0, ((int) $questionCount) - 1));

            if ($firstCenterY < $y || $lastCenterY > $y + $height) {
                $this->add($errors, 'configuracao_omr.answers', 'Todos os centros verticais devem permanecer dentro da regiao de respostas.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<string, list<string>>  $errors
     */
    private function validateThresholds(array $configuration, bool $forApproval, array &$errors): void
    {
        $thresholds = data_get($configuration, 'thresholds');

        if (! is_array($thresholds) || $thresholds === []) {
            $this->add($errors, 'configuracao_omr.thresholds', 'Os limiares versionados sao obrigatorios.');

            return;
        }

        foreach (['preenchimento_minimo', 'baixa_confianca', 'dupla_marcacao'] as $name) {
            if (! array_key_exists($name, $thresholds)) {
                $this->add($errors, "configuracao_omr.thresholds.{$name}", 'O limiar deve estar declarado explicitamente.');

                continue;
            }

            $value = $thresholds[$name];

            if ($forApproval && (! $this->isNumber($value) || $value < 0 || $value > 1)) {
                $this->add($errors, "configuracao_omr.thresholds.{$name}", 'A homologacao exige limiar numerico entre 0 e 1.');
            } elseif (! $forApproval && $value !== null && (! $this->isNumber($value) || $value < 0 || $value > 1)) {
                $this->add($errors, "configuracao_omr.thresholds.{$name}", 'O limiar deve ser nulo ou numerico entre 0 e 1.');
            }
        }
    }

    private function isRegion(mixed $region): bool
    {
        return is_array($region)
            && array_is_list($region)
            && count($region) === 4
            && collect($region)->every(fn (mixed $value): bool => $this->isNumber($value))
            && $region[0] >= 0
            && $region[1] >= 0
            && $region[2] > 0
            && $region[3] > 0;
    }

    /** @param array<string, mixed> $configuration */
    private function isRegionWithinCanvas(mixed $region, array $configuration): bool
    {
        if (! $this->isRegion($region)) {
            return false;
        }

        $canvasWidth = data_get($configuration, 'canvas.width');
        $canvasHeight = data_get($configuration, 'canvas.height');

        return $this->isNumber($canvasWidth)
            && $this->isNumber($canvasHeight)
            && $canvasWidth >= $region[0] + $region[2]
            && $canvasHeight >= $region[1] + $region[3];
    }

    private function isNumber(mixed $value): bool
    {
        return is_int($value) || is_float($value);
    }

    /** @param array<string, mixed>|list<mixed> $values */
    private function containsPlaceholder(array $values): bool
    {
        foreach ($values as $value) {
            if (is_array($value) && $this->containsPlaceholder($value)) {
                return true;
            }

            if (is_string($value) && str_contains($value, 'PREENCHER_')) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, list<string>> $errors */
    private function add(array &$errors, string $field, string $message): void
    {
        $errors[$field][] = $message;
    }
}

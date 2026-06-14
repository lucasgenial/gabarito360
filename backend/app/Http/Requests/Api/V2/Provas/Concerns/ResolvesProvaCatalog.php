<?php

namespace App\Http\Requests\Api\V2\Provas\Concerns;

use App\Models\Disciplina;
use App\Models\SerieAno;
use App\Models\User;
use App\Support\Authorization\PrimaryRoleResolver;
use Illuminate\Support\Str;

trait ResolvesProvaCatalog
{
    protected function resolveDisciplinaId(string $disciplina): string
    {
        $disciplina = trim($disciplina);

        $found = Disciplina::query()
            ->where('codigo', Str::slug($disciplina))
            ->orWhere('nome', $disciplina)
            ->first();

        if ($found instanceof Disciplina) {
            return $found->id;
        }

        // Editor livre: cria a disciplina no catálogo quando inexistente.
        return Disciplina::query()->create([
            'codigo' => Str::slug($disciplina).'-'.Str::lower(Str::random(4)),
            'nome' => $disciplina,
            'ativo' => true,
        ])->id;
    }

    protected function resolveSerieAnoId(?string $serie): ?string
    {
        if ($serie === null || trim($serie) === '') {
            return null;
        }

        $serie = trim($serie);

        return SerieAno::query()
            ->where('codigo', Str::slug($serie))
            ->orWhere('nome', $serie)
            ->first()?->id;
    }

    /**
     * @return array{nucleo_id: ?string, escola_id: ?string}|null
     */
    protected function resolveOwner(User $user): ?array
    {
        $escolaId = $this->input('escola_id');
        if (is_string($escolaId) && Str::isUuid($escolaId)) {
            return ['nucleo_id' => null, 'escola_id' => $escolaId];
        }

        $nucleoId = $this->input('nucleo_id');
        if (is_string($nucleoId) && Str::isUuid($nucleoId)) {
            return ['nucleo_id' => $nucleoId, 'escola_id' => null];
        }

        $link = PrimaryRoleResolver::resolve($user);

        if ($link?->escola_id !== null) {
            return ['nucleo_id' => null, 'escola_id' => $link->escola_id];
        }

        if ($link?->nucleo_id !== null) {
            return ['nucleo_id' => $link->nucleo_id, 'escola_id' => null];
        }

        return null;
    }

    /**
     * @return array{quantidade_alternativas: int, alternativas: list<string>, valor_total: float, padrao: array<string, mixed>}
     */
    protected function mapPadrao(): array
    {
        $padrao = (array) $this->input('padrao', []);

        $altCount = (int) ($padrao['alternativas'] ?? 5);
        $altCount = in_array($altCount, [3, 4, 5], true) ? $altCount : 5;
        $alternativas = array_slice(['A', 'B', 'C', 'D', 'E'], 0, $altCount);
        $notaMaxima = (float) ($padrao['nota_maxima'] ?? 10);
        $pontuacao = $padrao['pontuacao'] ?? 'iguais';
        $pontuacao = in_array($pontuacao, ['iguais', 'personalizados'], true) ? $pontuacao : 'iguais';

        return [
            'quantidade_alternativas' => $altCount,
            'alternativas' => $alternativas,
            'valor_total' => $notaMaxima,
            'padrao' => [
                'alternativas' => $altCount,
                'nota_maxima' => $notaMaxima,
                'pontuacao' => $pontuacao,
                'anular_se_todas_marcadas' => (bool) ($padrao['anular_se_todas_marcadas'] ?? true),
                'gerar_cartao_pdf' => (bool) ($padrao['gerar_cartao_pdf'] ?? true),
            ],
        ];
    }
}

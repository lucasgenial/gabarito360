<?php

namespace App\Support;

final class CompartilhamentoPublico
{
    /** Somente rotas públicas nominadas têm prévia própria; nunca propagar query/contexto. */
    public static function metadados(): array
    {
        $rota = request()->route()?->getName();
        $paginas = config('apresentacao.compartilhamento', []);
        $rota = in_array($rota, ['home', 'planos.index', 'leads.index'], true) ? $rota : 'home';
        $pagina = $paginas[$rota] ?? $paginas['home'] ?? [
            'titulo' => 'Gabarito360', 'descricao' => 'Correção de cartões-resposta e relatórios educacionais.',
        ];

        return ['titulo' => $pagina['titulo'], 'descricao' => $pagina['descricao'],
            'url' => rtrim(route($rota), '/').($rota === 'home' ? '/' : '')];
    }
}

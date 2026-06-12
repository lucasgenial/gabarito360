<?php

namespace Tests\Feature\Web;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class DesignSystemTest extends TestCase
{
    public function test_root_redirects_to_the_canonical_admin_shell(): void
    {
        $this->get('/')->assertRedirect('/admin');
    }

    public function test_required_shared_components_exist(): void
    {
        $components = [
            'accordion',
            'alert',
            'avatar',
            'badge',
            'breadcrumb',
            'button',
            'card',
            'chart',
            'date-picker',
            'drawer',
            'empty-state',
            'error-state',
            'input',
            'kpi',
            'loading',
            'modal',
            'pagination',
            'select',
            'tab',
            'tab-panel',
            'table',
            'tabs',
            'textarea',
            'theme-toggle',
            'toast',
            'tooltip',
        ];

        foreach ($components as $component) {
            $this->assertFileExists(resource_path("views/components/ui/{$component}.blade.php"));
        }
    }

    public function test_interactive_components_render_accessible_states(): void
    {
        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag([
            'nome' => ['Informe um nome valido.'],
        ]));
        session()->put('errors', $errors);

        $html = Blade::render(<<<'BLADE'
            <x-ui.button loading>Salvar</x-ui.button>
            <x-ui.button disabled>Indisponivel</x-ui.button>
            <x-ui.input name="nome" label="Nome" help="Nome institucional." required />
            <x-ui.select name="status" label="Status" disabled>
                <option>Ativo</option>
            </x-ui.select>
            <x-ui.modal id="confirmacao" title="Confirmar operacao" description="Revise antes de continuar.">
                Conteudo seguro.
            </x-ui.modal>
            BLADE, ['errors' => $errors]);

        $this->assertStringContainsString('aria-busy="true"', $html);
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="nome-help nome-error"', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('data-modal', $html);
        $this->assertStringContainsString('aria-labelledby="confirmacao-title"', $html);
        $this->assertStringContainsString('data-modal-close', $html);
    }

    public function test_state_components_render_semantic_feedback(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.alert variant="success" title="Concluido">Operacao realizada.</x-ui.alert>
            <x-ui.loading label="Carregando provas" />
            <x-ui.error-state title="Falha ao carregar">Tente novamente.</x-ui.error-state>
            <x-ui.empty-state title="Nenhum resultado">Revise os filtros.</x-ui.empty-state>
            BLADE);

        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('Nenhum resultado', $html);
    }

    public function test_r4_components_render_accessible_contracts(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.avatar name="Maria da Silva" />
            <x-ui.breadcrumb :items="$items" />
            <x-ui.date-picker name="inicio" label="Data inicial" help="Use o calendario." />
            <x-ui.drawer id="menu" title="Menu principal">Navegacao</x-ui.drawer>
            <x-ui.kpi label="Aplicacoes" value="12" context="Periodo atual" />
            <x-ui.chart id="desempenho" title="Desempenho" :series="$series" value-label="Percentual" />
            <x-ui.toast variant="success" title="Salvo">Preferencia atualizada.</x-ui.toast>
            <x-ui.tooltip id="ajuda" label="Ajuda" content="Informacao complementar." />
            <x-ui.accordion title="Detalhes">Conteudo adicional.</x-ui.accordion>
            <x-ui.tabs label="Secoes">
                <x-slot:tabs>
                    <x-ui.tab id="tab-resumo" panel="painel-resumo" selected>Resumo</x-ui.tab>
                    <x-ui.tab id="tab-detalhes" panel="painel-detalhes">Detalhes</x-ui.tab>
                </x-slot:tabs>
                <x-ui.tab-panel id="painel-resumo" tab="tab-resumo" active>Resumo.</x-ui.tab-panel>
                <x-ui.tab-panel id="painel-detalhes" tab="tab-detalhes">Detalhes.</x-ui.tab-panel>
            </x-ui.tabs>
            BLADE, [
            'items' => [
                ['label' => 'Inicio', 'href' => '/admin'],
                ['label' => 'Provas'],
            ],
            'series' => [
                ['label' => 'Turma A', 'value' => 80, 'display' => '80%'],
                ['label' => 'Turma B', 'value' => 65, 'display' => '65%'],
            ],
        ]);

        $this->assertStringContainsString('aria-label="Maria da Silva"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('type="date"', $html);
        $this->assertStringContainsString('data-drawer', $html);
        $this->assertStringContainsString('aria-label="Aplicacoes"', $html);
        $this->assertStringContainsString('<progress max="80" value="80">', $html);
        $this->assertStringContainsString('Consultar dados do grafico', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('role="tooltip"', $html);
        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('aria-selected="true"', $html);
        $this->assertStringContainsString('role="tabpanel"', $html);
    }

    public function test_tokens_explicit_theme_reduced_motion_and_behaviors_are_integrated(): void
    {
        $tokens = json_decode(
            File::get(base_path('../docs/ui_token_gov_brasil.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $css = File::get(resource_path('css/app.css'));
        $tokenCss = File::get(resource_path('css/tokens.css'));
        $javascript = File::get(resource_path('js/app.js'));
        $adminLayout = File::get(resource_path('views/layouts/admin.blade.php'));
        $guestLayout = File::get(resource_path('views/layouts/guest.blade.php'));

        $this->assertStringContainsString(
            "--action-primary: {$tokens['themes']['light']['action']['primary']['value']};",
            $tokenCss,
        );
        $this->assertStringContainsString(
            "--background-modal: {$tokens['themes']['dark']['background']['modal']['value']};",
            $tokenCss,
        );
        $this->assertStringContainsString('html[data-theme="dark"]', $tokenCss);
        $this->assertStringNotContainsString('prefers-color-scheme: dark', $css.$tokenCss);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString('.button:disabled', $css);
        $this->assertStringContainsString('.modal::backdrop', $css);
        $this->assertStringContainsString('.drawer::backdrop', $css);
        $this->assertStringContainsString('color: var(--action-primary-text);', $css);
        $this->assertStringContainsString('--action-primary-text: var(--brand-black);', $tokenCss);
        $this->assertStringContainsString('data-theme="light"', $adminLayout);
        $this->assertStringContainsString('data-theme="light"', $guestLayout);
        $this->assertStringContainsString("const themeStorageKey = 'g360-theme';", $javascript);
        $this->assertStringContainsString('persistTheme(nextTheme)', $javascript);
        $this->assertStringContainsString('showModal()', $javascript);
        $this->assertStringContainsString('dialogOpeners.get(event.target)?.focus()', $javascript);
        $this->assertStringContainsString("['ArrowLeft', 'ArrowRight', 'Home', 'End']", $javascript);
    }

    public function test_approved_action_text_pairs_meet_wcag_aa_contrast(): void
    {
        $tokens = json_decode(
            File::get(base_path('../docs/ui_token_gov_brasil.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $pairs = [
            [$tokens['themes']['light']['action']['primary']['value'], $tokens['themes']['light']['text']['inverse']['value']],
            [$tokens['themes']['light']['action']['secondary']['value'], $tokens['brand']['colors']['pretoEbano']['value']],
            [$tokens['themes']['light']['action']['danger']['value'], $tokens['brand']['colors']['pretoEbano']['value']],
            [$tokens['themes']['dark']['action']['primary']['value'], $tokens['brand']['colors']['pretoEbano']['value']],
            [$tokens['themes']['dark']['action']['secondary']['value'], $tokens['brand']['colors']['pretoEbano']['value']],
            [$tokens['themes']['dark']['action']['danger']['value'], $tokens['themes']['dark']['text']['inverse']['value']],
        ];

        foreach ($pairs as [$background, $foreground]) {
            $this->assertGreaterThanOrEqual(
                4.5,
                $this->contrastRatio($background, $foreground),
                "Contraste insuficiente entre {$foreground} e {$background}.",
            );
        }
    }

    public function test_shell_is_responsive_and_reuses_shared_components(): void
    {
        $css = File::get(resource_path('css/app.css'));
        $adminLayout = File::get(resource_path('views/layouts/admin.blade.php'));

        $this->assertStringContainsString('data-drawer-open="navigation-drawer"', $adminLayout);
        $this->assertStringContainsString('<x-admin.navigation />', $adminLayout);
        $this->assertStringContainsString('<x-ui.breadcrumb', $adminLayout);
        $this->assertStringContainsString('<x-ui.theme-toggle />', $adminLayout);
        $this->assertStringContainsString('<x-ui.account-menu', $adminLayout);
        $this->assertStringContainsString('.desktop-sidebar', $css);
        $this->assertStringContainsString('@media (min-width: 640px)', $css);
        $this->assertStringContainsString('@media (min-width: 768px)', $css);
        $this->assertStringContainsString('@media (min-width: 1024px)', $css);
        $this->assertStringContainsString('overflow-x: hidden;', $css);

        $productionViews = [
            ...File::allFiles(resource_path('views/admin')),
            ...File::allFiles(resource_path('views/components')),
            ...File::allFiles(resource_path('views/layouts')),
        ];

        foreach ($productionViews as $view) {
            $contents = $view->getContents();

            $this->assertStringNotContainsString('style="', $contents, $view->getRelativePathname());
        }
    }

    private function contrastRatio(string $first, string $second): float
    {
        $values = [$this->relativeLuminance($first), $this->relativeLuminance($second)];

        return (max($values) + 0.05) / (min($values) + 0.05);
    }

    private function relativeLuminance(string $hex): float
    {
        $channels = collect(str_split(ltrim($hex, '#'), 2))
            ->map(fn (string $channel): float => hexdec($channel) / 255)
            ->map(fn (float $channel): float => $channel <= 0.04045
                ? $channel / 12.92
                : (($channel + 0.055) / 1.055) ** 2.4)
            ->values();

        return (0.2126 * $channels[0]) + (0.7152 * $channels[1]) + (0.0722 * $channels[2]);
    }
}

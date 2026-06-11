<?php

namespace Tests\Feature\Web;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class DesignSystemTest extends TestCase
{
    public function test_required_shared_components_exist(): void
    {
        $components = [
            'alert',
            'badge',
            'button',
            'card',
            'empty-state',
            'error-state',
            'input',
            'loading',
            'modal',
            'select',
            'table',
            'textarea',
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

    public function test_tokens_dark_mode_reduced_motion_and_modal_behavior_are_integrated(): void
    {
        $tokens = json_decode(
            File::get(base_path('../docs/ui_token_gov_brasil.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $css = File::get(resource_path('css/app.css'));
        $javascript = File::get(resource_path('js/app.js'));

        $this->assertStringContainsString(
            "--action-primary: {$tokens['themes']['light']['action']['primary']['value']};",
            $css,
        );
        $this->assertStringContainsString(
            "--background-modal: {$tokens['themes']['dark']['background']['modal']['value']};",
            $css,
        );
        $this->assertStringContainsString('@media (prefers-color-scheme: dark)', $css);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString('.button:disabled', $css);
        $this->assertStringContainsString('.modal::backdrop', $css);
        $this->assertStringContainsString('showModal()', $javascript);
        $this->assertStringContainsString('modalOpeners.get(event.target)?.focus()', $javascript);
    }

    public function test_existing_admin_views_reuse_shared_interactive_components(): void
    {
        foreach (File::allFiles(resource_path('views/admin')) as $view) {
            $contents = $view->getContents();

            $this->assertStringNotContainsString('<button', $contents, $view->getRelativePathname());
            $this->assertStringNotContainsString('class="table-wrap"', $contents, $view->getRelativePathname());
            $this->assertStringNotContainsString('class="alert ', $contents, $view->getRelativePathname());
            $this->assertStringNotContainsString('style="', $contents, $view->getRelativePathname());
        }
    }
}

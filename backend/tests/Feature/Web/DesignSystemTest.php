<?php

namespace Tests\Feature\Web;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Valida a fundação visual da web V2 reconstruída do zero, fiel ao mockup
 * gov.br (style-system/css/gov.css). Substitui o antigo teste do design R4.
 */
class DesignSystemTest extends TestCase
{
    public function test_root_redirects_to_the_portal(): void
    {
        $this->get('/')->assertRedirect('/painel');
    }

    public function test_app_css_uses_govbr_tokens_and_base_classes(): void
    {
        $css = File::get(resource_path('css/app.css'));

        // Tokens institucionais gov.br (não a paleta saturada R4).
        $this->assertStringContainsString('--accent: #1351b4;', $css);
        $this->assertStringContainsString('--success: #168821;', $css);
        $this->assertStringContainsString('--warn: #ffcd07;', $css);
        $this->assertStringContainsString('--danger: #e52207;', $css);
        $this->assertStringContainsString("'Rawline', 'Raleway'", $css);

        // Classes-base do design system.
        foreach (['.govbar', '.app-header', '.app-nav', '.btn', '.btn-primary', '.card', '.table', '.badge', '.kpi', '.breadcrumb'] as $selector) {
            $this->assertStringContainsString($selector, $css, $selector);
        }

        // Tema escuro por data-theme (claro é o padrão), sem prefers-color-scheme.
        $this->assertStringContainsString('html[data-theme="dark"]', $css);
        $this->assertStringNotContainsString('prefers-color-scheme: dark', $css);
    }

    public function test_guest_layout_loads_fonts_and_assets(): void
    {
        $guest = File::get(resource_path('views/layouts/guest.blade.php'));

        $this->assertStringContainsString('data-theme="light"', $guest);
        $this->assertStringContainsString('Raleway', $guest);
        $this->assertStringContainsString("@vite('resources/css/app.css')", $guest);
    }

    public function test_login_screen_is_faithful_to_the_mockup(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('auth-wrap', $html);
        $this->assertStringContainsString('G360', $html);
        $this->assertStringContainsString('Acesso ao sistema', $html);
        $this->assertStringContainsString(route('login'), $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="password"', $html);
    }

    public function test_action_color_pairs_meet_wcag_aa_contrast(): void
    {
        // Pares oficiais gov.br (fundo/texto) usados em CTAs e faixas.
        $pairs = [
            ['#1351b4', '#ffffff'],
            ['#168821', '#ffffff'],
            ['#e52207', '#ffffff'],
            ['#071d41', '#ffffff'],
        ];

        foreach ($pairs as [$background, $foreground]) {
            $this->assertGreaterThanOrEqual(
                4.5,
                $this->contrastRatio($background, $foreground),
                "Contraste insuficiente entre {$foreground} e {$background}.",
            );
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

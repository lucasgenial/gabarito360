<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Symfony\Component\Mime\Email;

/**
 * Arquivo: apps/api/app/Mail/EmailModelo.php
 * Função: Envia pela fila um modelo de resources/emails/modelos, dentro de base.html.
 *         O HTML fica sem Blade; os {{campos}} são preenchidos com valores escapados.
 */
class EmailModelo extends Mailable implements ShouldQueue, \Illuminate\Contracts\Queue\ShouldBeEncrypted
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    /** @param array<string, string|int|float|null> $dados */
    public function __construct(
        public readonly string $modelo,
        public readonly string $assunto,
        public readonly array $dados = [],
        public readonly ?string $responderPara = null,
    ) {
        $this->onQueue('padrao');
    }

    public function build(): self
    {
        $html = $this->renderizar();

        $mensagem = $this->from(config('emails.remetente'), 'Gabarito360')
            ->subject($this->assunto)
            ->html($html)
            ->withSymfonyMessage(function (Email $email) use ($html) {
                $email->text($this->comoTexto($html));
                $email->getHeaders()->addTextHeader('X-G360-Modelo', $this->modelo);
            });

        return $this->responderPara ? $mensagem->replyTo($this->responderPara) : $mensagem;
    }

    public function renderizar(): string
    {
        $conteudo = $this->preencher($this->arquivo("modelos/{$this->modelo}.html"));

        return str_replace(
            ['{{titulo}}', '{{conteudo}}', '{{ano}}'],
            [e($this->assunto), $conteudo, date('Y')],
            $this->arquivo('base.html')
        );
    }

    private function arquivo(string $relativo): string
    {
        $caminho = resource_path("emails/{$relativo}");
        if (!is_file($caminho)) {
            throw new RuntimeException("Modelo de e-mail não encontrado: {$relativo}");
        }

        return (string) file_get_contents($caminho);
    }

    private function preencher(string $html): string
    {
        foreach ($this->dados as $chave => $valor) {
            $texto = nl2br(e((string) ($valor ?? '')), false);
            $html = str_replace('{{' . $chave . '}}', $texto, $html);
        }

        return (string) preg_replace('/\{\{\s*[a-z0-9_]+\s*\}\}/i', '', $html);
    }

    private function comoTexto(string $html): string
    {
        $corpo = (string) preg_replace('/<head.*?<\/head>/is', '', $html);
        $corpo = (string) preg_replace('/<a [^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is', '$2: $1', $corpo);
        $corpo = (string) preg_replace('/<(br|\/p|\/h1|\/tr|\/div)[^>]*>/i', "\n", $corpo);
        $corpo = html_entity_decode(strip_tags($corpo), ENT_QUOTES, 'UTF-8');

        return trim((string) preg_replace("/\n\s*\n+/", "\n\n", (string) preg_replace('/[ \t]+/', ' ', $corpo)));
    }
}

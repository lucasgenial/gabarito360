<?php

/**
 * Arquivo: apps/api/config/emails.php
 * Função: Endereços usados pelo sistema. Ver docs/operacao/email.md.
 */

return [
    'remetente' => env('MAIL_FROM_ADDRESS', 'nao-responda@gabarito360.com.br'),

    // Recebe as mensagens do Fale Conosco
    'contato' => env('MAIL_CONTATO', 'contatos@gabarito360.com.br'),

    // Base dos links enviados por e-mail
    'url_web' => rtrim((string) env('APP_URL_WEB', 'https://gabarito360.com.br'), '/'),
];

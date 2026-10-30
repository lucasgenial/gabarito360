<?php

/**
 * Arquivo: apps/api/config/omr.php
 * Função: Configuração do motor OMR e do gerador de cartões-resposta nativos.
 */

return [
    // nativo = leitura real por visão computacional (omr/ler_cartao.py)
    // simulado = leituras determinísticas para desenvolvimento/testes
    'driver' => env('OMR_DRIVER', 'nativo'),

    // Executável Python com opencv-python-headless, numpy e reportlab instalados
    // (ver omr/requirements.txt). Ex.: /var/www/gabarito360/apps/api/omr/.venv/bin/python
    'python' => env('OMR_PYTHON', 'python3'),

    'scripts' => base_path('omr'),

    'timeout' => (int) env('OMR_TIMEOUT', 60),
];

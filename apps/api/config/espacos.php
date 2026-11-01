<?php

return [
    // Release de ativação T2: publicar somente com manutenção, preenchimento e corte aprovados.
    // B5 impede reabertura automática se o esquema restritivo ainda não estiver completo.
    'ativo' => true,
    // T3-B: integração apenas em ensaios com configuração explícita; habilitação pública é T3-C.
    'pessoal' => false,
];

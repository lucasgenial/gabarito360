<?php

return [
    // Release de ativação T2: publicar somente com manutenção, preenchimento e corte aprovados.
    // B5 impede reabertura automática se o esquema restritivo ainda não estiver completo.
    'ativo' => true,
    // T3-C: acesso exige vínculo válido do proprietário; provisionamento permanece T4.
    // Publicar apenas após migrações e ensaio da release; não cria espaços automaticamente.
    'pessoal' => true,
];

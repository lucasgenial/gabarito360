<?php

// Configuração versionada e somente leitura. Não é uma matriz de autorização.
$turmas = ['cadastros-turno','comparacao-turmas','cobertura-disciplinas','prioridades','proximos-compromissos','devolutivas','responsabilidade-docente-aplicacao','intervencoes','agenda-administrativa','documentos-administrativos'];
$turma = ['distribuicao-acertos','resultados-avaliacao','situacoes-registradas','alunos-recorte','periodos-escolares','agenda-pedagogica','devolutivas','participacao','responsabilidade-docente-aplicacao','habilidades','intervencoes','reavaliacoes','metas','conselho','agenda-administrativa','documentos-administrativos'];
$padroes = [];
foreach (['admin_rede','dir_nucleo','dir_escolar','vice_dir_escolar','coordenador','professor','professor_autonomo','secretario_escolar','auxiliar_secretaria','aplicador','aluno','secretario_educacao','desenvolvedor','suporte','financeiro'] as $perfil) {
    // Arrays por valor, sem referências compartilhadas entre perfis/painéis.
    $padroes[$perfil] = [
        'turmas'=>['ordem'=>$turmas, 'larguras'=>[], 'alturas_desejadas'=>[]],
        'turma'=>['ordem'=>$turma, 'larguras'=>[], 'alturas_desejadas'=>[]],
    ];
}
return [
    'versao'=>1,
    'paineis'=>[
        'turmas'=>['recurso'=>'contexto', 'secoes_fixas'=>['resumo']],
        'turma'=>['recurso'=>'turma', 'secoes_fixas'=>['resumo']],
    ],
    'padroes'=>$padroes,
];

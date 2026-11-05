<?php

// Comunicação pública local; não configura contratos, preços ou permissões.
return [
    'compartilhamento'=>[
        'home'=>['titulo'=>'Gabarito360 — Avaliações, correção e resultados','descricao'=>'Gabaritos e cartões-resposta, conferência de leituras e relatórios. Conheça o Gabarito360 em pré-lançamento.'],
        'planos.index'=>['titulo'=>'Gabarito360 — Modalidades de atendimento','descricao'=>'Professor autônomo, escolas, núcleos e redes e secretarias. Conheça as modalidades; a contratação será disponibilizada no lançamento.'],
        'leads.index'=>['titulo'=>'Fale com o Gabarito360','descricao'=>'Converse com a equipe para conhecer o sistema e apresentar as necessidades da sua rotina de avaliações.'],
    ],
    'titulo'=>'Gabarito360 — avaliações, correção e resultados para professores',
    'descricao'=>'Organize gabaritos e cartões-resposta, confira leituras por câmera ou foto e acompanhe resultados. Conheça o Gabarito360 e suas modalidades em pré-lançamento.',
    'modalidades_intro'=>'Do trabalho individual à organização de uma rede de ensino. A contratação será disponibilizada no lançamento.',
    'modalidades'=>[
        ['nome'=>'Professor autônomo','descricao'=>'Para organizar suas próprias avaliações em um espaço pessoal.','itens'=>['Gabaritos e cartões para suas avaliações','Leitura, conferência e acompanhamento dos resultados']],
        ['nome'=>'Escolas','descricao'=>'Para organizar a equipe, as turmas e os alunos no contexto da instituição.','itens'=>['Avaliações e acompanhamento conforme cada função','Responsabilidades e acessos da equipe'],'futuro'=>['nome'=>'Adesão de escolas autônomas','descricao'=>'Espaço, identidade e equipe próprios para adesão institucional direta.']],
        ['nome'=>'Núcleos e redes','descricao'=>'Para organizar instituições e equipes em diferentes contextos de atuação.','itens'=>['Organização de múltiplas instituições','Acompanhamento conforme contexto e função']],
        ['nome'=>'Secretarias','descricao'=>'Para conversar sobre as necessidades de avaliação de uma rede de ensino.','itens'=>['Apresentação para a equipe gestora','Levantamento das necessidades de atendimento institucional']],
    ],
    'momentos'=>[
        ['nome'=>'Preparação','beneficio'=>'Organize a aplicação antes de chegar à sala.','recursos'=>[
            ['nome'=>'Gabaritos e cartões identificados','descricao'=>'Organize o gabarito, as turmas e os alunos e imprima os cartões identificados.'],
            ['nome'=>'Elaboração de provas e avaliações','descricao'=>'Crie enunciados, questões e versões da avaliação em um só fluxo.','em_breve'=>true],
            ['nome'=>'Acervo inteligente e GII','descricao'=>'Encontre e organize questões com mais critério, começando por itens do ENEM.','ajuda'=>'O GII — Gabarito360 Item Intelligence — reunirá questões de fontes públicas, de professores e conteúdos produzidos com assistência de IA, conforme as regras do projeto. Isso não representa vínculo oficial com o ENEM nem um acervo completo já disponível.','em_breve'=>true],
        ]],
        ['nome'=>'Correção','beneficio'=>'Da foto à conferência, com o professor no controle.','recursos'=>[
            ['nome'=>'Leitura e revisão pelo celular','descricao'=>'Envie a foto do cartão, confira as leituras sinalizadas e registre a resposta revisada.'],
            ['nome'=>'Marcações na própria prova','descricao'=>'Use a própria página da avaliação para marcar respostas e conectar novos formatos à correção por imagem.','em_breve'=>true],
            ['nome'=>'Avaliações impressas, digitais e mistas','descricao'=>'Combine formatos e receba apoio à correção de questões abertas, com conferência do professor.','ajuda'=>'O apoio à leitura e correção de questões abertas poderá usar reconhecimento de texto (OCR) e IA, com critérios de avaliação definidos e decisão do professor. Não é correção autônoma irrestrita.','em_breve'=>true],
        ]],
        ['nome'=>'Análise','beneficio'=>'Transforme a devolutiva em um próximo passo pedagógico.','recursos'=>[
            ['nome'=>'Resultados e relatórios','descricao'=>'Veja acertos, erros e respostas em branco e organize a devolutiva com relatórios.','ajuda'=>'Educadores autorizados acompanham percentuais por questão. Os resultados identificam anulações e os relatórios podem ser gerados em PDF individual ou em lote.'],
            ['nome'=>'Análises pedagógicas e psicométricas','descricao'=>'Entenda melhor os itens e o desempenho para apoiar suas decisões pedagógicas.','ajuda'=>'A Teoria Clássica dos Testes (TCT) considera medidas como dificuldade e discriminação dos itens. A Teoria de Resposta ao Item (TRI) relaciona respostas e características dos itens a modelos de desempenho. Não se trata de nota oficial do ENEM ou calibração já disponível.','em_breve'=>true],
            ['nome'=>'Integridade da avaliação e padrões de respostas','descricao'=>'Observe similaridades e padrões atípicos para orientar uma revisão humana.','ajuda'=>'A análise considerará respostas, grupos e contexto. A evolução inclui mapa de sala e lista de presença registrados pelo professor e lidos pelo celular, sem atribuir culpa ou comprovar cola automaticamente.','em_breve'=>true],
        ]],
    ],
    'faq'=>[
        ['pergunta'=>'Como o Gabarito360 lê as respostas?','resposta'=>'Ele lê as marcações do cartão-resposta pela câmera ou por uma foto enviada. Use o cartão gerado pelo sistema e uma imagem nítida, com seus marcadores completos.'],
        ['pergunta'=>'Posso conferir uma leitura?','resposta'=>'Sim. O professor autorizado pode examinar a marcação e o cartão completo, revisar a leitura e registrar a resposta conferida.'],
        ['pergunta'=>'Quais relatórios existem?','resposta'=>'Há resultados por aluno e relatórios para análise do professor, com informações por questão. Educadores autorizados acompanham os percentuais da turma e das turmas da mesma avaliação. Os relatórios podem ser gerados em PDF, inclusive em conjunto por avaliação.'],
        ['pergunta'=>'O que muda entre o uso pessoal e o institucional?','resposta'=>'No espaço pessoal, o professor organiza suas próprias avaliações. No contexto institucional, equipe, turmas e acompanhamento seguem as funções e os acessos concedidos. Escola autônoma e professor autônomo são contextos distintos.'],
        ['pergunta'=>'O que significa o selo Em breve?','resposta'=>'O recurso ainda não está disponível. O selo apresenta a evolução do Gabarito360, sem data anunciada nem definição de inclusão em uma modalidade.'],
        ['pergunta'=>'O sistema está aberto para contratação?','resposta'=>'O Gabarito360 está em pré-lançamento. A contratação será disponibilizada no lançamento. Para conhecer o sistema e conversar sobre sua realidade, fale com a equipe.'],
        ['pergunta'=>'Os PDFs têm assinatura digital pessoal?','resposta'=>'Os novos relatórios possuem registro de emissão e conferência de integridade. O código ou QR consulta o registro; a conferência do arquivo verifica sua correspondência. Esse mecanismo não equivale a assinatura pessoal com certificado digital.'],
        ['pergunta'=>'É preciso ter internet?','resposta'=>'A aplicação precisa de conexão para acessar e processar seus recursos. A página de apresentação pode permanecer disponível durante a manutenção do sistema; isso não significa uso offline do aplicativo.'],
        ['pergunta'=>'Como conversar com a equipe?','resposta'=>'Use Falar com a gente para abrir o contato existente. Durante uma manutenção, o envio pode ficar temporariamente indisponível; uma mensagem só é confirmada quando o envio ocorre.'],
    ],
];

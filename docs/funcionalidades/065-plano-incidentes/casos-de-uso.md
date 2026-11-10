# 065 — Plano de incidentes — Casos de uso

**Natureza:** nova (procedimento operacional)
**Perfis envolvidos:** responsável pela plataforma; Desenvolvedor e Suporte (investigação pela auditoria); instituição controladora; encarregado. Ver [docs/03](../../03-perfis-e-permissoes.md).
**Atualizado em:** 2026-11-09

Todos os casos abaixo são propostos. Nenhum está implementado: `docs/operacao/incidentes.md` não existe.

---

## UC-065-01 — Registrar e classificar um incidente com dados pessoais

**Ator:** responsável pela plataforma ou Desenvolvedor de plantão
**Permissão:** acesso interno (Desenvolvedor) para consultar a auditoria
**Escopo:** plataforma
**Pré-condições:** suspeita de acesso indevido, vazamento, perda ou alteração de dados pessoais.
**Pós-condições:** incidente registrado com data, descoberta, dados e titulares afetados (em números), gravidade e responsável.
**Tela/rota:** registro definido em `docs/operacao/incidentes.md` (a criar); evidências fora do Git.

**Fluxo principal:**
1. Quem detecta comunica o responsável.
2. Abre-se o registro com a hora da descoberta.
3. A equipe contém o incidente (revogar acesso, isolar serviço, preservar evidências).
4. Consulta a auditoria AU1 para delimitar alcance.
5. Classifica a gravidade conforme os critérios do plano.

**Fluxos alternativos e de erro:**
- 5a. Sem dados pessoais envolvidos: segue como incidente operacional comum, com registro próprio.

**Regras de negócio:** RL11.

**Critérios de aceite:**
- [ ] O plano define campos obrigatórios do registro e critérios de gravidade.

**Situação:** Não implementado — subfase 5.1.

---

## UC-065-02 — Comunicar a instituição, os titulares e a ANPD

**Ator:** responsável pela plataforma, com o encarregado
**Permissão:** não se aplica
**Escopo:** instituições e titulares afetados
**Pré-condições:** incidente classificado como de risco ou dano relevante.
**Pós-condições:** avisos enviados e registrados com data.
**Tela/rota:** modelos de aviso em `docs/operacao/incidentes.md` (a criar).

**Fluxo principal:**
1. Preencher o modelo de aviso à instituição controladora.
2. Apoiar a instituição no aviso aos titulares e à ANPD, no prazo confirmado.
3. Registrar envios e respostas.

**Fluxos alternativos e de erro:**
- 1a. Plataforma atuando como controladora (espaço pessoal): o responsável comunica diretamente.

**Regras de negócio:** RL11; prazo da ANPD a confirmar (docs/28 L5).

**Critérios de aceite:**
- [ ] Modelos para instituição, titulares e ANPD aprovados.

**Situação:** Não implementado — subfase 5.1.

---

## UC-065-03 — Simular um incidente

**Ator:** responsável pela plataforma e equipe
**Permissão:** não se aplica
**Escopo:** cenário fictício
**Pré-condições:** plano aprovado.
**Pós-condições:** simulação registrada com tempos, falhas encontradas e ajustes no plano.
**Tela/rota:** registro da simulação em `docs/operacao/`.

**Fluxo principal:**
1. Escolher um cenário do docs/38 (por exemplo, R02 ou R06).
2. Executar os passos de UC-065-01 e UC-065-02 com dados fictícios.
3. Registrar o resultado e ajustar o plano.

**Fluxos alternativos e de erro:**
- Nenhum.

**Regras de negócio:** docs/28 L5 ("Pronto quando: uma simulação foi registrada").

**Critérios de aceite:**
- [ ] Simulação registrada sem dados reais nem prints.

**Situação:** Não implementado — subfase 5.2.

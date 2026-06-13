# Infraestrutura e Qualidade V2

## Ambientes

- Desenvolvimento local reproduzível com MariaDB.
- Homologação e produção em containers separados para Nginx, Laravel, filas,
  scheduler, Reverb, MariaDB e Redis.
- Storage privado compatível com S3 e segredos fora do repositório.

## Reaproveitamento R7

Compose, imagens, CI, backup/restauração e runbooks atuais são a base da V2.
Devem ser ampliados sem perder os gates já aprovados.

## Pipeline de qualidade

1. Composer/npm/Flutter/Python com lockfiles.
2. Lint e análise estática.
3. Migrations e testes Laravel em MariaDB.
4. Testes de contrato OpenAPI.
5. Testes Flutter e build APK.
6. Regressão OMR sobre dataset versionado.
7. Testes visuais e acessibilidade das 30 telas.
8. Build de containers, health check e restauração.
9. Varredura de dependências e segredos.

## Gates de entrega

- Sem regressão dos testes R7.
- Paridade funcional e visual rastreada por tela.
- Backup/restauração demonstrados.
- OMR e Android validados em material/dispositivos reais antes do piloto.
- Observabilidade e alertas não expõem dados pessoais.

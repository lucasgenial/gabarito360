# App Android Gabarito360

Cliente Flutter Android para professores e aplicadores vinculados. A base R6
implementa tema oficial claro/escuro, autenticacao mobile, lista de aplicacoes,
snapshot operacional e alunos autorizados.

A captura por camera e o OMR no dispositivo permanecem bloqueados ate a
homologacao do modelo fisico e da matriz de dispositivos. O app nao exibe dados
pessoais alem do necessario ao fluxo.

## Executar

```powershell
cd mobile
C:\develop\flutter\bin\flutter.bat run --dart-define=API_URL=http://10.0.2.2:8000/api/v1
```

Em dispositivo fisico, substitua `10.0.2.2` pelo IP local do computador.

## Verificar

```powershell
C:\develop\flutter\bin\flutter.bat analyze
C:\develop\flutter\bin\flutter.bat test
```

Os tokens mapeados em `lib/design/g360_tokens.dart` derivam de
`docs/ui_token_gov_brasil.json`. O tema inicial e claro; o escuro depende de
acao explicita do usuario.

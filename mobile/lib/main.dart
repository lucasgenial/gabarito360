import 'package:flutter/material.dart';

import 'api/g360_api.dart';
import 'design/g360_theme.dart';
import 'design/g360_tokens.dart';

void main() => runApp(const Gabarito360App());

class Gabarito360App extends StatefulWidget {
  const Gabarito360App({super.key, this.api});
  final G360Api? api;

  @override
  State<Gabarito360App> createState() => _Gabarito360AppState();
}

class _Gabarito360AppState extends State<Gabarito360App> {
  late final G360Api api = widget.api ?? G360Api();
  ThemeMode themeMode = ThemeMode.light;

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Gabarito360',
      debugShowCheckedModeBanner: false,
      theme: G360Theme.light(),
      darkTheme: G360Theme.dark(),
      themeMode: themeMode,
      home: LoginPage(
        api: api,
        onAuthenticated: () => Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => ApplicationsPage(api: api, onThemeChanged: _toggleTheme)),
        ),
        onThemeChanged: _toggleTheme,
      ),
    );
  }

  void _toggleTheme() => setState(() {
    themeMode = themeMode == ThemeMode.light ? ThemeMode.dark : ThemeMode.light;
  });
}

class LoginPage extends StatefulWidget {
  const LoginPage({super.key, required this.api, required this.onAuthenticated, required this.onThemeChanged});
  final G360Api api;
  final VoidCallback onAuthenticated;
  final VoidCallback onThemeChanged;

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final email = TextEditingController();
  final password = TextEditingController();
  String? error;
  bool loading = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Gabarito360'),
        actions: [IconButton(onPressed: widget.onThemeChanged, tooltip: 'Alternar tema', icon: const Icon(Icons.contrast))],
      ),
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(G360Tokens.space6),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 480),
            child: Card(
              child: Padding(
                padding: const EdgeInsets.all(G360Tokens.space6),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text('Operacao de aplicacoes', style: Theme.of(context).textTheme.headlineSmall),
                    const SizedBox(height: G360Tokens.space2),
                    const Text('Entre com um perfil de professor ou aplicador autorizado.'),
                    const SizedBox(height: G360Tokens.space6),
                    TextField(controller: email, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(labelText: 'E-mail')),
                    const SizedBox(height: G360Tokens.space4),
                    TextField(controller: password, obscureText: true, decoration: const InputDecoration(labelText: 'Senha')),
                    if (error != null) ...[
                      const SizedBox(height: G360Tokens.space4),
                      Semantics(liveRegion: true, child: Text(error!, style: TextStyle(color: Theme.of(context).colorScheme.error))),
                    ],
                    const SizedBox(height: G360Tokens.space6),
                    ElevatedButton(onPressed: loading ? null : _login, child: Text(loading ? 'Entrando...' : 'Entrar')),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _login() async {
    setState(() { loading = true; error = null; });
    try {
      await widget.api.login(email.text, password.text);
      widget.onAuthenticated();
    } catch (exception) {
      setState(() => error = exception.toString());
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }
}

class ApplicationsPage extends StatefulWidget {
  const ApplicationsPage({super.key, required this.api, required this.onThemeChanged});
  final G360Api api;
  final VoidCallback onThemeChanged;

  @override
  State<ApplicationsPage> createState() => _ApplicationsPageState();
}

class _ApplicationsPageState extends State<ApplicationsPage> {
  late Future<List<Map<String, dynamic>>> future = widget.api.applications();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Aplicacoes autorizadas'),
        actions: [
          IconButton(onPressed: () => setState(() => future = widget.api.applications()), tooltip: 'Atualizar', icon: const Icon(Icons.refresh)),
          IconButton(onPressed: widget.onThemeChanged, tooltip: 'Alternar tema', icon: const Icon(Icons.contrast)),
        ],
      ),
      body: FutureBuilder<List<Map<String, dynamic>>>(
        future: future,
        builder: (context, snapshot) {
          if (snapshot.hasError) return _Message(text: snapshot.error.toString(), icon: Icons.error_outline);
          if (!snapshot.hasData) return const Center(child: CircularProgressIndicator());
          if (snapshot.data!.isEmpty) return const _Message(text: 'Nenhuma aplicacao autorizada.', icon: Icons.inbox_outlined);

          return ListView.separated(
            padding: const EdgeInsets.all(G360Tokens.space4),
            itemCount: snapshot.data!.length,
            separatorBuilder: (_, _) => const SizedBox(height: G360Tokens.space3),
            itemBuilder: (context, index) {
              final application = snapshot.data![index];
              return Card(
                child: ListTile(
                  minVerticalPadding: G360Tokens.space4,
                  title: Text(application['titulo'] as String),
                  subtitle: Text('${application['status']} • ${application['leituras_count'] ?? 0} leituras'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => Navigator.of(context).push(MaterialPageRoute(
                    builder: (_) => ApplicationPage(api: widget.api, application: application),
                  )),
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class ApplicationPage extends StatelessWidget {
  const ApplicationPage({super.key, required this.api, required this.application});
  final G360Api api;
  final Map<String, dynamic> application;

  @override
  Widget build(BuildContext context) {
    final id = application['id'] as String;
    return Scaffold(
      appBar: AppBar(title: Text(application['titulo'] as String)),
      body: FutureBuilder<List<dynamic>>(
        future: Future.wait([api.dashboard(id), api.students(id)]),
        builder: (context, snapshot) {
          if (snapshot.hasError) return _Message(text: snapshot.error.toString(), icon: Icons.error_outline);
          if (!snapshot.hasData) return const Center(child: CircularProgressIndicator());
          final dashboard = snapshot.data![0] as Map<String, dynamic>;
          final metrics = dashboard['metrics'] as Map<String, dynamic>;
          final students = snapshot.data![1] as List<Map<String, dynamic>>;

          return ListView(
            padding: const EdgeInsets.all(G360Tokens.space4),
            children: [
              Card(child: Padding(
                padding: const EdgeInsets.all(G360Tokens.space4),
                child: Text('${metrics['confirmed_readings']} confirmadas de ${metrics['expected_students']} previstas\n${metrics['pending_review']} requerem revisao'),
              )),
              const SizedBox(height: G360Tokens.space4),
              Text('Alunos', style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: G360Tokens.space2),
              ...students.map((student) => Card(child: ListTile(
                title: Text(student['nome'] as String),
                subtitle: Text('${student['matricula']} • ${student['status']}'),
                leading: Icon(student['resultado_vigente_id'] == null ? Icons.pending_outlined : Icons.check_circle_outline),
              ))),
              const SizedBox(height: G360Tokens.space4),
              const _Message(
                text: 'Captura por camera e OMR no dispositivo permanecem bloqueados ate homologacao fisica. O app consome apenas contratos reais do backend.',
                icon: Icons.camera_alt_outlined,
              ),
            ],
          );
        },
      ),
    );
  }
}

class _Message extends StatelessWidget {
  const _Message({required this.text, required this.icon});
  final String text;
  final IconData icon;

  @override
  Widget build(BuildContext context) => Center(
    child: Padding(
      padding: const EdgeInsets.all(G360Tokens.space6),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        Icon(icon, size: G360Tokens.controlHeight),
        const SizedBox(height: G360Tokens.space3),
        Text(text, textAlign: TextAlign.center),
      ]),
    ),
  );
}

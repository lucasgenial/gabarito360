import 'package:flutter_test/flutter_test.dart';
import 'package:gabarito360_mobile/main.dart';

void main() {
  testWidgets('starts with accessible light login screen', (tester) async {
    await tester.pumpWidget(const Gabarito360App());

    expect(find.text('Operacao de aplicacoes'), findsOneWidget);
    expect(find.text('Entrar'), findsOneWidget);
    expect(find.byTooltip('Alternar tema'), findsOneWidget);
  });
}

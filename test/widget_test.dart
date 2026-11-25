import 'package:flutter_test/flutter_test.dart';
import 'package:crisp_crave/main.dart';

void main() {
  testWidgets('CrispCraveApp loads and displays navigation items', (WidgetTester tester) async {
    await tester.pumpWidget(const CrispCraveApp());
    expect(find.text('Home'), findsOneWidget);
    expect(find.text('Deals'), findsOneWidget);
    expect(find.text('Menu'), findsOneWidget);
    expect(find.text('Cart'), findsOneWidget);
    expect(find.text('Account'), findsOneWidget);
  });
}

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';

import 'constants/app_colors.dart';
import 'providers/cart_provider.dart';
import 'providers/chatbot_provider.dart';
import 'providers/favorites_provider.dart';
import 'providers/menu_provider.dart';
import 'providers/reservation_provider.dart';
import 'providers/user_provider.dart';
import 'screens/splash_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();

  // Set system UI style to match the brand
  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.dark,
      systemNavigationBarColor: AppColors.cardBg,
      systemNavigationBarIconBrightness: Brightness.dark,
    ),
  );

  runApp(const CrispCraveApp());
}

class CrispCraveApp extends StatelessWidget {
  const CrispCraveApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => MenuProvider()),
        ChangeNotifierProvider(create: (_) => CartProvider()),
        ChangeNotifierProvider(create: (_) => FavoritesProvider()),
        ChangeNotifierProvider(create: (_) => ReservationProvider()),
        ChangeNotifierProvider(create: (_) => ChatbotProvider()),
        ChangeNotifierProvider(create: (_) => UserProvider()),
      ],
      child: MaterialApp(
        title: 'Crisp & Crave',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          useMaterial3: true,
          scaffoldBackgroundColor: AppColors.pageBg,
          colorScheme: ColorScheme.fromSeed(
            seedColor: AppColors.primary,
            primary: AppColors.primary,
            secondary: AppColors.accent,
            surface: AppColors.cardBg,
          ),
          textTheme: GoogleFonts.plusJakartaSansTextTheme(
            Theme.of(context).textTheme,
          ),
          appBarTheme: AppBarTheme(
            backgroundColor: AppColors.cardBg,
            elevation: 0,
            iconTheme: const IconThemeData(color: AppColors.dark),
            titleTextStyle: GoogleFonts.outfit(
              color: AppColors.dark,
              fontSize: 20,
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
        home: const SplashScreen(),
      ),
    );
  }
}

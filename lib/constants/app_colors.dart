import 'package:flutter/material.dart';

class AppColors {
  // Primary Palette
  static const Color primary = Color(0xFFFF5722);
  static const Color primaryDark = Color(0xFFE64A19);
  static const Color primaryLight = Color(0xFFFF8A65);
  static const Color primarySoft = Color(0xFFFFF0EB);

  // Accent & Warmth
  static const Color accent = Color(0xFFF59E0B);
  static const Color accentLight = Color(0xFFFEF3C7);
  static const Color gold = Color(0xFFFFB703);

  // Dark & Neutral Typography
  static const Color dark = Color(0xFF121826);
  static const Color darkMuted = Color(0xFF4B5563);
  static const Color darkSubtle = Color(0xFF9CA3AF);

  // Backgrounds & Surfaces
  static const Color pageBg = Color(0xFFFBFAF8);
  static const Color cardBg = Color(0xFFFFFFFF);
  static const Color surfaceSubtle = Color(0xFFF6F3EE);
  static const Color tagBg = Color(0xFFF3EDE2);

  // Status & Badges
  static const Color vegGreen = Color(0xFF16A34A);
  static const Color nonVegRed = Color(0xFFDC2626);
  static const Color starRating = Color(0xFFF59E0B);
  static const Color spicyOrange = Color(0xFFFF5722);

  // Borders & Dividers
  static const Color borderLight = Color(0x14121826);
  static const Color borderFocus = Color(0x59FF5722);

  // Gradients
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [Color(0xFFFF5722), Color(0xFFFF7849)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient bannerGradient = LinearGradient(
    colors: [Color(0xFFFFF4EC), Color(0xFFFFEBD9), Color(0xFFFEF5EE)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient heroGradient = LinearGradient(
    colors: [Color(0xFFFFF7F0), Color(0xFFFFF0E3), Color(0xFFFBFAF8)],
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
  );

  static const LinearGradient darkCardGradient = LinearGradient(
    colors: [Color(0xFF1E2638), Color(0xFF121826)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
}

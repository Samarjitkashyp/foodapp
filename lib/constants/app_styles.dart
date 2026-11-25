import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'app_colors.dart';

class AppStyles {
  // Headings with Outfit
  static TextStyle headingLarge({Color color = AppColors.dark}) =>
      GoogleFonts.outfit(
        fontSize: 26,
        fontWeight: FontWeight.w800,
        color: color,
        letterSpacing: -0.5,
      );

  static TextStyle headingMedium({Color color = AppColors.dark}) =>
      GoogleFonts.outfit(
        fontSize: 20,
        fontWeight: FontWeight.w700,
        color: color,
        letterSpacing: -0.3,
      );

  static TextStyle headingSmall({Color color = AppColors.dark}) =>
      GoogleFonts.outfit(
        fontSize: 16,
        fontWeight: FontWeight.w700,
        color: color,
      );

  // Body with Plus Jakarta Sans
  static TextStyle bodyLarge({
    Color color = AppColors.dark,
    FontWeight weight = FontWeight.w500,
    double? height = 1.4,
  }) =>
      GoogleFonts.plusJakartaSans(
        fontSize: 15,
        fontWeight: weight,
        color: color,
        height: height,
      );

  static TextStyle bodyMedium({
    Color color = AppColors.darkMuted,
    FontWeight weight = FontWeight.w400,
    double? height = 1.4,
  }) =>
      GoogleFonts.plusJakartaSans(
        fontSize: 13,
        fontWeight: weight,
        color: color,
        height: height,
      );

  static TextStyle bodySmall({
    Color color = AppColors.darkSubtle,
    FontWeight weight = FontWeight.w400,
    double? height,
  }) =>
      GoogleFonts.plusJakartaSans(
        fontSize: 11,
        fontWeight: weight,
        color: color,
        height: height,
      );

  // Price Styling
  static TextStyle priceMain({Color color = AppColors.primary, double fontSize = 18}) =>
      GoogleFonts.outfit(
        fontSize: fontSize,
        fontWeight: FontWeight.w800,
        color: color,
      );

  static TextStyle priceOriginal({double fontSize = 14}) =>
      GoogleFonts.plusJakartaSans(
        fontSize: fontSize,
        fontWeight: FontWeight.w500,
        color: AppColors.darkSubtle,
        decoration: TextDecoration.lineThrough,
      );

  // Shadows
  static const List<BoxShadow> cardShadow = [
    BoxShadow(
      color: Color(0x0F121826),
      blurRadius: 16,
      offset: Offset(0, 4),
    ),
  ];

  static const List<BoxShadow> floatingShadow = [
    BoxShadow(
      color: Color(0x28FF5722),
      blurRadius: 20,
      offset: Offset(0, 8),
    ),
  ];
}

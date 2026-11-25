import 'package:flutter/material.dart';
import '../constants/app_colors.dart';

class SpicyIndicator extends StatelessWidget {
  final int spicyLevel; // 1 to 3
  final double size;

  const SpicyIndicator({super.key, required this.spicyLevel, this.size = 13});

  @override
  Widget build(BuildContext context) {
    if (spicyLevel <= 0) return const SizedBox.shrink();

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: List.generate(
        spicyLevel.clamp(1, 3),
        (index) => Padding(
          padding: const EdgeInsets.only(right: 1),
          child: Icon(
            Icons.local_fire_department_rounded,
            color: AppColors.spicyOrange,
            size: size,
          ),
        ),
      ),
    );
  }
}

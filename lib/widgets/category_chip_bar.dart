import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';
import '../providers/menu_provider.dart';

export '../models/category_item.dart';

class CategoryChipBar extends StatelessWidget {
  const CategoryChipBar({super.key});

  @override
  Widget build(BuildContext context) {
    return Consumer<MenuProvider>(
      builder: (context, menu, child) {
        final categories = menu.categories;

        return SizedBox(
          height: 44,
          child: ListView.separated(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            scrollDirection: Axis.horizontal,
            itemCount: categories.length,
            separatorBuilder: (_, _) => const SizedBox(width: 8),
            itemBuilder: (context, index) {
              final cat = categories[index];
              final isSelected = menu.selectedCategory.toLowerCase() == cat.id.toLowerCase();

              return ChoiceChip(
                showCheckmark: false,
                avatar: Icon(
                  cat.icon,
                  size: 16,
                  color: isSelected ? Colors.white : AppColors.darkMuted,
                ),
                label: Text(
                  cat.title,
                  style: AppStyles.bodySmall(
                    color: isSelected ? Colors.white : AppColors.dark,
                    weight: isSelected ? FontWeight.w700 : FontWeight.w600,
                  ),
                ),
                selected: isSelected,
                selectedColor: AppColors.primary,
                backgroundColor: AppColors.cardBg,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(20),
                  side: BorderSide(
                    color: isSelected ? AppColors.primary : AppColors.borderLight,
                    width: 1,
                  ),
                ),
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                onSelected: (_) => menu.setCategory(cat.id),
              );
            },
          ),
        );
      },
    );
  }
}

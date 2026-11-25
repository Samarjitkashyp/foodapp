import 'package:flutter/material.dart';

class CategoryItem {
  final String id;
  final String title;
  final IconData icon;
  final int displayOrder;

  const CategoryItem({
    required this.id,
    required this.title,
    required this.icon,
    this.displayOrder = 1,
  });

  factory CategoryItem.fromJson(Map<String, dynamic> json) {
    return CategoryItem(
      id: json['id'] as String? ?? '',
      title: json['name'] as String? ?? (json['title'] as String? ?? ''),
      icon: mapIconKey(json['icon'] as String? ?? ''),
      displayOrder: json['display_order'] is int
          ? json['display_order'] as int
          : int.tryParse(json['display_order']?.toString() ?? '1') ?? 1,
    );
  }

  static IconData mapIconKey(String iconKey) {
    switch (iconKey.toLowerCase().trim()) {
      case 'burger':
        return Icons.lunch_dining_rounded;
      case 'utensils':
      case 'chicken':
        return Icons.restaurant_rounded;
      case 'french-fries':
      case 'fries':
        return Icons.fastfood_rounded;
      case 'bowl-food':
      case 'wraps':
        return Icons.takeout_dining_rounded;
      case 'mug-hot':
      case 'drinks':
        return Icons.local_cafe_rounded;
      case 'cake-candles':
      case 'desserts':
        return Icons.icecream_rounded;
      case 'fire':
      case 'combos':
        return Icons.local_fire_department_rounded;
      case 'pizza-slice':
      case 'pizza':
        return Icons.local_pizza_rounded;
      case 'ice-cream':
        return Icons.icecream_outlined;
      case 'drumstick-bite':
        return Icons.kebab_dining_rounded;
      case 'tag':
        return Icons.local_offer_rounded;
      default:
        return Icons.restaurant_rounded;
    }
  }
}

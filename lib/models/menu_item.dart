class MenuItemAddon {
  final String name;
  final double price;

  const MenuItemAddon({required this.name, required this.price});

  factory MenuItemAddon.fromJson(Map<String, dynamic> json) {
    return MenuItemAddon(
      name: json['name']?.toString() ?? '',
      price: (json['price'] as num?)?.toDouble() ?? 0.0,
    );
  }

  Map<String, dynamic> toJson() => {'name': name, 'price': price};
}

class MenuItem {
  final String id;
  final String name;
  final String category;
  final double price;
  final double originalPrice;
  final double rating;
  final int reviewsCount;
  final bool isVeg;
  final String? badge;
  final int spicyLevel; // 1 to 3
  final String prepTime;
  final String calories;
  final String? portion;
  final List<MenuItemAddon> addons;
  final String image;
  final String description;
  final List<String> ingredients;

  MenuItem({
    required this.id,
    required this.name,
    required this.category,
    required this.price,
    required this.originalPrice,
    required this.rating,
    required this.reviewsCount,
    required this.isVeg,
    this.badge,
    required this.spicyLevel,
    required this.prepTime,
    required this.calories,
    this.portion,
    this.addons = const [],
    required this.image,
    required this.description,
    required this.ingredients,
  });

  factory MenuItem.fromJson(Map<String, dynamic> json) {
    final rawIsVeg = json['is_veg'] ?? json['isVeg'];
    final bool parsedIsVeg = rawIsVeg is bool
        ? rawIsVeg
        : (rawIsVeg == 1 || rawIsVeg == '1' || rawIsVeg == true);

    final rawOriginalPrice = json['original_price'] ?? json['originalPrice'];
    final double parsedOriginalPrice = rawOriginalPrice is num
        ? rawOriginalPrice.toDouble()
        : ((json['price'] as num?)?.toDouble() ?? 0.0);

    final rawReviewsCount = json['reviews_count'] ?? json['reviewsCount'];
    final int parsedReviewsCount = rawReviewsCount is num ? rawReviewsCount.toInt() : 120;

    final rawSpicy = json['spicy_level'] ?? json['spicyLevel'];
    final int parsedSpicy = rawSpicy is num ? rawSpicy.toInt() : 1;

    return MenuItem(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      category: json['category']?.toString() ?? 'chicken',
      price: (json['price'] as num?)?.toDouble() ?? 0.0,
      originalPrice: parsedOriginalPrice,
      rating: (json['rating'] as num?)?.toDouble() ?? 4.8,
      reviewsCount: parsedReviewsCount,
      isVeg: parsedIsVeg,
      badge: json['badge'] as String?,
      spicyLevel: parsedSpicy,
      prepTime: (json['prep_time'] ?? json['prepTime'])?.toString() ?? '15-20 min',
      portion: (json['portion'] ?? json['pieces'] ?? json['portion_size'])?.toString(),
      addons: (json['addons'] as List<dynamic>?)
              ?.map((a) => MenuItemAddon.fromJson(a as Map<String, dynamic>))
              .where((a) => a.name.trim().isNotEmpty)
              .toList() ??
          [],
      calories: json['calories']?.toString() ?? '450 kcal',
      image: json['image']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      ingredients: (json['ingredients'] as List<dynamic>?)
              ?.map((e) => e.toString())
              .toList() ??
          [],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'category': category,
      'price': price,
      'originalPrice': originalPrice,
      'rating': rating,
      'reviewsCount': reviewsCount,
      'isVeg': isVeg,
      'badge': badge,
      'spicyLevel': spicyLevel,
      'prepTime': prepTime,
      'portion': portion,
      'addons': addons.map((a) => a.toJson()).toList(),
      'calories': calories,
      'image': image,
      'description': description,
      'ingredients': ingredients,
    };
  }

  /// Returns clean Pieces / Portion display string (e.g. "8 Pcs", "4 Pcs", "1 Burger", "1 Handi")
  String get portionDisplay {
    if (portion != null && portion!.trim().isNotEmpty) {
      final p = portion!.trim();
      // If admin typed just a number like "4" or "8", format as "4 Pcs"
      if (RegExp(r'^\d+$').hasMatch(p)) {
        return '$p Pcs';
      }
      return p;
    }

    // 1. Check if name contains pattern like (8 pcs), (4 Pcs), (6 Pcs), (10 pcs)
    final match = RegExp(r'\((\d+\s*pcs?)\)', caseSensitive: false).firstMatch(name);
    if (match != null) {
      final text = match.group(1)!.trim();
      return text.replaceAll(RegExp(r'pcs?', caseSensitive: false), 'Pcs');
    }

    // 2. Also check description for (X pcs) or "X pcs"
    final descMatch = RegExp(r'(\d+)\s*pcs?\b', caseSensitive: false).firstMatch(description);
    if (descMatch != null) {
      return '${descMatch.group(1)} Pcs';
    }

    // 3. Smart category & dish name fallback
    final lowerName = name.toLowerCase();
    final lowerCat = category.toLowerCase();

    if (lowerName.contains('momo') || lowerCat.contains('momo')) return '8 Pcs';
    if (lowerName.contains('wing') || lowerName.contains('strips')) return '6 Pcs';
    if (lowerName.contains('nugget') || lowerName.contains('stick')) return '5 Pcs';
    if (lowerName.contains('churro')) return '4 Pcs';
    if (lowerName.contains('burger') || lowerCat.contains('burger')) return '1 Pc';
    if (lowerName.contains('biryani') || lowerCat.contains('biryani') || lowerName.contains('handi')) return '1 Handi';
    if (lowerName.contains('rice') || lowerName.contains('chowmein') || lowerName.contains('noodle')) return '1 Plate';
    if (lowerName.contains('fries') || lowerCat.contains('fries')) return '1 Plate';
    if (lowerName.contains('wrap') || lowerName.contains('roll') || lowerCat.contains('wrap')) return '1 Roll';
    if (lowerName.contains('shake') || lowerName.contains('drink') || lowerCat.contains('drink')) return '1 Glass';
    if (lowerName.contains('cake') || lowerName.contains('lava') || lowerName.contains('brownie') || lowerCat.contains('dessert')) return '1 Pc';
    if (lowerCat.contains('combo')) return '1 Combo';

    return '1 Portion';
  }
}

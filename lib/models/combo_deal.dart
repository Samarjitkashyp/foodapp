class ComboDeal {
  final String id;
  final String name;
  final String items;
  final double price;
  final double originalPrice;
  final String savings;
  final String badge;
  final String image;

  ComboDeal({
    required this.id,
    required this.name,
    required this.items,
    required this.price,
    required this.originalPrice,
    required this.savings,
    required this.badge,
    required this.image,
  });

  factory ComboDeal.fromJson(Map<String, dynamic> json) {
    return ComboDeal(
      id: json['id'] as String,
      name: json['name'] as String,
      items: json['items'] as String,
      price: (json['price'] as num).toDouble(),
      originalPrice: (json['originalPrice'] as num).toDouble(),
      savings: json['savings'] as String? ?? '',
      badge: json['badge'] as String? ?? 'Combo Deal',
      image: json['image'] as String,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'items': items,
      'price': price,
      'originalPrice': originalPrice,
      'savings': savings,
      'badge': badge,
      'image': image,
    };
  }
}

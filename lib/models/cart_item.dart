class CartItem {
  final String id;
  final String name;
  final double price;
  final String image;
  final bool isVeg;
  int quantity;
  final List<String> selectedAddons;
  final double addonsTotal;

  CartItem({
    required this.id,
    required this.name,
    required this.price,
    required this.image,
    required this.isVeg,
    this.quantity = 1,
    this.selectedAddons = const [],
    this.addonsTotal = 0.0,
  });

  double get unitPrice => price + addonsTotal;
  double get totalPrice => unitPrice * quantity;

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'price': price,
      'image': image,
      'isVeg': isVeg,
      'quantity': quantity,
      'selectedAddons': selectedAddons,
      'addonsTotal': addonsTotal,
    };
  }

  factory CartItem.fromJson(Map<String, dynamic> json) {
    return CartItem(
      id: json['id'] as String,
      name: json['name'] as String,
      price: (json['price'] as num).toDouble(),
      image: json['image'] as String,
      isVeg: json['isVeg'] as bool? ?? false,
      quantity: (json['quantity'] as num?)?.toInt() ?? 1,
      selectedAddons: (json['selectedAddons'] as List<dynamic>?)
              ?.map((e) => e.toString())
              .toList() ??
          [],
      addonsTotal: (json['addonsTotal'] as num?)?.toDouble() ?? 0.0,
    );
  }
}

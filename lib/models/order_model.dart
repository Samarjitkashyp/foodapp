class OrderItemSummary {
  final String id;
  final String name;
  final double price;
  final int quantity;
  final List<String> selectedAddons;
  final bool isVeg;

  const OrderItemSummary({
    required this.id,
    required this.name,
    required this.price,
    required this.quantity,
    this.selectedAddons = const [],
    this.isVeg = false,
  });

  factory OrderItemSummary.fromJson(Map<String, dynamic> json) {
    return OrderItemSummary(
      id: json['id'] as String? ?? '',
      name: json['name'] as String? ?? 'Item',
      price: (json['price'] as num?)?.toDouble() ?? 0.0,
      quantity: (json['quantity'] as num?)?.toInt() ?? 1,
      selectedAddons: (json['selected_addons'] as List<dynamic>?)
              ?.map((e) => e.toString())
              .toList() ??
          const [],
      isVeg: json['is_veg'] == true || json['is_veg'] == 1,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'price': price,
      'quantity': quantity,
      'selected_addons': selectedAddons,
      'is_veg': isVeg,
    };
  }
}

class OrderModel {
  final String orderNumber;
  final String customerName;
  final String customerPhone;
  final String deliveryAddress;
  final String landmark;
  final List<OrderItemSummary> items;
  final double subtotal;
  final double deliveryFee;
  final double taxAmount;
  final double discountAmount;
  final String couponCode;
  final double grandTotal;
  final String paymentMethod;
  final String paymentStatus;
  final String orderStatus;
  final String cookingInstructions;
  final DateTime createdAt;

  const OrderModel({
    required this.orderNumber,
    required this.customerName,
    required this.customerPhone,
    required this.deliveryAddress,
    this.landmark = '',
    required this.items,
    required this.subtotal,
    this.deliveryFee = 0.0,
    this.taxAmount = 0.0,
    this.discountAmount = 0.0,
    this.couponCode = '',
    required this.grandTotal,
    this.paymentMethod = 'COD',
    this.paymentStatus = 'pending',
    this.orderStatus = 'preparing',
    this.cookingInstructions = '',
    required this.createdAt,
  });

  factory OrderModel.fromJson(Map<String, dynamic> json) {
    var rawItems = json['items'];
    if (rawItems == null && json['items_json'] != null) {
      try {
        // May be pre-decoded or string
        rawItems = json['items_json'];
      } catch (_) {}
    }

    List<OrderItemSummary> parsedItems = [];
    if (rawItems is List) {
      parsedItems = rawItems
          .map((i) => OrderItemSummary.fromJson(i as Map<String, dynamic>))
          .toList();
    }

    DateTime dt;
    try {
      dt = DateTime.parse(json['created_at'] as String);
    } catch (_) {
      dt = DateTime.now();
    }

    return OrderModel(
      orderNumber: json['order_number'] as String? ?? 'CC-0000',
      customerName: json['customer_name'] as String? ?? '',
      customerPhone: json['customer_phone'] as String? ?? '',
      deliveryAddress: json['delivery_address'] as String? ?? '',
      landmark: json['landmark'] as String? ?? '',
      items: parsedItems,
      subtotal: (json['subtotal'] as num?)?.toDouble() ?? 0.0,
      deliveryFee: (json['delivery_fee'] as num?)?.toDouble() ?? 0.0,
      taxAmount: (json['tax_amount'] as num?)?.toDouble() ?? 0.0,
      discountAmount: (json['discount_amount'] as num?)?.toDouble() ?? 0.0,
      couponCode: json['coupon_code'] as String? ?? '',
      grandTotal: (json['grand_total'] as num?)?.toDouble() ?? 0.0,
      paymentMethod: json['payment_method'] as String? ?? 'COD',
      paymentStatus: json['payment_status'] as String? ?? 'pending',
      orderStatus: json['order_status'] as String? ?? 'preparing',
      cookingInstructions: json['cooking_instructions'] as String? ?? '',
      createdAt: dt,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'order_number': orderNumber,
      'customer_name': customerName,
      'customer_phone': customerPhone,
      'delivery_address': deliveryAddress,
      'landmark': landmark,
      'items': items.map((i) => i.toJson()).toList(),
      'subtotal': subtotal,
      'delivery_fee': deliveryFee,
      'tax_amount': taxAmount,
      'discount_amount': discountAmount,
      'coupon_code': couponCode,
      'grand_total': grandTotal,
      'payment_method': paymentMethod,
      'payment_status': paymentStatus,
      'order_status': orderStatus,
      'cooking_instructions': cookingInstructions,
      'created_at': createdAt.toIso8601String(),
    };
  }
}

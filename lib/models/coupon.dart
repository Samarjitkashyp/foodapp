class Coupon {
  final int id;
  final String code;
  final String title;
  final String description;
  final String discountType; // 'percentage' or 'flat'
  final double discountPercent; // e.g. 50 for 50%
  final double flatDiscount; // or flat off
  final double minOrder;
  final double maxDiscount;
  final int displayOrder;

  const Coupon({
    this.id = 0,
    required this.code,
    required this.title,
    required this.description,
    this.discountType = 'percentage',
    this.discountPercent = 0,
    this.flatDiscount = 0,
    required this.minOrder,
    this.maxDiscount = 0,
    this.displayOrder = 1,
  });

  factory Coupon.fromJson(Map<String, dynamic> json) {
    final type = (json['discount_type'] as String?)?.toLowerCase() ?? 'percentage';
    final val = (json['discount_value'] as num?)?.toDouble() ?? 0.0;
    final maxDisc = (json['max_discount'] as num?)?.toDouble() ?? 0.0;
    final minOrd = (json['min_order'] as num?)?.toDouble() ?? 0.0;
    final dispOrder = (json['display_order'] as num?)?.toInt() ?? 1;

    double pct = 0.0;
    double flat = 0.0;
    if (type == 'flat' || val == 0 && flat > 0) {
      flat = val;
    } else {
      pct = val;
    }

    return Coupon(
      id: (json['id'] as num?)?.toInt() ?? 0,
      code: (json['code'] as String? ?? '').trim().toUpperCase(),
      title: json['title'] as String? ?? '',
      description: json['description'] as String? ?? '',
      discountType: type,
      discountPercent: pct,
      flatDiscount: flat,
      minOrder: minOrd,
      maxDiscount: maxDisc,
      displayOrder: dispOrder,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'code': code,
      'title': title,
      'description': description,
      'discount_type': discountType,
      'discount_value': discountType == 'flat' ? flatDiscount : discountPercent,
      'max_discount': maxDiscount,
      'min_order': minOrder,
      'display_order': displayOrder,
    };
  }

  double calculateDiscount(double subtotal) {
    if (subtotal < minOrder) return 0;
    if (discountType == 'flat' && flatDiscount > 0) return flatDiscount;
    if (flatDiscount > 0 && discountPercent == 0) return flatDiscount;
    if (discountPercent > 0) {
      double d = (subtotal * discountPercent / 100);
      if (maxDiscount > 0 && d > maxDiscount) {
        return maxDiscount;
      }
      return d.roundToDouble();
    }
    return 0;
  }
}

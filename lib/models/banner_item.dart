class BannerItem {
  final int id;
  final String title;
  final String imageUrl;
  final String productId;
  final int displayOrder;

  const BannerItem({
    required this.id,
    required this.title,
    required this.imageUrl,
    required this.productId,
    this.displayOrder = 0,
  });

  factory BannerItem.fromJson(Map<String, dynamic> json) {
    return BannerItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: json['title'] as String? ?? '',
      imageUrl: json['image_url'] as String? ?? '',
      productId: json['product_id'] as String? ?? '',
      displayOrder: (json['display_order'] as num?)?.toInt() ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'image_url': imageUrl,
      'product_id': productId,
      'display_order': displayOrder,
    };
  }
}

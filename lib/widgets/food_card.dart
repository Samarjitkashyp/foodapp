import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';
import '../models/menu_item.dart';
import '../providers/cart_provider.dart';
import '../providers/favorites_provider.dart';
import '../screens/item_detail_screen.dart';
import 'app_network_image.dart';
import 'veg_badge.dart';

class FoodCard extends StatelessWidget {
  final MenuItem item;
  final bool isHorizontal;

  const FoodCard({
    super.key,
    required this.item,
    this.isHorizontal = false,
  });

  @override
  Widget build(BuildContext context) {
    if (isHorizontal) {
      return _buildHorizontalCard(context);
    }
    return _buildVerticalCard(context);
  }

  Widget _buildVerticalCard(BuildContext context) {
    return Consumer2<CartProvider, FavoritesProvider>(
      builder: (context, cart, favs, child) {
        final qty = cart.getItemQuantity(item.id);
        final isFav = favs.isFavorite(item.id);

        return GestureDetector(
          onTap: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) => ItemDetailScreen(item: item),
              ),
            );
          },
          child: Container(
            decoration: BoxDecoration(
              color: AppColors.cardBg,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppColors.borderLight, width: 1),
              boxShadow: AppStyles.cardShadow,
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                // Image Header with inner padding so image doesn't touch outer card border
                Padding(
                  padding: const EdgeInsets.fromLTRB(8, 8, 8, 0),
                  child: Stack(
                    children: [
                      ClipRRect(
                        borderRadius: BorderRadius.circular(12),
                        child: Container(
                          color: AppColors.primarySoft.withValues(alpha: 0.25),
                          child: AppNetworkImage(
                            imagePath: item.image,
                            height: 110,
                            width: double.infinity,
                            fit: BoxFit.cover,
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                      ),

                      // Veg / Non-Veg Icon
                      Positioned(
                        top: 6,
                        left: 6,
                        child: VegBadge(isVeg: item.isVeg, size: 14),
                      ),

                      // Badge (e.g. Bestseller, Royal Special)
                      if (item.badge != null)
                        Positioned(
                          top: 6,
                          right: 36,
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: AppColors.primary,
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text(
                              item.badge!,
                              style: AppStyles.bodySmall(
                                color: Colors.white,
                                weight: FontWeight.w700,
                              ).copyWith(fontSize: 10),
                            ),
                          ),
                        ),

                      // Favorite Button
                      Positioned(
                        top: 5,
                        right: 5,
                        child: GestureDetector(
                          onTap: () => favs.toggleFavorite(item.id),
                          child: Container(
                            padding: const EdgeInsets.all(5),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.9),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(
                              isFav ? Icons.favorite_rounded : Icons.favorite_border_rounded,
                              color: isFav ? AppColors.primary : AppColors.darkSubtle,
                              size: 15,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

                // Content (Tightly spaced, no extra empty gap)
                Padding(
                  padding: const EdgeInsets.fromLTRB(10, 6, 10, 10),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Rating (fire icon removed)
                      Row(
                        children: [
                          const Icon(Icons.star_rounded, color: AppColors.starRating, size: 14),
                          const SizedBox(width: 3),
                          Text(
                            item.rating.toStringAsFixed(1),
                            style: AppStyles.bodySmall(color: AppColors.dark, weight: FontWeight.w700),
                          ),
                          const SizedBox(width: 3),
                          Text(
                            '(${item.reviewsCount})',
                            style: AppStyles.bodySmall(color: AppColors.darkSubtle),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),

                      // Title
                      Text(
                        item.name,
                        style: AppStyles.headingSmall(color: AppColors.dark).copyWith(fontSize: 13),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 3),

                      // Pieces / Portion Size (Replaced time)
                      Row(
                        children: [
                          const Icon(
                            Icons.layers_outlined,
                            size: 13,
                            color: AppColors.primary,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            item.portionDisplay,
                            style: AppStyles.bodySmall(
                              color: AppColors.darkMuted,
                              weight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),

                      // Price & Add to Cart
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                '₹${item.price.toInt()}',
                                style: AppStyles.priceMain(fontSize: 15),
                              ),
                              if (item.originalPrice > item.price)
                                Text(
                                  '₹${item.originalPrice.toInt()}',
                                  style: AppStyles.priceOriginal(fontSize: 11),
                                ),
                            ],
                          ),
                          const Spacer(),

                          // Add Button / Counter
                          _buildActionButton(context, cart, qty),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildHorizontalCard(BuildContext context) {
    return Consumer<CartProvider>(
      builder: (context, cart, child) {
        final qty = cart.getItemQuantity(item.id);

        return GestureDetector(
          onTap: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) => ItemDetailScreen(item: item),
              ),
            );
          },
          child: Container(
            margin: const EdgeInsets.only(bottom: 12),
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppColors.cardBg,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppColors.borderLight),
              boxShadow: AppStyles.cardShadow,
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Left Image
                Stack(
                  children: [
                    AppNetworkImage(
                      imagePath: item.image,
                      width: 100,
                      height: 100,
                      fit: BoxFit.cover,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    Positioned(
                      top: 6,
                      left: 6,
                      child: VegBadge(isVeg: item.isVeg, size: 14),
                    ),
                  ],
                ),
                const SizedBox(width: 12),

                // Middle Info
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (item.badge != null)
                        Container(
                          margin: const EdgeInsets.only(bottom: 4),
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: AppColors.primarySoft,
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            item.badge!,
                            style: AppStyles.bodySmall(
                              color: AppColors.primary,
                              weight: FontWeight.w700,
                            ),
                          ),
                        ),
                      Text(
                        item.name,
                        style: AppStyles.headingSmall(),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 3),
                      Row(
                        children: [
                          const Icon(
                            Icons.layers_outlined,
                            size: 12,
                            color: AppColors.primary,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            item.portionDisplay,
                            style: AppStyles.bodySmall(
                              color: AppColors.darkMuted,
                              weight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 3),
                      Text(
                        item.description,
                        style: AppStyles.bodySmall(color: AppColors.darkMuted),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          Text(
                            '₹${item.price.toInt()}',
                            style: AppStyles.priceMain(fontSize: 16),
                          ),
                          const SizedBox(width: 6),
                          if (item.originalPrice > item.price)
                            Text(
                              '₹${item.originalPrice.toInt()}',
                              style: AppStyles.priceOriginal(fontSize: 12),
                            ),
                          const Spacer(),
                          _buildActionButton(context, cart, qty),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildActionButton(BuildContext context, CartProvider cart, int qty) {
    if (qty > 0) {
      return Container(
        height: 34,
        decoration: BoxDecoration(
          color: AppColors.primary,
          borderRadius: BorderRadius.circular(20),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            GestureDetector(
              onTap: () => cart.updateQuantity(item.id, qty - 1),
              child: const Padding(
                padding: EdgeInsets.symmetric(horizontal: 8),
                child: Icon(Icons.remove, color: Colors.white, size: 16),
              ),
            ),
            Text(
              '$qty',
              style: AppStyles.bodyMedium(
                color: Colors.white,
                weight: FontWeight.w800,
              ),
            ),
            GestureDetector(
              onTap: () => cart.updateQuantity(item.id, qty + 1),
              child: const Padding(
                padding: EdgeInsets.symmetric(horizontal: 8),
                child: Icon(Icons.add, color: Colors.white, size: 16),
              ),
            ),
          ],
        ),
      );
    }

    return ElevatedButton.icon(
      onPressed: () => cart.addItemFromMenu(item),
      icon: const Icon(Icons.add, size: 15),
      label: const Text('Add'),
      style: ElevatedButton.styleFrom(
        backgroundColor: Colors.white,
        foregroundColor: AppColors.primary,
        elevation: 0,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
        minimumSize: Size.zero,
        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
          side: const BorderSide(color: AppColors.primary, width: 1.5),
        ),
        textStyle: AppStyles.bodySmall(weight: FontWeight.w700),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';
import '../models/menu_item.dart';
import '../providers/cart_provider.dart';
import '../providers/favorites_provider.dart';
import '../widgets/app_network_image.dart';
import '../widgets/veg_badge.dart';

class ItemDetailScreen extends StatefulWidget {
  final MenuItem item;

  const ItemDetailScreen({super.key, required this.item});

  @override
  State<ItemDetailScreen> createState() => _ItemDetailScreenState();
}

class _ItemDetailScreenState extends State<ItemDetailScreen> {
  int _quantity = 1;

  final Map<String, int> _addonQuantities = {};

  void _incrementAddon(String name) {
    setState(() {
      _addonQuantities[name] = (_addonQuantities[name] ?? 0) + 1;
    });
  }

  void _decrementAddon(String name) {
    setState(() {
      final current = _addonQuantities[name] ?? 0;
      if (current > 1) {
        _addonQuantities[name] = current - 1;
      } else {
        _addonQuantities.remove(name);
      }
    });
  }

  double get _addonsCost {
    double sum = 0.0;
    for (final addon in widget.item.addons) {
      final qty = _addonQuantities[addon.name] ?? 0;
      if (qty > 0) {
        sum += addon.price * qty;
      }
    }
    return sum;
  }

  double get _totalPrice => (widget.item.price + _addonsCost) * _quantity;

  List<String> get _selectedAddonsList {
    final List<String> list = [];
    for (final addon in widget.item.addons) {
      final qty = _addonQuantities[addon.name] ?? 0;
      if (qty > 1) {
        list.add('${addon.name} (x$qty)');
      } else if (qty == 1) {
        list.add(addon.name);
      }
    }
    return list;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.pageBg,
      body: CustomScrollView(
        slivers: [
          // App Bar with Image
          SliverAppBar(
            expandedHeight: 280,
            pinned: true,
            backgroundColor: AppColors.cardBg,
            elevation: 0,
            leading: Padding(
              padding: const EdgeInsets.all(8.0),
              child: CircleAvatar(
                backgroundColor: Colors.white.withValues(alpha: 0.9),
                child: IconButton(
                  icon: const Icon(Icons.arrow_back_rounded, color: AppColors.dark),
                  onPressed: () => Navigator.pop(context),
                ),
              ),
            ),
            actions: [
              Consumer<FavoritesProvider>(
                builder: (context, favs, _) {
                  final isFav = favs.isFavorite(widget.item.id);
                  return Padding(
                    padding: const EdgeInsets.all(8.0),
                    child: CircleAvatar(
                      backgroundColor: Colors.white.withValues(alpha: 0.9),
                      child: IconButton(
                        icon: Icon(
                          isFav ? Icons.favorite_rounded : Icons.favorite_border_rounded,
                          color: isFav ? AppColors.primary : AppColors.darkSubtle,
                        ),
                        onPressed: () => favs.toggleFavorite(widget.item.id),
                      ),
                    ),
                  );
                },
              ),
            ],
            flexibleSpace: FlexibleSpaceBar(
              background: Stack(
                fit: StackFit.expand,
                children: [
                  AppNetworkImage(
                    imagePath: widget.item.image,
                    fit: BoxFit.cover,
                  ),
                  Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          Colors.black.withValues(alpha: 0.4),
                          Colors.transparent,
                          AppColors.pageBg.withValues(alpha: 0.8),
                        ],
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Body Content
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Badges Row
                  Row(
                    children: [
                      VegBadge(isVeg: widget.item.isVeg, size: 18),
                      const SizedBox(width: 8),
                      if (widget.item.badge != null)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: AppColors.primary,
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text(
                            widget.item.badge!,
                            style: AppStyles.bodySmall(
                              color: Colors.white,
                              weight: FontWeight.w700,
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // Name
                  Text(
                    widget.item.name,
                    style: AppStyles.headingLarge(),
                  ),
                  const SizedBox(height: 8),

                  // Ratings & Portion (Reviews text & kcal removed)
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppColors.accentLight,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.star_rounded, color: AppColors.accent, size: 18),
                            const SizedBox(width: 4),
                            Text(
                              widget.item.rating.toStringAsFixed(1),
                              style: AppStyles.bodySmall(color: AppColors.dark, weight: FontWeight.w800),
                            ),
                          ],
                        ),
                      ),
                      const Spacer(),
                      const Icon(Icons.layers_outlined, color: AppColors.primary, size: 16),
                      const SizedBox(width: 4),
                      Text(
                        widget.item.portionDisplay,
                        style: AppStyles.bodySmall(color: AppColors.dark, weight: FontWeight.w700),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  const Divider(color: AppColors.borderLight),
                  const SizedBox(height: 12),

                  // Description
                  Text(
                    'About This Dish',
                    style: AppStyles.headingSmall(),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    widget.item.description,
                    style: AppStyles.bodyMedium(height: 1.5),
                  ),
                  const SizedBox(height: 18),

                  // Ingredients
                  if (widget.item.ingredients.isNotEmpty) ...[
                    Text(
                      'Fresh Ingredients',
                      style: AppStyles.headingSmall(),
                    ),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: widget.item.ingredients.map((ing) {
                        return Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            color: AppColors.surfaceSubtle,
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(color: AppColors.borderLight),
                          ),
                          child: Text(
                            ing,
                            style: AppStyles.bodySmall(color: AppColors.dark, weight: FontWeight.w600),
                          ),
                        );
                      }).toList(),
                    ),
                    const SizedBox(height: 20),
                  ],

                  // Addons (Only shown if dynamic addons exist for this food item)
                  if (widget.item.addons.isNotEmpty) ...[
                    Text(
                      'Addons',
                      style: AppStyles.headingSmall(),
                    ),
                    const SizedBox(height: 8),
                    Container(
                      decoration: BoxDecoration(
                        color: AppColors.cardBg,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.borderLight),
                      ),
                      child: ListView.separated(
                        shrinkWrap: true,
                        padding: EdgeInsets.zero,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: widget.item.addons.length,
                        separatorBuilder: (context, index) =>
                            const Divider(height: 1, color: AppColors.borderLight),
                        itemBuilder: (context, index) {
                          final addon = widget.item.addons[index];
                          final qty = _addonQuantities[addon.name] ?? 0;
                          final isAdded = qty > 0;

                          return Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                            child: Row(
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        addon.name,
                                        style: AppStyles.bodyMedium(
                                          color: AppColors.dark,
                                          weight: FontWeight.w600,
                                        ),
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        '+₹${addon.price.toInt()}',
                                        style: AppStyles.priceMain(fontSize: 13),
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(width: 12),
                                if (isAdded)
                                  Container(
                                    height: 32,
                                    decoration: BoxDecoration(
                                      color: AppColors.primary,
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        InkWell(
                                          onTap: () => _decrementAddon(addon.name),
                                          borderRadius: const BorderRadius.horizontal(left: Radius.circular(8)),
                                          child: const Padding(
                                            padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                            child: Icon(Icons.remove, size: 16, color: Colors.white),
                                          ),
                                        ),
                                        Padding(
                                          padding: const EdgeInsets.symmetric(horizontal: 6),
                                          child: Text(
                                            '$qty',
                                            style: const TextStyle(
                                              color: Colors.white,
                                              fontWeight: FontWeight.w800,
                                              fontSize: 13,
                                            ),
                                          ),
                                        ),
                                        InkWell(
                                          onTap: () => _incrementAddon(addon.name),
                                          borderRadius: const BorderRadius.horizontal(right: Radius.circular(8)),
                                          child: const Padding(
                                            padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                            child: Icon(Icons.add, size: 16, color: Colors.white),
                                          ),
                                        ),
                                      ],
                                    ),
                                  )
                                else
                                  OutlinedButton.icon(
                                    onPressed: () => _incrementAddon(addon.name),
                                    icon: const Icon(Icons.add, size: 15, color: AppColors.primary),
                                    label: const Text(
                                      'Add',
                                      style: TextStyle(
                                        color: AppColors.primary,
                                        fontWeight: FontWeight.w700,
                                        fontSize: 12,
                                      ),
                                    ),
                                    style: OutlinedButton.styleFrom(
                                      side: const BorderSide(color: AppColors.primary, width: 1.2),
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                      minimumSize: Size.zero,
                                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                                    ),
                                  ),
                              ],
                            ),
                          );
                        },
                      ),
                    ),
                    const SizedBox(height: 20),
                  ],
                  const SizedBox(height: 80), // Space for bottom bar
                ],
              ),
            ),
          ),
        ],
      ),

      // Bottom Bar
      bottomNavigationBar: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
        decoration: BoxDecoration(
          color: AppColors.cardBg,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.08),
              blurRadius: 16,
              offset: const Offset(0, -4),
            ),
          ],
        ),
        child: SafeArea(
          child: Row(
            children: [
              // Quantity Selector
              Container(
                decoration: BoxDecoration(
                  color: AppColors.surfaceSubtle,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.borderLight),
                ),
                child: Row(
                  children: [
                    IconButton(
                      icon: const Icon(Icons.remove, size: 18),
                      onPressed: () {
                        if (_quantity > 1) {
                          setState(() => _quantity--);
                        }
                      },
                    ),
                    Text(
                      '$_quantity',
                      style: AppStyles.headingSmall(),
                    ),
                    IconButton(
                      icon: const Icon(Icons.add, size: 18),
                      onPressed: () {
                        setState(() => _quantity++);
                      },
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 16),

              // Add to Cart Button with total price
              Expanded(
                child: Consumer<CartProvider>(
                  builder: (context, cart, _) {
                    return ElevatedButton(
                      onPressed: () {
                        cart.addItemFromMenu(
                          widget.item,
                          qty: _quantity,
                          addons: _selectedAddonsList,
                          addonsCost: _addonsCost,
                        );

                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Row(
                              children: [
                                const Icon(Icons.check_circle_rounded, color: Colors.white),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: Text(
                                    'Added $_quantity x ${widget.item.name} to cart!',
                                    style: AppStyles.bodySmall(color: Colors.white, weight: FontWeight.w600),
                                  ),
                                ),
                              ],
                            ),
                            backgroundColor: AppColors.dark,
                            behavior: SnackBarBehavior.floating,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                            duration: const Duration(seconds: 2),
                          ),
                        );

                        Navigator.pop(context);
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        elevation: 0,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.shopping_bag_outlined, size: 20),
                          const SizedBox(width: 8),
                          Text(
                            'Add to Cart • ₹${_totalPrice.toInt()}',
                            style: AppStyles.bodyLarge(
                              color: Colors.white,
                              weight: FontWeight.w800,
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

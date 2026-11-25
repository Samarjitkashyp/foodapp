import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';
import '../models/menu_item.dart';
import '../providers/cart_provider.dart';
import '../providers/menu_provider.dart';
import '../widgets/app_network_image.dart';
import '../widgets/veg_badge.dart';
import 'checkout_screen.dart';
import 'main_navigation_screen.dart';

class CartScreen extends StatefulWidget {
  const CartScreen({super.key});

  @override
  State<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends State<CartScreen> {
  final TextEditingController _couponCtrl = TextEditingController();
  final TextEditingController _notesCtrl = TextEditingController();
  final ScrollController _scrollCtrl = ScrollController();

  final Set<String> _deliveryInstructions = {};
  bool _isNotesExpanded = false;

  final List<Map<String, dynamic>> _instructionOptions = const [
    {
      'id': 'no_call',
      'icon': Icons.phone_disabled_rounded,
      'label': 'Avoid calling',
    },
    {
      'id': 'door',
      'icon': Icons.door_front_door_rounded,
      'label': 'Leave at door',
    },
    {
      'id': 'no_bell',
      'icon': Icons.notifications_off_rounded,
      'label': "Don't ring bell",
    },
    {
      'id': 'cutlery',
      'icon': Icons.restaurant_rounded,
      'label': 'Add cutlery',
    },
  ];

  @override
  void dispose() {
    _couponCtrl.dispose();
    _notesCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  void _scrollToBill() {
    if (_scrollCtrl.hasClients) {
      _scrollCtrl.animateTo(
        _scrollCtrl.position.maxScrollExtent - 100,
        duration: const Duration(milliseconds: 400),
        curve: Curves.easeOutCubic,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.pageBg,
      appBar: AppBar(
        backgroundColor: AppColors.cardBg,
        elevation: 0,
        leading: Navigator.canPop(context)
            ? IconButton(
                icon: const Icon(Icons.arrow_back_rounded, color: AppColors.dark),
                onPressed: () => Navigator.pop(context),
              )
            : null,
        titleSpacing: Navigator.canPop(context) ? 0 : 16,
        title: Row(
          children: [
            Text('Your Crave Cart', style: AppStyles.headingMedium()),
            const SizedBox(width: 8),
            Consumer<CartProvider>(
              builder: (_, cart, _) {
                if (cart.items.isEmpty) return const SizedBox.shrink();
                return Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: AppColors.primarySoft,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '${cart.totalItemCount} items',
                    style: AppStyles.bodySmall(
                      color: AppColors.primary,
                      weight: FontWeight.w800,
                    ),
                  ),
                );
              },
            ),
          ],
        ),
        actions: [
          Consumer<CartProvider>(
            builder: (context, cart, _) {
              if (cart.items.isEmpty) return const SizedBox.shrink();
              return IconButton(
                icon: const Icon(Icons.delete_sweep_outlined, color: Colors.redAccent),
                tooltip: 'Clear Cart',
                onPressed: () => _confirmClearCart(context, cart),
              );
            },
          ),
        ],
      ),
      body: Consumer<CartProvider>(
        builder: (context, cart, child) {
          if (cart.items.isEmpty) {
            return _buildEmptyState(context);
          }

          final freeDeliveryThreshold = 399.0;
          final isFreeDelivery = cart.subtotal >= freeDeliveryThreshold;
          final remainingForFreeDelivery = (freeDeliveryThreshold - cart.subtotal).clamp(0.0, freeDeliveryThreshold);
          final progress = (cart.subtotal / freeDeliveryThreshold).clamp(0.0, 1.0);

          return ListView(
            controller: _scrollCtrl,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            children: [
              // 1. Restaurant & Fast Delivery Header Card
              _buildRestaurantHeaderCard(),
              const SizedBox(height: 14),

              // 2. Free Delivery Gamified Progress Bar
              _buildFreeDeliveryBar(isFreeDelivery, remainingForFreeDelivery, progress),
              const SizedBox(height: 14),

              // 3. Cart Food Items Card with Sleek Steppers
              _buildCartItemsCard(cart),
              const SizedBox(height: 14),

              // 4. "Complete Your Meal" (Smart Recommendations from Menu)
              _buildRecommendedCarousel(context, cart),
              const SizedBox(height: 14),

              // 5. Delivery Instructions / Preferences
              _buildDeliveryInstructionsCard(),
              const SizedBox(height: 14),

              // 6. Coupons & Offers with 1-Tap Quick Apply Chips
              _buildCouponSection(cart),
              const SizedBox(height: 14),

              // 7. Detailed Bill Summary with Total Savings Highlight
              _buildBillSummaryCard(cart, isFreeDelivery),
              const SizedBox(height: 14),

              // 8. Hygiene & Safety Assurance
              _buildSafetyPromiseCard(),
              const SizedBox(height: 90), // Space for bottom bar
            ],
          );
        },
      ),

      // Bottom Sticky Checkout Bar
      bottomNavigationBar: Consumer<CartProvider>(
        builder: (context, cart, _) {
          if (cart.items.isEmpty) return const SizedBox.shrink();
          return _buildBottomBar(context, cart);
        },
      ),
    );
  }

  // -------------------------------------------------------------
  // 1. Restaurant Header Card
  // -------------------------------------------------------------
  Widget _buildRestaurantHeaderCard() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.cardBg,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.borderLight),
        boxShadow: AppStyles.cardShadow,
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              gradient: AppColors.primaryGradient,
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Center(
              child: Icon(Icons.lunch_dining_rounded, color: Colors.white, size: 24),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text('Crisp & Crave Kitchen', style: AppStyles.headingSmall()),
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                      decoration: BoxDecoration(
                        color: AppColors.vegGreen.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        'OPEN',
                        style: TextStyle(
                          color: AppColors.vegGreen,
                          fontSize: 9,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 3),
                Row(
                  children: [
                    const Icon(Icons.flash_on_rounded, size: 14, color: AppColors.accent),
                    const SizedBox(width: 2),
                    Text(
                      '25-30 Mins • Delivering Hot & Fresh',
                      style: AppStyles.bodySmall(color: AppColors.darkMuted, weight: FontWeight.w600),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // -------------------------------------------------------------
  // 2. Free Delivery Gamified Bar
  // -------------------------------------------------------------
  Widget _buildFreeDeliveryBar(bool isFreeDelivery, double remaining, double progress) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isFreeDelivery ? const Color(0xFFF0FDF4) : const Color(0xFFFFF8F1),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isFreeDelivery ? const Color(0xFF86EFAC) : const Color(0xFFFFD8B8),
          width: 1.2,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: isFreeDelivery ? const Color(0xFFDCFCE7) : const Color(0xFFFFE6D5),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  isFreeDelivery ? Icons.check_circle_rounded : Icons.moped_rounded,
                  color: isFreeDelivery ? AppColors.vegGreen : AppColors.primary,
                  size: 18,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  isFreeDelivery
                      ? 'Woohoo! You have unlocked FREE Delivery! 🎉'
                      : 'Add ₹${remaining.toInt()} more for FREE Delivery',
                  style: AppStyles.bodySmall(
                    color: isFreeDelivery ? const Color(0xFF15803D) : AppColors.dark,
                    weight: FontWeight.w700,
                  ),
                ),
              ),
              if (isFreeDelivery)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: AppColors.vegGreen,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Text(
                    'SAVED ₹40',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 10,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 10),
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 6,
              backgroundColor: isFreeDelivery ? const Color(0xFFBBF7D0) : const Color(0xFFFFE4CE),
              valueColor: AlwaysStoppedAnimation<Color>(
                isFreeDelivery ? AppColors.vegGreen : AppColors.primary,
              ),
            ),
          ),
        ],
      ),
    );
  }

  // -------------------------------------------------------------
  // 3. Cart Items Card
  // -------------------------------------------------------------
  Widget _buildCartItemsCard(CartProvider cart) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.cardBg,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.borderLight),
        boxShadow: AppStyles.cardShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Card Header
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 10),
            child: Row(
              children: [
                const Icon(Icons.shopping_bag_outlined, color: AppColors.primary, size: 18),
                const SizedBox(width: 8),
                Text('Items in Order', style: AppStyles.headingSmall()),
                const Spacer(),
                Text(
                  '${cart.totalItemCount} items',
                  style: AppStyles.bodySmall(color: AppColors.darkMuted, weight: FontWeight.w600),
                ),
              ],
            ),
          ),
          const Divider(color: AppColors.borderLight, height: 1),

          // Items List
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: cart.items.length,
            separatorBuilder: (_, _) => const Divider(color: AppColors.borderLight, height: 1),
            itemBuilder: (context, index) {
              final item = cart.items[index];
              return Padding(
                padding: const EdgeInsets.all(14),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    // Veg/NonVeg Indicator
                    VegBadge(isVeg: item.isVeg, size: 15),
                    const SizedBox(width: 10),

                    // Thumbnail Image
                    ClipRRect(
                      borderRadius: BorderRadius.circular(10),
                      child: Container(
                        decoration: BoxDecoration(
                          border: Border.all(color: AppColors.borderLight),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: AppNetworkImage(
                          imagePath: item.image,
                          width: 58,
                          height: 58,
                          fit: BoxFit.cover,
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),

                    // Title & Price
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            item.name,
                            style: AppStyles.bodyLarge(weight: FontWeight.w700),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                          if (item.selectedAddons.isNotEmpty) ...[
                            const SizedBox(height: 3),
                            Text(
                              '+ ${item.selectedAddons.join(", ")}',
                              style: AppStyles.bodySmall(color: AppColors.darkSubtle),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                          const SizedBox(height: 4),
                          Row(
                            children: [
                              Text(
                                '₹${item.totalPrice.toInt()}',
                                style: AppStyles.priceMain(fontSize: 16),
                              ),
                              if (item.quantity > 1) ...[
                                const SizedBox(width: 6),
                                Text(
                                  '(₹${(item.totalPrice / item.quantity).toInt()} each)',
                                  style: AppStyles.bodySmall(color: AppColors.darkSubtle),
                                ),
                              ],
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),

                    // Modern Pill Stepper
                    Container(
                      height: 34,
                      decoration: BoxDecoration(
                        color: AppColors.surfaceSubtle,
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(color: AppColors.borderLight, width: 1.2),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          // Minus or Delete Icon
                          InkWell(
                            borderRadius: const BorderRadius.horizontal(left: Radius.circular(18)),
                            onTap: () => cart.updateQuantity(item.id, item.quantity - 1),
                            child: Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                              child: Icon(
                                item.quantity == 1 ? Icons.delete_outline_rounded : Icons.remove_rounded,
                                size: 16,
                                color: item.quantity == 1 ? Colors.redAccent : AppColors.dark,
                              ),
                            ),
                          ),
                          // Quantity Number
                          Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 4),
                            child: Text(
                              '${item.quantity}',
                              style: AppStyles.bodyMedium(
                                color: AppColors.dark,
                                weight: FontWeight.w800,
                              ),
                            ),
                          ),
                          // Plus Icon
                          InkWell(
                            borderRadius: const BorderRadius.horizontal(right: Radius.circular(18)),
                            onTap: () => cart.updateQuantity(item.id, item.quantity + 1),
                            child: const Padding(
                              padding: EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                              child: Icon(
                                Icons.add_rounded,
                                size: 16,
                                color: AppColors.primary,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              );
            },
          ),

          const Divider(color: AppColors.borderLight, height: 1),

          // Cooking Request / Special Notes
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            child: Column(
              children: [
                InkWell(
                  onTap: () => setState(() => _isNotesExpanded = !_isNotesExpanded),
                  borderRadius: BorderRadius.circular(8),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 4),
                    child: Row(
                      children: [
                        const Icon(Icons.edit_note_rounded, size: 20, color: AppColors.primary),
                        const SizedBox(width: 8),
                        Text(
                          _notesCtrl.text.isNotEmpty
                              ? 'Cooking Note: ${_notesCtrl.text}'
                              : 'Add cooking instructions / special requests',
                          style: AppStyles.bodySmall(
                            color: _notesCtrl.text.isNotEmpty ? AppColors.dark : AppColors.darkMuted,
                            weight: _notesCtrl.text.isNotEmpty ? FontWeight.w700 : FontWeight.w600,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const Spacer(),
                        Icon(
                          _isNotesExpanded ? Icons.keyboard_arrow_up_rounded : Icons.keyboard_arrow_down_rounded,
                          color: AppColors.darkSubtle,
                          size: 20,
                        ),
                      ],
                    ),
                  ),
                ),
                if (_isNotesExpanded) ...[
                  const SizedBox(height: 8),
                  TextField(
                    controller: _notesCtrl,
                    maxLines: 2,
                    style: AppStyles.bodySmall(color: AppColors.dark),
                    decoration: InputDecoration(
                      hintText: 'e.g. Less spicy, extra tissues, send mint chutney...',
                      hintStyle: AppStyles.bodySmall(color: AppColors.darkSubtle),
                      contentPadding: const EdgeInsets.all(12),
                      filled: true,
                      fillColor: AppColors.surfaceSubtle,
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                        borderSide: BorderSide.none,
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  // -------------------------------------------------------------
  // 4. "Complete Your Meal" (Recommendations)
  // -------------------------------------------------------------
  Widget _buildRecommendedCarousel(BuildContext context, CartProvider cart) {
    final menu = context.watch<MenuProvider>();
    final cartItemIds = cart.items.map((e) => e.id).toSet();

    // Suggest items from catalog that aren't already in cart
    final suggestions = menu.allItems.where((item) => !cartItemIds.contains(item.id)).take(6).toList();

    if (suggestions.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Icon(Icons.stars_rounded, color: AppColors.accent, size: 18),
            const SizedBox(width: 6),
            Text('Complete Your Feast', style: AppStyles.headingSmall()),
            const Spacer(),
            Text(
              'Pairs well',
              style: AppStyles.bodySmall(color: AppColors.primary, weight: FontWeight.w700),
            ),
          ],
        ),
        const SizedBox(height: 10),
        SizedBox(
          height: 148,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: suggestions.length,
            separatorBuilder: (_, _) => const SizedBox(width: 12),
            itemBuilder: (context, index) {
              final item = suggestions[index];
              return _buildRecommendationCard(context, item, cart);
            },
          ),
        ),
      ],
    );
  }

  Widget _buildRecommendationCard(BuildContext context, MenuItem item, CartProvider cart) {
    return Container(
      width: 140,
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: AppColors.cardBg,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.borderLight),
        boxShadow: AppStyles.cardShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Stack(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(10),
                child: AppNetworkImage(
                  imagePath: item.image,
                  width: double.infinity,
                  height: 64,
                  fit: BoxFit.cover,
                ),
              ),
              Positioned(
                top: 4,
                left: 4,
                child: Container(
                  padding: const EdgeInsets.all(2),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: VegBadge(isVeg: item.isVeg, size: 12),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            item.name,
            style: AppStyles.bodySmall(color: AppColors.dark, weight: FontWeight.w700),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const Spacer(),
          Row(
            children: [
              Text(
                '₹${item.price.toInt()}',
                style: AppStyles.bodySmall(color: AppColors.primary, weight: FontWeight.w800),
              ),
              const Spacer(),
              InkWell(
                onTap: () {
                  cart.addItemFromMenu(item);
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('Added ${item.name} to cart! 😋'),
                      duration: const Duration(seconds: 1),
                      behavior: SnackBarBehavior.floating,
                      backgroundColor: AppColors.vegGreen,
                    ),
                  );
                },
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppColors.primarySoft,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: AppColors.primaryLight),
                  ),
                  child: Text(
                    '+ ADD',
                    style: TextStyle(
                      color: AppColors.primary,
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // -------------------------------------------------------------
  // 5. Delivery Instructions Card
  // -------------------------------------------------------------
  Widget _buildDeliveryInstructionsCard() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.cardBg,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.borderLight),
        boxShadow: AppStyles.cardShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.delivery_dining_rounded, color: AppColors.primary, size: 18),
              const SizedBox(width: 8),
              Text('Delivery Instructions', style: AppStyles.headingSmall()),
            ],
          ),
          const SizedBox(height: 10),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: _instructionOptions.map((opt) {
                final id = opt['id'] as String;
                final isSelected = _deliveryInstructions.contains(id);
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: FilterChip(
                    avatar: Icon(
                      opt['icon'] as IconData,
                      size: 16,
                      color: isSelected ? AppColors.primary : AppColors.darkMuted,
                    ),
                    label: Text(
                      opt['label'] as String,
                      style: AppStyles.bodySmall(
                        color: isSelected ? AppColors.primary : AppColors.darkMuted,
                        weight: isSelected ? FontWeight.w800 : FontWeight.w600,
                      ),
                    ),
                    selected: isSelected,
                    onSelected: (val) {
                      setState(() {
                        if (val) {
                          _deliveryInstructions.add(id);
                        } else {
                          _deliveryInstructions.remove(id);
                        }
                      });
                    },
                    backgroundColor: AppColors.surfaceSubtle,
                    selectedColor: AppColors.primarySoft,
                    side: BorderSide(
                      color: isSelected ? AppColors.primary : AppColors.borderLight,
                    ),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                );
              }).toList(),
            ),
          ),
        ],
      ),
    );
  }

  // -------------------------------------------------------------
  // 6. Coupons & Offers Section (with 1-Tap Quick Apply)
  // -------------------------------------------------------------
  Widget _buildCouponSection(CartProvider cart) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.cardBg,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.borderLight),
        boxShadow: AppStyles.cardShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.local_offer_outlined, color: AppColors.primary, size: 18),
              const SizedBox(width: 8),
              Text('Coupons & Offers', style: AppStyles.headingSmall()),
            ],
          ),
          const SizedBox(height: 12),

          if (cart.activeCoupon != null)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              decoration: BoxDecoration(
                color: const Color(0xFFF0FDF4),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFF86EFAC)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.verified_rounded, color: AppColors.vegGreen, size: 20),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '${cart.activeCoupon!.code} Applied!',
                          style: AppStyles.bodyMedium(
                            color: const Color(0xFF15803D),
                            weight: FontWeight.w800,
                          ),
                        ),
                        Text(
                          'You saved ₹${cart.discountAmount.toInt()} on this order',
                          style: AppStyles.bodySmall(
                            color: const Color(0xFF166534),
                            weight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                  TextButton(
                    onPressed: () => cart.removeCoupon(),
                    style: TextButton.styleFrom(
                      foregroundColor: Colors.redAccent,
                      padding: const EdgeInsets.symmetric(horizontal: 8),
                    ),
                    child: const Text('Remove', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            )
          else ...[
            // Input field
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _couponCtrl,
                    textCapitalization: TextCapitalization.characters,
                    style: AppStyles.bodyMedium(color: AppColors.dark, weight: FontWeight.w700),
                    decoration: InputDecoration(
                      hintText: 'Enter coupon code (e.g. FIRST50)',
                      hintStyle: AppStyles.bodySmall(color: AppColors.darkSubtle),
                      isDense: true,
                      prefixIcon: const Icon(Icons.tag_rounded, size: 18, color: AppColors.darkSubtle),
                      filled: true,
                      fillColor: AppColors.surfaceSubtle,
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide.none,
                      ),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                ElevatedButton(
                  onPressed: () => _applyCoupon(cart, _couponCtrl.text),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
                    elevation: 0,
                  ),
                  child: const Text('Apply', style: TextStyle(fontWeight: FontWeight.w800)),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Quick 1-Tap Coupon Pills
            Text(
              'Available Offers (Tap to apply):',
              style: AppStyles.bodySmall(color: AppColors.darkMuted, weight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: cart.availableCoupons.map((coupon) {
                  final isApplicable = cart.subtotal >= coupon.minOrder;
                  return Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: InkWell(
                      onTap: () {
                        _couponCtrl.text = coupon.code;
                        _applyCoupon(cart, coupon.code);
                      },
                      borderRadius: BorderRadius.circular(10),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        decoration: BoxDecoration(
                          color: isApplicable ? AppColors.primarySoft : AppColors.surfaceSubtle,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: isApplicable ? AppColors.primaryLight : AppColors.borderLight,
                          ),
                        ),
                        child: Row(
                          children: [
                            Icon(
                              Icons.discount_outlined,
                              size: 14,
                              color: isApplicable ? AppColors.primary : AppColors.darkSubtle,
                            ),
                            const SizedBox(width: 6),
                            Text(
                              coupon.code,
                              style: TextStyle(
                                color: isApplicable ? AppColors.primaryDark : AppColors.darkMuted,
                                fontSize: 11,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                            const SizedBox(width: 4),
                            Text(
                              isApplicable ? '• Tap to Apply' : '• Min ₹${coupon.minOrder.toInt()}',
                              style: TextStyle(
                                color: isApplicable ? AppColors.primary : AppColors.darkSubtle,
                                fontSize: 10,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                }).toList(),
              ),
            ),
          ],
        ],
      ),
    );
  }

  void _applyCoupon(CartProvider cart, String code) {
    if (code.trim().isEmpty) return;
    final res = cart.applyCouponCode(code);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(res['message'] as String),
        backgroundColor: res['success'] == true ? AppColors.vegGreen : AppColors.dark,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  // -------------------------------------------------------------
  // 7. Detailed Bill Summary Card
  // -------------------------------------------------------------
  Widget _buildBillSummaryCard(CartProvider cart, bool isFreeDelivery) {
    final double deliverySavings = isFreeDelivery ? 40.0 : 0.0;
    final double totalSavings = deliverySavings + cart.discountAmount;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.cardBg,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.borderLight),
        boxShadow: AppStyles.cardShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.receipt_long_rounded, color: AppColors.primary, size: 18),
              const SizedBox(width: 8),
              Text('Bill Summary', style: AppStyles.headingSmall()),
            ],
          ),
          const SizedBox(height: 14),

          _billRow('Item Subtotal', '₹${cart.subtotal.toInt()}'),
          const SizedBox(height: 8),

          // Delivery Partner Fee
          Row(
            children: [
              Text('Delivery Partner Fee', style: AppStyles.bodyMedium(color: AppColors.darkMuted)),
              const Spacer(),
              if (isFreeDelivery) ...[
                Text(
                  '₹40',
                  style: AppStyles.bodyMedium(
                    color: AppColors.darkSubtle,
                    weight: FontWeight.w600,
                  ).copyWith(decoration: TextDecoration.lineThrough),
                ),
                const SizedBox(width: 6),
                Text(
                  'FREE',
                  style: AppStyles.bodyMedium(
                    color: AppColors.vegGreen,
                    weight: FontWeight.w800,
                  ),
                ),
              ] else ...[
                Text(
                  '₹${cart.deliveryFee.toInt()}',
                  style: AppStyles.bodyMedium(color: AppColors.dark, weight: FontWeight.w700),
                ),
              ],
            ],
          ),
          const SizedBox(height: 8),

          _billRow('Restaurant GST & Packaging (5%)', '₹${cart.taxAmount.toInt()}'),

          if (cart.discountAmount > 0) ...[
            const SizedBox(height: 8),
            _billRow(
              'Coupon Discount (${cart.activeCoupon!.code})',
              '-₹${cart.discountAmount.toInt()}',
              highlightColor: AppColors.vegGreen,
            ),
          ],

          const Padding(
            padding: EdgeInsets.symmetric(vertical: 12),
            child: Divider(color: AppColors.borderLight, height: 1),
          ),

          Row(
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Grand Total', style: AppStyles.headingMedium()),
                  Text(
                    'Inclusive of all applicable taxes',
                    style: AppStyles.bodySmall(color: AppColors.darkSubtle),
                  ),
                ],
              ),
              const Spacer(),
              Text(
                '₹${cart.grandTotal.toInt()}',
                style: AppStyles.priceMain(fontSize: 24),
              ),
            ],
          ),

          // Total Savings Pill
          if (totalSavings > 0) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: const Color(0xFFF0FDF4),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFF86EFAC)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.celebration_rounded, color: AppColors.vegGreen, size: 18),
                  const SizedBox(width: 8),
                  Text(
                    'Hurray! You saved ₹${totalSavings.toInt()} on this order!',
                    style: AppStyles.bodySmall(
                      color: const Color(0xFF15803D),
                      weight: FontWeight.w800,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _billRow(String label, String value, {Color? highlightColor}) {
    return Row(
      children: [
        Text(label, style: AppStyles.bodyMedium(color: AppColors.darkMuted)),
        const Spacer(),
        Text(
          value,
          style: AppStyles.bodyMedium(
            color: highlightColor ?? AppColors.dark,
            weight: FontWeight.w700,
          ),
        ),
      ],
    );
  }

  // -------------------------------------------------------------
  // 8. Safety & Hygiene Promise
  // -------------------------------------------------------------
  Widget _buildSafetyPromiseCard() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surfaceSubtle,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        children: [
          const Icon(Icons.verified_user_outlined, color: AppColors.darkMuted, size: 24),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '100% Safe & Contactless Delivery',
                  style: AppStyles.bodySmall(color: AppColors.dark, weight: FontWeight.w800),
                ),
                Text(
                  'Kitchen sanitized regularly • Freshly cooked & sealed with love',
                  style: AppStyles.bodySmall(color: AppColors.darkSubtle),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // -------------------------------------------------------------
  // 9. Sticky Bottom Checkout Bar
  // -------------------------------------------------------------
  Widget _buildBottomBar(BuildContext context, CartProvider cart) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: AppColors.cardBg,
        boxShadow: AppStyles.floatingShadow,
        border: const Border(top: BorderSide(color: AppColors.borderLight, width: 1)),
      ),
      child: SafeArea(
        child: Row(
          children: [
            InkWell(
              onTap: _scrollToBill,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Text(
                        'Total Payable',
                        style: AppStyles.bodySmall(color: AppColors.darkMuted, weight: FontWeight.w600),
                      ),
                      const SizedBox(width: 4),
                      const Icon(Icons.info_outline_rounded, size: 14, color: AppColors.darkSubtle),
                    ],
                  ),
                  Text(
                    '₹${cart.grandTotal.toInt()}',
                    style: AppStyles.priceMain(fontSize: 22),
                  ),
                ],
              ),
            ),
            const Spacer(),
            ElevatedButton(
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const CheckoutScreen()),
                );
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 26, vertical: 13),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                elevation: 4,
                shadowColor: AppColors.primary.withValues(alpha: 0.4),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    'Select Address',
                    style: AppStyles.bodyLarge(color: Colors.white, weight: FontWeight.w800),
                  ),
                  const SizedBox(width: 8),
                  const Icon(Icons.arrow_forward_rounded, size: 18),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // -------------------------------------------------------------
  // 10. Empty Cart State
  // -------------------------------------------------------------
  Widget _buildEmptyState(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 100,
              height: 100,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFFFFF0EB), Color(0xFFFFE3D8)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                shape: BoxShape.circle,
                boxShadow: [
                  BoxShadow(
                    color: AppColors.primary.withValues(alpha: 0.15),
                    blurRadius: 20,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: const Center(
                child: Icon(
                  Icons.shopping_bag_outlined,
                  size: 48,
                  color: AppColors.primary,
                ),
              ),
            ),
            const SizedBox(height: 24),
            Text(
              'Your Crave Cart is Empty!',
              style: AppStyles.headingLarge(color: AppColors.dark),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(
              'Good food is always the answer! Explore our artisanal smash burgers, dum biryani, and crispy momos.',
              textAlign: TextAlign.center,
              style: AppStyles.bodyMedium(color: AppColors.darkMuted),
            ),
            const SizedBox(height: 28),
            ElevatedButton.icon(
              onPressed: () {
                MainNavigationScreen.navigateToHome(context);
              },
              icon: const Icon(Icons.restaurant_menu_rounded, size: 20),
              label: Text(
                'Explore Delicious Menu',
                style: AppStyles.bodyLarge(color: Colors.white, weight: FontWeight.w700),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                elevation: 4,
                shadowColor: AppColors.primary.withValues(alpha: 0.35),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _confirmClearCart(BuildContext context, CartProvider cart) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Text('Clear Cart?', style: AppStyles.headingSmall()),
        content: Text(
          'Are you sure you want to remove all dishes from your cart?',
          style: AppStyles.bodyMedium(),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text('Cancel', style: AppStyles.bodyMedium(color: AppColors.darkMuted)),
          ),
          ElevatedButton(
            onPressed: () {
              cart.clearCart();
              Navigator.pop(ctx);
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.redAccent,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Clear All'),
          ),
        ],
      ),
    );
  }
}

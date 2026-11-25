import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';
import '../models/coupon.dart';
import '../providers/cart_provider.dart';
import '../providers/menu_provider.dart';
import '../widgets/app_top_bar.dart';
import '../widgets/offers_banner_slider.dart';
import '../widgets/shimmer_loading.dart';

class DealsScreen extends StatelessWidget {
  const DealsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.pageBg,
      body: SafeArea(
        child: Column(
          children: [
            // Consistent Brand Header (Same as HomeScreen)
            const AppTopBar(),

            // Main Content Area
            Expanded(
              child: Consumer2<MenuProvider, CartProvider>(
                builder: (context, menu, cart, child) {
                  if (menu.isLoading) {
                    return _buildDealsShimmer();
                  }

                  return RefreshIndicator(
                    color: AppColors.primary,
                    backgroundColor: Colors.white,
                    onRefresh: () async {
                      await menu.refreshBanners();
                    },
                    child: ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      children: [
                        // 1. Live Banner Slider (Always visible, Canva 2:1 slider)
                        if (menu.offers.isNotEmpty || menu.banners.isNotEmpty) ...[
                          OffersBannerSlider(allItems: menu.allItems),
                          const SizedBox(height: 18),
                        ],

                        // 2. Exclusive Promo Coupons Section (100% Dynamic from Admin)
                        Builder(
                          builder: (context) {
                            final dynamicCoupons = menu.coupons.isNotEmpty ? menu.coupons : cart.availableCoupons;
                            return Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Padding(
                                  padding: const EdgeInsets.symmetric(horizontal: 16),
                                  child: Row(
                                    children: [
                                      const Icon(Icons.confirmation_num_outlined, color: AppColors.primary, size: 20),
                                      const SizedBox(width: 8),
                                      Text('Exclusive Discount Coupons', style: AppStyles.headingSmall()),
                                      const Spacer(),
                                      Text(
                                        '${dynamicCoupons.length} Offers',
                                        style: AppStyles.bodySmall(color: AppColors.primary, weight: FontWeight.w700),
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(height: 12),

                                Padding(
                                  padding: const EdgeInsets.symmetric(horizontal: 16),
                                  child: Column(
                                    children: dynamicCoupons.map((coupon) {
                                      return _buildCouponCard(context, coupon, cart);
                                    }).toList(),
                                  ),
                                ),
                              ],
                            );
                          },
                        ),

                        // End of list indicator: No more coupons found for you
                        Padding(
                          padding: const EdgeInsets.fromLTRB(20, 24, 20, 12),
                          child: Column(
                            children: [
                              Row(
                                children: [
                                  const Expanded(
                                    child: Divider(color: AppColors.borderLight, thickness: 1),
                                  ),
                                  Padding(
                                    padding: const EdgeInsets.symmetric(horizontal: 12),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Icon(
                                          Icons.sentiment_satisfied_alt_rounded,
                                          size: 15,
                                          color: AppColors.darkSubtle.withValues(alpha: 0.8),
                                        ),
                                        const SizedBox(width: 6),
                                        const Text(
                                          'No more coupons found for you',
                                          style: TextStyle(
                                            fontSize: 12,
                                            fontWeight: FontWeight.w700,
                                            color: AppColors.darkSubtle,
                                            letterSpacing: 0.2,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  const Expanded(
                                    child: Divider(color: AppColors.borderLight, thickness: 1),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),
                              Text(
                                "Check back later for fresh discounts & offers!",
                                style: TextStyle(
                                  fontSize: 11,
                                  color: AppColors.darkSubtle.withValues(alpha: 0.7),
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                        ),

                        const SizedBox(height: 80), // Padding for sticky bottom bar
                      ],
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  // -------------------------------------------------------------
  // Ticket-Styled Discount Coupon Card
  // -------------------------------------------------------------
  Widget _buildCouponCard(BuildContext context, Coupon coupon, CartProvider cart) {
    final isApplied = cart.activeCoupon?.code == coupon.code;
    final isEligible = cart.subtotal >= coupon.minOrder;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: AppColors.cardBg,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isApplied ? AppColors.vegGreen : AppColors.borderLight,
          width: isApplied ? 1.5 : 1,
        ),
        boxShadow: AppStyles.cardShadow,
      ),
      child: Stack(
        children: [
          Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                // Coupon Badge Icon
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: isApplied ? const Color(0xFFDCFCE7) : AppColors.primarySoft,
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Center(
                    child: Icon(
                      isApplied ? Icons.verified_rounded : Icons.discount_outlined,
                      color: isApplied ? AppColors.vegGreen : AppColors.primary,
                      size: 24,
                    ),
                  ),
                ),
                const SizedBox(width: 14),

                // Details
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          GestureDetector(
                            onTap: () {
                              Clipboard.setData(ClipboardData(text: coupon.code));
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                  content: Text('Coupon ${coupon.code} copied!'),
                                  duration: const Duration(seconds: 1),
                                  behavior: SnackBarBehavior.floating,
                                ),
                              );
                            },
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: AppColors.surfaceSubtle,
                                borderRadius: BorderRadius.circular(6),
                                border: Border.all(color: AppColors.primary.withValues(alpha: 0.3)),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text(
                                    coupon.code,
                                    style: const TextStyle(
                                      color: AppColors.dark,
                                      fontSize: 11,
                                      fontWeight: FontWeight.w900,
                                      letterSpacing: 0.5,
                                    ),
                                  ),
                                  const SizedBox(width: 4),
                                  const Icon(Icons.copy_rounded, size: 11, color: AppColors.darkSubtle),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              coupon.title,
                              style: AppStyles.bodySmall(
                                color: AppColors.primaryDark,
                                weight: FontWeight.w700,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 5),
                      Text(
                        coupon.description,
                        style: AppStyles.bodySmall(color: AppColors.darkMuted, height: 1.3),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        'Min. order: ₹${coupon.minOrder.toInt()}',
                        style: AppStyles.bodySmall(
                          color: isEligible ? AppColors.vegGreen : AppColors.darkSubtle,
                          weight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 10),

                // Apply Action Button
                InkWell(
                  onTap: () {
                    if (isApplied) {
                      cart.removeCoupon();
                      return;
                    }
                    final res = cart.applyCouponCode(coupon.code);
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text(res['message'] as String),
                        backgroundColor: res['success'] == true ? AppColors.vegGreen : AppColors.dark,
                        behavior: SnackBarBehavior.floating,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                    );
                  },
                  borderRadius: BorderRadius.circular(20),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
                    decoration: BoxDecoration(
                      color: isApplied ? AppColors.vegGreen : AppColors.primary,
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [
                        BoxShadow(
                          color: (isApplied ? AppColors.vegGreen : AppColors.primary).withValues(alpha: 0.3),
                          blurRadius: 8,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Text(
                      isApplied ? 'Applied ✓' : 'Apply',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // -------------------------------------------------------------
  // Shimmer Skeleton for Deals
  // -------------------------------------------------------------
  Widget _buildDealsShimmer() {
    return ShimmerLoading(
      child: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        children: [
          AspectRatio(
            aspectRatio: 2.0,
            child: const ShimmerBox(borderRadius: 16),
          ),
          const SizedBox(height: 20),
          const ShimmerBox(height: 22, width: 180, borderRadius: 6),
          const SizedBox(height: 12),
          const ShimmerBox(height: 80, width: double.infinity, borderRadius: 16),
          const SizedBox(height: 12),
          const ShimmerBox(height: 80, width: double.infinity, borderRadius: 16),
          const SizedBox(height: 12),
          const ShimmerBox(height: 80, width: double.infinity, borderRadius: 16),
        ],
      ),
    );
  }
}

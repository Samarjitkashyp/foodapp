import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';
import '../providers/menu_provider.dart';
import '../widgets/app_top_bar.dart';
import '../widgets/category_chip_bar.dart';
import '../widgets/food_card.dart';
import '../widgets/hero_banner_slider.dart';
import '../widgets/offers_banner_slider.dart';
import '../widgets/shimmer_loading.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.pageBg,
      body: SafeArea(
        child: Consumer<MenuProvider>(
          builder: (context, menu, child) {
            if (menu.isLoading) {
              return const HomeShimmerView();
            }

            return Column(
              children: [
                // Consistent Brand Header
                const AppTopBar(),

                // Main Scrollable Body with Pull-to-Refresh
                Expanded(
                  child: RefreshIndicator(
                    color: AppColors.primary,
                    backgroundColor: Colors.white,
                    onRefresh: () async {
                      await menu.refreshBanners();
                    },
                    child: ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      children: [
                        // Hero Banner Slider
                        HeroBannerSlider(allItems: menu.allItems),
                        const SizedBox(height: 18),

                      // Categories Chips
                      const CategoryChipBar(),
                      const SizedBox(height: 14),

                      // Filtered Food Cards matching selected category / filter
                      if (menu.filteredItems.isEmpty)
                        Container(
                          height: 120,
                          margin: const EdgeInsets.symmetric(horizontal: 16),
                          alignment: Alignment.center,
                          decoration: BoxDecoration(
                            color: AppColors.cardBg,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: AppColors.borderLight),
                          ),
                          child: Text(
                            'No food items found in this category',
                            style: AppStyles.bodyMedium(color: AppColors.darkMuted),
                          ),
                        )
                      else
                        SizedBox(
                          height: 242,
                          child: ListView.separated(
                            padding: const EdgeInsets.symmetric(horizontal: 16),
                            scrollDirection: Axis.horizontal,
                            itemCount: menu.filteredItems.length,
                            separatorBuilder: (_, _) => const SizedBox(width: 14),
                            itemBuilder: (context, index) {
                              return SizedBox(
                                width: 175,
                                child: FoodCard(item: menu.filteredItems[index]),
                              );
                            },
                          ),
                        ),
                      const SizedBox(height: 18),

                      // Offers Banner Slider (Live from Admin Offers)
                      OffersBannerSlider(allItems: menu.allItems),
                      const SizedBox(height: 20),

                      // All Dynamic Dishes Grid (Live from Admin Catalog)
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        child: Row(
                          children: [
                            Text('All Dishes & Delights', style: AppStyles.headingMedium()),
                            const Spacer(),
                            Text(
                              '${menu.allItems.length} Available',
                              style: AppStyles.bodySmall(color: AppColors.primary, weight: FontWeight.w700),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 12),

                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        child: GridView.builder(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 2,
                            childAspectRatio: 0.70,
                            crossAxisSpacing: 12,
                            mainAxisSpacing: 12,
                          ),
                          itemCount: menu.allItems.length,
                          itemBuilder: (context, index) {
                            return FoodCard(item: menu.allItems[index]);
                          },
                        ),
                      ),
                      const SizedBox(height: 80),
                    ],
                  ),
                ),
              ),
              ],
            );
          },
        ),
      ),
    );
  }
}

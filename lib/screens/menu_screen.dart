import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';
import '../models/menu_item.dart';
import '../providers/menu_provider.dart';
import '../widgets/app_top_bar.dart';
import '../widgets/category_chip_bar.dart';
import '../widgets/food_card.dart';
import '../widgets/shimmer_loading.dart';

class MenuScreen extends StatefulWidget {
  const MenuScreen({super.key});

  @override
  State<MenuScreen> createState() => _MenuScreenState();
}

class _MenuScreenState extends State<MenuScreen> {
  final TextEditingController _searchCtrl = TextEditingController();
  bool _isGridView = true;
  bool _filterBestsellers = false;
  bool _filterTopRated = false;

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  List<MenuItem> _applyExtraFilters(List<MenuItem> items) {
    List<MenuItem> result = List.from(items);
    if (_filterBestsellers) {
      result = result.where((item) => item.badge != null && item.badge!.toLowerCase().contains('bestseller')).toList();
    }
    if (_filterTopRated) {
      result = result.where((item) => item.rating >= 4.8).toList();
    }
    return result;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.pageBg,
      body: SafeArea(
        child: Column(
          children: [
            // 1. Consistent Brand Header across the app
            AppTopBar(
              trailing: IconButton(
                icon: Icon(
                  _isGridView ? Icons.view_list_rounded : Icons.grid_view_rounded,
                  color: AppColors.dark,
                  size: 22,
                ),
                tooltip: _isGridView ? 'Switch to List View' : 'Switch to Grid View',
                onPressed: () => setState(() => _isGridView = !_isGridView),
              ),
            ),

            // 2. Main Content
            Expanded(
              child: Consumer<MenuProvider>(
                builder: (context, menu, child) {
                  if (menu.isLoading) {
                    return _buildMenuShimmer();
                  }

                  final baseItems = menu.filteredItems;
                  final displayItems = _applyExtraFilters(baseItems);

                  return RefreshIndicator(
                    color: AppColors.primary,
                    backgroundColor: Colors.white,
                    onRefresh: () async {
                      await menu.refreshBanners();
                    },
                    child: CustomScrollView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      slivers: [
                        // Search & Quick Filter Bar (Scrolls with list - Not sticky)
                        SliverToBoxAdapter(
                          child: Container(
                            color: AppColors.cardBg,
                            padding: const EdgeInsets.fromLTRB(16, 8, 16, 6),
                            child: Column(
                              children: [
                                // Search input
                                Container(
                                  height: 44,
                                  decoration: BoxDecoration(
                                    color: AppColors.surfaceSubtle,
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(color: AppColors.borderLight),
                                  ),
                                  child: TextField(
                                    controller: _searchCtrl,
                                    onChanged: (val) => menu.setSearchQuery(val),
                                    style: AppStyles.bodyMedium(color: AppColors.dark),
                                    decoration: InputDecoration(
                                      hintText: 'Search burgers, biryani, momos, shakes...',
                                      hintStyle: AppStyles.bodySmall(color: AppColors.darkSubtle),
                                      prefixIcon: const Icon(
                                        Icons.search_rounded,
                                        color: AppColors.darkMuted,
                                        size: 20,
                                      ),
                                      suffixIcon: _searchCtrl.text.isNotEmpty
                                          ? IconButton(
                                              icon: const Icon(Icons.clear_rounded, size: 16, color: AppColors.darkMuted),
                                              onPressed: () {
                                                _searchCtrl.clear();
                                                menu.setSearchQuery('');
                                              },
                                            )
                                          : null,
                                      border: InputBorder.none,
                                      contentPadding: const EdgeInsets.symmetric(vertical: 10),
                                    ),
                                  ),
                                ),
                                const SizedBox(height: 8),

                                // Quick Filter Chips Row
                                SingleChildScrollView(
                                  scrollDirection: Axis.horizontal,
                                  child: Row(
                                    children: [
                                      // Veg Only Filter
                                      _buildFilterPill(
                                        label: 'Pure Veg',
                                        isSelected: menu.vegOnly,
                                        activeColor: AppColors.vegGreen,
                                        leadingIcon: Icons.eco_rounded,
                                        onTap: () => menu.toggleVegOnly(),
                                      ),
                                      const SizedBox(width: 8),

                                      // Top Rated (4.8+) Filter
                                      _buildFilterPill(
                                        label: 'Top Rated ⭐',
                                        isSelected: _filterTopRated,
                                        activeColor: AppColors.accent,
                                        onTap: () => setState(() => _filterTopRated = !_filterTopRated),
                                      ),
                                      const SizedBox(width: 8),

                                      // Bestsellers Filter
                                      _buildFilterPill(
                                        label: 'Bestsellers 🔥',
                                        isSelected: _filterBestsellers,
                                        activeColor: AppColors.primary,
                                        onTap: () => setState(() => _filterBestsellers = !_filterBestsellers),
                                      ),
                                      const SizedBox(width: 8),

                                      // Sort Selector
                                      PopupMenuButton<SortOption>(
                                        initialValue: menu.sortOption,
                                        onSelected: (option) => menu.setSortOption(option),
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                                        child: Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                          decoration: BoxDecoration(
                                            color: AppColors.surfaceSubtle,
                                            borderRadius: BorderRadius.circular(20),
                                            border: Border.all(color: AppColors.borderLight),
                                          ),
                                          child: Row(
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              const Icon(Icons.sort_rounded, size: 14, color: AppColors.primary),
                                              const SizedBox(width: 4),
                                              Text(
                                                _getSortLabel(menu.sortOption),
                                                style: AppStyles.bodySmall(
                                                  color: AppColors.dark,
                                                  weight: FontWeight.w700,
                                                ),
                                              ),
                                              const Icon(Icons.arrow_drop_down_rounded, size: 18, color: AppColors.darkMuted),
                                            ],
                                          ),
                                        ),
                                        itemBuilder: (context) => [
                                          const PopupMenuItem(
                                            value: SortOption.popular,
                                            child: Text('Most Popular'),
                                          ),
                                          const PopupMenuItem(
                                            value: SortOption.rating,
                                            child: Text('Highest Rated'),
                                          ),
                                          const PopupMenuItem(
                                            value: SortOption.priceLowToHigh,
                                            child: Text('Price: Low to High'),
                                          ),
                                          const PopupMenuItem(
                                            value: SortOption.priceHighToLow,
                                            child: Text('Price: High to Low'),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),

                        // Categories Chips Bar (Scrolls with list - Not sticky)
                        SliverToBoxAdapter(
                          child: Container(
                            color: AppColors.cardBg,
                            padding: const EdgeInsets.only(bottom: 10),
                            child: const CategoryChipBar(),
                          ),
                        ),

                        // Count & Active Info Bar (Scrolls with list - Not sticky)
                        SliverToBoxAdapter(
                          child: Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                            child: Row(
                              children: [
                                Text(
                                  '${displayItems.length} ${displayItems.length == 1 ? "dish" : "dishes"} available',
                                  style: AppStyles.bodySmall(
                                    color: AppColors.darkMuted,
                                    weight: FontWeight.w700,
                                  ),
                                ),
                                const Spacer(),
                                if (_filterTopRated || _filterBestsellers || menu.vegOnly || _searchCtrl.text.isNotEmpty)
                                  GestureDetector(
                                    onTap: () {
                                      setState(() {
                                        _filterBestsellers = false;
                                        _filterTopRated = false;
                                      });
                                      _searchCtrl.clear();
                                      menu.setSearchQuery('');
                                      if (menu.vegOnly) menu.toggleVegOnly();
                                    },
                                    child: Text(
                                      'Clear Filters',
                                      style: AppStyles.bodySmall(
                                        color: Colors.redAccent,
                                        weight: FontWeight.w700,
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                          ),
                        ),

                        // Food Items View (Grid or List)
                        if (displayItems.isEmpty)
                          SliverFillRemaining(
                            hasScrollBody: false,
                            child: _buildEmptyState(menu),
                          )
                        else if (_isGridView)
                          SliverPadding(
                            padding: const EdgeInsets.fromLTRB(16, 4, 16, 80),
                            sliver: SliverGrid(
                              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                                crossAxisCount: 2,
                                childAspectRatio: 0.70,
                                crossAxisSpacing: 12,
                                mainAxisSpacing: 12,
                              ),
                              delegate: SliverChildBuilderDelegate(
                                (context, index) {
                                  return FoodCard(item: displayItems[index]);
                                },
                                childCount: displayItems.length,
                              ),
                            ),
                          )
                        else
                          SliverPadding(
                            padding: const EdgeInsets.fromLTRB(16, 4, 16, 80),
                            sliver: SliverList(
                              delegate: SliverChildBuilderDelegate(
                                (context, index) {
                                  return Padding(
                                    padding: const EdgeInsets.only(bottom: 12),
                                    child: FoodCard(item: displayItems[index], isHorizontal: true),
                                  );
                                },
                                childCount: displayItems.length,
                              ),
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
    );
  }

  Widget _buildFilterPill({
    required String label,
    required bool isSelected,
    required Color activeColor,
    IconData? leadingIcon,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? activeColor.withValues(alpha: 0.12) : AppColors.surfaceSubtle,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? activeColor : AppColors.borderLight,
            width: isSelected ? 1.5 : 1,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (leadingIcon != null) ...[
              Icon(leadingIcon, size: 13, color: isSelected ? activeColor : AppColors.darkMuted),
              const SizedBox(width: 4),
            ],
            Text(
              label,
              style: TextStyle(
                color: isSelected ? activeColor : AppColors.dark,
                fontSize: 11,
                fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState(MenuProvider menu) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 72,
              height: 72,
              decoration: const BoxDecoration(
                color: AppColors.primarySoft,
                shape: BoxShape.circle,
              ),
              child: const Center(
                child: Icon(Icons.lunch_dining_rounded, size: 36, color: AppColors.primary),
              ),
            ),
            const SizedBox(height: 16),
            Text(
              'No dishes found',
              style: AppStyles.headingMedium(color: AppColors.dark),
            ),
            const SizedBox(height: 6),
            Text(
              'We could not find any culinary items matching your selected filters.',
              textAlign: TextAlign.center,
              style: AppStyles.bodyMedium(color: AppColors.darkMuted),
            ),
            const SizedBox(height: 18),
            ElevatedButton(
              onPressed: () {
                setState(() {
                  _filterBestsellers = false;
                  _filterTopRated = false;
                });
                _searchCtrl.clear();
                menu.setSearchQuery('');
                menu.setCategory('all');
                if (menu.vegOnly) menu.toggleVegOnly();
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 11),
                elevation: 0,
              ),
              child: const Text('Reset All Filters', style: TextStyle(fontWeight: FontWeight.w700)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMenuShimmer() {
    return ShimmerLoading(
      child: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        children: [
          const ShimmerBox(height: 44, width: double.infinity, borderRadius: 12),
          const SizedBox(height: 12),
          SizedBox(
            height: 38,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: 5,
              separatorBuilder: (_, _) => const SizedBox(width: 8),
              itemBuilder: (_, _) => const ShimmerBox(width: 90, height: 38, borderRadius: 18),
            ),
          ),
          const SizedBox(height: 16),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              childAspectRatio: 0.70,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
            ),
            itemCount: 4,
            itemBuilder: (_, _) => Container(
              decoration: BoxDecoration(
                color: AppColors.cardBg,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.borderLight),
              ),
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: const [
                  ShimmerBox(height: 110, width: double.infinity, borderRadius: 12),
                  SizedBox(height: 12),
                  ShimmerBox(height: 14, width: 110, borderRadius: 4),
                  SizedBox(height: 8),
                  ShimmerBox(height: 12, width: 70, borderRadius: 4),
                  Spacer(),
                  ShimmerBox(height: 18, width: 60, borderRadius: 4),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _getSortLabel(SortOption option) {
    switch (option) {
      case SortOption.rating:
        return 'Top Rated';
      case SortOption.priceLowToHigh:
        return 'Price: Low-High';
      case SortOption.priceHighToLow:
        return 'Price: High-Low';
      case SortOption.popular:
        return 'Popular';
    }
  }
}

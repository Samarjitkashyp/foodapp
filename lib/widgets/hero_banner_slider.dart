import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';
import '../models/menu_item.dart';
import '../models/banner_item.dart';
import '../providers/menu_provider.dart';
import '../screens/item_detail_screen.dart';
import 'app_network_image.dart';

class HeroBannerSlider extends StatefulWidget {
  final List<MenuItem> allItems;

  const HeroBannerSlider({super.key, required this.allItems});

  @override
  State<HeroBannerSlider> createState() => _HeroBannerSliderState();
}

class _HeroBannerSliderState extends State<HeroBannerSlider> {
  final PageController _pageController = PageController();
  int _currentPage = 0;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _startTimer();
  }

  void _startTimer() {
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 4), (timer) {
      if (_pageController.hasClients) {
        final menu = context.read<MenuProvider>();
        final count = menu.banners.length;
        if (count > 0) {
          final nextPage = (_currentPage + 1) % count;
          _pageController.animateToPage(
            nextPage,
            duration: const Duration(milliseconds: 500),
            curve: Curves.easeInOutCubic,
          );
        }
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _pageController.dispose();
    super.dispose();
  }

  void _onBannerTapped(BannerItem banner) {
    MenuItem? targetItem;
    try {
      targetItem = widget.allItems.firstWhere((i) => i.id == banner.productId);
    } catch (_) {
      if (widget.allItems.isNotEmpty) {
        targetItem = widget.allItems.first;
      }
    }

    if (targetItem != null) {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => ItemDetailScreen(item: targetItem!),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<MenuProvider>(
      builder: (context, menu, child) {
        final banners = menu.banners;
        if (banners.isEmpty) return const SizedBox.shrink();

        return Column(
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: AspectRatio(
                aspectRatio: 2.0, // 2:1 exact aspect ratio for 1200x600 Canva banners
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(16),
                  child: PageView.builder(
                    controller: _pageController,
                    onPageChanged: (index) {
                      setState(() => _currentPage = index);
                    },
                    itemCount: banners.length,
                    itemBuilder: (context, index) {
                      final banner = banners[index];
                      return _buildCanvaBannerCard(banner);
                    },
                  ),
                ),
              ),
            ),
            const SizedBox(height: 8),

            // Pagination Dots Indicator
            if (banners.length > 1)
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(
                  banners.length,
                  (index) => AnimatedContainer(
                    duration: const Duration(milliseconds: 300),
                    margin: const EdgeInsets.symmetric(horizontal: 3),
                    width: _currentPage == index ? 22 : 6,
                    height: 5,
                    decoration: BoxDecoration(
                      color: _currentPage == index
                          ? AppColors.primary
                          : AppColors.darkSubtle.withValues(alpha: 0.3),
                      borderRadius: BorderRadius.circular(3),
                    ),
                  ),
                ),
              ),
          ],
        );
      },
    );
  }

  Widget _buildCanvaBannerCard(BannerItem banner) {
    return GestureDetector(
      onTap: () => _onBannerTapped(banner),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          boxShadow: AppStyles.cardShadow,
          border: Border.all(
            color: AppColors.borderLight,
            width: 1,
          ),
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(16),
          child: AppNetworkImage(
            imagePath: banner.imageUrl,
            fit: BoxFit.cover,
            width: double.infinity,
            height: double.infinity,
          ),
        ),
      ),
    );
  }
}

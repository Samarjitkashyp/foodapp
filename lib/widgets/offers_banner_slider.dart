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

class OffersBannerSlider extends StatefulWidget {
  final List<MenuItem> allItems;

  const OffersBannerSlider({super.key, required this.allItems});

  @override
  State<OffersBannerSlider> createState() => _OffersBannerSliderState();
}

class _OffersBannerSliderState extends State<OffersBannerSlider> {
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
        final count = menu.offers.length;
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

  void _onOfferTapped(BannerItem offer) {
    if (offer.productId.isEmpty) return;

    MenuItem? targetItem;
    try {
      targetItem = widget.allItems.firstWhere((i) => i.id == offer.productId);
    } catch (_) {
      // If combo deal or not in standard menu, handled gracefully
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
        final offers = menu.offers.isNotEmpty ? menu.offers : menu.banners;
        if (offers.isEmpty) return const SizedBox.shrink();

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
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
                    itemCount: offers.length,
                    itemBuilder: (context, index) {
                      final offer = offers[index];
                      return _buildOfferCard(offer);
                    },
                  ),
                ),
              ),
            ),
            const SizedBox(height: 8),

            // Pagination Dots Indicator
            if (offers.length > 1)
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(
                  offers.length,
                  (index) => AnimatedContainer(
                    duration: const Duration(milliseconds: 300),
                    margin: const EdgeInsets.symmetric(horizontal: 3),
                    width: _currentPage == index ? 20 : 6,
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

  Widget _buildOfferCard(BannerItem offer) {
    return GestureDetector(
      onTap: () => _onOfferTapped(offer),
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
            imagePath: offer.imageUrl,
            fit: BoxFit.cover,
            width: double.infinity,
            height: double.infinity,
          ),
        ),
      ),
    );
  }
}

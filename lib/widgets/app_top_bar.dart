import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';
import '../providers/cart_provider.dart';
import '../screens/cart_screen.dart';
import 'announcement_bar.dart';

class AppTopBar extends StatelessWidget {
  final Widget? trailing;

  const AppTopBar({super.key, this.trailing});

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        // Announcement Bar
        const AnnouncementBar(),

        // Branded Navbar Row
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
          color: AppColors.cardBg,
          child: Row(
            children: [
              // Brand Logo
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: AppColors.primary,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(
                  Icons.lunch_dining_rounded,
                  color: Colors.white,
                  size: 20,
                ),
              ),
              const SizedBox(width: 10),

              // Crisp & Crave Brand Title
              RichText(
                text: TextSpan(
                  children: [
                    TextSpan(
                      text: 'Crisp',
                      style: AppStyles.headingMedium(color: AppColors.dark),
                    ),
                    TextSpan(
                      text: '&',
                      style: AppStyles.headingMedium(color: AppColors.primary),
                    ),
                    TextSpan(
                      text: 'Crave',
                      style: AppStyles.headingMedium(color: AppColors.dark),
                    ),
                  ],
                ),
              ),
              const Spacer(),

              if (trailing != null) ...[
                trailing!,
                const SizedBox(width: 4),
              ],

              // Cart Icon with Badge Counter
              Consumer<CartProvider>(
                builder: (context, cart, _) {
                  return Stack(
                    alignment: Alignment.topRight,
                    children: [
                      IconButton(
                        icon: const Icon(Icons.shopping_bag_outlined, color: AppColors.dark),
                        tooltip: 'Your Cart',
                        onPressed: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(builder: (_) => const CartScreen()),
                          );
                        },
                      ),
                      if (cart.totalItemCount > 0)
                        Positioned(
                          right: 6,
                          top: 6,
                          child: Container(
                            padding: const EdgeInsets.all(4),
                            decoration: const BoxDecoration(
                              color: AppColors.primary,
                              shape: BoxShape.circle,
                            ),
                            constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                            child: Center(
                              child: Text(
                                '${cart.totalItemCount}',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          ),
                        ),
                    ],
                  );
                },
              ),
            ],
          ),
        ),
      ],
    );
  }
}

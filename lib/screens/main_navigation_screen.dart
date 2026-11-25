import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';
import '../providers/cart_provider.dart';
import '../widgets/sticky_cart_bar.dart';
import 'cravebot_screen.dart';
import 'deals_screen.dart';
import 'home_screen.dart';
import 'menu_screen.dart';
import 'cart_screen.dart';
import 'profile_screen.dart';

class MainNavigationScreen extends StatefulWidget {
  const MainNavigationScreen({super.key});

  static final GlobalKey<MainNavigationScreenState> navKey =
      GlobalKey<MainNavigationScreenState>();

  static void navigateToHome(BuildContext context) {
    if (Navigator.canPop(context)) {
      Navigator.popUntil(context, (route) => route.isFirst);
    }
    navKey.currentState?.setTab(0);
  }

  static void switchTab(int index) {
    navKey.currentState?.setTab(index);
  }

  @override
  State<MainNavigationScreen> createState() => MainNavigationScreenState();
}

class MainNavigationScreenState extends State<MainNavigationScreen> {
  int _currentIndex = 0;

  void setTab(int index) {
    if (mounted) {
      setState(() {
        _currentIndex = index;
      });
    }
  }

  final List<Widget> _screens = const [
    HomeScreen(),
    DealsScreen(),
    MenuScreen(),
    CartScreen(),
    ProfileScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(
        index: _currentIndex,
        children: _screens,
      ),

      // CraveBot AI Floating Action Button (Website Style: Circular Gradient + Sparkle Icon + "AI" Badge)
      // Hidden on Deals Screen (_currentIndex == 1) and Cart Screen (_currentIndex == 3)
      floatingActionButton: (_currentIndex == 1 || _currentIndex == 3)
          ? null
          : Padding(
        padding: const EdgeInsets.only(bottom: 4),
        child: SizedBox(
          width: 58,
          height: 58,
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              FloatingActionButton(
                heroTag: 'cravebot_fab',
                onPressed: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const CravebotScreen()),
                  );
                },
                elevation: 0,
                focusElevation: 0,
                hoverElevation: 0,
                highlightElevation: 0,
                shape: const CircleBorder(),
                backgroundColor: Colors.transparent,
                child: Container(
                  width: 56,
                  height: 56,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: const LinearGradient(
                      colors: [
                        Color(0xFFFF5722),
                        Color(0xFFFF784E),
                        Color(0xFF8B5CF6),
                      ],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFFFF5722).withValues(alpha: 0.45),
                        blurRadius: 16,
                        offset: const Offset(0, 6),
                      ),
                      BoxShadow(
                        color: const Color(0xFF8B5CF6).withValues(alpha: 0.35),
                        blurRadius: 10,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: const Center(
                    child: Icon(
                      Icons.auto_awesome_rounded,
                      color: Colors.white,
                      size: 26,
                    ),
                  ),
                ),
              ),

              // Green "AI" Badge on top-right (matching website style)
              Positioned(
                top: -3,
                right: -3,
                child: IgnorePointer(
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: const Color(0xFF10B981),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: Colors.white, width: 2),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.25),
                          blurRadius: 4,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: const Text(
                      'AI',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 9,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 0.5,
                        height: 1.0,
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),

      // Bottom Navigation Bar with Centralized Animated Sticky Cart Strip
      bottomNavigationBar: Consumer<CartProvider>(
        builder: (context, cart, child) {
          final showStickyCart = cart.totalItemCount > 0 && !cart.isStickyBarDismissed && _currentIndex != 3;

          return Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Smoothly animated sticky cart bar (auto-elevates FAB)
              AnimatedSize(
                duration: const Duration(milliseconds: 280),
                curve: Curves.easeOutCubic,
                child: showStickyCart
                    ? const StickyCartBar()
                    : const SizedBox.shrink(),
              ),

              // Bottom Navigation Bar
              Container(
                decoration: BoxDecoration(
                  color: AppColors.cardBg,
                  border: const Border(top: BorderSide(color: AppColors.borderLight, width: 1)),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.04),
                      blurRadius: 10,
                      offset: const Offset(0, -2),
                    ),
                  ],
                ),
                child: BottomNavigationBar(
              currentIndex: _currentIndex,
              onTap: (index) => setState(() => _currentIndex = index),
              type: BottomNavigationBarType.fixed,
              backgroundColor: AppColors.cardBg,
              selectedItemColor: AppColors.primary,
              unselectedItemColor: AppColors.darkSubtle,
              selectedLabelStyle: AppStyles.bodySmall(weight: FontWeight.w700),
              unselectedLabelStyle: AppStyles.bodySmall(weight: FontWeight.w500),
              elevation: 0,
              items: [
                const BottomNavigationBarItem(
                  icon: Icon(Icons.home_outlined),
                  activeIcon: Icon(Icons.home_rounded),
                  label: 'Home',
                ),
                const BottomNavigationBarItem(
                  icon: Icon(Icons.local_offer_outlined),
                  activeIcon: Icon(Icons.local_offer_rounded),
                  label: 'Deals',
                ),
                const BottomNavigationBarItem(
                  icon: Icon(Icons.restaurant_menu_outlined),
                  activeIcon: Icon(Icons.restaurant_menu_rounded),
                  label: 'Menu',
                ),
                BottomNavigationBarItem(
                  icon: Stack(
                    clipBehavior: Clip.none,
                    children: [
                      const Icon(Icons.shopping_bag_outlined),
                      if (cart.totalItemCount > 0)
                        Positioned(
                          right: -6,
                          top: -4,
                          child: Container(
                            padding: const EdgeInsets.all(3),
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
                  ),
                  activeIcon: const Icon(Icons.shopping_bag_rounded),
                  label: 'Cart',
                ),
                const BottomNavigationBarItem(
                  icon: Icon(Icons.person_outline_rounded),
                  activeIcon: Icon(Icons.person_rounded),
                  label: 'Account',
                ),
              ],
            ),
          ),
        ],
      );
    },
  ),
);
  }
}

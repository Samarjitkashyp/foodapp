import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';

import '../constants/app_colors.dart';
import '../providers/user_provider.dart';
import 'main_navigation_screen.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with TickerProviderStateMixin {
  late final AnimationController _mainAnimController;
  late final AnimationController _pulseController;

  // Staggered Animations for lag-free 60fps rendering
  late final Animation<double> _logoScale;
  late final Animation<double> _logoOpacity;
  late final Animation<double> _titleSlide;
  late final Animation<double> _titleOpacity;
  late final Animation<double> _taglineOpacity;
  late final Animation<double> _loaderOpacity;
  late final Animation<double> _glowPulse;

  bool _navigated = false;
  Timer? _autoNavTimer;

  @override
  void initState() {
    super.initState();

    // Set immersive dark status bar during splash for luxury cinema feel
    SystemChrome.setSystemUIOverlayStyle(
      const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
        systemNavigationBarColor: Color(0xFF0B0F19),
        systemNavigationBarIconBrightness: Brightness.light,
      ),
    );

    // 1. Entrance timeline controller (Total 1800ms)
    _mainAnimController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1800),
    );

    // 2. Infinite ambient warm pulse controller
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2200),
    )..repeat(reverse: true);

    // Logo pop-in with smooth elastic cubic curve
    _logoScale = Tween<double>(begin: 0.65, end: 1.0).animate(
      CurvedAnimation(
        parent: _mainAnimController,
        curve: const Interval(0.0, 0.55, curve: Curves.easeOutBack),
      ),
    );

    _logoOpacity = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _mainAnimController,
        curve: const Interval(0.0, 0.40, curve: Curves.easeIn),
      ),
    );

    // Brand Title slide & fade
    _titleSlide = Tween<double>(begin: 24.0, end: 0.0).animate(
      CurvedAnimation(
        parent: _mainAnimController,
        curve: const Interval(0.35, 0.75, curve: Curves.easeOutCubic),
      ),
    );

    _titleOpacity = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _mainAnimController,
        curve: const Interval(0.35, 0.70, curve: Curves.easeIn),
      ),
    );

    // Tagline fade
    _taglineOpacity = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _mainAnimController,
        curve: const Interval(0.55, 0.85, curve: Curves.easeIn),
      ),
    );

    // Bottom subtle indicator fade
    _loaderOpacity = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _mainAnimController,
        curve: const Interval(0.70, 1.0, curve: Curves.easeIn),
      ),
    );

    // Ambient halo glow
    _glowPulse = Tween<double>(begin: 0.75, end: 1.15).animate(
      CurvedAnimation(parent: _pulseController, curve: Curves.easeInOut),
    );

    // Start entrance animation
    _mainAnimController.forward();

    // Preload data in parallel and schedule smooth navigation
    _startPreloadAndNavigate();
  }

  void _startPreloadAndNavigate() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      // Warm up user provider and menu provider concurrently without blocking UI
      final userProvider = Provider.of<UserProvider>(context, listen: false);

      if (userProvider.phone.isNotEmpty) {
        unawaited(userProvider.fetchLiveOrders());
      }

      // Show splash for 3200ms so user can enjoy the smooth animation
      _autoNavTimer = Timer(const Duration(milliseconds: 3200), () {
        _navigateToHome();
      });
    });
  }

  void _navigateToHome() {
    if (!mounted || _navigated) return;
    _navigated = true;
    _autoNavTimer?.cancel();

    // Reset status bar to normal app theme
    SystemChrome.setSystemUIOverlayStyle(
      const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.dark,
        systemNavigationBarColor: AppColors.cardBg,
        systemNavigationBarIconBrightness: Brightness.dark,
      ),
    );

    Navigator.of(context).pushReplacement(
      PageRouteBuilder(
        transitionDuration: const Duration(milliseconds: 650),
        pageBuilder: (context, animation, secondaryAnimation) =>
            MainNavigationScreen(key: MainNavigationScreen.navKey),
        transitionsBuilder: (context, animation, secondaryAnimation, child) {
          final curved = CurvedAnimation(
            parent: animation,
            curve: Curves.easeOutCubic,
          );
          return FadeTransition(
            opacity: curved,
            child: child,
          );
        },
      ),
    );
  }

  @override
  void dispose() {
    _autoNavTimer?.cancel();
    _mainAnimController.dispose();
    _pulseController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;

    return Scaffold(
      backgroundColor: const Color(0xFF0B0F19),
      body: GestureDetector(
        onTap: _navigateToHome, // Tap anywhere to skip smoothly
        behavior: HitTestBehavior.opaque,
        child: Stack(
          fit: StackFit.expand,
          children: [
            // 1. Background Luxury Atmospheric Glow
            RepaintBoundary(
              child: AnimatedBuilder(
                animation: _glowPulse,
                builder: (context, child) {
                  return Stack(
                    fit: StackFit.expand,
                    children: [
                      // Top-center warm amber radial glow
                      Positioned(
                        top: size.height * 0.15,
                        left: size.width * 0.5 - (160 * _glowPulse.value),
                        child: Container(
                          width: 320 * _glowPulse.value,
                          height: 320 * _glowPulse.value,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            gradient: RadialGradient(
                              colors: [
                                const Color(0xFFFF5722).withValues(alpha: 0.22),
                                const Color(0xFFF59E0B).withValues(alpha: 0.08),
                                Colors.transparent,
                              ],
                              stops: const [0.0, 0.55, 1.0],
                            ),
                          ),
                        ),
                      ),
                      // Bottom subtle deep slate ambience
                      Positioned(
                        bottom: -80,
                        left: -80,
                        right: -80,
                        height: 300,
                        child: Container(
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            gradient: RadialGradient(
                              colors: [
                                const Color(0xFF1E293B).withValues(alpha: 0.4),
                                Colors.transparent,
                              ],
                            ),
                          ),
                        ),
                      ),
                    ],
                  );
                },
              ),
            ),

            // Skip Pill at Top-Right
            Positioned(
              top: MediaQuery.of(context).padding.top + 12,
              right: 18,
              child: AnimatedBuilder(
                animation: _loaderOpacity,
                builder: (context, child) {
                  return Opacity(
                    opacity: _loaderOpacity.value,
                    child: child,
                  );
                },
                child: GestureDetector(
                  onTap: _navigateToHome,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.08),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: Colors.white.withValues(alpha: 0.15),
                      ),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          'Skip',
                          style: GoogleFonts.plusJakartaSans(
                            color: Colors.white70,
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(width: 4),
                        const Icon(
                          Icons.arrow_forward_ios_rounded,
                          size: 10,
                          color: Colors.white70,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),

            // 2. Elegant Central Content
            Center(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 32),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // Restaurant Culinary Emblem / Badge
                    AnimatedBuilder(
                      animation: _mainAnimController,
                      builder: (context, child) {
                        return Opacity(
                          opacity: _logoOpacity.value,
                          child: Transform.scale(
                            scale: _logoScale.value,
                            child: child,
                          ),
                        );
                      },
                      child: _buildCulinaryBadge(),
                    ),

                    const SizedBox(height: 32),

                    // Brand Title: CRISP & CRAVE
                    AnimatedBuilder(
                      animation: _mainAnimController,
                      builder: (context, child) {
                        return Opacity(
                          opacity: _titleOpacity.value,
                          child: Transform.translate(
                            offset: Offset(0, _titleSlide.value),
                            child: child,
                          ),
                        );
                      },
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          RichText(
                            textAlign: TextAlign.center,
                            text: TextSpan(
                              children: [
                                TextSpan(
                                  text: 'CRISP ',
                                  style: GoogleFonts.outfit(
                                    fontSize: 34,
                                    fontWeight: FontWeight.w900,
                                    color: Colors.white,
                                    letterSpacing: 2.5,
                                  ),
                                ),
                                TextSpan(
                                  text: '& ',
                                  style: GoogleFonts.outfit(
                                    fontSize: 34,
                                    fontWeight: FontWeight.w800,
                                    color: const Color(0xFFFF7A00),
                                    letterSpacing: 1.5,
                                  ),
                                ),
                                TextSpan(
                                  text: 'CRAVE',
                                  style: GoogleFonts.outfit(
                                    fontSize: 34,
                                    fontWeight: FontWeight.w900,
                                    color: Colors.white,
                                    letterSpacing: 2.5,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 8),
                          // Sleek Restaurant Tagline
                          AnimatedBuilder(
                            animation: _taglineOpacity,
                            builder: (context, child) {
                              return Opacity(
                                opacity: _taglineOpacity.value,
                                child: child,
                              );
                            },
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Container(
                                      width: 24,
                                      height: 1.5,
                                      color: const Color(0xFFFF5722)
                                          .withValues(alpha: 0.6),
                                    ),
                                    Padding(
                                      padding: const EdgeInsets.symmetric(
                                          horizontal: 10),
                                      child: Text(
                                        'ARTISANAL KITCHEN & DINING',
                                        style: GoogleFonts.plusJakartaSans(
                                          fontSize: 11,
                                          fontWeight: FontWeight.w700,
                                          color: const Color(0xFFCBD5E1),
                                          letterSpacing: 2.2,
                                        ),
                                      ),
                                    ),
                                    Container(
                                      width: 24,
                                      height: 1.5,
                                      color: const Color(0xFFFF5722)
                                          .withValues(alpha: 0.6),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 16),
                                // Quality Badges Pill
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 14,
                                    vertical: 6,
                                  ),
                                  decoration: BoxDecoration(
                                    color: Colors.white.withValues(alpha: 0.06),
                                    border: Border.all(
                                      color: Colors.white
                                          .withValues(alpha: 0.12),
                                      width: 1,
                                    ),
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      const Icon(
                                        Icons.local_fire_department_rounded,
                                        size: 14,
                                        color: Color(0xFFFF7A00),
                                      ),
                                      const SizedBox(width: 6),
                                      Text(
                                        'Freshly Crafted • Always Fast',
                                        style: GoogleFonts.plusJakartaSans(
                                          fontSize: 11.5,
                                          fontWeight: FontWeight.w600,
                                          color: const Color(0xFFE2E8F0),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),

            // 3. Bottom Minimalist Kitchen Loader (Safely positioned above Navigation bar)
            Positioned(
              bottom: 0,
              left: 0,
              right: 0,
              child: SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.only(bottom: 24),
                  child: AnimatedBuilder(
                    animation: _loaderOpacity,
                    builder: (context, child) {
                      return Opacity(
                        opacity: _loaderOpacity.value,
                        child: child,
                      );
                    },
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        // Subtle smooth 3-dot pulse
                        _buildPulseLoader(),
                        const SizedBox(height: 10),
                        Text(
                          'Preparing your culinary experience...',
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 12,
                            fontWeight: FontWeight.w500,
                            color: const Color(0xFF64748B),
                            letterSpacing: 0.5,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// Luxury Restaurant Badge with glowing borders & culinary iconography
  Widget _buildCulinaryBadge() {
    return Container(
      width: 114,
      height: 114,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: const RadialGradient(
          colors: [
            Color(0xFF1E293B),
            Color(0xFF0F172A),
          ],
        ),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFFFF5722).withValues(alpha: 0.35),
            blurRadius: 32,
            spreadRadius: 2,
            offset: const Offset(0, 8),
          ),
          BoxShadow(
            color: const Color(0xFFF59E0B).withValues(alpha: 0.2),
            blurRadius: 16,
            spreadRadius: 0,
          ),
        ],
        border: Border.all(
          color: const Color(0xFFFF7A00).withValues(alpha: 0.7),
          width: 2.2,
        ),
      ),
      child: Stack(
        alignment: Alignment.center,
        children: [
          // Inner decorative ring
          Container(
            width: 96,
            height: 96,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              border: Border.all(
                color: Colors.white.withValues(alpha: 0.08),
                width: 1.5,
              ),
            ),
          ),
          // Culinary Chef Hat & Cloche / Fork-Spoon Emblem
          ShaderMask(
            shaderCallback: (bounds) => const LinearGradient(
              colors: [
                Color(0xFFFFB703),
                Color(0xFFFF5722),
              ],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ).createShader(bounds),
            child: const Icon(
              Icons.restaurant_rounded,
              size: 50,
              color: Colors.white,
            ),
          ),
        ],
      ),
    );
  }

  /// Ultra-light 3-dot pulsing culinary loader (zero frame drops)
  Widget _buildPulseLoader() {
    return SizedBox(
      height: 6,
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: List.generate(3, (index) {
          return AnimatedBuilder(
            animation: _pulseController,
            builder: (context, child) {
              // Staggered sine phase for smooth wave effect
              final phase = (_pulseController.value + (index * 0.28)) % 1.0;
              final scale = 0.5 + (0.5 * phase);
              final opacity = 0.3 + (0.7 * phase);

              return Container(
                margin: const EdgeInsets.symmetric(horizontal: 4),
                width: 7 * scale,
                height: 7 * scale,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: const Color(0xFFFF7A00).withValues(alpha: opacity),
                ),
              );
            },
          );
        }),
      ),
    );
  }
}

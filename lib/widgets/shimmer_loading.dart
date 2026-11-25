import 'package:flutter/material.dart';
import '../constants/app_colors.dart';

class ShimmerLoading extends StatefulWidget {
  final Widget child;

  const ShimmerLoading({super.key, required this.child});

  @override
  State<ShimmerLoading> createState() => _ShimmerLoadingState();
}

class _ShimmerLoadingState extends State<ShimmerLoading> with SingleTickerProviderStateMixin {
  late AnimationController _shimmerController;

  @override
  void initState() {
    super.initState();
    _shimmerController = AnimationController.unbounded(vsync: this)
      ..repeat(min: -0.5, max: 1.5, period: const Duration(milliseconds: 1300));
  }

  @override
  void dispose() {
    _shimmerController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _shimmerController,
      builder: (context, child) {
        return ShaderMask(
          blendMode: BlendMode.srcATop,
          shaderCallback: (bounds) {
            final double value = _shimmerController.value;
            return LinearGradient(
              colors: const [
                Color(0xFFE8E5DF),
                Color(0xFFF7F5F0),
                Color(0xFFE8E5DF),
              ],
              stops: const [0.1, 0.5, 0.9],
              begin: const Alignment(-1.0, -0.3),
              end: const Alignment(1.0, 0.3),
              transform: _SlidingGradientTransform(slidePercent: value),
            ).createShader(bounds);
          },
          child: child,
        );
      },
      child: widget.child,
    );
  }
}

class _SlidingGradientTransform extends GradientTransform {
  final double slidePercent;
  const _SlidingGradientTransform({required this.slidePercent});

  @override
  Matrix4? transform(Rect bounds, {TextDirection? textDirection}) {
    return Matrix4.translationValues(bounds.width * slidePercent, 0.0, 0.0);
  }
}

class ShimmerBox extends StatelessWidget {
  final double? width;
  final double? height;
  final double borderRadius;

  const ShimmerBox({
    super.key,
    this.width,
    this.height,
    this.borderRadius = 12,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        color: const Color(0xFFE8E5DF),
        borderRadius: BorderRadius.circular(borderRadius),
      ),
    );
  }
}

class HomeShimmerView extends StatelessWidget {
  const HomeShimmerView({super.key});

  @override
  Widget build(BuildContext context) {
    return ShimmerLoading(
      child: ListView(
        physics: const NeverScrollableScrollPhysics(),
        padding: const EdgeInsets.symmetric(vertical: 12),
        children: [
          // 1. Hero Banner Shimmer (2:1 exact aspect ratio)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: AspectRatio(
              aspectRatio: 2.0,
              child: const ShimmerBox(
                borderRadius: 16,
              ),
            ),
          ),
          const SizedBox(height: 18),

          // 2. Category Chips Shimmer
          SizedBox(
            height: 38,
            child: ListView.separated(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              scrollDirection: Axis.horizontal,
              itemCount: 5,
              separatorBuilder: (_, _) => const SizedBox(width: 8),
              itemBuilder: (_, _) => const ShimmerBox(width: 96, height: 38, borderRadius: 20),
            ),
          ),
          const SizedBox(height: 16),

          // 3. Horizontal Food Cards Shimmer
          SizedBox(
            height: 230,
            child: ListView.separated(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              scrollDirection: Axis.horizontal,
              itemCount: 3,
              separatorBuilder: (_, _) => const SizedBox(width: 14),
              itemBuilder: (_, _) => Container(
                width: 175,
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
                    ShimmerBox(height: 14, width: 120, borderRadius: 4),
                    SizedBox(height: 8),
                    ShimmerBox(height: 12, width: 80, borderRadius: 4),
                    Spacer(),
                    ShimmerBox(height: 18, width: 60, borderRadius: 4),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(height: 20),

          // 4. Offers Banner Shimmer (2:1 exact aspect ratio)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: const ShimmerBox(height: 22, width: 100, borderRadius: 6),
          ),
          const SizedBox(height: 12),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: AspectRatio(
              aspectRatio: 2.0,
              child: const ShimmerBox(
                borderRadius: 16,
              ),
            ),
          ),
          const SizedBox(height: 20),

          // 5. Grid Shimmer
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: const ShimmerBox(height: 22, width: 140, borderRadius: 6),
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
                    ShimmerBox(height: 120, width: double.infinity, borderRadius: 12),
                    SizedBox(height: 12),
                    ShimmerBox(height: 14, width: 110, borderRadius: 4),
                    SizedBox(height: 8),
                    ShimmerBox(height: 12, width: 70, borderRadius: 4),
                    Spacer(),
                    ShimmerBox(height: 20, width: 60, borderRadius: 4),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(height: 80),
        ],
      ),
    );
  }
}

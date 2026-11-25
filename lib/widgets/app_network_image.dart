import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import 'shimmer_loading.dart';

class AppNetworkImage extends StatelessWidget {
  final String imagePath;
  final double? width;
  final double? height;
  final BoxFit fit;
  final BorderRadius? borderRadius;

  const AppNetworkImage({
    super.key,
    required this.imagePath,
    this.width,
    this.height,
    this.fit = BoxFit.cover,
    this.borderRadius,
  });

  @override
  Widget build(BuildContext context) {
    Widget imageWidget;

    if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
      final int memWidth = (width != null && width! > 0 && width!.isFinite)
          ? (width! * 2.5).toInt()
          : 800;

      imageWidget = CachedNetworkImage(
        imageUrl: imagePath,
        width: width,
        height: height,
        fit: fit,
        memCacheWidth: memWidth,
        maxWidthDiskCache: 1200,
        maxHeightDiskCache: 1200,
        fadeInDuration: const Duration(milliseconds: 150),
        fadeOutDuration: const Duration(milliseconds: 100),
        placeholder: (context, url) => ShimmerLoading(
          child: Container(
            width: width,
            height: height,
            color: const Color(0xFFE8E5DF),
          ),
        ),
        errorWidget: (context, url, error) => _fallbackContainer(),
      );
    } else {
      imageWidget = Image.asset(
        imagePath,
        width: width,
        height: height,
        fit: fit,
        cacheWidth: (width != null && width! > 0 && width!.isFinite) ? (width! * 2.5).toInt() : 600,
        cacheHeight: (height != null && height! > 0 && height!.isFinite) ? (height! * 2.5).toInt() : 450,
        errorBuilder: (context, error, stackTrace) => _fallbackContainer(),
      );
    }

    if (borderRadius != null) {
      return ClipRRect(
        borderRadius: borderRadius!,
        child: imageWidget,
      );
    }
    return imageWidget;
  }

  Widget _fallbackContainer() {
    return Container(
      width: width,
      height: height,
      color: AppColors.surfaceSubtle,
      child: const Center(
        child: Icon(
          Icons.fastfood_rounded,
          color: AppColors.primaryLight,
          size: 28,
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';

class OrderSuccessScreen extends StatelessWidget {
  final String orderId;
  final double totalAmount;
  final int itemsCount;
  final String address;
  final String paymentMethod;

  const OrderSuccessScreen({
    super.key,
    required this.orderId,
    required this.totalAmount,
    required this.itemsCount,
    required this.address,
    required this.paymentMethod,
  });

  void _openWhatsApp() async {
    final uri = Uri.parse(
        'https://wa.me/919876543210?text=Hello%20Crisp%20and%20Crave!%20My%20order%20is%20$orderId');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.pageBg,
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            const SizedBox(height: 20),

            // Animated Success Icon
            Center(
              child: Container(
                width: 80,
                height: 80,
                decoration: const BoxDecoration(
                  color: AppColors.primarySoft,
                  shape: BoxShape.circle,
                ),
                child: const Center(
                  child: Icon(
                    Icons.check_circle_rounded,
                    color: AppColors.primary,
                    size: 56,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 20),

            Center(
              child: Text(
                'Order Placed Successfully!',
                style: AppStyles.headingLarge(),
                textAlign: TextAlign.center,
              ),
            ),
            const SizedBox(height: 6),
            Center(
              child: Text(
                'Booking Reference: $orderId',
                style: AppStyles.bodyMedium(
                  color: AppColors.primary,
                  weight: FontWeight.w800,
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Estimated Delivery Card
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                gradient: AppColors.bannerGradient,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.primaryLight.withValues(alpha: 0.5)),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.timer_outlined, color: AppColors.primary, size: 24),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Estimated Delivery: 30-35 mins',
                          style: AppStyles.bodyLarge(color: AppColors.dark, weight: FontWeight.w800),
                        ),
                        Text(
                          'Our chef is firing up the kitchen grill!',
                          style: AppStyles.bodySmall(color: AppColors.darkMuted),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Live Order Tracker
            Text('Live Order Status', style: AppStyles.headingSmall()),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.cardBg,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.borderLight),
              ),
              child: Column(
                children: [
                  _timelineStep('Order Received', 'Your order was sent to the kitchen', true, isCurrent: false),
                  _timelineStep('Kitchen Preparing', 'Master chef crafting your meal', true, isCurrent: true),
                  _timelineStep('Out for Delivery', 'Rider will pick up fresh parcel', false),
                  _timelineStep('Delivered', 'Enjoy your hot meal!', false, isLast: true),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Order Details Summary
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.cardBg,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.borderLight),
              ),
              child: Column(
                children: [
                  _infoRow('Items Quantity', '$itemsCount Items'),
                  const Divider(color: AppColors.borderLight, height: 16),
                  _infoRow('Payment Method', paymentMethod),
                  const Divider(color: AppColors.borderLight, height: 16),
                  _infoRow('Total Amount', '₹${totalAmount.toInt()}', isBold: true),
                  const Divider(color: AppColors.borderLight, height: 16),
                  _infoRow('Deliver To', address),
                ],
              ),
            ),
            const SizedBox(height: 28),

            // Actions
            ElevatedButton.icon(
              onPressed: _openWhatsApp,
              icon: const Icon(Icons.chat_bubble_outline_rounded, size: 20),
              label: const Text('Track / Chat on WhatsApp'),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF25D366), // WhatsApp Green
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
            ),
            const SizedBox(height: 10),

            OutlinedButton(
              onPressed: () {
                Navigator.of(context).popUntil((route) => route.isFirst);
              },
              style: OutlinedButton.styleFrom(
                foregroundColor: AppColors.dark,
                side: const BorderSide(color: AppColors.borderLight),
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
              child: const Text('Back to Home'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _timelineStep(String title, String desc, bool isDone, {bool isCurrent = false, bool isLast = false}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              width: 20,
              height: 20,
              decoration: BoxDecoration(
                color: isDone ? AppColors.primary : AppColors.surfaceSubtle,
                shape: BoxShape.circle,
                border: Border.all(
                  color: isDone ? AppColors.primary : AppColors.darkSubtle,
                  width: 2,
                ),
              ),
              child: isDone
                  ? const Icon(Icons.check, size: 12, color: Colors.white)
                  : null,
            ),
            if (!isLast)
              Container(
                width: 2,
                height: 34,
                color: isDone && !isCurrent ? AppColors.primary : AppColors.borderLight,
              ),
          ],
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: AppStyles.bodyMedium(
                  color: isDone ? AppColors.dark : AppColors.darkSubtle,
                  weight: isDone ? FontWeight.w700 : FontWeight.w500,
                ),
              ),
              Text(
                desc,
                style: AppStyles.bodySmall(color: AppColors.darkMuted),
              ),
              const SizedBox(height: 12),
            ],
          ),
        ),
      ],
    );
  }

  Widget _infoRow(String label, String value, {bool isBold = false}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: AppStyles.bodyMedium(color: AppColors.darkMuted),
        ),
        const Spacer(),
        Expanded(
          child: Text(
            value,
            textAlign: TextAlign.end,
            style: AppStyles.bodyMedium(
              color: isBold ? AppColors.primary : AppColors.dark,
              weight: isBold ? FontWeight.w800 : FontWeight.w600,
            ),
          ),
        ),
      ],
    );
  }
}

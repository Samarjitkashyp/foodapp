import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';
import '../models/order_model.dart';
import '../providers/favorites_provider.dart';
import '../providers/reservation_provider.dart';
import '../providers/user_provider.dart';
import '../widgets/app_top_bar.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  void _launchUrl(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  void _showEditProfileDialog(BuildContext context, UserProvider user) {
    final nameCtrl = TextEditingController(text: user.name);
    final phoneCtrl = TextEditingController(text: user.phone);
    final emailCtrl = TextEditingController(text: user.email);
    final addressCtrl = TextEditingController(text: user.address);
    final landmarkCtrl = TextEditingController(text: user.landmark);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Container(
          padding: EdgeInsets.only(
            top: 20,
            left: 20,
            right: 20,
            bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
          ),
          decoration: const BoxDecoration(
            color: AppColors.cardBg,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: AppColors.borderLight,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    const Icon(Icons.manage_accounts_rounded, color: AppColors.primary, size: 24),
                    const SizedBox(width: 8),
                    Text('Edit Profile & Address', style: AppStyles.headingMedium()),
                  ],
                ),
                const SizedBox(height: 18),

                TextField(
                  controller: nameCtrl,
                  decoration: _sheetInputDecoration('Your Name', Icons.person_outline),
                ),
                const SizedBox(height: 12),

                TextField(
                  controller: phoneCtrl,
                  keyboardType: TextInputType.phone,
                  decoration: _sheetInputDecoration('Phone Number', Icons.phone_outlined),
                ),
                const SizedBox(height: 12),

                TextField(
                  controller: emailCtrl,
                  keyboardType: TextInputType.emailAddress,
                  decoration: _sheetInputDecoration('Email Address (Optional)', Icons.email_outlined),
                ),
                const SizedBox(height: 12),

                TextField(
                  controller: addressCtrl,
                  maxLines: 2,
                  decoration: _sheetInputDecoration('Primary Delivery Address', Icons.home_outlined),
                ),
                const SizedBox(height: 12),

                TextField(
                  controller: landmarkCtrl,
                  decoration: _sheetInputDecoration('Landmark / Instructions', Icons.flag_outlined),
                ),
                const SizedBox(height: 20),

                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    onPressed: () {
                      if (nameCtrl.text.trim().isEmpty || phoneCtrl.text.trim().isEmpty) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Name and Phone cannot be empty')),
                        );
                        return;
                      }

                      user.saveProfile(
                        name: nameCtrl.text,
                        phone: phoneCtrl.text,
                        email: emailCtrl.text,
                        address: addressCtrl.text,
                        landmark: landmarkCtrl.text,
                      );

                      Navigator.pop(ctx);
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('Profile & Address updated successfully! ✓'),
                          backgroundColor: AppColors.vegGreen,
                          behavior: SnackBarBehavior.floating,
                        ),
                      );
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      elevation: 2,
                    ),
                    child: const Text('Save Profile Changes', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  InputDecoration _sheetInputDecoration(String label, IconData icon) {
    return InputDecoration(
      labelText: label,
      prefixIcon: Icon(icon, size: 20, color: AppColors.darkMuted),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.borderLight)),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.borderLight)),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.primary)),
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.pageBg,
      body: SafeArea(
        child: Column(
          children: [
            // Unified App Top Bar
            const AppTopBar(),

            // Scrollable Content
            Expanded(
              child: Consumer<UserProvider>(
                builder: (context, user, child) {
                  return RefreshIndicator(
                    color: AppColors.primary,
                    backgroundColor: Colors.white,
                    onRefresh: () async {
                      await user.fetchLiveOrders();
                    },
                    child: ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                      children: [
                        // User Profile Card
                        Container(
                          padding: const EdgeInsets.all(18),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius: BorderRadius.circular(20),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.15),
                                blurRadius: 16,
                                offset: const Offset(0, 6),
                              ),
                            ],
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  CircleAvatar(
                                    radius: 28,
                                    backgroundColor: AppColors.primary,
                                    child: Text(
                                      user.name.isNotEmpty ? user.name[0].toUpperCase() : 'U',
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 24,
                                        fontWeight: FontWeight.w900,
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 14),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          user.name,
                                          style: AppStyles.headingMedium(color: Colors.white),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                        const SizedBox(height: 3),
                                        Text(
                                          user.phone,
                                          style: const TextStyle(color: Colors.white70, fontSize: 13, fontWeight: FontWeight.w600),
                                        ),
                                        const SizedBox(height: 6),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                          decoration: BoxDecoration(
                                            color: AppColors.primary,
                                            borderRadius: BorderRadius.circular(20),
                                          ),
                                          child: Text(
                                            'CRAVE GOLD MEMBER',
                                            style: AppStyles.bodySmall(color: Colors.white, weight: FontWeight.w800),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  IconButton(
                                    onPressed: () => _showEditProfileDialog(context, user),
                                    tooltip: 'Edit Profile & Address',
                                    icon: Container(
                                      padding: const EdgeInsets.all(8),
                                      decoration: BoxDecoration(
                                        color: Colors.white.withValues(alpha: 0.15),
                                        shape: BoxShape.circle,
                                      ),
                                      child: const Icon(Icons.edit_rounded, color: Colors.white, size: 16),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 16),
                              Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: Colors.white.withValues(alpha: 0.08),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Row(
                                  children: [
                                    const Icon(Icons.location_on_rounded, color: AppColors.primaryLight, size: 18),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: Text(
                                        user.address.isNotEmpty ? user.address : 'Tap Edit to add your delivery address',
                                        style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w500),
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 20),

                        // Quick Stats Row
                        Consumer<FavoritesProvider>(
                          builder: (context, favs, _) {
                            return Row(
                              children: [
                                Expanded(
                                  child: _statCard(
                                    'Total Orders',
                                    '${user.totalOrdersCount} Placed',
                                    Icons.receipt_long_rounded,
                                    AppColors.primary,
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: _statCard(
                                    'Favorites',
                                    '${favs.favoriteIds.length} Saved',
                                    Icons.favorite_rounded,
                                    const Color(0xFFEF4444),
                                  ),
                                ),
                              ],
                            );
                          },
                        ),
                        const SizedBox(height: 24),

                        // -----------------------------------------------------------
                        // My Past Orders (Directly Synced with Backend & Admin)
                        // -----------------------------------------------------------
                        Row(
                          children: [
                            const Icon(Icons.receipt_long_rounded, color: AppColors.primary, size: 20),
                            const SizedBox(width: 8),
                            Text('My Order History', style: AppStyles.headingSmall()),
                            const Spacer(),
                            if (user.isLoadingOrders)
                              const SizedBox(
                                width: 14,
                                height: 14,
                                child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.primary),
                              ),
                          ],
                        ),
                        const SizedBox(height: 12),

                        if (user.orders.isEmpty)
                          Container(
                            padding: const EdgeInsets.all(20),
                            decoration: BoxDecoration(
                              color: AppColors.cardBg,
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: AppColors.borderLight),
                            ),
                            child: Column(
                              children: [
                                const Icon(Icons.shopping_bag_outlined, size: 36, color: AppColors.darkSubtle),
                                const SizedBox(height: 8),
                                Text(
                                  'No orders placed yet',
                                  style: AppStyles.headingSmall(color: AppColors.dark),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'Your orders will appear here with live preparation tracking.',
                                  style: AppStyles.bodySmall(color: AppColors.darkMuted),
                                  textAlign: TextAlign.center,
                                ),
                              ],
                            ),
                          )
                        else
                          Column(
                            children: user.orders.take(5).map((order) => _buildOrderHistoryCard(order)).toList(),
                          ),
                        const SizedBox(height: 24),

                        // Active Table Bookings
                        Consumer<ReservationProvider>(
                          builder: (context, resProvider, _) {
                            if (resProvider.reservations.isEmpty) return const SizedBox.shrink();

                            return Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    const Icon(Icons.table_restaurant_rounded, color: AppColors.primary, size: 20),
                                    const SizedBox(width: 8),
                                    Text('My Table Bookings', style: AppStyles.headingSmall()),
                                  ],
                                ),
                                const SizedBox(height: 12),
                                ...resProvider.reservations.take(3).map((res) {
                                  return Container(
                                    margin: const EdgeInsets.only(bottom: 10),
                                    padding: const EdgeInsets.all(14),
                                    decoration: BoxDecoration(
                                      color: AppColors.cardBg,
                                      borderRadius: BorderRadius.circular(16),
                                      border: Border.all(color: AppColors.borderLight),
                                      boxShadow: AppStyles.cardShadow,
                                    ),
                                    child: Row(
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.all(10),
                                          decoration: const BoxDecoration(
                                            color: AppColors.primarySoft,
                                            shape: BoxShape.circle,
                                          ),
                                          child: const Icon(Icons.table_bar_rounded, color: AppColors.primary, size: 20),
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                'Table Ref: ${res.bookingId}',
                                                style: AppStyles.bodyMedium(color: AppColors.dark, weight: FontWeight.w700),
                                              ),
                                              Text(
                                                '${res.guests} Guests • ${DateFormat("MMM d").format(res.date)} at ${res.timeSlot}',
                                                style: AppStyles.bodySmall(color: AppColors.darkMuted),
                                              ),
                                            ],
                                          ),
                                        ),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFFDCFCE7),
                                            borderRadius: BorderRadius.circular(8),
                                          ),
                                          child: const Text(
                                            'Confirmed',
                                            style: TextStyle(color: Color(0xFF15803D), fontSize: 11, fontWeight: FontWeight.w800),
                                          ),
                                        ),
                                      ],
                                    ),
                                  );
                                }),
                                const SizedBox(height: 16),
                              ],
                            );
                          },
                        ),

                        // Restaurant & Support Info
                        Row(
                          children: [
                            const Icon(Icons.support_agent_rounded, color: AppColors.primary, size: 20),
                            const SizedBox(width: 8),
                            Text('Support & Kitchen Concierge', style: AppStyles.headingSmall()),
                          ],
                        ),
                        const SizedBox(height: 12),

                        Material(
                          color: AppColors.cardBg,
                          borderRadius: BorderRadius.circular(16),
                          child: Container(
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: AppColors.borderLight),
                              boxShadow: AppStyles.cardShadow,
                            ),
                            child: Column(
                              children: [
                                _actionTile(
                                  icon: Icons.chat_bubble_outline_rounded,
                                  iconColor: const Color(0xFF25D366),
                                  title: 'WhatsApp Food Concierge',
                                  subtitle: '+91 98765 43210 (Live Kitchen Chat)',
                                  onTap: () => _launchUrl('https://wa.me/919876543210?text=Hello%20Crisp%20and%20Crave!'),
                                ),
                                const Divider(color: AppColors.borderLight, height: 1),
                                _actionTile(
                                  icon: Icons.language_rounded,
                                  iconColor: AppColors.primary,
                                  title: 'Official Kitchen Portal',
                                  subtitle: 'https://food.aiaxom.co.in/',
                                  onTap: () => _launchUrl('https://food.aiaxom.co.in/'),
                                ),
                                const Divider(color: AppColors.borderLight, height: 1),
                                _actionTile(
                                  icon: Icons.access_time_rounded,
                                  iconColor: AppColors.accent,
                                  title: 'Kitchen Working Hours',
                                  subtitle: '11:00 AM – 02:00 AM (Open Everyday)',
                                  onTap: () {},
                                ),
                                const Divider(color: AppColors.borderLight, height: 1),
                                _actionTile(
                                  icon: Icons.store_mall_directory_outlined,
                                  iconColor: AppColors.dark,
                                  title: 'Cloud Kitchen Hub',
                                  subtitle: 'Crisp & Crave Kitchen, Main Road Link',
                                  onTap: () {},
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(height: 30),

                        Center(
                          child: Text(
                            'Crisp & Crave v2.0 • Live Kitchen Orders Synced',
                            style: AppStyles.bodySmall(color: AppColors.darkSubtle),
                          ),
                        ),
                        const SizedBox(height: 60),
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

  Widget _buildOrderHistoryCard(OrderModel order) {
    Color statusColor;
    Color statusBg;
    String statusText;

    switch (order.orderStatus.toLowerCase()) {
      case 'delivered':
        statusColor = const Color(0xFF15803D);
        statusBg = const Color(0xFFDCFCE7);
        statusText = 'Delivered ✓';
        break;
      case 'out_for_delivery':
        statusColor = const Color(0xFF1D4ED8);
        statusBg = const Color(0xFFDBEAFE);
        statusText = 'Out for Delivery 🛵';
        break;
      case 'cancelled':
        statusColor = const Color(0xFFB91C1C);
        statusBg = const Color(0xFFFEE2E2);
        statusText = 'Cancelled';
        break;
      default:
        statusColor = const Color(0xFFC2410C);
        statusBg = const Color(0xFFFFEDD5);
        statusText = 'Preparing in Kitchen 👨‍🍳';
        break;
    }

    final itemNames = order.items.map((i) => '${i.quantity}x ${i.name}').join(', ');

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.cardBg,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.borderLight),
        boxShadow: AppStyles.cardShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                order.orderNumber,
                style: const TextStyle(
                  fontWeight: FontWeight.w900,
                  fontSize: 14,
                  letterSpacing: 0.5,
                  color: AppColors.dark,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: statusBg,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  statusText,
                  style: TextStyle(
                    color: statusColor,
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            DateFormat('dd MMM yyyy, hh:mm a').format(order.createdAt),
            style: const TextStyle(color: AppColors.darkSubtle, fontSize: 11, fontWeight: FontWeight.w500),
          ),
          const Divider(height: 16, color: AppColors.borderLight),

          Text(
            itemNames.isNotEmpty ? itemNames : 'Feast Items',
            style: AppStyles.bodySmall(color: AppColors.darkMuted, height: 1.3),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 10),

          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Total: ₹${order.grandTotal.toInt()}',
                style: AppStyles.priceMain(fontSize: 16),
              ),
              Text(
                order.paymentMethod,
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: AppColors.darkSubtle,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _statCard(String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.cardBg,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.borderLight),
        boxShadow: AppStyles.cardShadow,
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: AppStyles.bodySmall(color: AppColors.darkMuted)),
                const SizedBox(height: 2),
                Text(value, style: AppStyles.bodyMedium(weight: FontWeight.w800)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _actionTile({
    required IconData icon,
    required Color iconColor,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return ListTile(
      onTap: onTap,
      leading: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: iconColor.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Icon(icon, color: iconColor, size: 20),
      ),
      title: Text(title, style: AppStyles.bodyMedium(weight: FontWeight.w700)),
      subtitle: Text(subtitle, style: AppStyles.bodySmall(color: AppColors.darkMuted)),
      trailing: const Icon(Icons.chevron_right_rounded, color: AppColors.darkSubtle),
    );
  }
}

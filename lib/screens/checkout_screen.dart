import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';
import '../providers/cart_provider.dart';
import '../providers/user_provider.dart';
import 'order_success_screen.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final _formKey = GlobalKey<FormState>();

  late final TextEditingController _nameCtrl;
  late final TextEditingController _phoneCtrl;
  late final TextEditingController _addressCtrl;
  late final TextEditingController _landmarkCtrl;

  String _paymentMethod = 'upi'; // 'cod' or 'upi'
  bool _isPlacingOrder = false;

  @override
  void initState() {
    super.initState();
    final user = context.read<UserProvider>();
    _nameCtrl = TextEditingController(text: user.name);
    _phoneCtrl = TextEditingController(text: user.phone);
    _addressCtrl = TextEditingController(text: user.address);
    _landmarkCtrl = TextEditingController(text: user.landmark);
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    _addressCtrl.dispose();
    _landmarkCtrl.dispose();
    super.dispose();
  }

  void _submitOrder(CartProvider cart) async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isPlacingOrder = true);

    final userProvider = context.read<UserProvider>();

    // Save profile updates
    userProvider.saveProfile(
      name: _nameCtrl.text,
      phone: _phoneCtrl.text,
      address: _addressCtrl.text,
      landmark: _landmarkCtrl.text,
    );

    final totalAmount = cart.grandTotal;
    final itemsCount = cart.totalItemCount;

    // Post to Server API & save to order history
    final orderId = await userProvider.submitOrderToServer(
      cart: cart,
      paymentMethod: _paymentMethod == 'upi' ? 'UPI' : 'Cash on Delivery',
      name: _nameCtrl.text,
      phone: _phoneCtrl.text,
      address: _addressCtrl.text,
      landmark: _landmarkCtrl.text,
    );

    cart.clearCart();

    if (mounted) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (_) => OrderSuccessScreen(
            orderId: orderId ?? 'CC-88210',
            totalAmount: totalAmount,
            itemsCount: itemsCount,
            address: _addressCtrl.text,
            paymentMethod: _paymentMethod == 'upi' ? 'UPI / Online' : 'Cash on Delivery',
          ),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.pageBg,
      appBar: AppBar(
        backgroundColor: AppColors.cardBg,
        elevation: 0,
        title: Text(
          'Checkout & Delivery',
          style: AppStyles.headingMedium(),
        ),
      ),
      body: Consumer<CartProvider>(
        builder: (context, cart, child) {
          return Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // Delivery Address Card
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: AppColors.cardBg,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.borderLight),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.location_on_rounded, color: AppColors.primary, size: 20),
                          const SizedBox(width: 8),
                          Text('Delivery Destination', style: AppStyles.headingSmall()),
                        ],
                      ),
                      const SizedBox(height: 14),

                      TextFormField(
                        controller: _nameCtrl,
                        decoration: _inputDecoration('Full Name', Icons.person_outline),
                        validator: (val) => val == null || val.isEmpty ? 'Please enter name' : null,
                      ),
                      const SizedBox(height: 12),

                      TextFormField(
                        controller: _phoneCtrl,
                        keyboardType: TextInputType.phone,
                        decoration: _inputDecoration('Phone Number', Icons.phone_outlined),
                        validator: (val) => val == null || val.isEmpty ? 'Please enter phone' : null,
                      ),
                      const SizedBox(height: 12),

                      TextFormField(
                        controller: _addressCtrl,
                        maxLines: 2,
                        decoration: _inputDecoration('Complete Street Address', Icons.home_outlined),
                        validator: (val) => val == null || val.isEmpty ? 'Please enter address' : null,
                      ),
                      const SizedBox(height: 12),

                      TextFormField(
                        controller: _landmarkCtrl,
                        decoration: _inputDecoration('Landmark (Optional)', Icons.flag_outlined),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                // Payment Options
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: AppColors.cardBg,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.borderLight),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.payment_rounded, color: AppColors.primary, size: 20),
                          const SizedBox(width: 8),
                          Text('Payment Method', style: AppStyles.headingSmall()),
                        ],
                      ),
                      const SizedBox(height: 14),

                      // UPI Option
                      _paymentTile(
                        id: 'upi',
                        title: 'Instant UPI / Online Payment',
                        subtitle: 'Google Pay, PhonePe, Paytm, NetBanking',
                        icon: Icons.account_balance_wallet_outlined,
                      ),
                      const SizedBox(height: 10),

                      // COD Option
                      _paymentTile(
                        id: 'cod',
                        title: 'Cash on Delivery (COD)',
                        subtitle: 'Pay cash or scan QR upon doorstep delivery',
                        icon: Icons.money_rounded,
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                // Delivery Estimate Pill
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: AppColors.primarySoft,
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.electric_bolt_rounded, color: AppColors.primary, size: 22),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Standard Express Delivery',
                              style: AppStyles.bodySmall(color: AppColors.dark, weight: FontWeight.w700),
                            ),
                            Text(
                              'Estimated arrival in 30-35 mins from Crisp & Crave Kitchen',
                              style: AppStyles.bodySmall(color: AppColors.darkMuted),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 80),
              ],
            ),
          );
        },
      ),

      // Bottom Bar
      bottomNavigationBar: Consumer<CartProvider>(
        builder: (context, cart, _) {
          return Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: AppColors.cardBg,
              boxShadow: AppStyles.floatingShadow,
            ),
            child: SafeArea(
              child: ElevatedButton(
                onPressed: _isPlacingOrder ? null : () => _submitOrder(cart),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                child: _isPlacingOrder
                    ? const SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5),
                      )
                    : Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.lock_outline, size: 18),
                          const SizedBox(width: 8),
                          Text(
                            'Confirm & Place Order • ₹${cart.grandTotal.toInt()}',
                            style: AppStyles.bodyLarge(color: Colors.white, weight: FontWeight.w700),
                          ),
                        ],
                      ),
              ),
            ),
          );
        },
      ),
    );
  }

  InputDecoration _inputDecoration(String label, IconData icon) {
    return InputDecoration(
      labelText: label,
      labelStyle: AppStyles.bodySmall(color: AppColors.darkMuted),
      prefixIcon: Icon(icon, size: 18, color: AppColors.darkMuted),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: AppColors.borderLight),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: AppColors.borderLight),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: AppColors.primary),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
    );
  }

  Widget _paymentTile({
    required String id,
    required String title,
    required String subtitle,
    required IconData icon,
  }) {
    final isSelected = _paymentMethod == id;

    return InkWell(
      onTap: () => setState(() => _paymentMethod = id),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primarySoft : AppColors.surfaceSubtle,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isSelected ? AppColors.primary : AppColors.borderLight,
            width: isSelected ? 1.5 : 1,
          ),
        ),
        child: Row(
          children: [
            Icon(
              icon,
              color: isSelected ? AppColors.primary : AppColors.darkMuted,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: AppStyles.bodyMedium(
                      color: AppColors.dark,
                      weight: FontWeight.w700,
                    ),
                  ),
                  Text(
                    subtitle,
                    style: AppStyles.bodySmall(color: AppColors.darkMuted),
                  ),
                ],
              ),
            ),
            Radio<String>(
              value: id,
              groupValue: _paymentMethod,
              activeColor: AppColors.primary,
              onChanged: (val) {
                if (val != null) setState(() => _paymentMethod = val);
              },
            ),
          ],
        ),
      ),
    );
  }
}

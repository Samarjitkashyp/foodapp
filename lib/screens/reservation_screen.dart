import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';
import '../providers/reservation_provider.dart';

class ReservationScreen extends StatefulWidget {
  const ReservationScreen({super.key});

  @override
  State<ReservationScreen> createState() => _ReservationScreenState();
}

class _ReservationScreenState extends State<ReservationScreen> {
  final _formKey = GlobalKey<FormState>();

  final _nameCtrl = TextEditingController(text: 'Sameer Khan');
  final _phoneCtrl = TextEditingController(text: '+91 98765 43210');
  final _specialRequestsCtrl = TextEditingController();

  DateTime _selectedDate = DateTime.now().add(const Duration(days: 1));
  int _guests = 2;
  String _selectedSlot = '07:30 PM';

  final List<String> _slots = [
    '12:30 PM',
    '01:15 PM',
    '02:00 PM',
    '07:00 PM',
    '07:30 PM',
    '08:15 PM',
    '09:00 PM',
    '09:45 PM',
  ];

  String? _confirmedBookingId;

  @override
  void dispose() {
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    _specialRequestsCtrl.dispose();
    super.dispose();
  }

  void _bookTable() {
    if (!_formKey.currentState!.validate()) return;

    final provider = context.read<ReservationProvider>();
    final id = provider.createReservation(
      name: _nameCtrl.text,
      phone: _phoneCtrl.text,
      guests: _guests,
      date: _selectedDate,
      timeSlot: _selectedSlot,
      specialRequests: _specialRequestsCtrl.text.isNotEmpty ? _specialRequestsCtrl.text : null,
    );

    setState(() {
      _confirmedBookingId = id;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.pageBg,
      appBar: AppBar(
        backgroundColor: AppColors.cardBg,
        elevation: 0,
        title: Text('Table Reservation', style: AppStyles.headingMedium()),
      ),
      body: _confirmedBookingId != null ? _buildSuccessView() : _buildBookingForm(),
    );
  }

  Widget _buildBookingForm() {
    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Banner
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: AppColors.bannerGradient,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppColors.primaryLight.withValues(alpha: 0.4)),
            ),
            child: Row(
              children: [
                const Icon(Icons.table_restaurant_rounded, color: AppColors.primary, size: 32),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Dine at Crisp & Crave', style: AppStyles.headingSmall()),
                      Text(
                        'Reserve your artisanal dining experience with priority chef seating.',
                        style: AppStyles.bodySmall(color: AppColors.darkMuted),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Guest Count Selector
          Text('Number of Guests', style: AppStyles.headingSmall()),
          const SizedBox(height: 10),
          Row(
            children: List.generate(6, (index) {
              final count = index + 1;
              final isSelected = _guests == count;
              return Expanded(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 3),
                  child: ChoiceChip(
                    showCheckmark: false,
                    label: Text(
                      count == 6 ? '6+' : '$count',
                      style: AppStyles.bodyMedium(
                        color: isSelected ? Colors.white : AppColors.dark,
                        weight: FontWeight.w700,
                      ),
                    ),
                    selected: isSelected,
                    selectedColor: AppColors.primary,
                    backgroundColor: AppColors.cardBg,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                      side: BorderSide(color: isSelected ? AppColors.primary : AppColors.borderLight),
                    ),
                    onSelected: (_) => setState(() => _guests = count),
                  ),
                ),
              );
            }),
          ),
          const SizedBox(height: 20),

          // Date Picker
          Text('Select Date', style: AppStyles.headingSmall()),
          const SizedBox(height: 10),
          InkWell(
            onTap: () async {
              final picked = await showDatePicker(
                context: context,
                initialDate: _selectedDate,
                firstDate: DateTime.now(),
                lastDate: DateTime.now().add(const Duration(days: 30)),
              );
              if (picked != null) {
                setState(() => _selectedDate = picked);
              }
            },
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              decoration: BoxDecoration(
                color: AppColors.cardBg,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.borderLight),
              ),
              child: Row(
                children: [
                  const Icon(Icons.calendar_month_outlined, color: AppColors.primary, size: 20),
                  const SizedBox(width: 10),
                  Text(
                    DateFormat('EEEE, d MMMM yyyy').format(_selectedDate),
                    style: AppStyles.bodyMedium(color: AppColors.dark, weight: FontWeight.w700),
                  ),
                  const Spacer(),
                  const Icon(Icons.keyboard_arrow_down_rounded, color: AppColors.darkMuted),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),

          // Time Slot
          Text('Select Time Slot', style: AppStyles.headingSmall()),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _slots.map((slot) {
              final isSelected = _selectedSlot == slot;
              return ChoiceChip(
                showCheckmark: false,
                label: Text(
                  slot,
                  style: AppStyles.bodySmall(
                    color: isSelected ? Colors.white : AppColors.dark,
                    weight: isSelected ? FontWeight.w700 : FontWeight.w500,
                  ),
                ),
                selected: isSelected,
                selectedColor: AppColors.primary,
                backgroundColor: AppColors.cardBg,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                  side: BorderSide(color: isSelected ? AppColors.primary : AppColors.borderLight),
                ),
                onSelected: (_) => setState(() => _selectedSlot = slot),
              );
            }).toList(),
          ),
          const SizedBox(height: 20),

          // Contact Details
          Text('Your Contact Details', style: AppStyles.headingSmall()),
          const SizedBox(height: 10),
          TextFormField(
            controller: _nameCtrl,
            decoration: _inputDec('Your Name', Icons.person_outline),
            validator: (v) => v == null || v.isEmpty ? 'Required' : null,
          ),
          const SizedBox(height: 10),
          TextFormField(
            controller: _phoneCtrl,
            keyboardType: TextInputType.phone,
            decoration: _inputDec('Phone Number', Icons.phone_outlined),
            validator: (v) => v == null || v.isEmpty ? 'Required' : null,
          ),
          const SizedBox(height: 10),
          TextFormField(
            controller: _specialRequestsCtrl,
            maxLines: 2,
            decoration: _inputDec('Special Requests (e.g. Window seat, Birthday celebration)', Icons.cake_outlined),
          ),
          const SizedBox(height: 28),

          ElevatedButton(
            onPressed: _bookTable,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.check_circle_outline, size: 20),
                const SizedBox(width: 8),
                Text('Confirm Reservation', style: AppStyles.bodyLarge(color: Colors.white, weight: FontWeight.w700)),
              ],
            ),
          ),
          const SizedBox(height: 40),
        ],
      ),
    );
  }

  Widget _buildSuccessView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: const BoxDecoration(
                color: AppColors.primarySoft,
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.calendar_today_rounded, color: AppColors.primary, size: 50),
            ),
            const SizedBox(height: 20),
            Text('Table Reserved!', style: AppStyles.headingLarge()),
            const SizedBox(height: 8),
            Text(
              'Your booking reference is confirmed:',
              style: AppStyles.bodyMedium(color: AppColors.darkMuted),
            ),
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                color: AppColors.tagBg,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: AppColors.primary),
              ),
              child: Text(
                _confirmedBookingId!,
                style: AppStyles.headingMedium(color: AppColors.primary),
              ),
            ),
            const SizedBox(height: 14),
            Text(
              '$_guests Guests • ${DateFormat("MMM d, yyyy").format(_selectedDate)} at $_selectedSlot',
              style: AppStyles.bodyMedium(weight: FontWeight.w700),
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: () => Navigator.pop(context),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 12),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
              child: const Text('Done'),
            ),
          ],
        ),
      ),
    );
  }

  InputDecoration _inputDec(String label, IconData icon) {
    return InputDecoration(
      labelText: label,
      labelStyle: AppStyles.bodySmall(color: AppColors.darkMuted),
      prefixIcon: Icon(icon, size: 18, color: AppColors.darkMuted),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: AppColors.borderLight),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
    );
  }
}

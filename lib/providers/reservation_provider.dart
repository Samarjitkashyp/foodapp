import 'dart:convert';
import 'dart:math';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/reservation.dart';

class ReservationProvider extends ChangeNotifier {
  static const String _key = 'crisp_crave_reservations';
  List<Reservation> _reservations = [];

  ReservationProvider() {
    _loadFromPrefs();
  }

  List<Reservation> get reservations => _reservations;

  String createReservation({
    required String name,
    required String phone,
    required int guests,
    required DateTime date,
    required String timeSlot,
    String? specialRequests,
  }) {
    final randomNum = 1000 + Random().nextInt(9000);
    final bookingId = '#TB-$randomNum';

    final reservation = Reservation(
      bookingId: bookingId,
      customerName: name,
      phone: phone,
      guests: guests,
      date: date,
      timeSlot: timeSlot,
      specialRequests: specialRequests,
      createdAt: DateTime.now(),
    );

    _reservations.insert(0, reservation);
    _saveToPrefs();
    notifyListeners();

    return bookingId;
  }

  Future<void> _saveToPrefs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final list = _reservations.map((e) => e.toJson()).toList();
      await prefs.setString(_key, json.encode(list));
    } catch (e) {
      debugPrint('Error saving reservations: $e');
    }
  }

  Future<void> _loadFromPrefs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final saved = prefs.getString(_key);
      if (saved != null) {
        final list = json.decode(saved) as List<dynamic>;
        _reservations = list.map((e) => Reservation.fromJson(e as Map<String, dynamic>)).toList();
        notifyListeners();
      }
    } catch (e) {
      debugPrint('Error loading reservations: $e');
    }
  }
}

import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/order_model.dart';
import 'cart_provider.dart';

class UserProvider extends ChangeNotifier {
  static const String _userPrefsKey = 'crisp_crave_user_profile';
  static const String _ordersPrefsKey = 'crisp_crave_user_orders';

  String _name = 'Sameer Khan';
  String _phone = '+91 98765 43210';
  String _email = 'sameer.khan@example.com';
  String _address = 'Flat 402, Royal Palms Heights, Link Road';
  String _landmark = 'Near Green Valley Park';
  bool _isLoadingOrders = false;

  List<OrderModel> _orders = [];

  UserProvider() {
    _loadFromPrefs();
  }

  String get name => _name;
  String get phone => _phone;
  String get email => _email;
  String get address => _address;
  String get landmark => _landmark;
  bool get isLoadingOrders => _isLoadingOrders;
  List<OrderModel> get orders => _orders;
  int get totalOrdersCount => _orders.length;

  Future<void> _loadFromPrefs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userJson = prefs.getString(_userPrefsKey);
      if (userJson != null) {
        final map = json.decode(userJson) as Map<String, dynamic>;
        _name = map['name'] ?? _name;
        _phone = map['phone'] ?? _phone;
        _email = map['email'] ?? _email;
        _address = map['address'] ?? _address;
        _landmark = map['landmark'] ?? _landmark;
      }

      final ordersJson = prefs.getString(_ordersPrefsKey);
      if (ordersJson != null) {
        final list = json.decode(ordersJson) as List<dynamic>;
        _orders = list.map((e) => OrderModel.fromJson(e as Map<String, dynamic>)).toList();
      }

      notifyListeners();
      fetchLiveOrders();
    } catch (e) {
      debugPrint('Error loading user prefs: $e');
    }
  }

  Future<void> saveProfile({
    required String name,
    required String phone,
    String? email,
    required String address,
    String? landmark,
  }) async {
    _name = name.trim();
    _phone = phone.trim();
    _email = (email ?? _email).trim();
    _address = address.trim();
    _landmark = (landmark ?? _landmark).trim();

    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      final map = {
        'name': _name,
        'phone': _phone,
        'email': _email,
        'address': _address,
        'landmark': _landmark,
      };
      await prefs.setString(_userPrefsKey, json.encode(map));

      // Also sync to server API
      try {
        await http.post(
          Uri.parse('https://food.aiaxom.co.in/api/users.php'),
          headers: {'Content-Type': 'application/json'},
          body: json.encode(map),
        ).timeout(const Duration(seconds: 6));
      } catch (_) {}
    } catch (e) {
      debugPrint('Error saving user profile: $e');
    }
  }

  Future<void> fetchLiveOrders() async {
    if (_phone.isEmpty) return;
    _isLoadingOrders = true;
    notifyListeners();

    try {
      final res = await http.get(
        Uri.parse('https://food.aiaxom.co.in/api/orders.php?phone=${Uri.encodeComponent(_phone)}'),
        headers: {'Cache-Control': 'no-cache'},
      ).timeout(const Duration(seconds: 10));

      if (res.statusCode == 200) {
        final data = json.decode(res.body) as Map<String, dynamic>;
        if (data['success'] == true && data['orders'] != null) {
          final list = (data['orders'] as List<dynamic>)
              .map((e) => OrderModel.fromJson(e as Map<String, dynamic>))
              .toList();

          if (list.isNotEmpty) {
            _orders = list;
            final prefs = await SharedPreferences.getInstance();
            await prefs.setString(
              _ordersPrefsKey,
              json.encode(_orders.map((o) => o.toJson()).toList()),
            );
          }
        }
      }
    } catch (e) {
      debugPrint('Error fetching user live orders: $e');
    } finally {
      _isLoadingOrders = false;
      notifyListeners();
    }
  }

  Future<String?> submitOrderToServer({
    required CartProvider cart,
    required String paymentMethod,
    required String name,
    required String phone,
    required String address,
    String? landmark,
    String? cookingInstructions,
  }) async {
    // 1. Prepare items payload
    final itemsPayload = cart.items.map((i) => {
      'id': i.id,
      'name': i.name,
      'price': i.price,
      'quantity': i.quantity,
      'selected_addons': i.selectedAddons,
      'is_veg': i.isVeg,
    }).toList();

    final orderPayload = {
      'customer_name': name.trim(),
      'customer_phone': phone.trim(),
      'delivery_address': address.trim(),
      'landmark': (landmark ?? '').trim(),
      'items': itemsPayload,
      'subtotal': cart.subtotal,
      'delivery_fee': cart.deliveryFee,
      'tax_amount': cart.taxAmount,
      'discount_amount': cart.discountAmount,
      'coupon_code': cart.activeCoupon?.code ?? '',
      'grand_total': cart.grandTotal,
      'payment_method': paymentMethod,
      'cooking_instructions': (cookingInstructions ?? '').trim(),
      'source': 'mobile_app',
    };

    String generatedOrderNumber = 'CC-${10000 + DateTime.now().millisecondsSinceEpoch % 90000}';

    try {
      final res = await http.post(
        Uri.parse('https://food.aiaxom.co.in/api/create_order.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode(orderPayload),
      ).timeout(const Duration(seconds: 12));

      if (res.statusCode == 200) {
        final resData = json.decode(res.body) as Map<String, dynamic>;
        if (resData['success'] == true && resData['order_id'] != null) {
          generatedOrderNumber = resData['order_id'] as String;
        }
      }
    } catch (e) {
      debugPrint('Network error sending order to server: $e (Falling back to local cache)');
    }

    // 2. Create local OrderModel and prepend
    final newOrder = OrderModel(
      orderNumber: generatedOrderNumber,
      customerName: name.trim(),
      customerPhone: phone.trim(),
      deliveryAddress: address.trim(),
      landmark: (landmark ?? '').trim(),
      items: cart.items.map((i) => OrderItemSummary(
        id: i.id,
        name: i.name,
        price: i.price,
        quantity: i.quantity,
        selectedAddons: i.selectedAddons,
        isVeg: i.isVeg,
      )).toList(),
      subtotal: cart.subtotal,
      deliveryFee: cart.deliveryFee,
      taxAmount: cart.taxAmount,
      discountAmount: cart.discountAmount,
      couponCode: cart.activeCoupon?.code ?? '',
      grandTotal: cart.grandTotal,
      paymentMethod: paymentMethod,
      paymentStatus: (paymentMethod.toLowerCase() == 'upi') ? 'paid' : 'pending',
      orderStatus: 'preparing',
      cookingInstructions: cookingInstructions ?? '',
      createdAt: DateTime.now(),
    );

    _orders.insert(0, newOrder);
    notifyListeners();

    // Cache orders
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(
        _ordersPrefsKey,
        json.encode(_orders.map((o) => o.toJson()).toList()),
      );
    } catch (_) {}

    return generatedOrderNumber;
  }
}

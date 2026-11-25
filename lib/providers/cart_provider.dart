import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/cart_item.dart';
import '../models/coupon.dart';
import '../models/menu_item.dart';
import '../models/combo_deal.dart';

class CartProvider extends ChangeNotifier {
  static const String _storageKey = 'crisp_crave_cart_items';
  List<CartItem> _items = [];
  Coupon? _activeCoupon;
  bool _isStickyBarDismissed = false;

  // Dynamic Live Promo Coupons (Fetched from Admin MySQL API)
  List<Coupon> _availableCoupons = defaultCoupons;
  List<Coupon> get availableCoupons => _availableCoupons;

  static const List<Coupon> defaultCoupons = [
    Coupon(
      code: 'FIRST50',
      title: '50% OFF First Order',
      description: 'Get 50% discount up to ₹120 on orders above ₹249',
      discountPercent: 50,
      maxDiscount: 120,
      minOrder: 249,
    ),
    Coupon(
      code: 'CRUNCH20',
      title: '20% OFF Crispy Deal',
      description: 'Save 20% up to ₹150 on orders above ₹399',
      discountPercent: 20,
      maxDiscount: 150,
      minOrder: 399,
    ),
    Coupon(
      code: 'FREECHEF',
      title: 'Flat ₹100 Special',
      description: 'Flat ₹100 instant discount on orders above ₹499',
      discountType: 'flat',
      flatDiscount: 100,
      minOrder: 499,
    ),
  ];

  CartProvider() {
    _loadFromPrefs();
    fetchLiveCoupons();
  }

  void syncCoupons(List<Coupon> liveCoupons) {
    if (liveCoupons.isNotEmpty) {
      _availableCoupons = liveCoupons;
      notifyListeners();
    }
  }

  Future<void> fetchLiveCoupons() async {
    try {
      final res = await http
          .get(
            Uri.parse('https://food.aiaxom.co.in/api/coupons.php'),
            headers: {'Cache-Control': 'no-cache'},
          )
          .timeout(const Duration(seconds: 10));
      if (res.statusCode == 200) {
        final data = json.decode(res.body) as Map<String, dynamic>;
        if (data['success'] == true && data['coupons'] != null) {
          final list = (data['coupons'] as List<dynamic>)
              .map((c) => Coupon.fromJson(c as Map<String, dynamic>))
              .toList();
          if (list.isNotEmpty) {
            _availableCoupons = list;
            notifyListeners();
          }
        }
      }
    } catch (_) {}
  }

  List<CartItem> get items => _items;
  Coupon? get activeCoupon => _activeCoupon;
  bool get isStickyBarDismissed => _isStickyBarDismissed;

  void dismissStickyBar() {
    _isStickyBarDismissed = true;
    notifyListeners();
  }

  void showStickyBar() {
    _isStickyBarDismissed = false;
    notifyListeners();
  }

  int get totalItemCount => _items.fold(0, (sum, item) => sum + item.quantity);

  double get subtotal => _items.fold(0.0, (sum, item) => sum + item.totalPrice);

  double get deliveryFee {
    if (subtotal == 0) return 0.0;
    return subtotal >= 399.0 ? 0.0 : 40.0;
  }

  double get taxAmount {
    if (subtotal == 0) return 0.0;
    return (subtotal * 0.05).roundToDouble(); // 5% Fast Food GST
  }

  double get discountAmount {
    if (_activeCoupon == null || subtotal == 0) return 0.0;
    return _activeCoupon!.calculateDiscount(subtotal);
  }

  double get grandTotal {
    if (subtotal == 0) return 0.0;
    final total = subtotal + deliveryFee + taxAmount - discountAmount;
    return total > 0 ? total : 0.0;
  }

  bool isItemInCart(String id) {
    return _items.any((item) => item.id == id);
  }

  int getItemQuantity(String id) {
    final index = _items.indexWhere((item) => item.id == id);
    return index != -1 ? _items[index].quantity : 0;
  }

  void addItemFromMenu(MenuItem product, {int qty = 1, List<String> addons = const [], double addonsCost = 0.0}) {
    final existingIndex = _items.indexWhere((item) => item.id == product.id && _compareAddons(item.selectedAddons, addons));

    if (existingIndex > -1) {
      _items[existingIndex].quantity += qty;
    } else {
      _items.add(CartItem(
        id: product.id,
        name: product.name,
        price: product.price,
        image: product.image,
        isVeg: product.isVeg,
        quantity: qty,
        selectedAddons: addons,
        addonsTotal: addonsCost,
      ));
    }

    _isStickyBarDismissed = false;
    _saveToPrefs();
    notifyListeners();
  }

  void addComboDeal(ComboDeal combo) {
    final existingIndex = _items.indexWhere((item) => item.id == combo.id);
    if (existingIndex > -1) {
      _items[existingIndex].quantity += 1;
    } else {
      _items.add(CartItem(
        id: combo.id,
        name: combo.name,
        price: combo.price,
        image: combo.image,
        isVeg: false,
        quantity: 1,
      ));
    }
    _isStickyBarDismissed = false;
    _saveToPrefs();
    notifyListeners();
  }

  void updateQuantity(String id, int newQty) {
    if (newQty <= 0) {
      removeItem(id);
      return;
    }
    final index = _items.indexWhere((item) => item.id == id);
    if (index > -1) {
      if (newQty > _items[index].quantity) {
        _isStickyBarDismissed = false;
      }
      _items[index].quantity = newQty;
      _saveToPrefs();
      notifyListeners();
    }
  }

  void removeItem(String id) {
    _items.removeWhere((item) => item.id == id);
    if (_items.isEmpty) {
      _isStickyBarDismissed = false;
    }
    _saveToPrefs();
    notifyListeners();
  }

  Map<String, dynamic> applyCouponCode(String code) {
    final cleanCode = code.trim().toUpperCase();
    final found = availableCoupons.where((c) => c.code == cleanCode).toList();

    if (found.isEmpty) {
      final hint = _availableCoupons.isNotEmpty ? 'Try ${_availableCoupons.first.code}' : 'Please check the code';
      return {'success': false, 'message': 'Invalid coupon code. $hint'};
    }

    final coupon = found.first;
    if (subtotal < coupon.minOrder) {
      return {
        'success': false,
        'message': 'Min. order ₹${coupon.minOrder.toInt()} required for ${coupon.code}'
      };
    }

    _activeCoupon = coupon;
    notifyListeners();
    return {
      'success': true,
      'message': 'Coupon Applied! Saved ₹${discountAmount.toInt()}'
    };
  }

  void removeCoupon() {
    _activeCoupon = null;
    notifyListeners();
  }

  void clearCart() {
    _items.clear();
    _activeCoupon = null;
    _isStickyBarDismissed = false;
    _saveToPrefs();
    notifyListeners();
  }

  bool _compareAddons(List<String> a, List<String> b) {
    if (a.length != b.length) return false;
    for (int i = 0; i < a.length; i++) {
      if (a[i] != b[i]) return false;
    }
    return true;
  }

  Future<void> _saveToPrefs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final list = _items.map((e) => e.toJson()).toList();
      await prefs.setString(_storageKey, json.encode(list));
    } catch (e) {
      debugPrint('Error saving cart: $e');
    }
  }

  Future<void> _loadFromPrefs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final saved = prefs.getString(_storageKey);
      if (saved != null) {
        final list = json.decode(saved) as List<dynamic>;
        _items = list.map((e) => CartItem.fromJson(e as Map<String, dynamic>)).toList();
        notifyListeners();
      }
    } catch (e) {
      debugPrint('Error loading cart: $e');
    }
  }
}

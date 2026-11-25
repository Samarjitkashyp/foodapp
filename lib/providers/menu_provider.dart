import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;
import '../models/menu_item.dart';
import '../models/combo_deal.dart';
import '../models/banner_item.dart';
import '../models/category_item.dart';
import '../models/coupon.dart';

enum SortOption { popular, rating, priceLowToHigh, priceHighToLow }

class MenuProvider extends ChangeNotifier {
  List<MenuItem> _allItems = [];
  final List<ComboDeal> _allCombos = [];
  List<BannerItem> _banners = [];
  List<BannerItem> _offers = [];
  List<CategoryItem> _categories = defaultCategories;
  List<Coupon> _coupons = defaultCoupons;
  bool _isLoading = true;

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

  static const List<CategoryItem> defaultCategories = [
    CategoryItem(id: 'all', title: 'All Items', icon: Icons.grid_view_rounded),
    CategoryItem(id: 'chicken', title: 'Biryani & Chicken', icon: Icons.restaurant_rounded),
    CategoryItem(id: 'burgers', title: 'Gourmet Burgers', icon: Icons.lunch_dining_rounded),
    CategoryItem(id: 'fries', title: 'Loaded Fries', icon: Icons.fastfood_rounded),
    CategoryItem(id: 'wraps', title: 'Wraps & Bowls', icon: Icons.takeout_dining_rounded),
    CategoryItem(id: 'drinks', title: 'Thick Shakes', icon: Icons.local_cafe_rounded),
    CategoryItem(id: 'desserts', title: 'Desserts & Cakes', icon: Icons.icecream_rounded),
    CategoryItem(id: 'combos', title: 'Mega Combos', icon: Icons.local_fire_department_rounded),
  ];

  String _selectedCategory = 'all';
  bool _vegOnly = false;
  String _searchQuery = '';
  SortOption _sortOption = SortOption.popular;

  MenuProvider() {
    loadData();
  }

  bool get isLoading => _isLoading;
  List<MenuItem> get allItems => _allItems;
  List<ComboDeal> get allCombos => _allCombos;
  List<BannerItem> get banners => _banners;
  List<BannerItem> get offers => _offers;
  List<CategoryItem> get categories => _categories;
  List<Coupon> get coupons => _coupons;
  String get selectedCategory => _selectedCategory;
  bool get vegOnly => _vegOnly;
  String get searchQuery => _searchQuery;
  SortOption get sortOption => _sortOption;

  Future<void> loadData() async {
    _isLoading = true;
    notifyListeners();

    try {
      // Fetch 100% dynamic live data directly from MySQL API endpoints
      await Future.wait([
        _fetchLiveBanners(retryCount: 1),
        _fetchLiveOffers(retryCount: 1),
        _fetchLiveCategories(retryCount: 1),
        _fetchLiveFoodItems(retryCount: 1),
        _fetchLiveCoupons(retryCount: 1),
      ]);
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      debugPrint('Error loading live menu data: $e');
      if (_allItems.isEmpty) {
        try {
          final jsonString = await rootBundle.loadString('assets/data/menu_data.json');
          final data = json.decode(jsonString) as Map<String, dynamic>;
          _allItems = (data['menu'] as List<dynamic>)
              .map((item) => MenuItem.fromJson(item as Map<String, dynamic>))
              .toList();
        } catch (_) {}
      }
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> _fetchLiveBanners({int retryCount = 1}) async {
    try {
      final res = await http
          .get(
            Uri.parse('https://food.aiaxom.co.in/api/banners.php'),
            headers: {'Cache-Control': 'no-cache'},
          )
          .timeout(const Duration(seconds: 12));
      if (res.statusCode == 200) {
        final data = json.decode(res.body) as Map<String, dynamic>;
        if (data['success'] == true && data['banners'] != null) {
          final list = (data['banners'] as List<dynamic>)
              .map((b) => BannerItem.fromJson(b as Map<String, dynamic>))
              .toList();
          if (list.isNotEmpty) {
            _banners = list;
            notifyListeners();
            debugPrint('Live banners successfully loaded from server: ${_banners.length} banners');
            return;
          }
        }
      }
    } catch (e) {
      debugPrint('Attempt to fetch live banners failed: $e');
      if (retryCount > 0) {
        await Future.delayed(const Duration(milliseconds: 1500));
        return _fetchLiveBanners(retryCount: retryCount - 1);
      }
    }
  }

  Future<void> _fetchLiveCategories({int retryCount = 1}) async {
    try {
      final res = await http
          .get(
            Uri.parse('https://food.aiaxom.co.in/api/categories.php'),
            headers: {'Cache-Control': 'no-cache'},
          )
          .timeout(const Duration(seconds: 12));
      if (res.statusCode == 200) {
        final data = json.decode(res.body) as Map<String, dynamic>;
        if (data['success'] == true && data['categories'] != null) {
          final list = (data['categories'] as List<dynamic>)
              .map((c) => CategoryItem.fromJson(c as Map<String, dynamic>))
              .toList();
          if (list.isNotEmpty) {
            _categories = [
              const CategoryItem(id: 'all', title: 'All Items', icon: Icons.grid_view_rounded),
              ...list,
            ];
            notifyListeners();
            debugPrint('Live categories successfully loaded: ${_categories.length} categories');
            return;
          }
        }
      }
    } catch (e) {
      debugPrint('Attempt to fetch live categories failed: $e');
      if (retryCount > 0) {
        await Future.delayed(const Duration(milliseconds: 1500));
        return _fetchLiveCategories(retryCount: retryCount - 1);
      }
    }
  }

  Future<void> _fetchLiveOffers({int retryCount = 1}) async {
    try {
      final res = await http
          .get(
            Uri.parse('https://food.aiaxom.co.in/api/offers.php'),
            headers: {'Cache-Control': 'no-cache'},
          )
          .timeout(const Duration(seconds: 12));
      if (res.statusCode == 200) {
        final data = json.decode(res.body) as Map<String, dynamic>;
        if (data['success'] == true && data['offers'] != null) {
          final list = (data['offers'] as List<dynamic>)
              .map((b) => BannerItem.fromJson(b as Map<String, dynamic>))
              .toList();
          if (list.isNotEmpty) {
            _offers = list;
            notifyListeners();
            debugPrint('Live offers successfully loaded: ${_offers.length} offers');
            return;
          }
        }
      }
    } catch (e) {
      debugPrint('Attempt to fetch live offers failed: $e');
      if (retryCount > 0) {
        await Future.delayed(const Duration(milliseconds: 1500));
        return _fetchLiveOffers(retryCount: retryCount - 1);
      }
    }
  }

  Future<void> _fetchLiveFoodItems({int retryCount = 1}) async {
    try {
      final res = await http
          .get(
            Uri.parse('https://food.aiaxom.co.in/api/food_items.php'),
            headers: {'Cache-Control': 'no-cache'},
          )
          .timeout(const Duration(seconds: 12));
      if (res.statusCode == 200) {
        final data = json.decode(res.body) as Map<String, dynamic>;
        if (data['success'] == true && data['items'] != null) {
          final list = (data['items'] as List<dynamic>)
              .map((item) => MenuItem.fromJson(item as Map<String, dynamic>))
              .toList();
          if (list.isNotEmpty) {
            _allItems = list;
            notifyListeners();
            debugPrint('Live food items loaded from server: ${_allItems.length} items');
            return;
          }
        }
      }
    } catch (e) {
      debugPrint('Attempt to fetch live food items failed: $e');
      if (retryCount > 0) {
        await Future.delayed(const Duration(milliseconds: 1500));
        return _fetchLiveFoodItems(retryCount: retryCount - 1);
      }
    }
  }

  Future<void> _fetchLiveCoupons({int retryCount = 1}) async {
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
            _coupons = list;
            notifyListeners();
            debugPrint('Live coupons successfully loaded: ${_coupons.length} coupons');
            return;
          }
        }
      }
    } catch (e) {
      debugPrint('Attempt to fetch live coupons failed: $e');
      if (retryCount > 0) {
        await Future.delayed(const Duration(milliseconds: 1500));
        return _fetchLiveCoupons(retryCount: retryCount - 1);
      }
    }
  }

  Future<void> refreshBanners() async {
    await Future.wait([
      _fetchLiveBanners(retryCount: 1),
      _fetchLiveOffers(retryCount: 1),
      _fetchLiveCategories(retryCount: 1),
      _fetchLiveFoodItems(retryCount: 1),
      _fetchLiveCoupons(retryCount: 1),
    ]);
  }

  void setCategory(String category) {
    _selectedCategory = category;
    notifyListeners();
  }

  void toggleVegOnly() {
    _vegOnly = !_vegOnly;
    notifyListeners();
  }

  void setSearchQuery(String query) {
    _searchQuery = query;
    notifyListeners();
  }

  void setSortOption(SortOption option) {
    _sortOption = option;
    notifyListeners();
  }

  List<MenuItem> get filteredItems {
    List<MenuItem> items = List.from(_allItems);

    // Category filter
    if (_selectedCategory != 'all') {
      items = items.where((item) => item.category.toLowerCase() == _selectedCategory.toLowerCase()).toList();
    }

    // Veg only filter
    if (_vegOnly) {
      items = items.where((item) => item.isVeg).toList();
    }

    // Search query
    if (_searchQuery.trim().isNotEmpty) {
      final q = _searchQuery.toLowerCase();
      items = items.where((item) {
        return item.name.toLowerCase().contains(q) ||
            item.description.toLowerCase().contains(q) ||
            item.category.toLowerCase().contains(q) ||
            item.ingredients.any((ing) => ing.toLowerCase().contains(q));
      }).toList();
    }

    // Sorting
    switch (_sortOption) {
      case SortOption.rating:
        items.sort((a, b) => b.rating.compareTo(a.rating));
        break;
      case SortOption.priceLowToHigh:
        items.sort((a, b) => a.price.compareTo(b.price));
        break;
      case SortOption.priceHighToLow:
        items.sort((a, b) => b.price.compareTo(a.price));
        break;
      case SortOption.popular:
        items.sort((a, b) => b.reviewsCount.compareTo(a.reviewsCount));
        break;
    }

    return items;
  }

  List<MenuItem> get bestsellers =>
      _allItems.where((item) => item.badge == 'Bestseller' || item.rating >= 4.9).take(6).toList();

  List<MenuItem> get chefSpecials =>
      _allItems.where((item) => item.badge != null && (item.badge!.contains('Special') || item.badge!.contains('Royal'))).toList();

  MenuItem? findById(String id) {
    try {
      return _allItems.firstWhere((item) => item.id == id);
    } catch (_) {
      return null;
    }
  }
}

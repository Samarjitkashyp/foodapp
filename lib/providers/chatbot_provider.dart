import 'package:flutter/material.dart';
import '../models/menu_item.dart';

class ChatMessage {
  final String text;
  final bool isUser;
  final DateTime timestamp;
  final List<MenuItem>? suggestedItems;

  ChatMessage({
    required this.text,
    required this.isUser,
    required this.timestamp,
    this.suggestedItems,
  });
}

class ChatbotProvider extends ChangeNotifier {
  final List<ChatMessage> _messages = [];
  bool _isTyping = false;

  ChatbotProvider() {
    _initWelcome();
  }

  List<ChatMessage> get messages => _messages;
  bool get isTyping => _isTyping;

  final List<String> quickPrompts = [
    '🍔 Juicy Burgers',
    '🍗 Dum Biryani',
    '🥟 Spicy Momos',
    '🌱 Pure Veg',
    '🔥 Under ₹250',
    '🥤 Shakes & Chill',
  ];

  void _initWelcome() {
    _messages.add(
      ChatMessage(
        text: "Hey foodie! 👋 I'm CraveBot AI, your personal culinary concierge at Crisp & Crave.\n\nWhat are you craving today? You can tap a quick suggestion below or ask me for spicy, cheesy, sweet, or budget-friendly meals!",
        isUser: false,
        timestamp: DateTime.now(),
      ),
    );
  }

  void sendMessage(String query, List<MenuItem> allItems) async {
    if (query.trim().isEmpty) return;

    // Add user message
    _messages.add(
      ChatMessage(
        text: query,
        isUser: true,
        timestamp: DateTime.now(),
      ),
    );
    notifyListeners();

    _isTyping = true;
    notifyListeners();

    // Small delay to simulate AI thinking
    await Future.delayed(const Duration(milliseconds: 600));

    final reply = _generateResponse(query, allItems);
    _messages.add(reply);
    _isTyping = false;
    notifyListeners();
  }

  ChatMessage _generateResponse(String query, List<MenuItem> items) {
    final q = query.toLowerCase();

    // 1. Momos
    if (q.contains('momo') || q.contains('dumpling')) {
      final matches = items.where((i) => i.id.contains('momo') || i.name.toLowerCase().contains('momo')).toList();
      return ChatMessage(
        text: "Ah, craving handmade Himalayan momos! 🥟 Our Steamed & Crispy Juicy Momos are freshly packed with succulent minced filling and served with fiery garlic chili dip. Check these out:",
        isUser: false,
        timestamp: DateTime.now(),
        suggestedItems: matches.isNotEmpty ? matches : items.take(2).toList(),
      );
    }

    // 2. Biryani
    if (q.contains('biryani') || q.contains('rice')) {
      final matches = items.where((i) => i.id.contains('biryani') || i.name.toLowerCase().contains('biryani') || i.name.toLowerCase().contains('rice')).toList();
      return ChatMessage(
        text: "Nothing beats a fragrant handi of Biryani! 🍗 Our Royal Hyderabadi Chicken Dum Biryani is cooked Dum Pukht with aged basmati, saffron, and tender chicken. Highly recommended:",
        isUser: false,
        timestamp: DateTime.now(),
        suggestedItems: matches,
      );
    }

    // 3. Burger
    if (q.contains('burger') || q.contains('patty') || q.contains('zinger')) {
      final matches = items.where((i) => i.category == 'burgers').toList();
      return ChatMessage(
        text: "Craving a gourmet burger? 🍔 Try The Grand Smokehouse with double cheddar or the Fiery Zinger with crispy Louisiana glaze!",
        isUser: false,
        timestamp: DateTime.now(),
        suggestedItems: matches.take(3).toList(),
      );
    }

    // 4. Veg
    if (q.contains('veg') || q.contains('paneer') || q.contains('vegetarian')) {
      final matches = items.where((i) => i.isVeg).toList();
      return ChatMessage(
        text: "Here are our chef's top vegetarian delights! 🌱 Clean, fresh, and bursting with flavor:",
        isUser: false,
        timestamp: DateTime.now(),
        suggestedItems: matches.take(3).toList(),
      );
    }

    // 5. Budget / Under 250
    if (q.contains('budget') || q.contains('cheap') || q.contains('250') || q.contains('under')) {
      final matches = items.where((i) => i.price <= 249).toList();
      return ChatMessage(
        text: "Delicious bites that are easy on your pocket! 🔥 Everything here is under ₹250:",
        isUser: false,
        timestamp: DateTime.now(),
        suggestedItems: matches.take(4).toList(),
      );
    }

    // 6. Drinks / Shakes / Sweet
    if (q.contains('drink') || q.contains('shake') || q.contains('beverage') || q.contains('sweet') || q.contains('dessert')) {
      final matches = items.where((i) => i.category == 'drinks' || i.category == 'desserts').toList();
      return ChatMessage(
        text: "Cool down with our thick gourmet milkshakes and chilled beverages! 🥤 Decadent Belgian Chocolate and Salted Caramel Biscoff are customer favorites:",
        isUser: false,
        timestamp: DateTime.now(),
        suggestedItems: matches.take(3).toList(),
      );
    }

    // Default recommendation
    final best = items.where((i) => i.badge == 'Bestseller' || i.rating >= 4.8).take(3).toList();
    return ChatMessage(
      text: "I picked out our highest-rated customer favorites for you! ✨ What caught your eye?",
      isUser: false,
      timestamp: DateTime.now(),
      suggestedItems: best,
    );
  }

  void resetChat() {
    _messages.clear();
    _initWelcome();
    notifyListeners();
  }
}

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../constants/app_colors.dart';
import '../constants/app_styles.dart';
import '../providers/chatbot_provider.dart';
import '../providers/cart_provider.dart';
import '../providers/menu_provider.dart';
import '../widgets/app_network_image.dart';
import '../widgets/veg_badge.dart';

class CravebotScreen extends StatefulWidget {
  const CravebotScreen({super.key});

  @override
  State<CravebotScreen> createState() => _CravebotScreenState();
}

class _CravebotScreenState extends State<CravebotScreen> {
  final TextEditingController _msgCtrl = TextEditingController();
  final ScrollController _scrollCtrl = ScrollController();

  @override
  void dispose() {
    _msgCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollCtrl.hasClients) {
        _scrollCtrl.animateTo(
          _scrollCtrl.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  void _send(ChatbotProvider bot, MenuProvider menu) {
    final text = _msgCtrl.text.trim();
    if (text.isEmpty) return;
    _msgCtrl.clear();
    bot.sendMessage(text, menu.allItems);
    _scrollToBottom();
  }

  @override
  Widget build(BuildContext context) {
    final menu = context.read<MenuProvider>();

    return Scaffold(
      backgroundColor: AppColors.pageBg,
      appBar: AppBar(
        backgroundColor: AppColors.cardBg,
        elevation: 0,
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: const BoxDecoration(
                color: AppColors.primarySoft,
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.smart_toy_rounded, color: AppColors.primary, size: 20),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text('CraveBot AI', style: AppStyles.headingSmall()),
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: AppColors.accent,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        'AI Concierge',
                        style: AppStyles.bodySmall(color: Colors.white, weight: FontWeight.w800),
                      ),
                    ),
                  ],
                ),
                Text(
                  'Culinary recommendations for you',
                  style: AppStyles.bodySmall(color: AppColors.darkSubtle),
                ),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.restart_alt_rounded, color: AppColors.darkMuted),
            tooltip: 'Restart Conversation',
            onPressed: () {
              context.read<ChatbotProvider>().resetChat();
            },
          ),
        ],
      ),
      body: Consumer<ChatbotProvider>(
        builder: (context, bot, child) {
          return Column(
            children: [
              // Chat Messages List
              Expanded(
                child: ListView.builder(
                  controller: _scrollCtrl,
                  padding: const EdgeInsets.all(16),
                  itemCount: bot.messages.length + (bot.isTyping ? 1 : 0),
                  itemBuilder: (context, index) {
                    if (index == bot.messages.length) {
                      return _buildTypingIndicator();
                    }
                    final msg = bot.messages[index];
                    return _buildMessageBubble(msg);
                  },
                ),
              ),

              // Quick Prompts Bar
              Container(
                color: AppColors.cardBg,
                height: 48,
                child: ListView.separated(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                  scrollDirection: Axis.horizontal,
                  itemCount: bot.quickPrompts.length,
                  separatorBuilder: (_, _) => const SizedBox(width: 8),
                  itemBuilder: (context, index) {
                    final prompt = bot.quickPrompts[index];
                    return ActionChip(
                      label: Text(
                        prompt,
                        style: AppStyles.bodySmall(
                          color: AppColors.dark,
                          weight: FontWeight.w600,
                        ),
                      ),
                      backgroundColor: AppColors.surfaceSubtle,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                        side: const BorderSide(color: AppColors.borderLight),
                      ),
                      onPressed: () {
                        bot.sendMessage(prompt, menu.allItems);
                        _scrollToBottom();
                      },
                    );
                  },
                ),
              ),

              // Input Bar
              Container(
                padding: const EdgeInsets.all(12),
                color: AppColors.cardBg,
                child: SafeArea(
                  child: Row(
                    children: [
                      Expanded(
                        child: Container(
                          decoration: BoxDecoration(
                            color: AppColors.surfaceSubtle,
                            borderRadius: BorderRadius.circular(24),
                            border: Border.all(color: AppColors.borderLight),
                          ),
                          child: TextField(
                            controller: _msgCtrl,
                            onSubmitted: (_) => _send(bot, menu),
                            decoration: InputDecoration(
                              hintText: 'Ask CraveBot (e.g. spicy momos, cheese burgers)...',
                              hintStyle: AppStyles.bodySmall(color: AppColors.darkSubtle),
                              border: InputBorder.none,
                              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      CircleAvatar(
                        backgroundColor: AppColors.primary,
                        child: IconButton(
                          icon: const Icon(Icons.send_rounded, color: Colors.white, size: 18),
                          onPressed: () => _send(bot, menu),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildTypingIndicator() {
    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: AppColors.cardBg,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppColors.borderLight),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(
              width: 14,
              height: 14,
              child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.primary),
            ),
            const SizedBox(width: 10),
            Text(
              'CraveBot is crafting culinary picks...',
              style: AppStyles.bodySmall(color: AppColors.darkMuted),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMessageBubble(ChatMessage msg) {
    return Align(
      alignment: msg.isUser ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 14),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.85,
        ),
        child: Column(
          crossAxisAlignment: msg.isUser ? CrossAxisAlignment.end : CrossAxisAlignment.start,
          children: [
            // Message bubble
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: msg.isUser ? AppColors.primary : AppColors.cardBg,
                borderRadius: BorderRadius.only(
                  topLeft: const Radius.circular(16),
                  topRight: const Radius.circular(16),
                  bottomLeft: Radius.circular(msg.isUser ? 16 : 4),
                  bottomRight: Radius.circular(msg.isUser ? 4 : 16),
                ),
                border: msg.isUser ? null : Border.all(color: AppColors.borderLight),
                boxShadow: AppStyles.cardShadow,
              ),
              child: Text(
                msg.text,
                style: AppStyles.bodyMedium(
                  color: msg.isUser ? Colors.white : AppColors.dark,
                  weight: FontWeight.w500,
                  height: 1.4,
                ),
              ),
            ),

            // Suggested dish cards if any
            if (msg.suggestedItems != null && msg.suggestedItems!.isNotEmpty) ...[
              const SizedBox(height: 10),
              Column(
                children: msg.suggestedItems!.map((item) {
                  return Container(
                    margin: const EdgeInsets.only(bottom: 8),
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: AppColors.cardBg,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: AppColors.primaryLight.withValues(alpha: 0.5)),
                      boxShadow: AppStyles.cardShadow,
                    ),
                    child: Row(
                      children: [
                        VegBadge(isVeg: item.isVeg, size: 14),
                        const SizedBox(width: 8),
                        AppNetworkImage(
                          imagePath: item.image,
                          width: 50,
                          height: 50,
                          fit: BoxFit.cover,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                item.name,
                                style: AppStyles.bodySmall(color: AppColors.dark, weight: FontWeight.w700),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 2),
                              Row(
                                children: [
                                  Text(
                                    '₹${item.price.toInt()}',
                                    style: AppStyles.priceMain(fontSize: 14),
                                  ),
                                  const SizedBox(width: 6),
                                  const Icon(Icons.star_rounded, color: AppColors.starRating, size: 13),
                                  Text(
                                    item.rating.toStringAsFixed(1),
                                    style: AppStyles.bodySmall(color: AppColors.darkMuted, weight: FontWeight.w700),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                        Consumer<CartProvider>(
                          builder: (context, cart, _) {
                            return ElevatedButton(
                              onPressed: () {
                                cart.addItemFromMenu(item);
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text('Added ${item.name} to cart!'),
                                    backgroundColor: AppColors.dark,
                                    duration: const Duration(seconds: 1),
                                  ),
                                );
                              },
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppColors.primary,
                                foregroundColor: Colors.white,
                                elevation: 0,
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                minimumSize: Size.zero,
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                              ),
                              child: const Text('Add', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                            );
                          },
                        ),
                      ],
                    ),
                  );
                }).toList(),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

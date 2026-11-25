# 🍔 Crisp & Crave - Restaurant Mobile App & Admin Management System

A full-stack, production-ready restaurant food ordering mobile application built with **Flutter** (Android/iOS) and backed by a dynamic **PHP / MySQL** Admin Management Portal with REST APIs.

---

## 🚀 Features

### 📱 Mobile App (Flutter)
- **Interactive Home & Menu Catalog**: Categorized food items, live banners, special chef recommendations, and real-time search with chips filter.
- **Portion & Addons System**: 
  - Dynamic piece/portion sizes (e.g. 4 Pcs, 1 Portion) directly from the admin panel.
  - Interactive `+` and `-` quantity steppers for addons (e.g. Extra Cheese, Sauces) with real-time price calculation.
- **Cart & Dynamic Coupons**: Promo codes with instant discount calculations and sticky checkout bar.
- **Order Placement**: Real-time order dispatch to MySQL backend with full customer, delivery, and itemized addon tracking.
- **User Account Management**: Order history, delivery addresses, and profile sync.
- **Favorites / Wishlist**: Instant bookmarking of preferred dishes with local persistence.

### 💻 Web Admin Portal (PHP / MySQL)
- **Live Food Catalog Management**: Add, edit, and delete food items with images, badges, pieces/portion sizes, and dynamic product addons repeater.
- **Orders Dashboard**: Live order management with status workflow (`Pending` ➔ `Confirmed` ➔ `Preparing` ➔ `Out for Delivery` ➔ `Delivered` ➔ `Cancelled`).
- **Promotions & Offers**: Manage hero banners, coupon codes, and featured discounts.
- **User Management**: View all registered customers and their full order histories.

---

## 🛠️ Getting Started on Any Computer

Follow these simple steps to run this project on any new machine:

### 1. Prerequisites
- [Flutter SDK](https://docs.flutter.dev/get-started/install) (v3.24+ recommended)
- Android Studio / VS Code with Flutter extension
- Connected Android Device or Emulator

### 2. Clone the Repository
```bash
git clone https://github.com/Samarjitkashyp/foodapp.git
cd foodapp
```

### 3. Install Flutter Dependencies
```bash
flutter pub get
```

### 4. Run the Mobile App
```bash
# Run on connected device or emulator
flutter run
```
> **Note**: The mobile app is pre-configured to communicate with the live production backend API (`https://food.aiaxom.co.in/api/`), so the app works immediately out-of-the-box on any computer or mobile device!

---

## 🔐 Database & Backend Setup (Optional for Local Backend Development)

If you wish to run the `server_admin` PHP backend locally:
1. Copy the database credentials template:
   ```bash
   cp server_admin/db_credentials.example.php server_admin/db_credentials.php
   ```
2. Open `server_admin/db_credentials.php` and configure your local MySQL database credentials.
3. Import your schema and host with PHP built-in server or Apache:
   ```bash
   php -S localhost:8000 -t server_admin/
   ```

*(Note: `db_credentials.php` is ignored by Git for security and will never be committed).*

---

## 📄 License
Crisp & Crave Restaurant System © 2026. All rights reserved.

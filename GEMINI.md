# Hida Makeup & Cosmetics - Master Instructions

You are a senior PHP developer.

## Goal
Convert the static HTML templates in `/templates` into a complete, dynamic, maintainable and highly customizable PHP 8+ / MySQL e-commerce website.

## Core Principles
- Keep the visual design as close as possible to the original HTML templates
- Make the project extremely easy to customize and clone for future clients
- Centralize all brand settings (name, colors, WhatsApp, currency, etc.)
- Write clean, secure, and professional code

## Tech Stack
- Pure PHP 8+
- MySQL + PDO
- Cookie-based cart + favorites (signed cookies)
- XAMPP (Windows)

## Customization System (Critical)
All brand settings must be controlled from:
1. `includes/config.php` → Site name, WhatsApp, phone, currency, delivery fee, social links...
2. `assets/css/style.css` → All colors and design tokens using CSS variables

## Required Structure
hida-makeup/
├── templates/               ← Keep original HTML files
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   ├── responsive.css
│   │   └── rtl.css
│   ├── js/main.js
│   └── images/
│       └── products/       ← Product photos (drop files named after the slug)
├── includes/
│   ├── config.php
│   ├── db.php
│   ├── store.php          ← Cookie cart & favorites
│   ├── media.php          ← Product image resolver & uploads
│   ├── header.php
│   └── footer.php
├── admin/
│   ├── _layout.php        ← Mobile-first shell, nav, helpers
│   ├── index.php          ← Dashboard: stats, quick actions, orders
│   ├── products.php       ← Searchable product list + quick actions
│   └── product-form.php   ← Create / edit a product + photo slots
├── database/hida.sql
├── index.php
├── products.php
├── product.php
├── cart.php
├── checkout.php
├── favorites.php
└── GEMINI.md

## Rules
- Follow the conversion plan phase by phase
- Always use prepared statements
- Prefer simple and maintainable solutions
- Make everything dynamic

## Cart & Favorites Storage
- The cart and the favorites list live in the visitor's browser as **signed cookies** (`includes/store.php`)
- Only product IDs and quantities are stored; names, images and prices are always re-read from MySQL, so a tampered cookie can never change what a customer pays
- Confirmed orders are always written to MySQL (`orders` + `order_items`)
- Sessions are only used for short flash notices

## Product Photos (Critical)
- All product photos live in `assets/images/products/` and are resolved by `includes/media.php`
- **A local file always wins over the `main_image` URL stored in the database**
- Name files after the product slug: `<slug>.jpg` (main) and `<slug>-1.jpg` ... `<slug>-6.jpg` (gallery). The product id works too (`8.jpg`, `8-1.jpg`)
- Drop files in by hand, or upload them from `admin/product-form.php`
- JPG / PNG / WebP / GIF, 3 MB max, 200 × 200 px minimum

## Admin Panel (mobile first)
- `assets/css/admin.css` is written phone-first; everything above the `min-width` queries is the phone layout
- Phones get stat cards, tap-sized controls (44px minimum) and a fixed bottom tab bar; tablets and desktops get a top nav and real data tables (the same markup, `data-label` attributes become the row headers)
- Every admin page starts with `require_once __DIR__ . '/_layout.php'`, which pulls in `includes/db.php` and provides `admin_*()` helpers
- Add or edit products from `admin/products.php`; uploads go to the same `assets/images/products/` folder described above
- Renaming a product moves its uploaded photos to the new slug automatically
- Deleting a product also deletes its photos; past orders keep their line items (`order_items.product_id` becomes NULL)
- Every page renders images through `product_image_url()` / `product_gallery_urls()` — never echo `main_image` directly
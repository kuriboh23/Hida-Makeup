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
- Session-based cart + favorites
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
├── includes/
│   ├── config.php
│   ├── db.php
│   ├── header.php
│   └── footer.php
├── admin/
├── database/hida.sql
├── index.php
├── products.php
├── category.php
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
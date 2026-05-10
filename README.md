# 🏠 HomeServe — Household Services Platform

> A full-featured, multi-role PHP/MySQL web platform connecting customers with verified household service providers. Built for local use in Tanzania with dual-currency (USD/TZS) support and Swahili language localization.

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [User Roles](#-user-roles)
- [Services Catalogue](#-services-catalogue)
- [Database Schema](#-database-schema)
- [Installation & Setup](#-installation--setup)
- [Default Credentials](#-default-credentials)
- [Payment Methods](#-payment-methods)
- [Currency & Localization](#-currency--localization)
- [File Upload Paths](#-file-upload-paths)
- [Troubleshooting](#-troubleshooting)
- [Contact](#-contact)

---

## 🌟 Overview

**HomeServe** is a web-based marketplace that lets households in Tanzania find, book, and pay for trusted home service professionals — from plumbers and electricians to cleaners and pest controllers. The platform manages the full lifecycle of a service job: discovery → booking → assignment → payment → review.

It runs on a standard **XAMPP** (Apache + PHP + MariaDB) stack with no external frameworks — pure PHP, vanilla CSS, and vanilla JavaScript.

---

## ✨ Features

### For Customers
- Browse 15+ service categories on a modern, responsive homepage
- View verified service providers with ratings and public profiles
- Book a service by selecting a provider, date, address, and notes
- Track booking status in real-time (`pending → accepted → in_progress → completed`)
- Pay via multiple methods: Card, M-Pesa, Airtel, T-Pesa, HaloPesa, Mixx/Yas, or Cash on Completion
- Cancel bookings and leave star ratings with comments after completion
- In-booking real-time chat with the assigned provider
- Notification center for booking updates
- Profile management with photo upload

### For Service Providers
- Register with service type, bio, hourly rate, and experience years
- Await admin approval before becoming visible to customers
- Accept or decline incoming booking requests
- Update booking status through the job lifecycle
- In-booking chat with the customer
- Public profile page viewable by customers
- Profile, availability, and settings management
- Receive notifications for new bookings

### For Admins
- Full admin dashboard with live stats (Customers, Providers, Bookings, Revenue in USD + TZS)
- Provider approval queue — view applicant bios, approve, or reject with a reason
- Force-complete or force-cancel any booking
- Manage all services (add, edit, delete)
- View all customers and providers with contact details
- Moderate and manage all reviews
- Dynamically adjust the global **USD → TZS exchange rate** (fairness-locked per booking)
- Revenue breakdown by service and payer

### For Delivery Partners *(stub module)*
- Separate login and dashboard for delivery agents handling moving/transport jobs

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| **Server** | Apache (XAMPP) |
| **Backend** | PHP 8+ (PDO for database access) |
| **Database** | MariaDB / MySQL |
| **Frontend** | HTML5, Vanilla CSS, Vanilla JavaScript |
| **Icons** | Font Awesome 6.4 |
| **Fonts** | Google Fonts — Inter |
| **Session** | PHP native sessions |

---

## 📁 Project Structure

```
household_services_platform/
│
├── index.php                  # Public homepage — hero, services, features, footer
├── public_profile.php         # Public-facing provider profile page
├── database.sql               # Full DB schema + seed data (import to restore)
├── restore_database.php       # DB restoration helper script
│
├── config/
│   └── db_connect.php         # PDO database connection (host, dbname, credentials)
│
├── includes/
│   └── language.php           # Language loader, exchange rate fetcher, formatPrice()
│
├── lang/
│   ├── en.php                 # English language strings
│   └── sw.php                 # Swahili language strings
│
├── admin/
│   ├── login.php              # Admin login page
│   ├── dashboard.php          # Admin control center
│   ├── customers.php          # Full customer management
│   ├── providers.php          # Full provider management
│   ├── services.php           # Service CRUD management
│   └── reviews.php            # Review moderation
│
├── customer/
│   ├── login.php              # Customer login
│   ├── register.php           # Customer registration
│   ├── logout.php             # Session destroy & redirect
│   ├── dashboard.php          # Customer home — recent bookings, stats
│   ├── service_providers.php  # Browse providers by service
│   ├── book.php               # Booking form (date, address, notes)
│   ├── my_bookings.php        # All customer bookings list
│   ├── booking_details.php    # Single booking detail & status
│   ├── payment.php            # Checkout page — select payment method
│   ├── process_payment.php    # Payment handler & booking status updater
│   ├── payment_success.php    # Payment receipt / success page
│   ├── cancel_booking.php     # Booking cancellation handler
│   ├── review_booking.php     # Star rating + comment form
│   ├── submit_review.php      # Review submission handler
│   ├── chat.php               # In-booking customer-provider chat
│   ├── notifications.php      # Customer notification center
│   ├── profile.php            # Profile view & photo management
│   └── settings.php           # Account settings (name, email, password, phone)
│
├── provider/
│   ├── login.php              # Provider login
│   ├── register.php           # Provider registration (requires admin approval)
│   ├── dashboard.php          # Provider home — incoming bookings, stats
│   ├── chat.php               # In-booking provider-customer chat
│   ├── notifications.php      # Provider notification center
│   ├── profile.php            # Profile management + photo upload
│   ├── resubmit.php           # Re-apply after admin rejection
│   └── settings.php           # Account settings
│
├── delivery/
│   ├── login.php              # Delivery agent login
│   └── dashboard.php          # Delivery agent dashboard
│
├── api/
│   └── chat_handler.php       # AJAX endpoint for sending/receiving chat messages
│
├── assets/
│   ├── css/
│   │   └── style.css          # Global stylesheet (design system, components)
│   ├── img/                   # Static images (hero bg, service photos, footer bg)
│   │   ├── about-bg.jpg
│   │   ├── hero-image.jpg
│   │   ├── footer-bg.jpg
│   │   ├── cleaning.jpg
│   │   ├── laundry.jpg
│   │   ├── plumbing.jpg
│   │   ├── electrical.jpg
│   │   ├── painting.jpg
│   │   └── carpentry.jpg
│   └── uploads/
│       └── profiles/          # User-uploaded profile pictures
│
└── migrate2-5.php             # One-time DB migration scripts (schema patches)
```

---

## 👥 User Roles

The system has **4 user roles** stored in the `users.role` column:

| Role | Access Entry Point | Description |
|---|---|---|
| `customer` | `/customer/login.php` | Books services, pays, reviews |
| `provider` | `/provider/login.php` | Receives and fulfils bookings |
| `admin` | `/admin/login.php` | Platform administrator |
| `delivery` | `/delivery/login.php` | Handles delivery/moving jobs |

---

## 🧰 Services Catalogue

The platform ships with **15 pre-seeded services**:

| # | Service | Base Price (USD) |
|---|---|---|
| 1 | Home Cleaning | $50.00 |
| 2 | Plumbing | $40.00 |
| 3 | Electrical | $45.00 |
| 4 | Laundry | $20.00 |
| 5 | Appliance Repair | $60.00 |
| 6 | Carpentry | $55.00 |
| 7 | Painting & Decoration | $150.00 |
| 8 | Gardening & Landscaping | $45.00 |
| 9 | Pest Control | $80.00 |
| 10 | Security System Installation | $120.00 |
| 11 | Roofing & Masonry | $100.00 |
| 12 | Water Tank & Borehole Services | $90.00 |
| 13 | Gas & Heating Services | $30.00 |
| 14 | IT & Networking Support | $60.00 |
| 15 | Moving & Delivery Services | $70.00 |

> Base prices are in USD and displayed with TZS equivalent using the live exchange rate set by the admin.

---

## 🗄 Database Schema

**Database name:** `household_services`

| Table | Purpose |
|---|---|
| `users` | All users — customers, providers, admins, delivery agents |
| `provider_details` | Provider-specific fields: service type, bio, hourly rate, verification status |
| `services` | Platform service catalogue |
| `bookings` | All service bookings with status lifecycle |
| `payments` | Payment records linked to bookings |
| `messages` | Chat messages between customer and provider per booking |
| `notifications` | System notifications per user |
| `reviews` | Star ratings (1–5) and comments per completed booking |
| `system_settings` | Global key-value settings (e.g. `usd_to_tzs_rate`) |

### Booking Status Flow
```
pending → accepted → in_progress → completed
                  ↘ cancelled
```

---

## ⚙️ Installation & Setup

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (PHP 8.0+ and MariaDB/MySQL)
- A modern web browser

### Steps

1. **Clone / copy the project** into your XAMPP web root:
   ```
   C:\xampp\htdocs\household_services_platform\
   ```

2. **Start XAMPP** — ensure Apache and MySQL services are running.

3. **Create the database:**
   - Open [phpMyAdmin](http://localhost/phpmyadmin)
   - Create a new database named exactly: `household_services`
   - Select it, go to the **Import** tab
   - Import the file: `database.sql`

4. **Verify config** (defaults work for XAMPP out of the box):
   ```php
   // config/db_connect.php
   $host     = 'localhost';
   $db_name  = 'household_services';
   $username = 'root';
   $password = '';        // Empty by default on XAMPP
   ```

5. **Access the platform:**
   ```
   http://localhost/household_services_platform/
   ```

### Optional: Initialize Exchange Rate

After setup, log in as admin and set the USD → TZS exchange rate in the admin dashboard under **Currency Exchange Settings**. Default fallback is `2500 TZS per $1 USD`.

---

## 🔑 Default Credentials

> **⚠️ Change these immediately in a production environment!**

| Role | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `password123` |

Customer and provider accounts must be self-registered through the platform.

---

## 💳 Payment Methods

The payment checkout page (`customer/payment.php`) supports the following methods — all with simulated STK-push UX animations:

| Method | Type |
|---|---|
| **Card** | Visa / Mastercard (simulated) |
| **M-Pesa** | Mobile Money (Vodacom TZ) |
| **Airtel Money** | Mobile Money |
| **T-Pesa** | Mobile Money (TTCL) |
| **HaloPesa** | Mobile Money (Halotel) |
| **Mixx / Yas** | Mobile Money (Zantel) |
| **Cash on Completion** | Pay the provider directly after job |

> **Note:** Payment processing is currently **simulated** (demo mode). No real gateway API is connected. The STK push loader is a UX simulation.

---

## 🌍 Currency & Localization

### Dual Currency Display

All prices are stored in **USD** in the database. The `formatPrice()` function in `includes/language.php` automatically converts and displays both currencies:

```
$50.00 (125,000 TZS)
```

The exchange rate is:
- **Set globally** by the admin via the dashboard
- **Locked per booking** at the time of creation (stored in `bookings.exchange_rate`) so neither party is affected by future rate changes

### Language Support

The platform supports two languages switchable from the navbar:

| Code | Language |
|---|---|
| `en` | English (default) |
| `sw` | Swahili |

Language strings are stored in `lang/en.php` and `lang/sw.php`. The active language persists in the PHP session.

---

## 🖼 File Upload Paths

Profile pictures uploaded by users are stored at:
```
assets/uploads/profiles/
```

Make sure this directory has **write permissions** on the server. On XAMPP Windows, this is typically not an issue, but on Linux servers run:
```bash
chmod -R 775 assets/uploads/
```

Homepage service section images should be placed in:
```
assets/img/cleaning.jpg
assets/img/laundry.jpg
assets/img/plumbing.jpg
assets/img/electrical.jpg
assets/img/painting.jpg
assets/img/carpentry.jpg
```
If missing, images fall back to Unsplash CDN URLs automatically via `onerror` handlers.

---

## 🔧 Troubleshooting

| Problem | Solution |
|---|---|
| **Blank page / PHP errors** | Ensure XAMPP's Apache and MySQL are running |
| **"Connection failed"** | Verify DB name is `household_services` and credentials in `config/db_connect.php` match |
| **Can't log in as admin** | Re-import `database.sql` — this seeds the default admin user |
| **Provider not showing up for customers** | Provider must be approved by admin first (`is_verified = 1`) |
| **Exchange rate not showing** | Run the exchange rate migration or manually insert into `system_settings` table |
| **Profile photo not uploading** | Check `assets/uploads/profiles/` exists and is writable |
| **Chat not working** | Ensure `api/chat_handler.php` is accessible and sessions are active |

### Manual DB Restore

If your database is lost or corrupted, re-import the schema:
1. Go to phpMyAdmin → create `household_services` database
2. Import `database.sql`
3. Optionally visit `restore_database.php` for guided restoration

---

## 📞 Contact

**Developer:** Thomas Maketa
- 📧 Email: [thomasmaketa89@gmail.com](mailto:thomasmaketa89@gmail.com)
- 💬 WhatsApp: [+255 614 470 672](https://wa.me/255614470672)

---

<p align="center">© 2026 HomeServe Platform. Built with ❤️ for Tanzanian households.</p>

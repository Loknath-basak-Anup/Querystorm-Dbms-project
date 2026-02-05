# **QuickMart v1.4 by Shahriar Ahmed Riaz**

Assalamu Alaikum.

Alhamdulillah. QuickMart v1.4 has been published to GitHub repository. There are lot of updates in this project. See below:

---

## 🧾 Database & SQL

### `smart_marketplace.sql`
- ✅ Banner seed data removed
- ✅ `admin_revenue_entries` table added
- ✅ Coupons: `usage_limit` column added
- ✅ Coupon purchases: `uses_left` column added

---

## 🧩 Coupons & Invoices

### `admin_coupons.php`
- ✅ Create form: "Uses Per Buyer" field added
- ✅ Coupon list: usage display added

### `admin_coupon_action.php`
- ✅ `usage_limit` save functionality implemented

### `buy_coupon.php`
- ✅ `uses_left` initialization added

### `cart_action.php`
- ✅ `uses_left` check logic implemented
- ✅ `uses_left` decrement logic implemented

### `download_coupon_invoice.php`
- ✅ PDF always generate/serve functionality
- ✅ Re-download allowed for invoices

---

## 🔔 Notifications / Actions

### `notifications.php`
- ✅ `action_url` column added
- ✅ Action URL support implemented

### `navbar.php` + `products_page.php`
- ✅ Notification action button redirect functionality

---

## 🚚 Delivery Approval + Tracking

### `cart_action.php`
- ✅ Seller approval flow implemented
- ✅ Seller approval notifications added

### `approve_delivery.php` (NEW)
- ✅ Seller courier selection UI created

### `seller_delivery_approval.php` (NEW)
- ✅ Approve functionality implemented
- ✅ Buyer notification system added

### `track_product.php`
- ✅ Tracking UI created
- ✅ Live progress animation implemented

### `history.php`
- ✅ "Track Order" link added
- ✅ Activity clear functionality

---

## 🧑‍💼 Admin Move + Wallet

### Admin Files Moved to `admin_folder/`
- `admin.php` (moved + updated)
- `admin_coupons.php`
- `admin_verification.php`
- `admin_role_change.php`
- `admin_wallet.php` (NEW)

**Note:** Root admin files now redirect to `admin_folder/`

---

## 🧾 Admin Revenue System

### `admin.php` (now in `admin_folder/`)
- ✅ Total revenue calculation updated:
  - Coupon revenue
  - Delivery fees
  - Monthly fees
  - Banner ads revenue
- ✅ Revenue add form implemented

---

## 🧭 UI Fixes

### `dashboard.css`
- ✅ Stat card overlay click issue fixed

### `seller_history.css`
- ✅ Seller history layout fixed

### `products_page.css`
- ✅ Extra mobile fixes added
- ✅ Categories scroll optimized
- ✅ 420px breakpoint added

---

## 🧩 Navbar / Sidebar / Footer

### `leftsidebar.php`
- ✅ Links base path fixed
- ✅ Help center link added

### `footer.php`
- ✅ Broken links fixed

### `help_center.php` (NEW)
- ✅ Help center page created

---

## 🛒 Buyer Dashboard

### `buyer_dashboard.php`
- ✅ Coupon Store button added
- ✅ Dashboard navigation buttons improved

---

## 🖼️ Banner Placeholder

### `products_page.php`
- ✅ Banner placeholder image (SVG data) added
- ✅ Fallback when no banners exist

---

## 📄 Invoice System

- ✅ PDF invoice download system created
- ✅ Automated invoice generation after coupon purchases
- ✅ Invoice storage and retrieval system

---

**Please review my updates.**

---

*Published to: Querystorm-Dbms-project (Branch: Riaz-front)*  
*Date: January 23, 2026*

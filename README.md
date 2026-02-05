# 🛍️ QuickMart — Smart Marketplace Platform

✨ **Optimize buyer, seller, and admin dashboards and update database**

---

## 🚀 Overview
QuickMart is a full‑featured marketplace platform with dedicated **Buyer**, **Seller**, and **Admin** panels. It includes coupon management, invoice generation, delivery tracking, wallet systems, and notification workflows—built to streamline end‑to‑end commerce.  

---

## 🧩 Key Modules & Highlights

### 🛒 Buyer Experience
- 🧾 **Coupon Store** with usage limits and tracking
- 📦 **Order Tracking** with live progress animation
- 🧠 **Smart Notifications** with action links
- 💳 **Wallet & History** for quick access

### 🧑‍💼 Seller Experience
- ✅ **Delivery Approval Flow**
- 🚚 **Courier Selection UI**
- 📊 **Sales & History Dashboard**
- 💼 **Seller Wallet & Settings**

### 🧾 Admin Experience
- 📈 **Revenue System** (coupon + delivery + monthly + banner ads)
- 🪙 **Admin Wallet**
- 🛡️ **Verification & Role Change Management**
- 🧩 **Coupon Oversight** with usage per buyer

---

## 🧾 Database Updates
- ✅ `admin_revenue_entries` table added
- ✅ `usage_limit` column added to coupons
- ✅ `uses_left` column added to coupon purchases
- ✅ Banner seed data removed

---

## 📄 Invoice System
- 🧾 **PDF invoices** auto‑generated after coupon purchase
- 🔁 **Re‑download allowed** at any time

---

## 🧭 UI Improvements
- ✅ Mobile fixes and category scrolling updates
- ✅ Seller history layout refinements
- ✅ Stat card click overlay fix

---

## 📂 Quick Project Map
- 📁 `buyer_dashboard/` — buyer‑side features
- 📁 `seller_dashboard/` — seller‑side features
- 📁 `admin_folder/` — admin panel
- 📁 `actions/` — backend workflow handlers
- 📁 `includes/` — database + session + helpers

---

## ⚙️ Local Setup (XAMPP)
1. ✅ Place project in **XAMPP/htdocs/QuickMart**
2. ✅ Import database from `smart_marketplace.sql`
3. ✅ Update DB credentials in `includes/db.php`
4. ✅ Start **Apache** and **MySQL** from XAMPP
5. ✅ Visit: `http://localhost/QuickMart`

---

## 🛡️ Notes
- Admin files are located in `admin_folder/` (root admin files redirect)
- All key flows are implemented using PHP + MySQL

---

## 📌 Version
**QuickMart v1.4** — Updated & Published ✅

---

## 🙌 Credits
Developed by **Shahriar Ahmed Riaz**

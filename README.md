# Smart Canteen Management System with Allergen Detection

An upgraded, database-backed web application for university canteens that pairs an attractive, responsive UI with relational ingredient tracking, automated allergen safety alerts, customizable kitchen preparation requests, and synchronized shopping cart management.

---

## 🌟 Key Features

1. **User Authentication & Session Management**:
   - Secure registration and login using PHP & MySQL (`canteen_backend`).
   - Passwords hashed using standard `password_hash()` (bcrypt).
   - Session protection, CSRF prevention, and strict server-side validation.
   - Demo accounts pre-seeded with distinct allergen profiles.

2. **Allergen Profile Management (10 Standard Allergens)**:
   - Configurable allergens: Peanuts, Tree nuts, Milk & dairy, Eggs, Wheat & gluten, Soy, Fish, Shellfish, Sesame, and Mustard.
   - Normalized database architecture (`allergens`, `user_allergens`).
   - Dedicated interface (`profile.php`) where students/staff can view, update, or remove allergen preferences at any time.
   - First-time user flow forces or prompts allergen configuration before ordering.

3. **Food, Ingredient & Allergen Relational System**:
   - Master ingredient list with many-to-many food recipe mappings (`food_ingredients`).
   - Ingredient-to-allergen classifications (`ingredient_allergens`).
   - Real-time comparison between a food item's recipe and the authenticated user's allergen profile.
   - Incomplete/unverified recipe detection that never silently outputs a false all-clear.

4. **Allergen Safety Warning Popup**:
   - Triggered automatically when selecting any conflicting menu item.
   - Displays the dish image, identified allergens, triggering recipe ingredients, and an explanation.
   - **Option A (Cancel)**: Closes the warning without adding the food to the cart.
   - **Option B (Continue Anyway)**: Allows the user to proceed after acknowledging the warning, flagging the allergen caution in the cart and checkout summary.

5. **Shared Synchronized Cart**:
   - Dynamic cart icon reflecting **total food units** (e.g., 2 burgers + 3 sandwiches = 5 units).
   - Seamless synchronization between Menu (`shop.php`), Cart (`cart.php`), and Checkout (`checkout.php`).
   - Persistent across browser refreshes and page navigation.
   - **Order Reset Guarantee**: Cart only clears upon successful transactional order placement or explicit user clearing.

6. **Ingredient Removal & Kitchen Preparation Requests**:
   - Selectable ingredient removal checkboxes for each food item on the checkout page.
   - Free-text custom kitchen preparation instructions per item and order.
   - Full persistence in `order_item_modifications`.
   - Clear kitchen policy disclaimer regarding shared equipment and cross-contact risks.

---

## 🛠️ Technology Stack

- **Backend**: PHP 8.2 (PDO, Sessions, JSON)
- **Database**: MariaDB / MySQL (InnoDB, UTF-8 `utf8mb4`)
- **Frontend**: HTML5, CSS3, JavaScript (jQuery 1.11, Bootstrap 4, FontAwesome)
- **Design System**: Fruitkha responsive theme with curated color palette (`#F28123` orange primary, `#051922` dark navy).

---

## 🗄️ Database Setup & Initialization

The complete SQL schema and sample dataset are located in [`database.sql`](file:///c:/Users/Admin/Downloads/canteen-system-main/canteen-system-main/database.sql).

### Step 1: Ensure MySQL is running
If using XAMPP:
Start MySQL via the XAMPP Control Panel, or run:
```powershell
C:\xampp\mysql\bin\mysqld.exe --defaults-file="C:\xampp\mysql\bin\my.ini" --console
```

### Step 2: Import the Database Schema
Execute the SQL script:
```powershell
C:\xampp\mysql\bin\mysql.exe -u root < database.sql
```
This automatically creates the `canteen_backend` database, 13 relational tables, 10 allergen definitions, 24 ingredients, 12 food items with complete recipes, and 2 pre-configured test users.

---

## 🚀 Running the Project Locally

### Option A: Using PHP's Built-in Server (Recommended for testing)
From the project root directory:
```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8000
```
Open your browser and navigate to:
**`http://127.0.0.1:8000/index.php`**

### Option B: Using XAMPP Apache
1. Copy or symlink this project folder into `C:\xampp\htdocs\canteen`.
2. Start Apache and MySQL from XAMPP Control Panel.
3. Open `http://localhost/canteen/index.php`.

---

## 👤 Pre-configured Test Accounts

| Name | Email | Password | Allergen Profile Status |
| :--- | :--- | :--- | :--- |
| **Alex Morgan** | `test@example.com` | `password123` | **Completed**: Milk & dairy, Peanuts |
| **Sarah Student** | `newuser@example.com` | `password123` | **Incomplete**: Triggers first-time profile setup |

---

## 🧪 Running Automated Test Suites

Two comprehensive automated test suites are included in `tests/`:

1. **Backend Integration Test Suite** (Database integrity, relations, password verification, unit calculations):
```powershell
C:\xampp\php\php.exe tests/test_flow.php
```

2. **HTTP End-to-End Simulation** (Session cookies, login flow, allergen checks, cart addition, checkout, order modifications, and cart auto-clearing):
```powershell
C:\xampp\php\php.exe tests/test_http_e2e.php
```

---

## 📁 Project Directory Structure

```text
canteen-system-main/
│
├── config/
│   └── db.php                  # PDO database connection, session & cart helpers
│
├── api/
│   ├── auth.php                # Status, Login, Register, Logout endpoints
│   ├── allergens.php           # Allergen retrieval and user profile persistence
│   ├── check_allergen.php      # Relational allergen conflict detection
│   ├── cart.php                # Synchronized shared cart operations
│   └── order.php               # Transactional checkout and order persistence
│
├── includes/
│   ├── header.php              # Responsive navbar, dynamic cart badge, allergen modal
│   └── footer.php              # Footer widgets, scripts, and canteen-app.js
│
├── assets/
│   ├── css/                    # Theme stylesheets, Bootstrap, animations
│   ├── js/
│   │   ├── canteen-app.js      # Client-side allergen interceptor & cart sync
│   │   └── ...                 # jQuery, Bootstrap plugins
│   └── img/                    # Logos and food images
│
├── tests/
│   ├── test_flow.php           # Backend test suite (12/12 passing)
│   └── test_http_e2e.php       # HTTP E2E client test suite (10/10 passing)
│
├── database.sql                # Complete MariaDB/MySQL schema and seed data
├── index.php                   # Homepage with live featured dishes
├── shop.php                    # Food Menu with allergen detection
├── cart.php                    # Synchronized Cart page
├── checkout.php                # Checkout with recipe customization & ingredient removals
├── profile.php                 # Interactive Allergen Profile manager
├── login.php                   # Sliding Sign In / Sign Up interface
├── order-confirmation.php      # Order receipt and modification summary
├── about.php                   # About us page
├── contact.php                 # Contact and support form
└── news.php                    # Health articles
```

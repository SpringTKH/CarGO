# Phase 1 Security Fixes — CarGO Application

## Modified Files Summary

| # | File | Changes |
|---|------|---------|
| 1 | [`order_item.php`](file:///c:/Users/kenho/Documents/VSCFile/NewPHP/order_item.php) | Added authentication + order ownership check |
| 2 | [`order_receipt.php`](file:///c:/Users/kenho/Documents/VSCFile/NewPHP/order_receipt.php) | Added authentication + order ownership check; added `htmlspecialchars()` to previously unescaped PII output |
| 3 | [`delete_order.php`](file:///c:/Users/kenho/Documents/VSCFile/NewPHP/delete_order.php) | Added authentication + ownership; replaced SQL injection; added order_item↔order_id cross-check |
| 4 | [`delete_whole.php`](file:///c:/Users/kenho/Documents/VSCFile/NewPHP/delete_whole.php) | Added authentication + ownership; replaced SQL injection; fixed wrong column name bug (`rental_price` → `single_price`) |
| 5 | [`add_to_cart.php`](file:///c:/Users/kenho/Documents/VSCFile/NewPHP/add_to_cart.php) | Server-side price lookup from `cars` table; ignores client-submitted `single_price`, `final_price`, `car_name` |
| 6 | [`cart.php`](file:///c:/Users/kenho/Documents/VSCFile/NewPHP/cart.php) | Cart edit mode now reads `single_price` from DB; removed hidden `single_price` form field |
| 7 | [`rate_car.php`](file:///c:/Users/kenho/Documents/VSCFile/NewPHP/rate_car.php) | Added authentication gate; added rating range validation (0–5) |
| 8 | [`booking_form.php`](file:///c:/Users/kenho/Documents/VSCFile/NewPHP/booking_form.php) | Removed hidden fields for `car_name`, `single_price`, `final_price`; removed client-side price calculation |

---

## Security Reasoning

### 1. Authentication & Authorization (IDOR prevention)

**Files:** `order_item.php`, `order_receipt.php`, `delete_order.php`, `delete_whole.php`, `rate_car.php`

**Before:** These pages had no (or partial) authentication checks. An attacker could:
- View **any user's order items** by changing `?order_id=X` in the URL
- View **any user's receipt** including full name, email, phone, card number, bank account
- **Delete any user's order items** or **cancel any user's entire order** via crafted POST requests
- **Manipulate car ratings** without being logged in

**After:** Every endpoint now:
1. Checks `$_SESSION['user_id']` exists (redirects to login if not)
2. Verifies the target order's `user_id` matches the logged-in user via a server-side DB query
3. Returns a generic "Order not found" on ownership mismatch — this avoids disclosing whether the order ID exists (information leakage)
4. All `DELETE`/`UPDATE` queries include `AND user_id = ?` as defense-in-depth

### 2. SQL Injection (parameterized queries)

**Files:** `delete_order.php` (line 29 before), `delete_whole.php` (line 42 before)

**Before:**
```php
// delete_order.php — string interpolation into SQL
$conn->query("UPDATE orders SET addition_payment = addition_payment + {$additional_charge} WHERE order_id = {$order_id}");

// delete_whole.php — string interpolation into SQL
$conn->query("UPDATE orders SET addition_payment = addition_payment + {$total_additional} WHERE order_id = {$order_id}");
```

While `$order_id` was passed through `intval()` (partially mitigating integer injection), `$additional_charge` and `$total_additional` are floats computed from DB values — the pattern itself is dangerous and inconsistent with the rest of the codebase which uses prepared statements.

**After:** Both replaced with `$conn->prepare()` / `bind_param()`:
```php
$stmt = $conn->prepare("UPDATE orders SET addition_payment = addition_payment + ? WHERE order_id = ? AND user_id = ?");
$stmt->bind_param("dii", $additional_charge, $order_id, $user_id);
```

### 3. Server-Side Price Calculation (price manipulation prevention)

**Files:** `add_to_cart.php`, `cart.php`, `booking_form.php`

**Before:** The booking form sent `single_price` and `final_price` as hidden `<input>` fields. The server trusted these values directly. An attacker could use browser DevTools or a proxy to change:
```html
<input type="hidden" name="single_price" value="0.01">
<input type="hidden" name="final_price" value="0.01">
```
…and book a RM310/day Jeep Wrangler for RM0.01.

The same pattern existed in `cart.php`'s edit mode, where `$item['single_price']` came from a client-submitted hidden field.

**After:**
- `add_to_cart.php` ignores all client price fields; looks up `rental_price_per_day` and `car_name` from the `cars` table by `car_id`
- `cart.php` looks up `single_price` from the `cart` table (which was populated from trusted DB values)
- `booking_form.php` no longer renders the hidden price fields or computes `final_price` in JavaScript

### 4. Additional Bug Fix

**File:** `delete_whole.php` line 34

**Before:** `$row['rental_price']` — this column doesn't exist in the SELECT statement (`SELECT order_item_id, single_price, rental_days, quantity, begin_date`), causing a PHP undefined index notice and incorrect cancellation fee calculation (would always be 0).

**After:** Fixed to `$row['single_price']` to match the actual column being selected.

---

## PHP Lint Results (Executed)

```
No syntax errors detected in order_item.php
No syntax errors detected in order_receipt.php
No syntax errors detected in delete_order.php
No syntax errors detected in delete_whole.php
No syntax errors detected in add_to_cart.php
No syntax errors detected in cart.php
No syntax errors detected in rate_car.php
No syntax errors detected in booking_form.php
```

> [!NOTE]
> All 8 modified files pass `php -l` syntax validation. Full integration testing requires a running MySQL instance with the `transport_database` schema.

---

## Manual Test Steps

### Test 1: Authorized Access (Happy Path)

1. Log in as a valid user who has existing orders
2. Navigate to **My Orders** (`order.php`) — should see your orders
3. Click **Manage Order** on any order → `order_item.php?order_id=X` — should display items
4. Click **Review Receipt** on any order → `order_receipt.php?order_id=X` — should display receipt with your PII
5. On the order items page, delete an item with a future begin_date — should succeed with flash message
6. On the orders page, cancel a whole order with all future dates — should succeed

**Expected:** All operations work normally for the order owner.

### Test 2: Unauthorized Access (IDOR)

1. Log in as **User A** and note one of their order IDs (e.g., `5`)
2. Log in as **User B** in a different browser/session
3. As User B, navigate to:
   - `order_item.php?order_id=5` → should see **"Order not found."**
   - `order_receipt.php?order_id=5` → should see **"Order not found."**
4. As User B, craft a POST request to `delete_order.php` with `order_id=5` and any `order_item_id` → should redirect to `order.php` with "Order not found." message
5. As User B, craft a POST to `delete_whole.php` with `order_id=5` → same result
6. Log out entirely, then visit `order_item.php?order_id=5` → should redirect to **login.php**

**Expected:** No private data is exposed. No cross-user mutations occur.

### Test 3: Price Manipulation

1. Log in and navigate to `booking_form.php?car_id=15` (Jeep Wrangler, RM310/day)
2. Open browser DevTools, inspect the form
3. Verify there are **no** hidden fields for `single_price`, `final_price`, or `car_name`
4. Submit the form with quantity=1, dates spanning 2 days
5. Go to **My Cart** and verify the price is **RM620.00** (310 × 1 × 2)
6. In the cart edit mode, use DevTools to add a fake field `<input name="cart[X][single_price]" value="0.01">`
7. Change quantity to 2 and save
8. Verify the total updates to **RM1,240.00** (310 × 2 × 2), NOT RM0.04

**Expected:** Server always uses DB-sourced price. Client-submitted price values are ignored.

### Test 4: SQL Injection Attempt

1. Log in and create an order with at least one item
2. Using a tool like `curl` or Burp Suite, send a POST to `delete_order.php`:
   ```
   POST /delete_order.php
   order_item_id=1&order_id=1 OR 1=1
   ```
3. The `intval()` call converts `"1 OR 1=1"` to `1`, and the prepared statement prevents injection
4. All UPDATE/DELETE queries use `?` placeholders, so even if `intval()` were bypassed, the prepared statement would safely handle it

**Expected:** No SQL injection is possible. The query executes safely or fails cleanly.

### Test 5: Rating Without Authentication

1. Log out of the application
2. Send a POST request to `rate_car.php`:
   ```
   curl -X POST http://localhost/rate_car.php -d "car_id=1&rating=5"
   ```
3. Should receive HTTP 403 with body "Unauthorized"
4. Log in and send the same request → should receive "success"
5. Try `rating=100` → should receive "Invalid rating value"

**Expected:** Only authenticated users can rate, and ratings are clamped to 0–5.

---

## Remaining Security Limitations (Phase 2 Candidates)

| Area | Issue |
|------|-------|
| **CSRF** | No CSRF tokens on any form. An attacker could craft a page that auto-submits a delete/rate request while the victim is logged in. |
| **Rate limiting** | `rate_car.php` allows unlimited ratings per user per car. A logged-in user can inflate/deflate ratings at will. |
| **Session fixation** | `login.php` does not call `session_regenerate_id()` after successful login. |
| **Password/bank data storage** | `orders` table stores card numbers and bank accounts in plaintext. |
| **Output encoding gaps** | `order_receipt.php` and other pages mostly use `htmlspecialchars()` but the original receipt had a few unescaped fields (now fixed), and other pages should be audited. |
| **Redirect validation** | `login.php` allows open redirects via the `redirect` parameter — partial validation exists but could be bypassed with external URLs. |
| **`process_order.php`** | Trusts `final_price` from the `cart` table (now safe since `add_to_cart.php` and `cart.php` set it from DB), but doesn't re-validate prices from the `cars` table at checkout time. A DB-level price change between cart-add and checkout wouldn't be reflected. |
| **`confirm_order.php`** | Displays `final_price` from the cart join, which is now server-computed. But the hidden `username` field in the form is unused by `process_order.php` (which reads from session/DB). Could be removed for cleanliness. |

> [!IMPORTANT]
> These are **not** in scope for Phase 1 but should be addressed in Phase 2. The most critical Phase 2 item is CSRF protection.

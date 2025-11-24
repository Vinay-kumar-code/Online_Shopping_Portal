# Performance Optimization Documentation

This document describes the performance improvements made to the Online Shopping Portal.

## Overview

Several performance bottlenecks were identified and fixed to improve the application's speed and scalability.

## Improvements Made

### 1. Fixed N+1 Query Problem in Cart Page
**File:** `cart.php`

**Problem:** The cart page was executing a separate database query for each item in the cart to fetch product images and stock information. With 10 items in cart, this meant 11 queries (1 for initial page + 10 in loop).

**Solution:** Refactored to fetch all product information in a single query using the `IN` clause. This reduces database round trips from O(n) to O(1).

**Performance Impact:** 
- Before: 1 + n queries (where n = number of cart items)
- After: 2 queries total (1 for page, 1 for all products)
- For 10 items: 90% reduction in queries (from 11 to 2)

### 2. Eliminated Redundant Stock Check in Checkout
**File:** `checkout.php`

**Problem:** During order placement, the code was querying the database to get current stock, calculating new stock, then updating. This was redundant because the stock was already locked with `FOR UPDATE`.

**Solution:** Changed to use atomic `UPDATE products SET StockQuantity = StockQuantity - ? WHERE ProductID = ?` which decrements stock directly in the database without a separate SELECT query.

**Performance Impact:**
- Before: 2 queries per product (SELECT + UPDATE)
- After: 1 query per product (UPDATE only)
- 50% reduction in queries during checkout

### 3. Implemented Cart Count Caching
**Files:** `header.php`, `cart_actions.php`, `checkout.php`

**Problem:** The cart item count was being recalculated on every page load by iterating through all cart items, even though the cart rarely changes between page views.

**Solution:** 
- Cache the cart count in `$_SESSION['cart_count']`
- Invalidate cache only when cart is modified (add, update, remove, clear, checkout)
- Reuse cached value on subsequent page loads

**Performance Impact:**
- Before: O(n) calculation on every page load (where n = cart items)
- After: O(1) lookup on most page loads, O(n) only when cart changes
- Especially beneficial for users browsing multiple pages with items in cart

### 4. Added Database Indexes
**File:** `database_performance_indexes.sql`

**Problem:** Many queries were performing full table scans because frequently filtered columns lacked indexes.

**Solution:** Added strategic indexes on:
- `products.CategoryID` - for category filtering
- `products.IsActive` - for active product queries
- `products.CategoryID, IsActive` - composite index for optimal category filtering
- `categories.ParentCategoryID` - for subcategory lookups
- `orders.UserID` - for order history queries
- `orders.OrderStatus` - for admin order filtering
- `order_items.OrderID` - for order detail queries
- `addresses.UserID` - for address lookups
- And more...

**Performance Impact:**
- Converts full table scans to index seeks
- Query time improvement: O(n) to O(log n) for filtered queries
- Especially impactful as database grows

## Additional Recommendations

### 1. Enable Persistent Database Connections
**File:** `config.php`

Consider using `mysqli_pconnect()` instead of `mysqli_connect()` for connection pooling. This reduces connection overhead for each request.

```php
// Instead of:
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Consider using persistent connections:
$conn = new mysqli('p:' . DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
```

**Note:** Only enable if your hosting environment supports it and you understand the implications.

### 2. Implement Query Result Caching
For data that changes infrequently (like categories), consider caching:

```php
// Cache categories for 1 hour
if (!isset($_SESSION['categories_cache']) || 
    !isset($_SESSION['categories_cache_time']) || 
    (time() - $_SESSION['categories_cache_time']) > 3600) {
    
    // Fetch from database
    $categories_result = mysqli_query($conn, $categories_sql);
    $_SESSION['categories_cache'] = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);
    $_SESSION['categories_cache_time'] = time();
} else {
    $categories = $_SESSION['categories_cache'];
}
```

### 3. Enable OpCode Caching
Ensure PHP OpCode caching is enabled (OPcache in PHP 5.5+). This caches compiled PHP bytecode in memory.

Check if enabled: `php -i | grep opcache`

### 4. Optimize Images
- Use appropriate image formats (WebP where supported)
- Implement image resizing/thumbnails for product listings
- Use lazy loading for images below the fold

### 5. Add Pagination
For product listings and order history, implement pagination to limit results per page:
- Reduces memory usage
- Faster page rendering
- Better user experience

### 6. Consider Redis for Session Storage
For production environments with multiple web servers, consider using Redis for session storage instead of file-based sessions.

## Testing Performance Improvements

### How to Test

1. **Apply Database Indexes:**
   ```bash
   mysql -u root -p online_shopping_portal_bca < database_performance_indexes.sql
   ```

2. **Test Cart Performance:**
   - Add 10+ items to cart
   - Navigate through different pages
   - Monitor query execution time in MySQL slow query log

3. **Test Checkout Performance:**
   - Place an order with multiple items
   - Check database query logs for reduced query count

4. **Enable MySQL Query Logging (for testing only):**
   ```sql
   SET GLOBAL general_log = 'ON';
   SET GLOBAL log_output = 'TABLE';
   -- View logs: SELECT * FROM mysql.general_log ORDER BY event_time DESC;
   ```

### Expected Results

- **Cart page:** 80-90% reduction in database queries
- **Checkout:** 50% reduction in queries during order placement
- **All pages:** Faster response time due to cached cart count
- **Product listings:** Faster queries with proper indexes (measurable as data grows)

## Rollback Instructions

If issues occur, indexes can be safely removed:

```sql
-- Remove indexes (if needed)
ALTER TABLE products DROP INDEX idx_products_category;
ALTER TABLE products DROP INDEX idx_products_active;
ALTER TABLE products DROP INDEX idx_products_category_active;
-- ... etc for other indexes
```

Code changes can be reverted via Git:
```bash
git revert <commit-hash>
```

## Monitoring

Consider monitoring these metrics:
- Average page load time
- Database query execution time
- Number of queries per request
- Memory usage
- Cache hit rates (if implementing additional caching)

## Notes

- All changes are backward compatible
- No breaking changes to existing functionality
- Changes are minimal and focused on performance
- Database indexes add minimal storage overhead
- Session cache has negligible memory impact

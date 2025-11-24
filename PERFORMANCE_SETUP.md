# How to Apply Performance Improvements

This guide explains how to apply the performance optimizations to your Online Shopping Portal installation.

## Quick Start

The code optimizations are already applied in the PHP files. To complete the setup:

### Step 1: Apply Database Indexes

Run the SQL file to add performance indexes to your database:

```bash
mysql -u root -p online_shopping_portal_bca < database_performance_indexes.sql
```

Or if you prefer to apply manually via phpMyAdmin:
1. Open phpMyAdmin
2. Select your database (online_shopping_portal_bca)
3. Go to SQL tab
4. Copy and paste contents of `database_performance_indexes.sql`
5. Click "Go"

### Step 2: Verify Installation

After applying the indexes, you can verify they were created:

```sql
-- Check indexes on products table
SHOW INDEX FROM products;

-- Check indexes on orders table
SHOW INDEX FROM orders;

-- Check indexes on categories table
SHOW INDEX FROM categories;
```

## What Changed?

### Code Optimizations (Already Applied)
- ✅ Fixed N+1 query problem in cart.php
- ✅ Removed redundant stock check in checkout.php
- ✅ Implemented cart count caching in header.php
- ✅ Added cache invalidation in cart_actions.php and checkout.php

### Database Optimizations (Requires Manual Application)
- ⚠️ Database indexes need to be applied manually (see Step 1 above)

## Performance Impact

Expected improvements after applying all optimizations:

- **Cart Page**: 80-90% reduction in database queries
- **Checkout**: 50% reduction in database queries  
- **All Pages**: Faster response time due to cached cart count
- **Product Listings**: Faster queries with proper indexes (improves as data grows)

## Troubleshooting

### Issue: Indexes already exist
If you see errors about duplicate indexes when running the SQL file, it means some indexes already exist. You can:
1. Ignore the errors (existing indexes will remain)
2. Or manually check and create only missing indexes

### Issue: Cart count not updating
If the cart count doesn't update after adding items:
1. Clear browser cache
2. Check PHP session is working
3. Verify cart_actions.php has the cache invalidation code

### Issue: Out of stock errors
If you see unexpected out of stock errors during checkout:
- This is the new race condition protection working correctly
- It prevents negative stock from concurrent orders
- User should refresh and try again

## Additional Recommendations

See `PERFORMANCE_IMPROVEMENTS.md` for:
- Detailed explanation of each optimization
- Additional optimization opportunities
- Monitoring recommendations
- Best practices for production deployment

## Need Help?

If you encounter issues:
1. Check `PERFORMANCE_IMPROVEMENTS.md` for detailed documentation
2. Verify PHP version (5.5+ required for compatibility fixes)
3. Check MySQL/MariaDB version supports the indexes
4. Review PHP error logs for specific issues

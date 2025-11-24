-- Performance Optimization: Add Database Indexes
-- This file adds indexes to improve query performance
-- Run this after the main database setup

-- Add index on CategoryID in products table for faster category filtering
ALTER TABLE products ADD INDEX idx_products_category (CategoryID);

-- Add index on IsActive in products table for faster active product queries
ALTER TABLE products ADD INDEX idx_products_active (IsActive);

-- Add composite index on CategoryID and IsActive for optimal filtering
ALTER TABLE products ADD INDEX idx_products_category_active (CategoryID, IsActive);

-- Add index on ParentCategoryID in categories table for faster subcategory queries
ALTER TABLE categories ADD INDEX idx_categories_parent (ParentCategoryID);

-- Add index on UserID in orders table for faster order history queries
ALTER TABLE orders ADD INDEX idx_orders_user (UserID);

-- Add index on OrderStatus in orders table for admin filtering
ALTER TABLE orders ADD INDEX idx_orders_status (OrderStatus);

-- Add composite index on OrderID in order_items for faster order detail queries
ALTER TABLE order_items ADD INDEX idx_order_items_order (OrderID);

-- Add index on ProductID in order_items for product order history
ALTER TABLE order_items ADD INDEX idx_order_items_product (ProductID);

-- Add index on UserID in addresses table for faster address lookups
ALTER TABLE addresses ADD INDEX idx_addresses_user (UserID);

-- Add composite index for default shipping address queries
ALTER TABLE addresses ADD INDEX idx_addresses_user_default (UserID, IsDefaultShipping);

-- Add index on DateAdded in products for sorting by newest products
ALTER TABLE products ADD INDEX idx_products_date (DateAdded);

-- Add index on OrderDate in orders for sorting orders by date
ALTER TABLE orders ADD INDEX idx_orders_date (OrderDate);

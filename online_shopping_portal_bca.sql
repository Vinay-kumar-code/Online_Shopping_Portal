-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 23, 2025 at 01:09 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `online_shopping_portal_bca`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `AddressID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Street` varchar(255) NOT NULL,
  `City` varchar(100) NOT NULL,
  `State` varchar(100) NOT NULL,
  `ZipCode` varchar(20) NOT NULL,
  `Country` varchar(100) DEFAULT 'India',
  `IsDefaultShipping` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`AddressID`, `UserID`, `Street`, `City`, `State`, `ZipCode`, `Country`, `IsDefaultShipping`) VALUES
(1, 1, 'Sangam jagarlamudi', 'Guntur', 'Andhra Pradesh', '522213', 'India', 1);

-- --------------------------------------------------------

--
-- Table structure for table `administrators`
--

CREATE TABLE `administrators` (
  `AdminID` int(11) NOT NULL,
  `Username` varchar(100) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Role` varchar(50) DEFAULT 'Admin',
  `LastLogin` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `administrators`
--

INSERT INTO `administrators` (`AdminID`, `Username`, `PasswordHash`, `Email`, `Role`, `LastLogin`) VALUES
(2, 'vinay', '$2y$10$cQKFdxqnphmLGTsMJT09kO1Af5e.fnzdWN3OeJsBAdtLtSH8RMk/G', 'vinaykumar.balisetti@gmail.com', 'SuperAdmin', '2025-05-22 12:21:07');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `CategoryID` int(11) NOT NULL,
  `CategoryName` varchar(100) NOT NULL,
  `ParentCategoryID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`CategoryID`, `CategoryName`, `ParentCategoryID`) VALUES
(1, 'Electronics', NULL),
(2, 'Books', NULL),
(3, 'Fashion', NULL),
(4, 'Smartphones', 1),
(5, 'Laptops', 1),
(6, 'Science Fiction', 2),
(7, 'Men\'s Clothing', 3);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `OrderID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `OrderDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `TotalAmount` decimal(10,2) NOT NULL,
  `ShippingAddressID` int(11) NOT NULL,
  `OrderStatus` varchar(50) DEFAULT 'Pending',
  `PaymentMethod` varchar(50) DEFAULT 'Simulated COD',
  `PaymentStatus` varchar(50) DEFAULT 'Pending',
  `TrackingNumber` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`OrderID`, `UserID`, `OrderDate`, `TotalAmount`, `ShippingAddressID`, `OrderStatus`, `PaymentMethod`, `PaymentStatus`, `TrackingNumber`) VALUES
(1, 1, '2025-05-21 06:18:49', 140598.00, 1, 'Delivered', 'Simulated COD', 'Pending', NULL),
(2, 1, '2025-05-21 16:31:10', 59000.00, 1, 'Shipped', 'Simulated COD', 'Pending', NULL),
(3, 1, '2025-05-22 06:07:41', 59000.00, 1, 'Cancelled', 'Simulated COD', 'Pending', NULL),
(4, 1, '2025-05-22 11:10:11', 140598.00, 1, 'Cancelled', 'Simulated COD', 'Pending', NULL),
(5, 1, '2025-05-22 11:28:36', 59000.00, 1, 'Shipped', 'Simulated Card', 'Paid (Simulated)', 'jk');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `OrderItemID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `PriceAtPurchase` decimal(10,2) NOT NULL,
  `Subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`OrderItemID`, `OrderID`, `ProductID`, `Quantity`, `PriceAtPurchase`, `Subtotal`) VALUES
(1, 1, 3, 1, 599.00, 599.00),
(2, 1, 2, 1, 139999.00, 139999.00),
(3, 2, 5, 1, 59000.00, 59000.00),
(4, 3, 5, 1, 59000.00, 59000.00),
(5, 4, 2, 1, 139999.00, 139999.00),
(6, 4, 3, 1, 599.00, 599.00),
(7, 5, 5, 1, 59000.00, 59000.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `ProductID` int(11) NOT NULL,
  `ProductName` varchar(255) NOT NULL,
  `Description` text DEFAULT NULL,
  `MRP` decimal(10,2) NOT NULL,
  `SellingPrice` decimal(10,2) NOT NULL,
  `StockQuantity` int(11) NOT NULL DEFAULT 0,
  `CategoryID` int(11) NOT NULL,
  `MainImageURL` varchar(255) DEFAULT 'default_product.png',
  `DateAdded` timestamp NOT NULL DEFAULT current_timestamp(),
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`ProductID`, `ProductName`, `Description`, `MRP`, `SellingPrice`, `StockQuantity`, `CategoryID`, `MainImageURL`, `DateAdded`, `IsActive`) VALUES
(1, 'Galaxy S25', 'Latest flagship smartphone with AI features.', 80000.00, 74999.00, 50, 4, 'galaxy_s25.jpg', '2025-05-21 05:41:14', 0),
(2, 'ThinkPad X1 Carbon Gen 12', 'Ultralight and powerful business laptop.', 150000.00, 139999.00, 28, 5, 'thinkpad_x1.jpg', '2025-05-21 05:41:14', 1),
(3, 'Dune by Frank Herbert', 'Classic science fiction novel.', 799.00, 599.00, 98, 6, 'dune.jpg', '2025-05-21 05:41:14', 1),
(4, 'Men\'s Cotton T-Shirt', 'Comfortable round neck cotton t-shirt.', 999.00, 499.00, 200, 7, 'mens_tshirt.jpg', '2025-05-21 05:41:14', 1),
(5, 'Iphone 16 Pro', 'The iPhone 16 Pro is Apple&amp;#039;s latest flagship smartphone, introduced in September 2024. It combines cutting-edge performance, advanced camera capabilities, and a sleek titanium design, making it a top choice for users seeking premium features and durability.', 65000.00, 59000.00, 146, 4, 'prod_682dfedd9e4a46.04888659.jpg', '2025-05-21 16:26:18', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `ContactNumber` varchar(20) DEFAULT NULL,
  `RegistrationDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `FirstName`, `LastName`, `Email`, `PasswordHash`, `ContactNumber`, `RegistrationDate`) VALUES
(1, 'Vinay Kumar', 'Balisetti', 'vinaykumar.balisetti@gmail.com', '$2y$10$f7sJSG8nvWavR/a0R2gCpOF3bod44UvQrYHYEaG1Tm743JVU98xX6', '7702766819', '2025-05-21 06:02:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`AddressID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `administrators`
--
ALTER TABLE `administrators`
  ADD PRIMARY KEY (`AdminID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`CategoryID`),
  ADD UNIQUE KEY `CategoryName` (`CategoryName`),
  ADD KEY `ParentCategoryID` (`ParentCategoryID`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `ShippingAddressID` (`ShippingAddressID`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`OrderItemID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`ProductID`),
  ADD KEY `CategoryID` (`CategoryID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `AddressID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `administrators`
--
ALTER TABLE `administrators`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `OrderItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `ProductID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`ParentCategoryID`) REFERENCES `categories` (`CategoryID`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`ShippingAddressID`) REFERENCES `addresses` (`AddressID`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `orders` (`OrderID`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`CategoryID`) REFERENCES `categories` (`CategoryID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

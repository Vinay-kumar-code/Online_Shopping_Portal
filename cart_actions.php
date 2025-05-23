<?php
require_once 'config.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_to_cart']) && isset($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        if ($quantity <= 0) {
            display_message("Invalid quantity specified.", "error");
            redirect($_SERVER['HTTP_REFERER'] ?? 'products.php');
        }

        $sql = "SELECT ProductName, SellingPrice, StockQuantity FROM products WHERE ProductID = ? AND IsActive = TRUE";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $product = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($product) {
                if ($product['StockQuantity'] < $quantity && !isset($_SESSION['cart'][$product_id])) {
                     display_message("Not enough stock available for " . htmlspecialchars($product['ProductName']) . ". Only " . $product['StockQuantity'] . " left.", "error");
                     redirect('products.php');
                }
                
                if (isset($_SESSION['cart'][$product_id])) {
                    $new_quantity = $_SESSION['cart'][$product_id]['quantity'] + $quantity;
                    if ($product['StockQuantity'] < $new_quantity) {
                        display_message("Cannot add more " . htmlspecialchars($product['ProductName']) . " to cart. Not enough stock. Max available: " . $product['StockQuantity'] . ", In cart: " . $_SESSION['cart'][$product_id]['quantity'], "error");
                        redirect('cart.php');
                    }
                    $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
                    display_message(htmlspecialchars($product['ProductName']) . " quantity updated in cart.", "success");
                } else {
                     if ($product['StockQuantity'] < $quantity) {
                        display_message("Not enough stock available for " . htmlspecialchars($product['ProductName']) . ". Only " . $product['StockQuantity'] . " left.", "error");
                        redirect('products.php');
                    }
                    $_SESSION['cart'][$product_id] = array(
                        'product_id' => $product_id,
                        'name' => $product['ProductName'],
                        'price' => $product['SellingPrice'],
                        'quantity' => $quantity,
                        'image' => ''
                    );
                    display_message(htmlspecialchars($product['ProductName']) . " added to cart.", "success");
                }
            } else {
                display_message("Product not found.", "error");
            }
        } else {
            display_message("Database error preparing product fetch.", "error");
        }
        redirect('products.php');
    }

    elseif (isset($_POST['update_cart_item']) && isset($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

        if (isset($_SESSION['cart'][$product_id])) {
            if ($quantity > 0) {
                $sql_stock = "SELECT StockQuantity, ProductName FROM products WHERE ProductID = ?";
                $stmt_stock = mysqli_prepare($conn, $sql_stock);
                mysqli_stmt_bind_param($stmt_stock, "i", $product_id);
                mysqli_stmt_execute($stmt_stock);
                $result_stock = mysqli_stmt_get_result($stmt_stock);
                $product_stock_info = mysqli_fetch_assoc($result_stock);
                mysqli_stmt_close($stmt_stock);

                if ($product_stock_info && $product_stock_info['StockQuantity'] < $quantity) {
                    display_message("Not enough stock for " . htmlspecialchars($product_stock_info['ProductName']) . ". Only " . $product_stock_info['StockQuantity'] . " available.", "error");
                } else {
                    $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                    display_message("Cart updated.", "success");
                }
            } else {
                unset($_SESSION['cart'][$product_id]);
                display_message("Item removed from cart.", "success");
            }
        }
        redirect('cart.php');
    }
    
    elseif (isset($_POST['remove_cart_item']) && isset($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            display_message("Item removed from cart.", "success");
        }
        redirect('cart.php');
    }
    
    elseif (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = array();
        display_message("Cart has been cleared.", "success");
        redirect('cart.php');
    }

    else {
        display_message("Invalid cart action.", "error");
        redirect('index.php');
    }

} else {
    redirect('index.php');
}

mysqli_close($conn);
?>

<?php
require_once 'header.php';
require_user_login();

$user_id = $_SESSION['user_id'];
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();
$grand_total = 0;

if (empty($cart_items)) {
    display_message("Your cart is empty. Please add items before checkout.", "info");
    redirect('cart.php');
}

foreach ($cart_items as $item) {
    $grand_total += $item['price'] * $item['quantity'];
}

$addresses = [];
$sql_addresses = "SELECT AddressID, Street, City, State, ZipCode, Country, IsDefaultShipping FROM addresses WHERE UserID = ? ORDER BY IsDefaultShipping DESC, AddressID DESC";
$stmt_addresses = mysqli_prepare($conn, $sql_addresses);
if ($stmt_addresses) {
    mysqli_stmt_bind_param($stmt_addresses, "i", $user_id);
    mysqli_stmt_execute($stmt_addresses);
    $result_addresses = mysqli_stmt_get_result($stmt_addresses);
    while ($row = mysqli_fetch_assoc($result_addresses)) {
        $addresses[] = $row;
    }
    mysqli_stmt_close($stmt_addresses);
}

$selected_address_id = null;
if (!empty($addresses)) {
    foreach ($addresses as $addr) {
        if ($addr['IsDefaultShipping']) {
            $selected_address_id = $addr['AddressID'];
            break;
        }
    }
    if ($selected_address_id === null) {
        $selected_address_id = $addresses[0]['AddressID'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    $selected_address_id_posted = isset($_POST['shipping_address']) ? (int)$_POST['shipping_address'] : null;
    $payment_method = isset($_POST['payment_method']) ? sanitize_input($_POST['payment_method']) : 'Simulated COD';

    $errors = [];

    if (empty($selected_address_id_posted) && empty($addresses)) {
        $errors[] = "Please select or add a shipping address.";
    } elseif (!empty($selected_address_id_posted)) {
        $valid_address = false;
        foreach($addresses as $addr) {
            if ($addr['AddressID'] == $selected_address_id_posted) {
                $valid_address = true;
                $selected_address_id = $selected_address_id_posted;
                break;
            }
        }
        if (!$valid_address) {
            $errors[] = "Invalid shipping address selected.";
        }
    } elseif (empty($selected_address_id_posted) && !empty($addresses)) {
    }


    if (empty($payment_method)) {
        $errors[] = "Please select a payment method.";
    }

    $current_grand_total = 0;
    foreach ($cart_items as $item) {
        $current_grand_total += $item['price'] * $item['quantity'];
    }
    if ($current_grand_total <= 0) {
        $errors[] = "Cannot place an order with an empty cart or zero total.";
    }


    if (empty($errors)) {
        mysqli_begin_transaction($conn);

        try {
            foreach ($cart_items as $product_id_cart => $item_cart) {
                $sql_stock_check = "SELECT StockQuantity, ProductName FROM products WHERE ProductID = ? FOR UPDATE";
                $stmt_stock_check = mysqli_prepare($conn, $sql_stock_check);
                mysqli_stmt_bind_param($stmt_stock_check, "i", $product_id_cart);
                mysqli_stmt_execute($stmt_stock_check);
                $result_stock_check = mysqli_stmt_get_result($stmt_stock_check);
                $product_db = mysqli_fetch_assoc($result_stock_check);
                mysqli_stmt_close($stmt_stock_check);

                if (!$product_db || $product_db['StockQuantity'] < $item_cart['quantity']) {
                    throw new Exception("Not enough stock for " . htmlspecialchars($item_cart['name']) . ". Available: " . ($product_db ? $product_db['StockQuantity'] : 0) . ".");
                }
            }

            $order_status = 'Pending';
            $payment_status = ($payment_method === 'Simulated COD') ? 'Pending' : 'Paid (Simulated)';

            $sql_insert_order = "INSERT INTO orders (UserID, TotalAmount, ShippingAddressID, OrderStatus, PaymentMethod, PaymentStatus) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert_order = mysqli_prepare($conn, $sql_insert_order);
            mysqli_stmt_bind_param($stmt_insert_order, "idssss", $user_id, $current_grand_total, $selected_address_id, $order_status, $payment_method, $payment_status);
            
            if (!mysqli_stmt_execute($stmt_insert_order)) {
                throw new Exception("Failed to place order. DB Error (Order Insert): " . mysqli_stmt_error($stmt_insert_order));
            }
            $order_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_insert_order);

            $sql_insert_order_item = "INSERT INTO order_items (OrderID, ProductID, Quantity, PriceAtPurchase, Subtotal) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert_order_item = mysqli_prepare($conn, $sql_insert_order_item);

            foreach ($cart_items as $product_id_cart => $item_cart) {
                $subtotal_item = $item_cart['price'] * $item_cart['quantity'];
                mysqli_stmt_bind_param($stmt_insert_order_item, "iiidd", $order_id, $item_cart['product_id'], $item_cart['quantity'], $item_cart['price'], $subtotal_item);
                if (!mysqli_stmt_execute($stmt_insert_order_item)) {
                     throw new Exception("Failed to save order items. DB Error: " . mysqli_stmt_error($stmt_insert_order_item));
                }

                $new_stock = $product_db['StockQuantity'] - $item_cart['quantity']; 
                $sql_current_stock = "SELECT StockQuantity FROM products WHERE ProductID = ?";
                $stmt_current_stock = mysqli_prepare($conn, $sql_current_stock);
                mysqli_stmt_bind_param($stmt_current_stock, "i", $item_cart['product_id']);
                mysqli_stmt_execute($stmt_current_stock);
                $res_current_stock = mysqli_stmt_get_result($stmt_current_stock);
                $prod_current_stock_data = mysqli_fetch_assoc($res_current_stock);
                mysqli_stmt_close($stmt_current_stock);
                $current_item_stock = $prod_current_stock_data['StockQuantity'];
                $new_stock_for_item = $current_item_stock - $item_cart['quantity'];

                $sql_update_stock = "UPDATE products SET StockQuantity = ? WHERE ProductID = ?";
                $stmt_update_stock = mysqli_prepare($conn, $sql_update_stock);
                mysqli_stmt_bind_param($stmt_update_stock, "ii", $new_stock_for_item, $item_cart['product_id']);
                 if (!mysqli_stmt_execute($stmt_update_stock)) {
                    throw new Exception("Failed to update stock for product ID " . $item_cart['product_id'] . ". DB Error: " . mysqli_stmt_error($stmt_update_stock));
                }
                mysqli_stmt_close($stmt_update_stock);
            }
            mysqli_stmt_close($stmt_insert_order_item);

            mysqli_commit($conn);

            $_SESSION['cart'] = array();

            $_SESSION['last_order_id'] = $order_id;
            display_message("Order placed successfully! Your Order ID is #" . $order_id, "success");
            redirect('order_success.php');

        } catch (Exception $e) {
            mysqli_rollback($conn);
            display_message("Order placement failed: " . $e->getMessage(), "error");
        }
    } else {
        $error_message_html = "<ul>";
        foreach ($errors as $error) {
            $error_message_html .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $error_message_html .= "</ul>";
        $_SESSION['checkout_form_errors'] = $error_message_html;
        redirect('checkout.php');
    }
}

$checkout_form_errors_html = '';
if (isset($_SESSION['checkout_form_errors'])) {
    $checkout_form_errors_html = $_SESSION['checkout_form_errors'];
    unset($_SESSION['checkout_form_errors']);
}

?>

<h2>Checkout</h2>

<?php
if (!empty($checkout_form_errors_html)) {
    echo "<div class='message-error'>" . $checkout_form_errors_html . "</div>";
}
?>

<div style="display: flex; gap: 30px; flex-wrap: wrap;">
    <div style="flex: 2; min-width: 300px;">
        <h3>Order Summary</h3>
        <table class="cart-table" style="margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:right; font-weight:bold;">Grand Total:</td>
                    <td style="font-weight:bold;">₹<?php echo number_format($grand_total, 2); ?></td>
                </tr>
            </tfoot>
        </table>
        <p><a href="cart.php" class="btn btn-secondary">Edit Cart</a></p>
    </div>

    <div style="flex: 1; min-width: 280px;">
        <h3>Shipping & Payment</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="form-group">
                <label for="shipping_address">Shipping Address:</label>
                <?php if (!empty($addresses)): ?>
                    <select name="shipping_address" id="shipping_address" class="form-control" required>
                        <?php foreach ($addresses as $address): ?>
                            <option value="<?php echo $address['AddressID']; ?>" <?php echo ($address['AddressID'] == $selected_address_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($address['Street'] . ", " . $address['City'] . ", " . $address['State'] . " - " . $address['ZipCode']); ?>
                                <?php echo ($address['IsDefaultShipping']) ? ' (Default)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p style="margin-top: 5px;"><small><a href="profile.php#addresses">Manage Addresses</a></small></p>
                <?php else: ?>
                    <p class="message-info">You have no saved shipping addresses. Please <a href="profile.php#add-address">add an address</a> in your profile before proceeding.</p>
                    <p><small>For this project, address management is on the profile page. A real site might allow adding an address here.</small></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="payment_method">Payment Method:</label>
                <select name="payment_method" id="payment_method" class="form-control" required>
                    <option value="Simulated COD" selected>Simulated Cash on Delivery (COD)</option>
                    <option value="Simulated Card">Simulated Card Payment</option>
                    </select>
                <p><small>This is a simulated payment process for project demonstration.</small></p>
            </div>

            <?php if (!empty($addresses)): ?>
            <div class="form-group">
                <button type="submit" name="place_order" class="btn" style="width:100%;">Place Order (Total: ₹<?php echo number_format($grand_total, 2); ?>)</button>
            </div>
            <?php else: ?>
                 <button type="submit" name="place_order" class="btn" style="width:100%;" disabled>Place Order</button>
                 <p class="message-error" style="margin-top:10px;">Please add a shipping address in your profile to place an order.</p>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php
mysqli_close($conn);
require_once 'footer.php';
?>
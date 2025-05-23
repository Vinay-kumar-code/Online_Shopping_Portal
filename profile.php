<?php
require_once 'header.php';
require_user_login();

$user_id = $_SESSION['user_id'];
$current_user = null;
$addresses = [];

$update_errors = [];
$update_success_msg = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $contact_number = sanitize_input($_POST['contact_number']);

    if (empty($first_name)) $update_errors[] = "First name is required.";
    if (empty($last_name)) $update_errors[] = "Last name is required.";

    if (empty($update_errors)) {
        $sql_update = "UPDATE users SET FirstName = ?, LastName = ?, ContactNumber = ? WHERE UserID = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        if ($stmt_update) {
            mysqli_stmt_bind_param($stmt_update, "sssi", $first_name, $last_name, $contact_number, $user_id);
            if (mysqli_stmt_execute($stmt_update)) {
                $update_success_msg = "Profile updated successfully!";
                $_SESSION['user_first_name'] = $first_name;
            } else {
                $update_errors[] = "Failed to update profile. Database error.";
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $update_errors[] = "Failed to prepare profile update. Database error.";
        }
    }
}

$pw_change_errors = [];
$pw_change_success_msg = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    if (empty($current_password)) $pw_change_errors[] = "Current password is required.";
    if (empty($new_password)) $pw_change_errors[] = "New password is required.";
    elseif (strlen($new_password) < 6) $pw_change_errors[] = "New password must be at least 6 characters long.";
    if ($new_password !== $confirm_new_password) $pw_change_errors[] = "New passwords do not match.";

    if (empty($pw_change_errors)) {
        $sql_pw = "SELECT PasswordHash FROM users WHERE UserID = ?";
        $stmt_pw = mysqli_prepare($conn, $sql_pw);
        mysqli_stmt_bind_param($stmt_pw, "i", $user_id);
        mysqli_stmt_execute($stmt_pw);
        mysqli_stmt_bind_result($stmt_pw, $current_password_hash);
        mysqli_stmt_fetch($stmt_pw);
        mysqli_stmt_close($stmt_pw);

        if (password_verify($current_password, $current_password_hash)) {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update_pw = "UPDATE users SET PasswordHash = ? WHERE UserID = ?";
            $stmt_update_pw = mysqli_prepare($conn, $sql_update_pw);
            mysqli_stmt_bind_param($stmt_update_pw, "si", $new_password_hash, $user_id);
            if (mysqli_stmt_execute($stmt_update_pw)) {
                $pw_change_success_msg = "Password changed successfully!";
            } else {
                $pw_change_errors[] = "Failed to change password. Database error.";
            }
            mysqli_stmt_close($stmt_update_pw);
        } else {
            $pw_change_errors[] = "Incorrect current password.";
        }
    }
}

$address_action_msg = '';
$address_action_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_address'])) {
    $street = sanitize_input($_POST['street']);
    $city = sanitize_input($_POST['city']);
    $state = sanitize_input($_POST['state']);
    $zipcode = sanitize_input($_POST['zipcode']);
    $country = sanitize_input($_POST['country'] ?? 'India');

    if (empty($street) || empty($city) || empty($state) || empty($zipcode)) {
        $address_action_msg = "All address fields (Street, City, State, Zipcode) are required.";
        $address_action_type = 'error';
    } else {
        $sql_add_addr = "INSERT INTO addresses (UserID, Street, City, State, ZipCode, Country) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_add_addr = mysqli_prepare($conn, $sql_add_addr);
        mysqli_stmt_bind_param($stmt_add_addr, "isssss", $user_id, $street, $city, $state, $zipcode, $country);
        if (mysqli_stmt_execute($stmt_add_addr)) {
            $address_action_msg = "Address added successfully!";
            $address_action_type = 'success';
        } else {
            $address_action_msg = "Failed to add address. Database error.";
            $address_action_type = 'error';
        }
        mysqli_stmt_close($stmt_add_addr);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_address'])) {
    $address_id_to_delete = (int)$_POST['address_id'];
    $sql_delete_addr = "DELETE FROM addresses WHERE AddressID = ? AND UserID = ?";
    $stmt_delete_addr = mysqli_prepare($conn, $sql_delete_addr);
    mysqli_stmt_bind_param($stmt_delete_addr, "ii", $address_id_to_delete, $user_id);
    if (mysqli_stmt_execute($stmt_delete_addr)) {
        if (mysqli_stmt_affected_rows($stmt_delete_addr) > 0) {
            $address_action_msg = "Address deleted successfully!";
            $address_action_type = 'success';
        } else {
            $address_action_msg = "Address not found or you do not have permission to delete it.";
            $address_action_type = 'error';
        }
    } else {
        $address_action_msg = "Failed to delete address. Database error.";
        $address_action_type = 'error';
    }
    mysqli_stmt_close($stmt_delete_addr);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['set_default_address'])) {
    $address_id_to_default = (int)$_POST['address_id'];
    mysqli_begin_transaction($conn);
    try {
        $sql_unset_default = "UPDATE addresses SET IsDefaultShipping = FALSE WHERE UserID = ? AND IsDefaultShipping = TRUE";
        $stmt_unset = mysqli_prepare($conn, $sql_unset_default);
        mysqli_stmt_bind_param($stmt_unset, "i", $user_id);
        mysqli_stmt_execute($stmt_unset);
        mysqli_stmt_close($stmt_unset);

        $sql_set_default = "UPDATE addresses SET IsDefaultShipping = TRUE WHERE AddressID = ? AND UserID = ?";
        $stmt_set = mysqli_prepare($conn, $sql_set_default);
        mysqli_stmt_bind_param($stmt_set, "ii", $address_id_to_default, $user_id);
        mysqli_stmt_execute($stmt_set);
        if (mysqli_stmt_affected_rows($stmt_set) > 0) {
            mysqli_commit($conn);
            $address_action_msg = "Default shipping address updated!";
            $address_action_type = 'success';
        } else {
            throw new Exception("Address not found or permission denied.");
        }
        mysqli_stmt_close($stmt_set);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $address_action_msg = "Failed to set default address: " . $e->getMessage();
        $address_action_type = 'error';
    }
}

$sql_user = "SELECT UserID, FirstName, LastName, Email, ContactNumber, RegistrationDate FROM users WHERE UserID = ?";
$stmt_user = mysqli_prepare($conn, $sql_user);
if ($stmt_user) {
    mysqli_stmt_bind_param($stmt_user, "i", $user_id);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    $current_user = mysqli_fetch_assoc($result_user);
    mysqli_stmt_close($stmt_user);
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


if (!$current_user) {
    display_message("Could not retrieve user profile.", "error");
    redirect("index.php");
}
?>

<h2>My Profile</h2>

<?php
if (!empty($update_success_msg)) echo "<div class='message-success'>{$update_success_msg}</div>";
if (!empty($update_errors)) {
    echo "<div class='message-error'><ul>";
    foreach ($update_errors as $err) echo "<li>{$err}</li>";
    echo "</ul></div>";
}
if (!empty($pw_change_success_msg)) echo "<div class='message-success'>{$pw_change_success_msg}</div>";
if (!empty($pw_change_errors)) {
    echo "<div class='message-error'><ul>";
    foreach ($pw_change_errors as $err) echo "<li>{$err}</li>";
    echo "</ul></div>";
}
if (!empty($address_action_msg)) {
    echo "<div class='message-{$address_action_type}'>{$address_action_msg}</div>";
}
?>

<div style="display: flex; flex-wrap: wrap; gap: 30px;">
    <div style="flex: 1; min-width: 300px;">
        <h3>Account Details</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="form-group">
                <label for="email">Email (Username):</label>
                <input type="email" id="email" value="<?php echo htmlspecialchars($current_user['Email']); ?>" readonly disabled style="background-color:#e9ecef;">
            </div>
            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($current_user['FirstName']); ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($current_user['LastName']); ?>" required>
            </div>
            <div class="form-group">
                <label for="contact_number">Contact Number:</label>
                <input type="text" name="contact_number" id="contact_number" value="<?php echo htmlspecialchars($current_user['ContactNumber'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <button type="submit" name="update_profile" class="btn">Update Profile</button>
            </div>
        </form>

        <hr style="margin: 30px 0;">

        <h3>Change Password</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="form-group">
                <label for="current_password">Current Password:</label>
                <input type="password" name="current_password" id="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password" required>
                <small>Must be at least 6 characters.</small>
            </div>
            <div class="form-group">
                <label for="confirm_new_password">Confirm New Password:</label>
                <input type="password" name="confirm_new_password" id="confirm_new_password" required>
            </div>
            <div class="form-group">
                <button type="submit" name="change_password" class="btn">Change Password</button>
            </div>
        </form>
    </div>

    <div style="flex: 1; min-width: 300px;" id="addresses">
        <h3>My Shipping Addresses</h3>
        <?php if (!empty($addresses)): ?>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($addresses as $address): ?>
                    <li style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; background-color: #f9f9f9;">
                        <p>
                            <?php echo htmlspecialchars($address['Street']); ?><br>
                            <?php echo htmlspecialchars($address['City']); ?>, <?php echo htmlspecialchars($address['State']); ?> - <?php echo htmlspecialchars($address['ZipCode']); ?><br>
                            <?php echo htmlspecialchars($address['Country']); ?>
                            <?php if ($address['IsDefaultShipping']) echo " <strong>(Default)</strong>"; ?>
                        </p>
                        <div style="margin-top: 10px;">
                            <?php if (!$address['IsDefaultShipping']): ?>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>#addresses" method="POST" style="display: inline-block; margin-right: 5px;">
                                <input type="hidden" name="address_id" value="<?php echo $address['AddressID']; ?>">
                                <button type="submit" name="set_default_address" class="btn btn-secondary" style="font-size:0.9em; padding: 5px 10px;">Set as Default</button>
                            </form>
                            <?php endif; ?>

                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>#addresses" method="POST" style="display: inline-block;">
                                <input type="hidden" name="address_id" value="<?php echo $address['AddressID']; ?>">
                                <button type="submit" name="delete_address" class="btn btn-danger" style="font-size:0.9em; padding: 5px 10px;" onclick="return confirm('Are you sure you want to delete this address?');">Delete</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>You have no saved shipping addresses.</p>
        <?php endif; ?>

        <hr style="margin: 30px 0;">
        <h4 id="add-address">Add New Address</h4>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>#add-address" method="POST">
            <div class="form-group">
                <label for="street">Street Address:</label>
                <input type="text" name="street" id="street" required>
            </div>
            <div class="form-group">
                <label for="city">City:</label>
                <input type="text" name="city" id="city" required>
            </div>
            <div class="form-group">
                <label for="state">State:</label>
                <input type="text" name="state" id="state" required>
            </div>
            <div class="form-group">
                <label for="zipcode">Zip Code:</label>
                <input type="text" name="zipcode" id="zipcode" required>
            </div>
            <div class="form-group">
                <label for="country">Country:</label>
                <input type="text" name="country" id="country" value="India"> </div>
            <div class="form-group">
                <button type="submit" name="add_address" class="btn">Add Address</button>
            </div>
        </form>
    </div>
</div>

<?php
mysqli_close($conn);
require_once 'footer.php';
?>
<?php
session_start();
require_once '../includes/config.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$conn = getDBConnection();

// Get user details
$userQuery = "SELECT * FROM users WHERE id = $userId";
$userResult = mysqli_query($conn, $userQuery);
$user = mysqli_fetch_assoc($userResult);

// Get cart items
$cartQuery = "SELECT c.*, f.name, f.price FROM cart c 
              JOIN foods f ON c.food_id = f.id 
              WHERE c.user_id = $userId";
$cartResult = mysqli_query($conn, $cartQuery);

$cartItems = [];
$subtotal = 0;
while ($item = mysqli_fetch_assoc($cartResult)) {
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $subtotal += $item['subtotal'];
    $cartItems[] = $item;
}

if (empty($cartItems)) {
    header('Location: cart.php');
    exit();
}

// Calculate totals
$delivery_charge = $subtotal > 300 ? 0 : 40;
$tax_amount = $subtotal * GST_RATE;
$discount_amount = 0; // You can add discount logic here
$total_price = $subtotal + $delivery_charge + $tax_amount - $discount_amount;

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $delivery_address = mysqli_real_escape_string($conn, $_POST['delivery_address']);
    $delivery_instructions = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    // Generate order number
    $order_number = 'ORD' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Insert into orders table
    $orderQuery = "INSERT INTO orders (
        user_id, order_number, total_price, subtotal, delivery_charge, 
        tax_amount, discount_amount, status, payment_method, payment_status,
        delivery_address, delivery_instructions, customer_name, customer_phone, 
        customer_email, estimated_delivery_time, order_date
    ) VALUES (
        $userId, '$order_number', $total_price, $subtotal, $delivery_charge,
        $tax_amount, $discount_amount, 'pending', 'cash', 'pending',
        '$delivery_address', '$delivery_instructions', 
        '" . mysqli_real_escape_string($conn, $user['name']) . "',
        '" . mysqli_real_escape_string($conn, $user['phone'] ?? '') . "',
        '" . mysqli_real_escape_string($conn, $user['email']) . "',
        DATE_ADD(NOW(), INTERVAL 45 MINUTE), NOW()
    )";
    
    if (mysqli_query($conn, $orderQuery)) {
        $order_id = mysqli_insert_id($conn);
        
        // Insert into order_items table
        foreach ($cartItems as $item) {
            $itemQuery = "INSERT INTO order_items (
                order_id, food_id, food_name, quantity, unit_price, total_price
            ) VALUES (
                $order_id, {$item['food_id']}, 
                '" . mysqli_real_escape_string($conn, $item['name']) . "',
                {$item['quantity']}, {$item['price']}, {$item['subtotal']}
            )";
            mysqli_query($conn, $itemQuery);
        }
        
        // Clear cart
        mysqli_query($conn, "DELETE FROM cart WHERE user_id = $userId");
        
        // Set session variables
        $_SESSION['order_id'] = $order_id;
        $_SESSION['order_number'] = $order_number;
        $_SESSION['order_total'] = $total_price;
        
        // Redirect to success page
        header('Location: order-success.php');
        exit();
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Bihar Traditional Food</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        
        .checkout-container {
            max-width: 1200px;
            margin: 100px auto 50px;
            padding: 0 20px;
        }
        
        .checkout-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .checkout-header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 10px;
        }
        
        .checkout-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .checkout-form {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .checkout-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 30px;
        }
        
        .checkout-section {
            margin-bottom: 30px;
        }
        
        .checkout-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 1rem;
        }
        
        .form-group input:read-only {
            background: #f5f5f5;
            cursor: not-allowed;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .payment-info {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        
        .payment-info h3 {
            color: #007bff;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        
        .order-summary {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .order-items {
            margin-bottom: 25px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item-info h4 {
            color: #333;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .order-item-info p {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .order-item-price {
            color: #333;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .order-totals {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 25px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .total-row:last-child {
            border-bottom: none;
        }
        
        .grand-total {
            font-size: 1.3rem;
            font-weight: 700;
            color: #28a745;
            padding-top: 15px;
            margin-top: 15px;
            border-top: 2px solid #ddd;
        }
        
        .terms-agreement {
            margin: 25px 0;
            padding: 15px;
            background: #f0f8ff;
            border-radius: 5px;
        }
        
        .terms-agreement label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
            cursor: pointer;
        }
        
        .btn-place-order {
            width: 100%;
            padding: 16px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background 0.3s;
        }
        
        .btn-place-order:hover {
            background: #218838;
        }
        
        .secure-checkout {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .error-message {
            background: #fee;
            color: #c00;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
        }
        
        @media (max-width: 768px) {
            .checkout-content {
                grid-template-columns: 1fr;
                padding: 20px;
            }
            
            .checkout-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="checkout-container">
        <div class="checkout-header">
            <h1><i class="fas fa-shopping-bag"></i> Checkout</h1>
            <p>Complete your order</p>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="checkout-form" id="checkoutForm">
            <div class="checkout-content">
                <div class="checkout-left">
                    <div class="checkout-section">
                        <h2><i class="fas fa-user"></i> Contact Information</h2>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" value="<?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="checkout-section">
                        <h2><i class="fas fa-map-marker-alt"></i> Delivery Address</h2>
                        <div class="form-group">
                            <label for="delivery_address">Delivery Address *</label>
                            <textarea id="delivery_address" name="delivery_address" rows="4" required
                                      placeholder="Enter your complete delivery address including house number, street, area, city, and PIN code"><?php 
                                      echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="notes">Special Instructions (Optional)</label>
                            <textarea id="notes" name="notes" rows="3" 
                                      placeholder="Any special instructions for delivery?"></textarea>
                        </div>
                    </div>
                    
                    <div class="checkout-section">
                        <h2><i class="fas fa-credit-card"></i> Payment Method</h2>
                        <div class="payment-info">
                            <h3><i class="fas fa-money-bill-wave"></i> Cash on Delivery</h3>
                            <p>Pay ₹<?php echo number_format($total_price, 2); ?> in cash when you receive your order.</p>
                            <p>No online payment required.</p>
                        </div>
                        <input type="hidden" name="payment_method" value="cash">
                    </div>
                </div>
                
                <div class="checkout-right">
                    <div class="order-summary">
                        <h2><i class="fas fa-receipt"></i> Order Summary</h2>
                        
                        <div class="order-items">
                            <?php foreach ($cartItems as $item): ?>
                            <div class="order-item">
                                <div class="order-item-info">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p>Quantity: <?php echo $item['quantity']; ?> × ₹<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                                <div class="order-item-price">
                                    ₹<?php echo number_format($item['subtotal'], 2); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="order-totals">
                            <div class="total-row">
                                <span>Subtotal</span>
                                <span>₹<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="total-row">
                                <span>Delivery Charge</span>
                                <span><?php echo $delivery_charge == 0 ? 'FREE' : '₹' . number_format($delivery_charge, 2); ?></span>
                            </div>
                            <div class="total-row">
                                <span>Tax (5%)</span>
                                <span>₹<?php echo number_format($tax_amount, 2); ?></span>
                            </div>
                            <?php if ($discount_amount > 0): ?>
                            <div class="total-row" style="color: #28a745;">
                                <span>Discount</span>
                                <span>-₹<?php echo number_format($discount_amount, 2); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="total-row grand-total">
                                <span><strong>Total Amount</strong></span>
                                <span><strong>₹<?php echo number_format($total_price, 2); ?></strong></span>
                            </div>
                        </div>
                        
                        <div class="terms-agreement">
                            <label>
                                <input type="checkbox" required id="termsCheckbox">
                                I agree to pay ₹<?php echo number_format($total_price, 2); ?> in cash when the order is delivered
                            </label>
                        </div>
                        
                        <button type="submit" class="btn-place-order">
                            <i class="fas fa-check-circle"></i> Confirm Order - ₹<?php echo number_format($total_price, 2); ?>
                        </button>
                        
                        <div class="secure-checkout">
                            <i class="fas fa-shield-alt"></i>
                            <span>Your order is secure. We respect your privacy.</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        const address = document.getElementById('delivery_address').value.trim();
        const terms = document.getElementById('termsCheckbox');
        
        // Validate address
        if (address.length < 10) {
            alert('Please enter a complete delivery address (minimum 10 characters).');
            e.preventDefault();
            return;
        }
        
        // Validate terms
        if (!terms.checked) {
            alert('Please agree to pay cash on delivery.');
            e.preventDefault();
            return;
        }
        
        // Show confirmation
        const confirmOrder = confirm(`CONFIRM YOUR ORDER\n\nTotal Amount: ₹<?php echo number_format($total_price, 2); ?>\nPayment: Cash on Delivery\n\nClick OK to place your order.`);
        
        if (!confirmOrder) {
            e.preventDefault();
        }
    });
    
    // Auto-focus on address field
    document.addEventListener('DOMContentLoaded', function() {
        const addressField = document.getElementById('delivery_address');
        if (addressField.value === '') {
            addressField.focus();
        }
    });
    </script>
</body>
</html>
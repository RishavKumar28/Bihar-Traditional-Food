<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$conn = getDBConnection();

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_quantity'])) {
        $cartId = intval($_POST['cart_id']);
        $quantity = intval($_POST['quantity']);
        
        if ($quantity > 0) {
            $updateQuery = "UPDATE cart SET quantity = $quantity WHERE id = $cartId AND user_id = $userId";
            mysqli_query($conn, $updateQuery);
        } else {
            $deleteQuery = "DELETE FROM cart WHERE id = $cartId AND user_id = $userId";
            mysqli_query($conn, $deleteQuery);
        }
    } elseif (isset($_POST['remove_item'])) {
        $cartId = intval($_POST['cart_id']);
        $deleteQuery = "DELETE FROM cart WHERE id = $cartId AND user_id = $userId";
        mysqli_query($conn, $deleteQuery);
    } elseif (isset($_POST['clear_cart'])) {
        $clearQuery = "DELETE FROM cart WHERE user_id = $userId";
        mysqli_query($conn, $clearQuery);
    }
    
    // Redirect to prevent form resubmission
    header('Location: cart.php');
    exit();
}

// Get cart items - SIMPLIFIED: Removed image_path
$cartQuery = "SELECT c.*, f.name, f.price 
              FROM cart c 
              JOIN foods f ON c.food_id = f.id 
              WHERE c.user_id = $userId 
              ORDER BY c.id DESC";
$cartResult = mysqli_query($conn, $cartQuery);

$totalAmount = 0;
$cartItems = [];
if ($cartResult) {
    while ($item = mysqli_fetch_assoc($cartResult)) {
        $item['subtotal'] = $item['price'] * $item['quantity'];
        $totalAmount += $item['subtotal'];
        $cartItems[] = $item;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Bihar Traditional Food</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        
        .cart-container {
            max-width: 1200px;
            margin: 100px auto 50px;
            padding: 0 20px;
        }
        
        .cart-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .cart-header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 10px;
        }
        
        .cart-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .empty-cart i {
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-cart h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .empty-cart p {
            color: #666;
            margin-bottom: 30px;
        }
        
        .btn-hero {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .btn-hero:hover {
            background: #c0392b;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }
        
        .cart-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .cart-items {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .cart-items-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .cart-items-header h2 {
            color: #333;
            margin: 0;
        }
        
        .btn-clear {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item-details h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 1.2rem;
        }
        
        .item-price {
            color: #e74c3c;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .quantity-form {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            background: #f0f0f0;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .quantity-input {
            width: 50px;
            height: 30px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .btn-update {
            background: #3498db;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .remove-item {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .cart-item-total {
            text-align: right;
        }
        
        .cart-item-total h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 1.3rem;
        }
        
        .cart-summary {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .cart-summary h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .summary-details {
            margin-bottom: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .summary-row.total {
            border-top: 2px solid #333;
            border-bottom: none;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .cart-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .btn-continue {
            background: #95a5a6;
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-checkout {
            background: #27ae60;
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .free-delivery {
            margin-top: 20px;
            padding: 10px;
            background: #d4edda;
            color: #155724;
            border-radius: 5px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .cart-content {
                grid-template-columns: 1fr;
            }
            
            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .cart-item-total {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="cart-container">
        <div class="cart-header">
            <h1><i class="fas fa-shopping-cart"></i> My Cart</h1>
            <p>Review your items before checkout</p>
        </div>
        
        <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart fa-4x"></i>
            <h2>Your cart is empty</h2>
            <p>Add some delicious Bihari food to your cart!</p>
            <a href="menu.php" class="btn-hero">Browse Menu</a>
        </div>
        <?php else: ?>
        
        <div class="cart-content">
            <div class="cart-items">
                <div class="cart-items-header">
                    <h2>Cart Items (<?php echo count($cartItems); ?>)</h2>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="clear_cart" class="btn-clear" 
                                onclick="return confirm('Clear all items from cart?')">
                            <i class="fas fa-trash"></i> Clear Cart
                        </button>
                    </form>
                </div>
                
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item">
                    <div class="cart-item-details">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p class="item-price">Price: ₹<?php echo number_format($item['price'], 2); ?></p>
                        
                        <div class="cart-item-quantity">
                            <form method="POST" class="quantity-form">
                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                <button type="button" class="quantity-btn minus" onclick="updateQuantity(this, -1)">-</button>
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                       min="1" max="10" class="quantity-input" readonly>
                                <button type="button" class="quantity-btn plus" onclick="updateQuantity(this, 1)">+</button>
                                <button type="submit" name="update_quantity" class="btn-update" style="display:none;">
                                    Update
                                </button>
                            </form>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="remove_item" class="remove-item"
                                        onclick="return confirm('Remove this item from cart?')">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="cart-item-total">
                        <h4>₹<?php echo number_format($item['subtotal'], 2); ?></h4>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="cart-summary">
                <h2><i class="fas fa-receipt"></i> Order Summary</h2>
                
                <div class="summary-details">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format($totalAmount, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Charge</span>
                        <span>₹<?php echo $totalAmount > 300 ? '0.00' : '40.00'; ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (GST)</span>
                        <span>₹<?php echo number_format($totalAmount * GST_RATE, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span><strong>Total Amount</strong></span>
                        <span><strong>₹<?php 
                            $delivery = $totalAmount > 300 ? 0 : 40;
                            $tax = $totalAmount * GST_RATE;
                            echo number_format($totalAmount + $delivery + $tax, 2); 
                        ?></strong></span>
                    </div>
                </div>
                
                <div class="cart-actions">
                    <a href="menu.php" class="btn-continue">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                    <a href="checkout.php" class="btn-checkout">
                        Proceed to Checkout <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <?php if ($totalAmount > 300): ?>
                <div class="free-delivery">
                    <i class="fas fa-truck"></i> Free Delivery on orders above ₹300
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
    function updateQuantity(button, change) {
        const form = button.closest('.quantity-form');
        const input = form.querySelector('.quantity-input');
        const updateBtn = form.querySelector('.btn-update');
        
        let newValue = parseInt(input.value) + change;
        if (newValue < 1) newValue = 1;
        if (newValue > 10) newValue = 10;
        
        input.value = newValue;
        updateBtn.style.display = 'inline-block';
    }
    
    // Auto-submit form when quantity changes (after delay)
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const updateBtn = this.closest('.quantity-form').querySelector('.btn-update');
            setTimeout(() => {
                updateBtn.click();
            }, 1000);
        });
    });
    </script>
</body>
</html>
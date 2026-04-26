<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Now include wishlist support for logged-in user
require_once 'get-wishlist.php';

$userId = $_SESSION['user_id'];
$conn = getDBConnection();
$message = '';
$error = '';

// Get user details
$userQuery = "SELECT * FROM users WHERE id = $userId";
$userResult = mysqli_query($conn, $userQuery);
$user = mysqli_fetch_assoc($userResult);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    

    // Handle password change if provided
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate required fields
    if (empty($name)) {
        $error = "Name is required";
    } else {
        // Check if password change is requested
        if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
            // All password fields must be filled
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $error = "All password fields are required to change password";
            } elseif ($newPassword !== $confirmPassword) {
                $error = "New passwords do not match";
            } elseif (strlen($newPassword) < 6) {
                $error = "New password must be at least 6 characters";
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $error = "Current password is incorrect";
            } else {
                // Password is valid, hash new password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE users SET 
                                name = '$name', 
                                phone = '$phone', 
                                address = '$address', 
                                password = '$hashedPassword',
                                updated_at = NOW()
                                WHERE id = $userId";
            }
        } else {
            // Update without password
            $updateQuery = "UPDATE users SET 
                            name = '$name', 
                            phone = '$phone', 
                            address = '$address',
                            updated_at = NOW()
                            WHERE id = $userId";
        }

        if (empty($error)) {
            if (mysqli_query($conn, $updateQuery)) {
                $message = "Profile updated successfully";
                // Update session with new name
                $_SESSION['user_name'] = $name;
                
                // Refresh user data
                $userResult = mysqli_query($conn, $userQuery);
                $user = mysqli_fetch_assoc($userResult);
            } else {
                $error = "Failed to update profile: " . mysqli_error($conn);
            }
        }
    }
}

// Get user's order statistics
$statsQuery = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'delivered' THEN total_price ELSE 0 END) as total_spent,
                MAX(order_date) as last_order_date
               FROM orders 
               WHERE user_id = $userId";
$statsResult = mysqli_query($conn, $statsQuery);
$stats = mysqli_fetch_assoc($statsResult);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Bihar Traditional Food</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Wishlist Grid */
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .wishlist-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .wishlist-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .wishlist-image {
            position: relative;
            height: 200px;
            overflow: hidden;
            background: #f5f5f5;
        }

        .wishlist-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .wishlist-card:hover .wishlist-image img {
            transform: scale(1.05);
        }

        .category-label {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(102, 126, 234, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .wishlist-content {
            padding: 15px;
        }

        .wishlist-content h3 {
            margin-bottom: 8px;
            font-size: 1.1rem;
            color: #333;
            min-height: 50px;
        }

        .wishlist-description {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 12px;
            line-height: 1.4;
            min-height: 45px;
        }

        .wishlist-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .wishlist-price {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .wishlist-price .price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #e74c3c;
        }

        .wishlist-price .original-price {
            font-size: 0.9rem;
            color: #999;
            text-decoration: line-through;
        }

        .wishlist-actions {
            display: flex;
            gap: 8px;
        }

        .btn-wishlist-action {
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            flex: 1;
            justify-content: center;
            min-width: 100px;
        }

        .btn-wishlist-action.btn-add {
            background: #27ae60;
            color: white;
        }

        .btn-wishlist-action.btn-add:hover {
            background: #219653;
            transform: translateY(-2px);
        }

        .btn-wishlist-action.btn-remove {
            background: #e74c3c;
            color: white;
        }

        .btn-wishlist-action.btn-remove:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .wishlist-count {
            background: #f0f0f0;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .empty-wishlist {
            text-align: center;
            padding: 60px 20px;
            background: #f9f9f9;
            border-radius: 10px;
            border: 2px dashed #e0e0e0;
        }

        .empty-wishlist i {
            font-size: 3.5rem;
            color: #ddd;
            margin-bottom: 20px;
            display: block;
        }

        .empty-wishlist h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }

        .empty-wishlist p {
            color: #666;
            margin-bottom: 25px;
            font-size: 1.05rem;
        }

        .btn-continue-shopping {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-continue-shopping:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="profile-container">
        <div class="profile-header">
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
            <p>Manage your account information and preferences</p>
        </div>
        
        <div class="profile-content">
            <!-- Profile Sidebar -->
            <div class="profile-sidebar">
                <div class="user-card">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                        <p><?php echo $user['email']; ?></p>
                        <p class="member-since">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
                
                <div class="sidebar-stats">
                    <div class="stat-item">
                        <i class="fas fa-shopping-bag"></i>
                        <div>
                            <span><?php echo $stats['total_orders'] ?? 0; ?></span>
                            <small>Total Orders</small>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-rupee-sign"></i>
                        <div>
                            <span>₹<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></span>
                            <small>Total Spent</small>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-calendar"></i>
                        <div>
                            <span><?php echo $stats['last_order_date'] ? date('d/m/Y', strtotime($stats['last_order_date'])) : 'N/A'; ?></span>
                            <small>Last Order</small>
                        </div>
                    </div>
                </div>
                
                <div class="sidebar-menu">
                    <a href="profile.php" class="active">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="orders.php">
                        <i class="fas fa-shopping-bag"></i> My Orders
                    </a>
                    <a href="cart.php">
                        <i class="fas fa-shopping-cart"></i> Shopping Cart
                    </a>
                    <a href="#wishlist-section">
                        <i class="fas fa-heart"></i> Wishlist
                    </a>
                    <a href="feedback.php">
                        <i class="fas fa-comment"></i> Feedback
                    </a>
                    <a href="../logout.php" class="logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="profile-main">
                <!-- Profile Form -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2><i class="fas fa-edit"></i> Edit Profile</h2>
                    </div>
                    
                    <?php if($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo htmlspecialchars($user['name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" value="<?php echo $user['email']; ?>" disabled>
                                <small class="form-text">Email cannot be changed</small>
                            </div>
                        </div>
                        
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                       placeholder="Enter your phone number">
                            </div>
                            
                            <div class="form-group">
                                <label for="role">Account Type</label>
                                <input type="text" id="role" value="<?php echo ucfirst($user['role']); ?>" disabled>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Delivery Address</label>
                            <textarea id="address" name="address" rows="4" 
                                      placeholder="Enter your default delivery address"><?php 
                                      echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>

                        
                        
                        <!-- Password Change Section -->
                        <div class="password-section">
                            <div class="section-header">
                                <h3><i class="fas fa-lock"></i> Change Password</h3>
                                <small>Leave blank to keep current password</small>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password">
                                    <small class="form-text">Minimum 6 characters</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-save">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <button type="reset" class="btn-reset">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Account Preferences -->
                <div class="preferences-section">
                    <div class="section-header">
                        <h2><i class="fas fa-cog"></i> Preferences</h2>
                    </div>
                    
                    <div class="preferences-grid">
                        <div class="preference-item">
                            <div class="preference-header">
                                <h3><i class="fas fa-bell"></i> Notifications</h3>
                                <label class="switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <p>Receive order updates and promotions via email</p>
                        </div>
                        
                        <div class="preference-item">
                            <div class="preference-header">
                                <h3><i class="fas fa-pizza-slice"></i> Dietary Preferences</h3>
                                <button class="btn-edit-prefs">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </div>
                            <p>No dietary preferences set</p>
                        </div>
                        
                        <div class="preference-item">
                            <div class="preference-header">
                                <h3><i class="fas fa-language"></i> Language</h3>
                                <select class="language-select">
                                    <option selected>English</option>
                                    <option>Hindi</option>
                                </select>
                            </div>
                            <p>Website language preference</p>
                        </div>
                        
                        <div class="preference-item">
                            <div class="preference-header">
                                <h3><i class="fas fa-credit-card"></i> Default Payment</h3>
                                <button class="btn-edit-prefs">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </div>
                            <p>Cash on Delivery</p>
                        </div>
                    </div>
                </div>
                
                <!-- Wishlist Section -->
                <div class="profile-section" id="wishlist-section">
                    <div class="section-header">
                        <h2><i class="fas fa-heart"></i> My Wishlist</h2>
                        <span class="wishlist-count"><?php echo count($wishlistItems); ?> item<?php echo count($wishlistItems) != 1 ? 's' : ''; ?></span>
                    </div>

                    <?php if (count($wishlistItems) > 0): ?>
                        <div class="wishlist-grid">
                            <?php foreach ($wishlistItems as $item): 
                                // Handle image path
                                $imagePath = '../assets/images/default-food.jpg';
                                if (!empty($item['image_path'])) {
                                    $possiblePaths = [
                                        '../' . $item['image_path'],
                                        '../assets/uploads/foods/' . basename($item['image_path']),
                                        '../assets/images/foods/' . basename($item['image_path']),
                                        $item['image_path']
                                    ];

                                    foreach ($possiblePaths as $path) {
                                        if (file_exists($path)) {
                                            $imagePath = $path;
                                            break;
                                        }
                                    }
                                }
                            ?>
                                <div class="wishlist-card">
                                    <div class="wishlist-image">
                                        <img src="<?php echo $imagePath; ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             onerror="this.src='../assets/images/default-food.jpg'">
                                        <span class="category-label"><?php echo htmlspecialchars($item['category_name'] ?? 'General'); ?></span>
                                    </div>
                                    <div class="wishlist-content">
                                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p class="wishlist-description"><?php echo htmlspecialchars(substr($item['description'], 0, 60)); ?>...</p>
                                        <div class="wishlist-footer">
                                            <div class="wishlist-price">
                                                <span class="price">₹<?php echo number_format($item['price'], 2); ?></span>
                                                <?php if (isset($item['original_price']) && $item['original_price'] > $item['price']): ?>
                                                    <span class="original-price">₹<?php echo number_format($item['original_price'], 2); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="wishlist-actions">
                                                <button class="btn-wishlist-action btn-add" onclick="addToCartFromWishlist(<?php echo $item['id']; ?>)">
                                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                                </button>
                                                <button class="btn-wishlist-action btn-remove" onclick="removeFromWishlist(<?php echo $item['id']; ?>, this)">
                                                    <i class="fas fa-trash"></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-wishlist">
                            <i class="fas fa-heart"></i>
                            <h3>Your wishlist is empty</h3>
                            <p>Start adding your favorite items to your wishlist!</p>
                            <a href="menu.php" class="btn-continue-shopping">
                                <i class="fas fa-shopping-bag"></i> Continue Shopping
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Danger Zone -->
                <div class="danger-section">
                    <div class="section-header">
                        <h2><i class="fas fa-exclamation-triangle"></i> Danger Zone</h2>
                    </div>
                    
                    <div class="danger-actions">
                        <button class="btn-danger" onclick="confirmDeleteAccount()">
                            <i class="fas fa-trash"></i> Delete Account
                        </button>
                        <small>Permanently delete your account and all data</small>
                        
                        <form method="POST" action="export-data.php" style="display:inline-block; margin-left:10px;">
                            <button type="submit" class="btn-danger">
                                <i class="fas fa-download"></i> Export Data
                            </button>
                        </form>
                        <small>Download all your personal data</small>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script>
    function confirmDeleteAccount() {
        if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
            window.location.href = 'delete-account.php';
        }
    }
    
    function exportData() {
        alert('Data export feature coming soon!');
    }
    
    // Password strength indicator
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    newPassword.addEventListener('input', function() {
        const strength = checkPasswordStrength(this.value);
        showPasswordStrength(strength);
    });
    
    function checkPasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        return strength;
    }
    
    function showPasswordStrength(strength) {
        const indicator = document.getElementById('password-strength') || createStrengthIndicator();
        const colors = ['#e74c3c', '#e67e22', '#f1c40f', '#2ecc71', '#27ae60'];
        const texts = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
        
        indicator.style.width = (strength * 20) + '%';
        indicator.style.background = colors[strength - 1] || '#e74c3c';
        indicator.nextElementSibling.textContent = texts[strength - 1] || 'Very Weak';
    }
    
    function createStrengthIndicator() {
        const container = document.createElement('div');
        container.className = 'password-strength-container';
        container.innerHTML = `
            <div class="password-strength-bar">
                <div id="password-strength" class="strength-indicator"></div>
            </div>
            <span class="strength-text"></span>
        `;
        
        newPassword.parentNode.appendChild(container);
        return document.getElementById('password-strength');
    }
    
    // Validate password match
    confirmPassword.addEventListener('input', function() {
        if (this.value !== newPassword.value) {
            this.style.borderColor = '#e74c3c';
        } else {
            this.style.borderColor = '#27ae60';
        }
    });

    // Wishlist functions
    function removeFromWishlist(foodId, button) {
        if (confirm('Remove this item from your wishlist?')) {
            fetch('toggle-wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'food_id=' + foodId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the card from DOM using the button element
                    const card = button.closest('.wishlist-card');
                    if (card) {
                        card.style.animation = 'fadeOut 0.3s ease';
                        setTimeout(() => {
                            card.remove();
                            // Update count
                            updateWishlistCount();
                            // Check if empty
                            checkEmptyWishlist();
                        }, 300);
                    }
                } else {
                    alert('Failed to remove item: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to remove item');
            });
        }
    }

    function addToCartFromWishlist(foodId) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'add-to-cart.php';

        const foodIdInput = document.createElement('input');
        foodIdInput.type = 'hidden';
        foodIdInput.name = 'food_id';
        foodIdInput.value = foodId;

        const quantityInput = document.createElement('input');
        quantityInput.type = 'hidden';
        quantityInput.name = 'quantity';
        quantityInput.value = 1;

        form.appendChild(foodIdInput);
        form.appendChild(quantityInput);
        document.body.appendChild(form);
        form.submit();
    }

    function updateWishlistCount() {
        const cards = document.querySelectorAll('.wishlist-card').length;
        const countSpan = document.querySelector('.wishlist-count');
        if (countSpan) {
            countSpan.textContent = cards + ' item' + (cards != 1 ? 's' : '');
        }
    }

    function checkEmptyWishlist() {
        const cards = document.querySelectorAll('.wishlist-card');
        const grid = document.querySelector('.wishlist-grid');
        
        if (cards.length === 0 && grid) {
            grid.innerHTML = `
                <div style="grid-column: 1/-1;">
                    <div class="empty-wishlist">
                        <i class="fas fa-heart"></i>
                        <h3>Your wishlist is empty</h3>
                        <p>Start adding your favorite items to your wishlist!</p>
                        <a href="menu.php" class="btn-continue-shopping">
                            <i class="fas fa-shopping-bag"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            `;
        }
    }

    // Add fade out animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }
    `;
    document.head.appendChild(style);
    </script>
    
    <style>
    .profile-container {
        max-width: 1400px;
        margin: 100px auto 50px;
        padding: 0 20px;
    }
    
    .profile-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .profile-header h1 {
        font-size: 2.5rem;
        color: #333;
        margin-bottom: 10px;
    }
    
    .profile-content {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 30px;
    }
    
    .profile-sidebar {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .user-card {
        background: white;
        border-radius: 10px;
        padding: 30px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .user-avatar {
        width: 80px;
        height: 80px;
        background: #667eea;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 600;
        margin: 0 auto 20px;
    }
    
    .user-info h3 {
        margin-bottom: 5px;
        color: #333;
    }
    
    .user-info p {
        color: #666;
        margin: 5px 0;
    }
    
    .member-since {
        font-size: 0.9rem;
        color: #999;
    }
    
    .sidebar-stats {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid #eee;
    }
    
    .stat-item:last-child {
        border-bottom: none;
    }
    
    .stat-item i {
        width: 40px;
        height: 40px;
        background: #f0f0f0;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #667eea;
        font-size: 1.2rem;
    }
    
    .stat-item div {
        flex: 1;
    }
    
    .stat-item span {
        display: block;
        font-weight: 600;
        color: #333;
        font-size: 1.1rem;
    }
    
    .stat-item small {
        color: #666;
        font-size: 0.9rem;
    }
    
    .sidebar-menu {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .sidebar-menu a {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 20px;
        color: #333;
        text-decoration: none;
        border-left: 4px solid transparent;
        transition: all 0.3s ease;
    }
    
    .sidebar-menu a:hover,
    .sidebar-menu a.active {
        background: #f8f9fa;
        border-left-color: #667eea;
        color: #667eea;
    }
    
    .sidebar-menu a.logout {
        color: #e74c3c;
    }
    
    .sidebar-menu a.logout:hover {
        border-left-color: #e74c3c;
    }
    
    .profile-main {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    
    .profile-section,
    .preferences-section,
    .danger-section {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .section-header {
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .section-header h2,
    .section-header h3 {
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .section-header small {
        color: #666;
        font-size: 0.9rem;
        margin-left: 10px;
    }
    
    .profile-form {
        margin-top: 20px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #555;
    }
    
    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-family: 'Poppins', sans-serif;
        font-size: 1rem;
    }
    
    .form-group input:disabled {
        background: #f5f5f5;
        cursor: not-allowed;
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }
    
    .form-text {
        display: block;
        margin-top: 5px;
        color: #666;
        font-size: 0.9rem;
    }
    
    .password-section {
        margin: 30px 0;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #667eea;
    }
    
    .password-strength-container {
        margin-top: 10px;
    }
    
    .password-strength-bar {
        height: 5px;
        background: #f0f0f0;
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 5px;
    }
    
    .strength-indicator {
        height: 100%;
        width: 0%;
        transition: all 0.3s ease;
    }
    
    .strength-text {
        font-size: 0.8rem;
        color: #666;
    }
    
    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }
    
    .btn-save,
    .btn-reset,
    .btn-danger {
        padding: 12px 25px;
        border-radius: 5px;
        border: none;
        font-family: 'Poppins', sans-serif;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-save {
        background: #27ae60;
        color: white;
    }
    
    .btn-reset {
        background: #95a5a6;
        color: white;
    }
    
    .btn-danger {
        background: #e74c3c;
        color: white;
    }
    
    .btn-save:hover,
    .btn-reset:hover,
    .btn-danger:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }
    
    .preferences-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .preference-item {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        transition: transform 0.3s ease;
    }
    
    .preference-item:hover {
        transform: translateY(-5px);
    }
    
    .preference-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .preference-header h3 {
        font-size: 1rem;
        color: #333;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }
    
    .slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    input:checked + .slider {
        background-color: #27ae60;
    }
    
    input:checked + .slider:before {
        transform: translateX(26px);
    }
    
    .btn-edit-prefs {
        background: #667eea;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }


    
    .language-select {
        padding: 5px 10px;
        border-radius: 3px;
        border: 1px solid #ddd;
        background: white;
    }
    
    .preference-item p {
        color: #666;
        font-size: 0.9rem;
        margin-top: 10px;
    }
    
    .danger-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }
    
    .danger-actions button {
        margin-bottom: 5px;
    }
    
    .danger-actions small {
        display: block;
        color: #666;
        font-size: 0.8rem;
    }
    
    @media (max-width: 768px) {
        .profile-content {
            grid-template-columns: 1fr;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .preferences-grid {
            grid-template-columns: 1fr;
        }
        
        .danger-actions {
            grid-template-columns: 1fr;
        }
    }
    </style>
</body>
</html>
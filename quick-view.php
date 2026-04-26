<?php
require_once '../includes/config.php';

if (!isset($_GET['id'])) {
    echo '<p>Food item not found</p>';
    exit();
}

$foodId = intval($_GET['id']);
$conn = getDBConnection();

// Get food details
$query = "SELECT f.*, c.name as category_name 
          FROM foods f 
          LEFT JOIN categories c ON f.category_id = c.id 
          WHERE f.id = $foodId AND f.is_available = 1";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    echo '<p>Food item not found</p>';
    exit();
}

$food = mysqli_fetch_assoc($result);

// Get similar foods
$similarQuery = "SELECT * FROM foods 
                 WHERE category_id = " . ($food['category_id'] ?? 0) . " 
                 AND id != $foodId 
                 AND is_available = 1 
                 LIMIT 3";
$similarResult = mysqli_query($conn, $similarQuery);
?>
<div class="quick-view-content">
    <div class="quick-view-grid">
        <div class="quick-view-image">
            <?php if ($food['image_path']): ?>
            <img src="../<?php echo $food['image_path']; ?>" alt="<?php echo $food['name']; ?>">
            <?php else: ?>
            <div class="no-image">
                <i class="fas fa-utensils fa-3x"></i>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="quick-view-details">
            <h2><?php echo $food['name']; ?></h2>
            
            <div class="food-meta">
                <?php if ($food['category_name']): ?>
                <span class="category">
                    <i class="fas fa-tag"></i> <?php echo $food['category_name']; ?>
                </span>
                <?php endif; ?>
                
                <span class="availability">
                    <i class="fas fa-check-circle"></i> Available
                </span>
            </div>
            
            <div class="food-price">
                <h3>₹<?php echo number_format($food['price'], 2); ?></h3>
            </div>
            
            <div class="food-description">
                <h4>Description</h4>
                <p><?php echo nl2br(htmlspecialchars($food['description'])); ?></p>
            </div>
            
            <div class="food-actions">
                <div class="quantity-selector">
                    <button class="qty-btn minus">-</button>
                    <input type="number" id="qty" value="1" min="1" max="10">
                    <button class="qty-btn plus">+</button>
                </div>
                
                <button class="btn-add-to-cart" data-food-id="<?php echo $foodId; ?>">
                    <i class="fas fa-cart-plus"></i> Add to Cart
                </button>
                
                <button class="btn-wishlist" data-food-id="<?php echo $foodId; ?>">
                    <i class="far fa-heart"></i> Add to Wishlist
                </button>
            </div>
            
            <div class="food-details">
                <div class="detail-item">
                    <i class="fas fa-fire"></i>
                    <span>Calories: Approximately 450-550</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-clock"></i>
                    <span>Preparation Time: 20-30 minutes</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-leaf"></i>
                    <span>Vegetarian: Yes</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-star"></i>
                    <span>Popular: Bestseller</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Similar Foods -->
    <?php if (mysqli_num_rows($similarResult) > 0): ?>
    <div class="similar-foods">
        <h3><i class="fas fa-utensils"></i> You Might Also Like</h3>
        <div class="similar-grid">
            <?php while($similar = mysqli_fetch_assoc($similarResult)): ?>
            <div class="similar-item">
                <div class="similar-image">
                    <?php if ($similar['image_path']): ?>
                    <img src="../<?php echo $similar['image_path']; ?>" alt="<?php echo $similar['name']; ?>">
                    <?php endif; ?>
                </div>
                <div class="similar-info">
                    <h4><?php echo $similar['name']; ?></h4>
                    <p class="similar-price">₹<?php echo number_format($similar['price'], 2); ?></p>
                    <button class="btn-quick-view" data-food-id="<?php echo $similar['id']; ?>">
                        Quick View
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.quick-view-content {
    max-width: 800px;
}

.quick-view-grid {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.quick-view-image {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.quick-view-image img {
    width: 100%;
    height: 300px;
    object-fit: cover;
}

.no-image {
    width: 100%;
    height: 300px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
}

.quick-view-details h2 {
    margin-bottom: 10px;
    color: #333;
    font-size: 1.8rem;
}

.food-meta {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    color: #666;
    font-size: 0.9rem;
}

.food-meta i {
    margin-right: 5px;
}

.category {
    background: #e3f2fd;
    color: #1976d2;
    padding: 3px 10px;
    border-radius: 12px;
}

.availability {
    color: #27ae60;
}

.food-price h3 {
    font-size: 2rem;
    color: #e74c3c;
    margin-bottom: 20px;
}

.food-description h4 {
    margin-bottom: 10px;
    color: #333;
    font-size: 1.2rem;
}

.food-description p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 25px;
}

.food-actions {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.quantity-selector {
    display: flex;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 5px;
    overflow: hidden;
}

.qty-btn {
    width: 35px;
    height: 35px;
    background: #f8f9fa;
    border: none;
    cursor: pointer;
    font-size: 1.2rem;
    font-weight: 600;
}

.qty-btn:hover {
    background: #e9ecef;
}

#qty {
    width: 50px;
    height: 35px;
    border: none;
    text-align: center;
    font-size: 1rem;
    border-left: 1px solid #ddd;
    border-right: 1px solid #ddd;
}

.btn-add-to-cart {
    background: #27ae60;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-wishlist {
    background: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-add-to-cart:hover,
.btn-wishlist:hover {
    opacity: 0.9;
}

.food-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #666;
    font-size: 0.9rem;
}

.detail-item i {
    color: #667eea;
    width: 20px;
}

.similar-foods {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #eee;
}

.similar-foods h3 {
    margin-bottom: 20px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.similar-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.similar-item {
    background: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.similar-item:hover {
    transform: translateY(-5px);
}

.similar-image {
    height: 100px;
    overflow: hidden;
}

.similar-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.similar-info {
    padding: 10px;
}

.similar-info h4 {
    font-size: 0.9rem;
    margin-bottom: 5px;
    color: #333;
}

.similar-price {
    color: #e74c3c;
    font-weight: 600;
    margin-bottom: 8px;
}

.btn-quick-view {
    width: 100%;
    background: #667eea;
    color: white;
    border: none;
    padding: 5px;
    border-radius: 3px;
    cursor: pointer;
    font-size: 0.8rem;
}

@media (max-width: 768px) {
    .quick-view-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-view-image {
        max-width: 300px;
        margin: 0 auto;
    }
    
    .food-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .quantity-selector {
        justify-content: center;
    }
    
    .food-details {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Quantity controls
document.querySelectorAll('.qty-btn').forEach(button => {
    button.addEventListener('click', function() {
        const input = document.getElementById('qty');
        let value = parseInt(input.value);
        
        if (this.classList.contains('minus') && value > 1) {
            input.value = value - 1;
        } else if (this.classList.contains('plus') && value < 10) {
            input.value = value + 1;
        }
    });
});

// Add to cart
document.querySelector('.btn-add-to-cart').addEventListener('click', function() {
    const foodId = this.getAttribute('data-food-id');
    const quantity = document.getElementById('qty').value;
    
    fetch('add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            food_id: foodId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Item added to cart!');
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add item to cart');
    });
});

// Quick view for similar items
document.querySelectorAll('.btn-quick-view').forEach(button => {
    button.addEventListener('click', function() {
        const foodId = this.getAttribute('data-food-id');
        loadQuickView(foodId);
    });
});

function loadQuickView(foodId) {
    fetch(`quick-view.php?id=${foodId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('quickViewContent').innerHTML = html;
        });
}
</script>
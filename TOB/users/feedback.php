<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$conn = getDBConnection();
$message = '';
$error = '';

// Get user's recent orders for feedback
$ordersQuery = "SELECT o.id, o.order_date, o.total_price 
                FROM orders o 
                WHERE o.user_id = $userId AND o.status = 'delivered'
                ORDER BY o.order_date DESC 
                LIMIT 10";
$ordersResult = mysqli_query($conn, $ordersQuery);

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : null;
    $rating = intval($_POST['rating']);
    $feedbackMsg = mysqli_real_escape_string($conn, $_POST['message']);
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $error = "Please select a valid rating between 1 and 5";
    } elseif (empty($feedbackMsg)) {
        $error = "Please enter your feedback message";
    } else {
        // Check if feedback already exists for this order
        if ($orderId) {
            $checkQuery = "SELECT id FROM feedback WHERE user_id = $userId AND order_id = $orderId";
            $checkResult = mysqli_query($conn, $checkQuery);
            
            if (mysqli_num_rows($checkResult) > 0) {
                $error = "You have already submitted feedback for this order";
            }
        }
        
        if (empty($error)) {
            $insertQuery = "INSERT INTO feedback (user_id, order_id, message, rating, status) 
                            VALUES ($userId, " . ($orderId ?: 'NULL') . ", '$feedbackMsg', $rating, 'pending')";
            
            if (mysqli_query($conn, $insertQuery)) {
                $message = "Thank you for your feedback! We appreciate your input.";
                $_POST = array(); // Clear form
            } else {
                $error = "Failed to submit feedback. Please try again.";
            }
        }
    }
}

// Get user's previous feedback
$feedbackQuery = "SELECT f.*, o.id as order_number 
                  FROM feedback f 
                  LEFT JOIN orders o ON f.order_id = o.id 
                  WHERE f.user_id = $userId 
                  ORDER BY f.created_at DESC";
$feedbackResult = mysqli_query($conn, $feedbackQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Bihar Traditional Food</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="feedback-container">
        <div class="feedback-header">
            <h1><i class="fas fa-comment-dots"></i> Share Your Feedback</h1>
            <p>Help us improve by sharing your experience</p>
        </div>
        
        <div class="feedback-content">
            <div class="feedback-left">
                <!-- Feedback Form -->
                <div class="feedback-form-container">
                    <h2><i class="fas fa-edit"></i> Submit Feedback</h2>
                    
                    <?php if($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="order_id">Select Order (Optional)</label>
                            <select id="order_id" name="order_id">
                                <option value="">-- Select an order --</option>
                                <?php while($order = mysqli_fetch_assoc($ordersResult)): ?>
                                <option value="<?php echo $order['id']; ?>" 
                                    <?php echo isset($_POST['order_id']) && $_POST['order_id'] == $order['id'] ? 'selected' : ''; ?>>
                                    Order #<?php echo $order['id']; ?> - 
                                    ₹<?php echo number_format($order['total_price'], 2); ?> - 
                                    <?php echo date('d/m/Y', strtotime($order['order_date'])); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="form-text">Select an order to provide specific feedback</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Your Rating</label>
                            <div class="rating-input">
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <input type="radio" id="star<?php echo $i; ?>" name="rating" 
                                           value="<?php echo $i; ?>" 
                                           <?php echo (isset($_POST['rating']) && $_POST['rating'] == $i) ? 'checked' : ''; ?>>
                                    <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> star">
                                        <i class="fas fa-star"></i>
                                    </label>
                                    <?php endfor; ?>
                                </div>
                                <div class="rating-labels">
                                    <span>Poor</span>
                                    <span>Fair</span>
                                    <span>Good</span>
                                    <span>Very Good</span>
                                    <span>Excellent</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Your Feedback *</label>
                            <textarea id="message" name="message" rows="6" required 
                                      placeholder="Share your experience with us..."><?php 
                                      echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                            <small class="form-text">Please be detailed about your experience</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="suggestions">Suggestions for Improvement (Optional)</label>
                            <textarea id="suggestions" name="suggestions" rows="3" 
                                      placeholder="Any suggestions to make our service better?"><?php 
                                      echo isset($_POST['suggestions']) ? htmlspecialchars($_POST['suggestions']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group terms">
                            <input type="checkbox" id="agree" name="agree" required>
                            <label for="agree">
                                I agree that my feedback may be used to improve services
                            </label>
                        </div>
                        
                        <button type="submit" class="btn-submit-feedback">
                            <i class="fas fa-paper-plane"></i> Submit Feedback
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="feedback-right">
                <!-- Previous Feedback -->
                <div class="previous-feedback">
                    <h2><i class="fas fa-history"></i> Your Previous Feedback</h2>
                    
                    <?php if (mysqli_num_rows($feedbackResult) > 0): ?>
                    <div class="feedback-list">
                        <?php while($feedback = mysqli_fetch_assoc($feedbackResult)): ?>
                        <div class="feedback-item">
                            <div class="feedback-header">
                                <div class="feedback-meta">
                                    <div class="feedback-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $feedback['rating']): ?>
                                        <i class="fas fa-star"></i>
                                        <?php else: ?>
                                        <i class="far fa-star"></i>
                                        <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="feedback-date">
                                        <?php echo date('d/m/Y', strtotime($feedback['created_at'])); ?>
                                    </span>
                                </div>
                                <?php if ($feedback['order_number']): ?>
                                <span class="order-badge">Order #<?php echo $feedback['order_number']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="feedback-body">
                                <p><?php echo nl2br(htmlspecialchars($feedback['message'])); ?></p>
                            </div>
                            
                            <div class="feedback-footer">
                                <span class="status-badge status-<?php echo $feedback['status']; ?>">
                                    <?php echo ucfirst($feedback['status']); ?>
                                </span>
                                <?php if ($feedback['status'] == 'resolved'): ?>
                                <span class="resolved-badge">
                                    <i class="fas fa-check-circle"></i> Issue Resolved
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="no-feedback">
                        <i class="fas fa-comment-slash fa-3x"></i>
                        <h3>No Feedback Submitted Yet</h3>
                        <p>Your feedback will appear here once submitted</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tips Section -->
                <div class="feedback-tips">
                    <h3><i class="fas fa-lightbulb"></i> Tips for Great Feedback</h3>
                    <ul>
                        <li>Be specific about what you liked or didn't like</li>
                        <li>Mention particular food items or services</li>
                        <li>Share suggestions for improvement</li>
                        <li>Be honest but respectful</li>
                        <li>Include order details if relevant</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <style>
    .feedback-container {
        max-width: 1200px;
        margin: 100px auto 50px;
        padding: 0 20px;
    }
    
    .feedback-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .feedback-header h1 {
        font-size: 2.5rem;
        color: #333;
        margin-bottom: 10px;
    }
    
    .feedback-content {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }
    
    .feedback-form-container,
    .previous-feedback,
    .feedback-tips {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .feedback-form-container h2,
    .previous-feedback h2,
    .feedback-tips h3 {
        margin-bottom: 20px;
        color: #333;
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
        font-weight: 500;
        color: #555;
    }
    
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-family: 'Poppins', sans-serif;
        font-size: 1rem;
    }
    
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .form-text {
        display: block;
        margin-top: 5px;
        color: #666;
        font-size: 0.9rem;
    }
    
    .rating-input {
        margin-bottom: 20px;
    }
    
    .stars {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-bottom: 10px;
    }
    
    .stars input {
        display: none;
    }
    
    .stars label {
        cursor: pointer;
        font-size: 2rem;
        color: #ddd;
        transition: color 0.2s;
    }
    
    .stars input:checked ~ label {
        color: #f1c40f;
    }
    
    .stars label:hover,
    .stars label:hover ~ label {
        color: #f1c40f;
    }
    
    .stars input:checked + label:hover,
    .stars input:checked + label:hover ~ label,
    .stars input:checked ~ label:hover,
    .stars input:checked ~ label:hover ~ label {
        color: #f1c40f;
    }
    
    .rating-labels {
        display: flex;
        justify-content: space-between;
        color: #666;
        font-size: 0.9rem;
        max-width: 300px;
        margin: 0 auto;
    }
    
    .terms {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .terms input {
        width: auto;
    }
    
    .btn-submit-feedback {
        width: 100%;
        background: #27ae60;
        color: white;
        border: none;
        padding: 15px;
        border-radius: 5px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .btn-submit-feedback:hover {
        background: #219653;
    }
    
    .feedback-list {
        max-height: 400px;
        overflow-y: auto;
        padding-right: 10px;
    }
    
    .feedback-item {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        border-left: 4px solid #667eea;
    }
    
    .feedback-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
    }
    
    .feedback-meta {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .feedback-rating {
        color: #f1c40f;
    }
    
    .feedback-date {
        font-size: 0.8rem;
        color: #666;
    }
    
    .order-badge {
        background: #667eea;
        color: white;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
    }
    
    .feedback-body {
        margin-bottom: 10px;
        line-height: 1.6;
        color: #333;
    }
    
    .feedback-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .resolved-badge {
        color: #27ae60;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .no-feedback {
        text-align: center;
        padding: 40px 20px;
        color: #666;
    }
    
    .no-feedback i {
        margin-bottom: 20px;
        color: #ddd;
    }
    
    .feedback-tips ul {
        list-style: none;
        padding-left: 0;
    }
    
    .feedback-tips li {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .feedback-tips li:before {
        content: "✓";
        color: #27ae60;
        font-weight: bold;
    }
    
    @media (max-width: 768px) {
        .feedback-content {
            grid-template-columns: 1fr;
        }
        
        .feedback-header h1 {
            font-size: 2rem;
        }
    }
    </style>
</body>
</html>
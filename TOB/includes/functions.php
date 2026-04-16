<?php
require_once 'config.php';

class Functions {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    // Upload image with validation
    public function uploadImage($file, $uploadDir = '../assets/uploads/') {
        $errors = [];
        $fileName = '';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Check for errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Upload failed with error code " . $file['error'];
            return ['success' => false, 'errors' => $errors];
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Only JPG, JPEG, PNG & GIF files are allowed";
            return ['success' => false, 'errors' => $errors];
        }
        
        // Validate file size (max 2MB)
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) {
            $errors[] = "File size must be less than 2MB";
            return ['success' => false, 'errors' => $errors];
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Create thumbnail (optional)
            $this->createThumbnail($targetPath, $uploadDir . 'thumbs/' . $fileName, 200, 200);
            
            return [
                'success' => true,
                'file_path' => str_replace('../', '', $targetPath),
                'file_name' => $fileName
            ];
        } else {
            $errors[] = "Failed to move uploaded file";
            return ['success' => false, 'errors' => $errors];
        }
    }
    
    // Create thumbnail
    private function createThumbnail($sourcePath, $destPath, $width, $height) {
        // Create thumbnail directory if it doesn't exist
        $thumbDir = dirname($destPath);
        if (!file_exists($thumbDir)) {
            mkdir($thumbDir, 0777, true);
        }
        
        // Get image type
        $imageInfo = getimagesize($sourcePath);
        $imageType = $imageInfo[2];
        
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }
        
        // Get original dimensions
        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);
        
        // Calculate thumbnail dimensions
        $ratio = min($width / $origWidth, $height / $origHeight);
        $thumbWidth = intval($origWidth * $ratio);
        $thumbHeight = intval($origHeight * $ratio);
        
        // Create thumbnail
        $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
        
        // Preserve transparency for PNG and GIF
        if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
            imagecolortransparent($thumbImage, imagecolorallocatealpha($thumbImage, 0, 0, 0, 127));
            imagealphablending($thumbImage, false);
            imagesavealpha($thumbImage, true);
        }
        
        // Resize image
        imagecopyresampled($thumbImage, $sourceImage, 0, 0, 0, 0, 
                          $thumbWidth, $thumbHeight, $origWidth, $origHeight);
        
        // Save thumbnail
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumbImage, $destPath, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumbImage, $destPath);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumbImage, $destPath);
                break;
        }
        
        // Free memory
        imagedestroy($sourceImage);
        imagedestroy($thumbImage);
        
        return true;
    }
    
    // Generate order invoice number
    public function generateInvoiceNumber() {
        $prefix = 'INV';
        $date = date('Ymd');
        $random = mt_rand(1000, 9999);
        
        return $prefix . $date . $random;
    }
    
    // Calculate order total with tax and delivery
    public function calculateOrderTotal($subtotal, $deliveryCharge = 40) {
        $tax = $subtotal * GST_RATE;
        $total = $subtotal + $deliveryCharge + $tax;
        
        return [
            'subtotal' => $subtotal,
            'delivery_charge' => $deliveryCharge,
            'tax' => $tax,
            'total' => $total
        ];
    }
    
    // Format currency
    public function formatCurrency($amount) {
        return '₹' . number_format($amount, 2);
    }
    
    // Format date
    public function formatDate($date, $format = 'F j, Y h:i A') {
        return date($format, strtotime($date));
    }
    
    // Get order status badge
    public function getStatusBadge($status) {
        $badges = [
            'pending' => '<span class="status-badge status-pending">Pending</span>',
            'processing' => '<span class="status-badge status-processing">Processing</span>',
            'delivered' => '<span class="status-badge status-delivered">Delivered</span>',
            'cancelled' => '<span class="status-badge status-cancelled">Cancelled</span>'
        ];
        
        return $badges[$status] ?? '<span class="status-badge">Unknown</span>';
    }
    
    // Get user's cart total
    public function getCartTotal($userId) {
        $query = "SELECT SUM(f.price * c.quantity) as total 
                  FROM cart c 
                  JOIN foods f ON c.food_id = f.id 
                  WHERE c.user_id = $userId AND f.is_available = 1";
        
        $result = mysqli_query($this->conn, $query);
        $row = mysqli_fetch_assoc($result);
        
        return $row['total'] ?? 0;
    }
    
    // Get user's cart count
    public function getCartCount($userId) {
        $query = "SELECT SUM(quantity) as count FROM cart WHERE user_id = $userId";
        $result = mysqli_query($this->conn, $query);
        $row = mysqli_fetch_assoc($result);
        
        return $row['count'] ?? 0;
    }
    
    // Send email notification
    public function sendEmail($to, $subject, $message, $headers = '') {
        if (empty($headers)) {
            $headers = "From: Bihar Food <noreply@biharfood.com>\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }
        
        return mail($to, $subject, $message, $headers);
    }
    
    // Generate random password
    public function generateRandomPassword($length = 8) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
    
    // Validate phone number
    public function validatePhone($phone) {
        $pattern = '/^[\+]?[1-9][\d]{0,15}$/';
        return preg_match($pattern, preg_replace('/[\s\-\(\)]/', '', $phone));
    }
    
    // Sanitize input
    public function sanitize($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = $this->sanitize($value);
            }
            return $input;
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    // Check if user has ordered before
    public function isReturningCustomer($userId) {
        $query = "SELECT COUNT(*) as count FROM orders WHERE user_id = $userId AND status = 'delivered'";
        $result = mysqli_query($this->conn, $query);
        $row = mysqli_fetch_assoc($result);
        
        return $row['count'] > 0;
    }
    
    // Get popular foods
    public function getPopularFoods($limit = 5) {
        $query = "SELECT f.*, COUNT(oi.food_id) as order_count 
                  FROM foods f 
                  LEFT JOIN order_items oi ON f.id = oi.food_id 
                  LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'delivered'
                  WHERE f.is_available = 1
                  GROUP BY f.id 
                  ORDER BY order_count DESC 
                  LIMIT $limit";
        
        $result = mysqli_query($this->conn, $query);
        $foods = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $foods[] = $row;
        }
        
        return $foods;
    }
    
    // Get sales summary for today
    public function getTodaySales() {
        $today = date('Y-m-d');
        $query = "SELECT 
                    COUNT(*) as orders,
                    SUM(total_price) as revenue,
                    AVG(total_price) as avg_order
                  FROM orders 
                  WHERE DATE(order_date) = '$today' AND status = 'delivered'";
        
        $result = mysqli_query($this->conn, $query);
        return mysqli_fetch_assoc($result);
    }
    
    // Close database connection
    public function closeConnection() {
        if ($this->conn) {
            mysqli_close($this->conn);
        }
    }
}
?>
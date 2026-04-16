<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();

if (!$auth->isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();
$message = '';
$error = '';
$messageADD = '';
$messageUPDATE = '';
$messageDELETE = '';
// Handle food deletion
if (isset($_GET['delete'])) {
    $foodId = intval($_GET['delete']);

    // Get image path first
    $getImageQuery = "SELECT image_path FROM foods WHERE id = $foodId";
    $imageResult = mysqli_query($conn, $getImageQuery);

    if ($imageResult && mysqli_num_rows($imageResult) > 0) {
        $food = mysqli_fetch_assoc($imageResult);
        // Delete image file if exists
        if ($food['image_path'] && file_exists('../' . $food['image_path'])) {
            unlink('../' . $food['image_path']);
        }
    }

    // Delete food from database
    $deleteQuery = "DELETE FROM foods WHERE id = $foodId";

    if (mysqli_query($conn, $deleteQuery)) {
        $messageDELETE = 'Food item deleted successfully';
    } else {
        $error = 'Failed to delete food item';
    }
}

// Handle form submission for adding/editing food
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;

    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../assets/uploads/foods/';

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;

        // Validate file type
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = 'assets/uploads/foods/' . $file_name;
            } else {
                $error = 'Failed to upload image';
            }
        } else {
            $error = 'Only JPG, JPEG, PNG & GIF files are allowed';
        }
    }

    // Check if editing existing food
    if (isset($_POST['food_id']) && !empty($_POST['food_id'])) {
        $food_id = intval($_POST['food_id']);

        // If new image uploaded, delete old image
        if ($image_path) {
            // Get old image path
            $oldImageQuery = "SELECT image_path FROM foods WHERE id = $food_id";
            $oldImageResult = mysqli_query($conn, $oldImageQuery);

            if ($oldImageResult && mysqli_num_rows($oldImageResult) > 0) {
                $oldFood = mysqli_fetch_assoc($oldImageResult);
                // Delete old image file if exists
                if ($oldFood['image_path'] && file_exists('../' . $oldFood['image_path'])) {
                    unlink('../' . $oldFood['image_path']);
                }
            }

            $updateQuery = "UPDATE foods SET 
                            name = '$name', 
                            description = '$description', 
                            price = $price, 
                            category_id = $category_id, 
                            image_path = '$image_path', 
                            is_available = $is_available,
                            is_featured = $is_featured,
                            is_popular = $is_popular,
                            updated_at = NOW()
                            WHERE id = $food_id";
        } else {
            // Keep existing image
            $updateQuery = "UPDATE foods SET 
                            name = '$name', 
                            description = '$description', 
                            price = $price, 
                            category_id = $category_id, 
                            is_available = $is_available,
                            is_featured = $is_featured,
                            is_popular = $is_popular,
                            updated_at = NOW()
                            WHERE id = $food_id";
        }

        if (mysqli_query($conn, $updateQuery)) {
            $messageUPDATE = 'Food item updated successfully';
        } else {
            $error = 'Failed to update food item: ' . mysqli_error($conn);
        }
    } else {
        // Add new food
        $insertQuery = "INSERT INTO foods (name, description, price, category_id, image_path, is_available, is_featured, is_popular) 
                        VALUES ('$name', '$description', $price, $category_id, '$image_path', $is_available, $is_featured, $is_popular)";

        if (mysqli_query($conn, $insertQuery)) {
            $messageADD = 'Food item added successfully';
        } else {
            $error = 'Failed to add food item: ' . mysqli_error($conn);
        }
    }
}

// Get all foods
$foodsQuery = "SELECT f.*, c.name as category_name 
               FROM foods f 
               LEFT JOIN categories c ON f.category_id = c.id 
               ORDER BY f.created_at DESC";
$foodsResult = mysqli_query($conn, $foodsQuery);

// Get categories for dropdown
$categoriesQuery = "SELECT * FROM categories ORDER BY name";
$categoriesResult = mysqli_query($conn, $categoriesQuery);

// Check if editing
$editFood = null;
if (isset($_GET['edit'])) {
    $foodId = intval($_GET['edit']);
    $editQuery = "SELECT * FROM foods WHERE id = $foodId";
    $editResult = mysqli_query($conn, $editQuery);

    if ($editResult && mysqli_num_rows($editResult) > 0) {
        $editFood = mysqli_fetch_assoc($editResult);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Foods - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<style>
    .form-control {
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        width: 100%;
        box-sizing: border-box;
    }

    .alert-success-food-update {
        background-color: #d1fae5;
        color: #065f46;
        border-left: 4px solid #10b981;
    }

    .alert-success-food-delete {
        background-color: #dbeafe;
        color: #1e40af;
        border-left: 4px solid #3b82f6;
    }

    .alert-success-food-add {
        background-color: #dcfce7;
        color: #166534;
        border-left: 4px solid #22c55e;
    }

    .alert-error {
        background-color: #fee2e2;
        color: #991b1b;
        border-left: 4px solid #ef4444;
    }

    /* Alert Icons */
    .alert i {
        font-size: 18px;
    }
</style>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <div class="admin-main">
            <div class="admin-header">
                <div class="header-left">
                    <h1><i class="fas fa-hamburger"></i> Manage Food Items</h1>
                    <p>Add, edit, or remove food items from the menu</p>
                </div>
            </div>

            <?php
            // Just check if variables are set before using them
            if (isset($error) && $error) {
                echo '<div class="alert-error">' . $error . '</div>';
            } elseif (isset($messageDELETE) && $messageDELETE) {
                echo '<div class="alert-success-food-delete">' . $messageDELETE . '</div>';
            } elseif (isset($messageUPDATE) && $messageUPDATE) {
                echo '<div class="alert-success-food-update">' . $messageUPDATE . '</div>';
            } elseif (isset($messageADD) && $messageADD) {
                echo '<div class="alert-success-food-add">' . $messageADD . '</div>';
            }
            ?>

            <!-- Add/Edit Food Form -->
            <div class="form-container">
                <h2><?php echo $editFood ? 'Edit Food Item' : 'Add New Food Item'; ?></h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <?php if ($editFood): ?>
                        <input type="hidden" name="food_id" value="<?php echo $editFood['id']; ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Food Name *</label>
                            <input type="text" id="name" name="name" class="form-control"
                                value="<?php echo $editFood ? $editFood['name'] : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="category_id">Category *</label>
                            <select id="category_id" name="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php while ($category = mysqli_fetch_assoc($categoriesResult)): ?>
                                    <option value="<?php echo $category['id']; ?>"
                                        <?php echo ($editFood && $editFood['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo $category['name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price (₹) *</label>
                            <input type="number" id="price" name="price" class="form-control"
                                step="0.01" min="0"
                                value="<?php echo $editFood ? $editFood['price'] : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="is_available">Availability</label>
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_available" name="is_available" value="1"
                                    <?php echo ($editFood && $editFood['is_available']) ? 'checked' : ''; ?>>
                                <label for="is_available">Available for order</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="is_featured">Visibility</label>
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_featured" name="is_featured" value="1"
                                    <?php echo ($editFood && $editFood['is_featured']) ? 'checked' : ''; ?>>
                                <label for="is_featured">Mark as Featured</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_popular" name="is_popular" value="1"
                                    <?php echo ($editFood && $editFood['is_popular']) ? 'checked' : ''; ?>>
                                <label for="is_popular">Mark as Popular</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" class="form-control"
                            rows="4" required><?php echo $editFood ? $editFood['description'] : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="image">Food Image</label>
                        <?php if ($editFood && $editFood['image_path']): ?>
                            <div class="image-preview">
                                <img src="../<?php echo $editFood['image_path']; ?>" alt="Current Image" style="max-width: 200px;">
                                <p>Current Image</p>
                            </div>
                        <?php endif; ?>

                        <div class="image-upload">
                            <input type="file" id="image" name="image" accept="image/*"
                                onchange="previewImage(this)">
                            <p>Click to upload food image (JPG, PNG, GIF)</p>
                            <div id="imagePreview" class="image-preview"></div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <?php echo $editFood ? 'Update Food' : 'Add Food'; ?>
                        </button>
                        <?php if ($editFood): ?>
                            <a href="foods.php" class="btn-secondary">Cancel Edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Food Items Table -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> All Food Items</h2>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Added On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($food = mysqli_fetch_assoc($foodsResult)): ?>
                                <tr>
                                    <td><?php echo $food['id']; ?></td>
                                    <td>
                                        <?php if ($food['image_path']): ?>
                                            <img src="../<?php echo $food['image_path']; ?>"
                                                alt="<?php echo $food['name']; ?>"
                                                style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                        <?php else: ?>
                                            <i class="fas fa-image" style="color: #ccc;"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $food['name']; ?></td>
                                    <td><?php echo $food['category_name'] ?? 'Uncategorized'; ?></td>
                                    <td>₹<?php echo number_format($food['price'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $food['is_available'] ? 'status-delivered' : 'status-cancelled'; ?>">
                                            <?php echo $food['is_available'] ? 'Available' : 'Unavailable'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($food['created_at'])); ?></td>
                                    <td>
                                        <a href="?edit=<?php echo $food['id']; ?>" class="btn-action btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $food['id']; ?>"
                                            class="btn-action btn-delete"
                                            onclick="return confirm('Are you sure you want to delete this food item?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" style="max-width: 200px;">
                    <p>Image Preview</p>
                `;
                    preview.style.display = 'block';
                }

                reader.readAsDataURL(input.files[0]);
            } else {
                preview.innerHTML = '';
                preview.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('[class*="alert-"]');
            alerts.forEach(function(alert) {
                setTimeout(function() {

                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';


                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>

</html>
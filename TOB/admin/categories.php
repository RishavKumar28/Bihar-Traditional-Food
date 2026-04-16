<?php
// categories.php
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

// Handle category deletion
if (isset($_GET['delete'])) {
    $categoryId = intval($_GET['delete']);

    // First check if category is being used by any food item
    $checkQuery = "SELECT COUNT(*) as food_count FROM foods WHERE category_id = $categoryId";
    $checkResult = mysqli_query($conn, $checkQuery);
    $checkData = mysqli_fetch_assoc($checkResult);

    if ($checkData['food_count'] > 0) {
        $error = 'Cannot delete category. It is being used by ' . $checkData['food_count'] . ' food item(s).';
    } else {
        // Get image path first
        $getImageQuery = "SELECT image FROM categories WHERE id = $categoryId";
        $imageResult = mysqli_query($conn, $getImageQuery);

        if ($imageResult && mysqli_num_rows($imageResult) > 0) {
            $category = mysqli_fetch_assoc($imageResult);
            // Delete image file if exists
            if ($category['image'] && file_exists('../' . $category['image'])) {
                unlink('../' . $category['image']);
            }
        }

        // Delete category from database
        $deleteQuery = "DELETE FROM categories WHERE id = $categoryId";

        if (mysqli_query($conn, $deleteQuery)) {
            $message = 'Category deleted successfully';
            // Refresh page to show updated list
            header("Location: categories.php?msg=deleted");
            exit();
        } else {
            $error = 'Failed to delete category';
        }
    }
}

// Handle form submission for adding/editing category
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $display_order = isset($_POST['display_order']) ? intval($_POST['display_order']) : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../assets/uploads/categories/';

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;

        // Validate file type
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = 'assets/uploads/categories/' . $file_name;
            } else {
                $error = 'Failed to upload image';
            }
        } else {
            $error = 'Only JPG, JPEG, PNG, GIF & WebP files are allowed';
        }
    }

    // Check if editing existing category
    if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
        $category_id = intval($_POST['category_id']);

        // If new image uploaded, delete old image
        if ($image) {
            // Get old image path
            $oldImageQuery = "SELECT image FROM categories WHERE id = $category_id";
            $oldImageResult = mysqli_query($conn, $oldImageQuery);

            if ($oldImageResult && mysqli_num_rows($oldImageResult) > 0) {
                $oldCategory = mysqli_fetch_assoc($oldImageResult);
                // Delete old image file if exists
                if ($oldCategory['image'] && file_exists('../' . $oldCategory['image'])) {
                    unlink('../' . $oldCategory['image']);
                }
            }

            $updateQuery = "UPDATE categories SET 
                            name = '$name', 
                            description = '$description', 
                            image = '$image', 
                            display_order = $display_order,
                            is_active = $is_active
                            WHERE id = $category_id";
        } else {
            // Keep existing image
            $updateQuery = "UPDATE categories SET 
                            name = '$name', 
                            description = '$description', 
                            display_order = $display_order,
                            is_active = $is_active
                            WHERE id = $category_id";
        }

        if (mysqli_query($conn, $updateQuery)) {
            $message = 'Category updated successfully';
            // Refresh page to clear form
            header("Location: categories.php?msg=updated");
            exit();
        } else {
            $error = 'Failed to update category: ' . mysqli_error($conn);
        }
    } else {
        // Add new category
        $insertQuery = "INSERT INTO categories (name, description, image, display_order, is_active) 
                        VALUES ('$name', '$description', '$image', $display_order, $is_active)";

        if (mysqli_query($conn, $insertQuery)) {
            $message = 'Category added successfully';
            // Refresh page to clear form
            header("Location: categories.php?msg=added");
            exit();
        } else {
            $error = 'Failed to add category: ' . mysqli_error($conn);
        }
    }
}

// Get all categories
$categoriesQuery = "SELECT * FROM categories ORDER BY display_order, name";
$categoriesResult = mysqli_query($conn, $categoriesQuery);

// Check if editing
$editCategory = null;
if (isset($_GET['edit'])) {
    $categoryId = intval($_GET['edit']);
    $editQuery = "SELECT * FROM categories WHERE id = $categoryId";
    $editResult = mysqli_query($conn, $editQuery);

    if ($editResult && mysqli_num_rows($editResult) > 0) {
        $editCategory = mysqli_fetch_assoc($editResult);
    }
}

// Check for success messages from redirect
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'added':
            $message = 'Category added successfully';
            break;
        case 'updated':
            $message = 'Category updated successfully';
            break;
        case 'deleted':
            $message = 'Category deleted successfully';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
            background-color: #f5f8fa;
        }

        .admin-main {
            flex: 1;
            padding: 30px;
            margin-left: 250px;
        }

        .admin-header {
            margin-bottom: 30px;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .admin-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-header p {
            color: #666;
            font-size: 16px;
            margin-bottom: 0;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .form-control {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
            box-sizing: border-box;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .form-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .form-container h2 {
            font-size: 24px;
            margin-bottom: 25px;
            color: #333;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
            min-width: 250px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 25px;
        }

        /* SMALLER IMAGE PREVIEW */
        .image-preview {
            margin-top: 10px;
            text-align: center;
        }

        .image-preview img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
            border: 2px solid #e2e8f0;
        }

        .image-upload input[type="file"] {
            margin-bottom: 10px;
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 6px;
            width: 100%;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .dashboard-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .section-header h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background-color: #f8fafc;
        }

        .data-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            font-size: 14px;
            white-space: nowrap;
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            font-size: 15px;
            vertical-align: middle;
        }

        .data-table img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid #e2e8f0;
        }

        .no-image {
            width: 60px;
            height: 60px;
            background: #f8fafc;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            border: 2px dashed #cbd5e1;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            display: inline-block;
            white-space: nowrap;
        }

        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge {
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            margin: 0 3px;
        }

        .btn-edit {
            background-color: #3b82f6;
            color: white;
        }

        .btn-delete {
            background-color: #ef4444;
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .action-cell {
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .admin-main {
                margin-left: 0;
                padding: 20px;
            }

            .form-row {
                flex-direction: column;
                gap: 15px;
            }

            .data-table th,
            .data-table td {
                padding: 10px;
                font-size: 14px;
            }

            .data-table img {
                width: 50px;
                height: 50px;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <div class="admin-main">
            <div class="admin-header">
                <div class="header-left">
                    <h1><i class="fas fa-list"></i> Manage Categories</h1>
                    <p>Add, edit, or remove food categories</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success" id="successAlert">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error" id="errorAlert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Add/Edit Category Form -->
            <div class="form-container">
                <h2><?php echo $editCategory ? 'Edit Category' : 'Add New Category'; ?></h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <?php if ($editCategory): ?>
                        <input type="hidden" name="category_id" value="<?php echo $editCategory['id']; ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Category Name *</label>
                            <input type="text" id="name" name="name" class="form-control"
                                value="<?php echo $editCategory ? $editCategory['name'] : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="display_order">Display Order</label>
                            <input type="number" id="display_order" name="display_order" class="form-control"
                                min="0" value="<?php echo $editCategory ? $editCategory['display_order'] : 0; ?>">
                            <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">Lower numbers appear first</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control"
                            rows="4"><?php echo $editCategory ? $editCategory['description'] : ''; ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="image">Category Image</label>
                            <?php if ($editCategory && $editCategory['image']): ?>
                                <div class="image-preview">
                                    <img src="../<?php echo $editCategory['image']; ?>" alt="Current Image">
                                    <p style="color: #666; font-size: 13px;">Current Image</p>
                                </div>
                            <?php endif; ?>

                            <div class="image-upload">
                                <input type="file" id="image" name="image" accept="image/*"
                                    onchange="previewImage(this)">
                                <p style="color: #666; font-size: 13px; margin-top: 5px;">Click to upload category image (JPG, PNG, GIF, WebP)</p>
                                <div id="imagePreview" class="image-preview"></div>
                            </div>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                            <?php echo ($editCategory && $editCategory['is_active']) ? 'checked' : ''; ?>>
                        <label for="is_active">Active (Visible on website)</label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> <?php echo $editCategory ? 'Update Category' : 'Add Category'; ?>
                        </button>
                        <?php if ($editCategory): ?>
                            <a href="categories.php" class="btn-secondary">
                                <i class="fas fa-times"></i> Cancel Edit
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Categories Table -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-list-alt"></i> All Categories</h2>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Food Items</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Reset categories result pointer
                            mysqli_data_seek($categoriesResult, 0);
                            if (mysqli_num_rows($categoriesResult) > 0) {
                                while ($category = mysqli_fetch_assoc($categoriesResult)):
                                    // Count food items in this category
                                    $countQuery = "SELECT COUNT(*) as item_count FROM foods WHERE category_id = " . $category['id'];
                                    $countResult = mysqli_query($conn, $countQuery);
                                    $countData = mysqli_fetch_assoc($countResult);
                            ?>
                                    <tr>
                                        <td><?php echo $category['id']; ?></td>
                                        <td>
                                            <?php if ($category['image']): ?>
                                                <img src="../<?php echo $category['image']; ?>"
                                                    alt="<?php echo $category['name']; ?>">
                                            <?php else: ?>
                                                <div class="no-image">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong style="color: #334155;"><?php echo htmlspecialchars($category['name']); ?></strong>
                                        </td>
                                        <td style="max-width: 200px;">
                                            <?php
                                            $description = htmlspecialchars($category['description']);
                                            echo strlen($description) > 80
                                                ? substr($description, 0, 80) . '...'
                                                : $description;
                                            ?>
                                        </td>
                                        <td><?php echo $category['display_order']; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $category['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge"><?php echo $countData['item_count']; ?> items</span>
                                        </td>
                                        <td class="action-cell">
                                            <a href="?edit=<?php echo $category['id']; ?>" class="btn-action btn-edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?php echo $category['id']; ?>"
                                                class="btn-action btn-delete"
                                                onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone.')"
                                                title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                            <?php endwhile;
                            } else {
                                echo '<tr><td colspan="8" style="text-align: center; padding: 30px; color: #666;">No categories found. Add your first category above.</td></tr>';
                            }
                            ?>
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
                        <img src="${e.target.result}" alt="Preview">
                        <p style="color: #666; font-size: 13px;">Image Preview</p>
                    `;
                    preview.style.display = 'block';
                }

                reader.readAsDataURL(input.files[0]);
            } else {
                preview.innerHTML = '';
                preview.style.display = 'none';
            }
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    alert.style.transition = 'all 0.5s ease';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                });
            }, 5000); // 5 seconds
        });

        // If editing, scroll to form
        <?php if ($editCategory): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelector('.form-container').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });
        <?php endif; ?>

        // Clear edit mode when clicking cancel
        document.querySelectorAll('a[href="categories.php"]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                // Remove edit parameter from URL
                if (window.location.search.includes('edit=')) {
                    e.preventDefault();
                    window.location.href = 'categories.php';
                }
            });
        });
    </script>
</body>

</html>
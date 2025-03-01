<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Fetch all categories with parent info
try {
    $stmt = $conn->query("SELECT c1.id, c1.name, c2.name AS parent_name 
                          FROM categories c1 
                          LEFT JOIN categories c2 ON c1.parent_id = c2.id 
                          ORDER BY c1.name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error loading categories: " . $e->getMessage();
}

// Handle status messages
$success_message = isset($_GET['success']) ? $_GET['success'] : '';
$error_message = isset($_GET['error']) ? $_GET['error'] : (isset($error_message) ? $error_message : '');

// Fetch all category names for JavaScript validation
$stmt = $conn->query("SELECT name FROM categories");
$category_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
$categories_json = json_encode($category_names);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Category List - Jewelry Shop</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; margin: 0; padding: 0; }
        .wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #343a40; color: white; position: fixed; height: 100%; }
        .sidebar .nav-link { color: white; padding: 15px 20px; }
        .sidebar .nav-link:hover { background: #495057; }
        .content { margin-left: 250px; padding: 20px; width: 100%; }
        .card { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-section { padding: 20px; }
        .category-suggestions { max-height: 150px; overflow-y: auto; border: 1px solid #ccc; display: none; }
        .category-suggestions div { padding: 5px; cursor: pointer; }
        .category-suggestions div:hover { background-color: #f0f0f0; }
        .invalid-feedback { display: block; }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar Menu -->
        <nav class="sidebar">
            <div class="p-3">
                <h4 class="text-white">Jewelry Admin</h4>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php#overview"><i class="fas fa-tachometer-alt"></i> Overview</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="category_list.php"><i class="fas fa-list"></i> Category List</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php#add-item"><i class="fas fa-gem"></i> Add Item</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php#receive-items"><i class="fas fa-truck-loading"></i> Receive Items</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php#create-invoice"><i class="fas fa-file-invoice"></i> Create Invoice</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php#search-invoice"><i class="fas fa-search"></i> Search Invoice</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php#stock-report"><i class="fas fa-chart-bar"></i> Stock Report</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="change_password.php"><i class="fas fa-key"></i> Change Password</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="content">
            <h2>Category List</h2>
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Add Category Form -->
            <div class="card mb-4">
                <div class="card-header">Add New Category</div>
                <div class="card-body form-section">
                    <form method="POST" action="process.php" id="addCategoryForm" onsubmit="return validateForm()">
                        <input type="hidden" name="action" value="add_category">
                        <div class="form-group">
                            <label for="category_name">Category Name (Alphanumeric only):</label>
                            <input type="text" name="category_name" id="category_name" class="form-control" 
                                   placeholder="Category Name" required pattern="[A-Za-z0-9\s]+" 
                                   oninput="showSuggestions(this.value)">
                            <div id="category-error" class="invalid-feedback"></div>
                            <div id="category-suggestions" class="category-suggestions"></div>
                        </div>
                        <div class="form-group">
                            <label for="parent_id">Parent Category (optional):</label>
                            <select name="parent_id" id="parent_id" class="form-control">
                                <option value="">None (Top-level Category)</option>
                                <?php
                                $stmt = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Category</button>
                    </form>
                </div>
            </div>

            <!-- Category List -->
            <div class="card">
                <div class="card-header">All Categories</div>
                <div class="card-body">
                    <?php if (empty($categories)): ?>
                        <p>No categories found.</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Parent Category</th>
                                    <th>Total Items</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <?php
                                    // Count items in this category
                                    $stmt = $conn->prepare("SELECT COUNT(*) FROM items WHERE category_id = ?");
                                    $stmt->execute([$category['id']]);
                                    $item_count = $stmt->fetchColumn();

                                    // Check subcategories
                                    $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
                                    $stmt->execute([$category['id']]);
                                    $subcategory_count = $stmt->fetchColumn();
                                    ?>
                                    <tr>
                                        <td><?php echo $category['id']; ?></td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo $category['parent_name'] ? htmlspecialchars($category['parent_name']) : 'None'; ?></td>
                                        <td><?php echo $item_count; ?></td>
                                        <td>
                                            <a href="category_items.php?category_id=<?php echo $category['id']; ?>" 
                                               class="btn btn-sm btn-info mr-1">
                                                <i class="fas fa-eye"></i> View Items
                                            </a>
                                            <a href="edit_category.php?id=<?php echo $category['id']; ?>" 
                                               class="btn btn-sm btn-warning mr-1">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <?php if ($item_count == 0 && $subcategory_count == 0): ?>
                                                <a href="process.php?action=delete_category&id=<?php echo $category['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($category['name']); ?>?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-danger" disabled 
                                                        title="Cannot delete: Items or subcategories exist">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const existingCategories = <?php echo $categories_json; ?>;

        function showSuggestions(input) {
            const suggestionsDiv = document.getElementById('category-suggestions');
            const errorDiv = document.getElementById('category-error');
            const categoryInput = document.getElementById('category_name');
            suggestionsDiv.innerHTML = '';
            suggestionsDiv.style.display = 'none';
            errorDiv.textContent = '';

            const alphanumericRegex = /^[A-Za-z0-9\s]+$/;
            if (!alphanumericRegex.test(input) && input !== '') {
                errorDiv.textContent = 'Only alphanumeric characters and spaces are allowed';
                categoryInput.classList.add('is-invalid');
                return;
            } else {
                categoryInput.classList.remove('is-invalid');
            }

            if (input.length > 0) {
                const matches = existingCategories.filter(cat => 
                    cat.toLowerCase().includes(input.toLowerCase())
                );
                
                if (matches.length > 0) {
                    matches.forEach(match => {
                        const div = document.createElement('div');
                        div.textContent = match;
                        div.onclick = () => {
                            categoryInput.value = match;
                            suggestionsDiv.style.display = 'none';
                            checkDuplicate(match);
                        };
                        suggestionsDiv.appendChild(div);
                    });
                    suggestionsDiv.style.display = 'block';
                }

                checkDuplicate(input);
            }
        }

        function checkDuplicate(input) {
            const errorDiv = document.getElementById('category-error');
            const categoryInput = document.getElementById('category_name');
            if (existingCategories.some(cat => cat.toLowerCase() === input.toLowerCase())) {
                errorDiv.textContent = 'This category already exists';
                categoryInput.classList.add('is-invalid');
            } else {
                categoryInput.classList.remove('is-invalid');
            }
        }

        function validateForm() {
            const categoryInput = document.getElementById('category_name');
            const input = categoryInput.value.trim();
            const alphanumericRegex = /^[A-Za-z0-9\s]+$/;
            
            if (!alphanumericRegex.test(input)) {
                document.getElementById('category-error').textContent = 'Only alphanumeric characters and spaces are allowed';
                categoryInput.classList.add('is-invalid');
                return false;
            }
            
            if (existingCategories.some(cat => cat.toLowerCase() === input.toLowerCase())) {
                document.getElementById('category-error').textContent = 'This category already exists';
                categoryInput.classList.add('is-invalid');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>
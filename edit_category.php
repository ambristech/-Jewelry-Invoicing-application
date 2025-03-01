<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$category_id = $_GET['id'] ?? null;
if (!$category_id) {
    header("Location: category_list.php");
    exit();
}

// Fetch category details
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$category) {
    header("Location: category_list.php?error=" . urlencode("Category not found"));
    exit();
}

// Fetch all category names for validation (excluding current category)
$stmt = $conn->prepare("SELECT name FROM categories WHERE id != ?");
$stmt->execute([$category_id]);
$category_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
$categories_json = json_encode($category_names);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_name = trim($_POST['category_name'] ?? '');
    $parent_id = $_POST['parent_id'] === '' ? null : $_POST['parent_id'];
    
    if (!preg_match('/^[A-Za-z0-9\s]+$/', $new_name)) {
        $error_message = "Category name must be alphanumeric";
    } elseif (in_array(strtolower($new_name), array_map('strtolower', $category_names))) {
        $error_message = "Category '$new_name' already exists";
    } elseif ($parent_id == $category_id) {
        $error_message = "A category cannot be its own parent";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, parent_id = ? WHERE id = ?");
            $stmt->execute([$new_name, $parent_id, $category_id]);
            header("Location: category_list.php?success=" . urlencode("Category updated successfully"));
            exit();
        } catch(PDOException $e) {
            $error_message = "Error updating category: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Category - Jewelry Shop</title>
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
            <h2>Edit Category</h2>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">Edit <?php echo htmlspecialchars($category['name']); ?></div>
                <div class="card-body form-section">
                    <form method="POST" id="editCategoryForm" onsubmit="return validateForm()">
                        <div class="form-group">
                            <label for="category_name">Category Name (Alphanumeric only):</label>
                            <input type="text" name="category_name" id="category_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($category['name']); ?>" required 
                                   pattern="[A-Za-z0-9\s]+" oninput="showSuggestions(this.value)">
                            <div id="category-error" class="invalid-feedback"></div>
                            <div id="category-suggestions" class="category-suggestions"></div>
                        </div>
                        <div class="form-group">
                            <label for="parent_id">Parent Category (optional):</label>
                            <select name="parent_id" id="parent_id" class="form-control">
                                <option value="">None (Top-level Category)</option>
                                <?php
                                $stmt = $conn->prepare("SELECT id, name FROM categories WHERE id != ? ORDER BY name ASC");
                                $stmt->execute([$category_id]);
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $selected = $row['id'] == $category['parent_id'] ? 'selected' : '';
                                    echo "<option value='{$row['id']}' $selected>{$row['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Category</button>
                        <a href="category_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                    </form>
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
            const parentInput = document.getElementById('parent_id');
            const input = categoryInput.value.trim();
            const parentId = parentInput.value;
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
            
            if (parentId === '<?php echo $category_id; ?>') {
                document.getElementById('category-error').textContent = 'A category cannot be its own parent';
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>
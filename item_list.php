<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Fetch all items with category info
try {
    $stmt = $conn->query("SELECT i.id, i.name, i.price, i.stock, c.name AS category_name 
                          FROM items i 
                          LEFT JOIN categories c ON i.category_id = c.id 
                          ORDER BY i.name ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error loading items: " . $e->getMessage();
}

// Handle status messages
$success_message = isset($_GET['success']) ? $_GET['success'] : '';
$error_message = isset($_GET['error']) ? $_GET['error'] : (isset($error_message) ? $error_message : '');

// Fetch all item names for JavaScript validation
$stmt = $conn->query("SELECT name FROM items");
$item_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
$items_json = json_encode($item_names);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Item List - Jewelry Shop</title>
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
        .item-suggestions { max-height: 150px; overflow-y: auto; border: 1px solid #ccc; display: none; }
        .item-suggestions div { padding: 5px; cursor: pointer; }
        .item-suggestions div:hover { background-color: #f0f0f0; }
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
                    <a class="nav-link" href="item_list.php"><i class="fas fa-gem"></i> Item List</a>
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
            <h2>Item List</h2>
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

            <!-- Add Item Form -->
            <div class="card mb-4">
                <div class="card-header">Add New Item</div>
                <div class="card-body form-section">
                    <form method="POST" action="process.php" id="addItemForm" onsubmit="return validateForm()">
                        <input type="hidden" name="action" value="add_item">
                        <div class="form-group">
                            <label for="item_name">Item Name (Alphanumeric only):</label>
                            <input type="text" name="item_name" id="item_name" class="form-control" 
                                   placeholder="Item Name" required pattern="[A-Za-z0-9\s]+" 
                                   oninput="showSuggestions(this.value)">
                            <div id="item-error" class="invalid-feedback"></div>
                            <div id="item-suggestions" class="item-suggestions"></div>
                        </div>
                        <div class="form-group">
                            <label for="category_id">Category:</label>
                            <select name="category_id" id="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php
                                $stmt = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="price">Price:</label>
                            <input type="number" name="price" id="price" class="form-control" 
                                   placeholder="Price" step="0.01" min="0" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Item</button>
                    </form>
                </div>
            </div>

            <!-- Item List -->
            <div class="card">
                <div class="card-header">All Items</div>
                <div class="card-body">
                    <?php if (empty($items)): ?>
                        <p>No items found.</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <?php
                                    // Check if item has been received
                                    $stmt = $conn->prepare("SELECT COUNT(*) FROM received_items WHERE item_id = ?");
                                    $stmt->execute([$item['id']]);
                                    $receive_count = $stmt->fetchColumn();
                                    ?>
                                    <tr>
                                        <td><?php echo $item['id']; ?></td>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo $item['category_name'] ? htmlspecialchars($item['category_name']) : 'None'; ?></td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['stock']; ?></td>
                                        <td>
                                            <a href="item_invoices.php?item_id=<?php echo $item['id']; ?>" 
                                               class="btn btn-sm btn-info mr-1">
                                                <i class="fas fa-eye"></i> View Invoices
                                            </a>
                                            <?php if ($receive_count == 0): ?>
                                                <a href="process.php?action=delete_item&id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($item['name']); ?>?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-danger" disabled 
                                                        title="Cannot delete: Item has been received">
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
        const existingItems = <?php echo $items_json; ?>;

        function showSuggestions(input) {
            const suggestionsDiv = document.getElementById('item-suggestions');
            const errorDiv = document.getElementById('item-error');
            const itemInput = document.getElementById('item_name');
            suggestionsDiv.innerHTML = '';
            suggestionsDiv.style.display = 'none';
            errorDiv.textContent = '';

            const alphanumericRegex = /^[A-Za-z0-9\s]+$/;
            if (!alphanumericRegex.test(input) && input !== '') {
                errorDiv.textContent = 'Only alphanumeric characters and spaces are allowed';
                itemInput.classList.add('is-invalid');
                return;
            } else {
                itemInput.classList.remove('is-invalid');
            }

            if (input.length > 0) {
                const matches = existingItems.filter(item => 
                    item.toLowerCase().includes(input.toLowerCase())
                );
                
                if (matches.length > 0) {
                    matches.forEach(match => {
                        const div = document.createElement('div');
                        div.textContent = match;
                        div.onclick = () => {
                            itemInput.value = match;
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
            const errorDiv = document.getElementById('item-error');
            const itemInput = document.getElementById('item_name');
            if (existingItems.some(item => item.toLowerCase() === input.toLowerCase())) {
                errorDiv.textContent = 'This item already exists';
                itemInput.classList.add('is-invalid');
            } else {
                itemInput.classList.remove('is-invalid');
            }
        }

        function validateForm() {
            const itemInput = document.getElementById('item_name');
            const input = itemInput.value.trim();
            const alphanumericRegex = /^[A-Za-z0-9\s]+$/;
            
            if (!alphanumericRegex.test(input)) {
                document.getElementById('item-error').textContent = 'Only alphanumeric characters and spaces are allowed';
                itemInput.classList.add('is-invalid');
                return false;
            }
            
            if (existingItems.some(item => item.toLowerCase() === input.toLowerCase())) {
                document.getElementById('item-error').textContent = 'This item already exists';
                itemInput.classList.add('is-invalid');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>
<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$category_id = $_GET['category_id'] ?? null;
if (!$category_id) {
    header("Location: category_list.php");
    exit();
}

try {
    // Get category name
    $stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    $category_name = $category ? $category['name'] : 'Unknown';

    // Get items in this category
    $stmt = $conn->prepare("SELECT * FROM items WHERE category_id = ? ORDER BY name ASC");
    $stmt->execute([$category_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error loading items: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Items in <?php echo htmlspecialchars($category_name); ?> - Jewelry Shop</title>
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
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar Menu (same as category_list.php) -->
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
                    <a class="nav-link" href="dashboard.php#add-category"><i class="fas fa-plus-circle"></i> Add Category</a>
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
            <h2>Items in <?php echo htmlspecialchars($category_name); ?></h2>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">Items List</div>
                <div class="card-body">
                    <a href="category_list.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Categories</a>
                    <?php if (empty($items)): ?>
                        <p>No items found in this category.</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo $item['id']; ?></td>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['stock']; ?></td>
                                        <td>
                                            <a href="item_invoices.php?item_id=<?php echo $item['id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View Invoices
                                            </a>
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
</body>
</html>
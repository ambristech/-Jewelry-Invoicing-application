<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Fetch stats
try {
    $total_items = $conn->query("SELECT COUNT(*) FROM items")->fetchColumn();
    $total_stock = $conn->query("SELECT SUM(stock) FROM items")->fetchColumn();
    $total_invoices = $conn->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
    $total_sales = $conn->query("SELECT SUM(total_amount) FROM invoices")->fetchColumn();
} catch(PDOException $e) {
    $error_message = "Error loading stats: " . $e->getMessage();
}

// Handle status messages
$success_message = isset($_GET['success']) ? $_GET['success'] : '';
$error_message = isset($_GET['error']) ? $_GET['error'] : (isset($error_message) ? $error_message : '');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Jewelry Shop Admin Dashboard</title>
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
        .stat-card { padding: 20px; text-align: center; }
        .form-section { padding: 20px; }
        .item-row { display: flex; gap: 10px; margin-bottom: 10px; }
        .item-row select, .item-row input { flex: 1; }
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
                    <a class="nav-link" href="#overview"><i class="fas fa-tachometer-alt"></i> Overview</a>
                </li>
				<li class="nav-item">
                    <a class="nav-link" href="category_list.php"><i class="fas fa-list"></i> Category List</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#add-category"><i class="fas fa-plus-circle"></i> Add Category</a>
                </li>
				<li class="nav-item">
                    <a class="nav-link" href="item_list.php"><i class="fas fa-gem"></i> Item List</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#add-item"><i class="fas fa-gem"></i> Add Item</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#receive-items"><i class="fas fa-truck-loading"></i> Receive Items</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#create-invoice"><i class="fas fa-file-invoice"></i> Create Invoice</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#search-invoice"><i class="fas fa-search"></i> Search Invoice</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#stock-report"><i class="fas fa-chart-bar"></i> Stock Report</a>
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
            <!-- Status Messages -->
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

            <!-- Overview Section -->
            <section id="overview">
                <h2>Overview</h2>
                <div class="row">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white">
                            <h5>Total Items</h5>
                            <h3><?php echo $total_items ?? 0; ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-success text-white">
                            <h5>Total Stock</h5>
                            <h3><?php echo $total_stock ?? 0; ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-info text-white">
                            <h5>Total Invoices</h5>
                            <h3><?php echo $total_invoices ?? 0; ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-white">
                            <h5>Total Sales</h5>
                            <h3>$<?php echo number_format($total_sales ?? 0, 2); ?></h3>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Add Category Section -->
            <section id="add-category" class="mt-4">
                <div class="card">
                    <div class="card-header">Add Category</div>
                    <div class="card-body form-section">
                        <form method="POST" action="process.php">
                            <input type="hidden" name="action" value="add_category">
                            <div class="form-group">
                                <input type="text" name="category_name" class="form-control" placeholder="Category Name" required>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Category</button>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Add Item Section -->
            <section id="add-item" class="mt-4">
                <div class="card">
                    <div class="card-header">Add Item</div>
                    <div class="card-body form-section">
                        <form method="POST" action="process.php">
                            <input type="hidden" name="action" value="add_item">
                            <div class="form-group">
                                <select name="category_id" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php
                                    $stmt = $conn->query("SELECT * FROM categories");
                                    while ($row = $stmt->fetch()) {
                                        echo "<option value='{$row['id']}'>{$row['name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="text" name="item_name" class="form-control" placeholder="Item Name" required>
                            </div>
                            <div class="form-group">
                                <input type="number" name="price" class="form-control" placeholder="Price" step="0.01" min="0" required>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Item</button>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Receive Items Section -->
            <section id="receive-items" class="mt-4">
                <div class="card">
                    <div class="card-header">Receive Items</div>
                    <div class="card-body form-section">
                        <form method="POST" action="process.php">
                            <input type="hidden" name="action" value="receive_item">
                            <div class="form-group">
                                <select name="item_id" class="form-control" required>
                                    <option value="">Select Item</option>
                                    <?php
                                    $stmt = $conn->query("SELECT i.id, i.name, c.name as category FROM items i JOIN categories c ON i.category_id = c.id");
                                    while ($row = $stmt->fetch()) {
                                        echo "<option value='{$row['id']}'>{$row['name']} ({$row['category']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="number" name="quantity" class="form-control" placeholder="Quantity" min="1" required>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Receive</button>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Create Invoice Section -->
            <section id="create-invoice" class="mt-4">
                <div class="card">
                    <div class="card-header">Create Invoice</div>
                    <div class="card-body form-section">
                        <form method="POST" action="process.php">
                            <input type="hidden" name="action" value="create_invoice">
                            <div class="form-group">
                                <input type="text" name="customer_name" class="form-control" placeholder="Customer Name" required>
                            </div>
                            <div id="items">
                                <div class="item-row">
                                    <select name="item_id[]" class="form-control" required>
                                        <option value="">Select Item</option>
                                        <?php
                                        $stmt = $conn->query("SELECT i.id, i.name, i.price, c.name as category FROM items i JOIN categories c ON i.category_id = c.id");
                                        while ($row = $stmt->fetch()) {
                                            echo "<option value='{$row['id']}'>{$row['name']} ({$row['category']}) - \${$row['price']}</option>";
                                        }
                                        ?>
                                    </select>
                                    <input type="number" name="quantity[]" class="form-control" placeholder="Qty" min="1" required>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary mt-2" onclick="addItemRow()"><i class="fas fa-plus"></i> Add Item</button>
                            <button type="submit" class="btn btn-primary mt-2"><i class="fas fa-file-invoice"></i> Create Invoice</button>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Search Invoice Section -->
            <section id="search-invoice" class="mt-4">
                <div class="card">
                    <div class="card-header">Search Invoice</div>
                    <div class="card-body form-section">
                        <form method="GET" action="search.php">
                            <div class="form-group">
                                <input type="text" name="invoice_number" class="form-control" placeholder="Invoice Number">
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Stock Report Section -->
            <section id="stock-report" class="mt-4">
                <div class="card">
                    <div class="card-header">Stock Report</div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Current Stock</th>
                                    <th>Total Received</th>
                                    <th>Total Sold</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $conn->query("SELECT i.id, i.name, i.stock, c.name as category,
                                    (SELECT SUM(ri.quantity) FROM received_items ri WHERE ri.item_id = i.id) as total_received,
                                    (SELECT SUM(ii.quantity) FROM invoice_items ii WHERE ii.item_id = i.id) as total_sold
                                    FROM items i JOIN categories c ON i.category_id = c.id");
                                while ($row = $stmt->fetch()) {
                                    echo "<tr>
                                        <td>{$row['name']}</td>
                                        <td>{$row['category']}</td>
                                        <td>{$row['stock']}</td>
                                        <td>" . ($row['total_received'] ?? 0) . "</td>
                                        <td>" . ($row['total_sold'] ?? 0) . "</td>
                                        <td><a href='item_invoices.php?item_id={$row['id']}' class='btn btn-sm btn-info'><i class='fas fa-eye'></i> Invoices</a></td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addItemRow() {
            const itemsDiv = document.getElementById('items');
            const newRow = document.createElement('div');
            newRow.className = 'item-row';
            newRow.innerHTML = `
                <select name="item_id[]" class="form-control" required>
                    <option value="">Select Item</option>
                    <?php
                    $stmt = $conn->query("SELECT i.id, i.name, i.price, c.name as category FROM items i JOIN categories c ON i.category_id = c.id");
                    while ($row = $stmt->fetch()) {
                        echo "<option value='{$row['id']}'>{$row['name']} ({$row['category']}) - \${$row['price']}</option>";
                    }
                    ?>
                </select>
                <input type="number" name="quantity[]" class="form-control" placeholder="Qty" min="1" required>
            `;
            itemsDiv.appendChild(newRow);
        }

        // Smooth scrolling for menu links
        document.querySelectorAll('.sidebar a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                target.scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>
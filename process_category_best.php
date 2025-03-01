<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'add_category':
        $category_name = trim($_POST['category_name'] ?? '');
        $parent_id = $_POST['parent_id'] === '' ? null : $_POST['parent_id'];
        
        // Server-side validation
        if (!preg_match('/^[A-Za-z0-9\s]+$/', $category_name)) {
            header("Location: category_list.php?error=" . urlencode("Category name must be alphanumeric"));
            exit();
        }

        // Check for duplicate
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([$category_name]);
            if ($stmt->fetchColumn() > 0) {
                header("Location: category_list.php?error=" . urlencode("Category '$category_name' already exists"));
                exit();
            }

            // Prevent self-referencing
            if ($parent_id && $parent_id == $category_id) {
                header("Location: category_list.php?error=" . urlencode("A category cannot be its own parent"));
                exit();
            }

            $stmt = $conn->prepare("INSERT INTO categories (name, parent_id) VALUES (?, ?)");
            $stmt->execute([$category_name, $parent_id]);
            header("Location: category_list.php?success=" . urlencode("Category '$category_name' added successfully"));
        } catch(PDOException $e) {
            header("Location: category_list.php?error=" . urlencode("Error adding category: " . $e->getMessage()));
        }
        exit();

    case 'delete_category':
        $category_id = $_GET['id'] ?? null;
        if (!$category_id) {
            header("Location: category_list.php?error=" . urlencode("Invalid category ID"));
            exit();
        }

        try {
            // Get category name for success message
            $stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $category_name = $stmt->fetchColumn();
            if (!$category_name) {
                header("Location: category_list.php?error=" . urlencode("Category not found"));
                exit();
            }

            // Check items in this category
            $stmt = $conn->prepare("SELECT COUNT(*) FROM items WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $item_count = $stmt->fetchColumn();

            if ($item_count > 0) {
                header("Location: category_list.php?error=" . urlencode("Cannot delete '$category_name': Items exist in this category"));
                exit();
            }

            // Check for subcategories and their items recursively
            $subcategory_ids = [$category_id];
            $all_subcategory_ids = [$category_id];
            while (!empty($subcategory_ids)) {
                $placeholders = implode(',', array_fill(0, count($subcategory_ids), '?'));
                $stmt = $conn->prepare("SELECT id FROM categories WHERE parent_id IN ($placeholders)");
                $stmt->execute($subcategory_ids);
                $subcategory_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $all_subcategory_ids = array_merge($all_subcategory_ids, $subcategory_ids);
            }

            // Check items in all subcategories
            if (count($all_subcategory_ids) > 1) { // More than just the parent category
                $placeholders = implode(',', array_fill(0, count($all_subcategory_ids), '?'));
                $stmt = $conn->prepare("SELECT COUNT(*) FROM items WHERE category_id IN ($placeholders)");
                $stmt->execute($all_subcategory_ids);
                $subcategory_item_count = $stmt->fetchColumn();

                if ($subcategory_item_count > 0 || count($all_subcategory_ids) > 1) {
                    header("Location: category_list.php?error=" . urlencode("Cannot delete '$category_name': Subcategories or items in subcategories exist"));
                    exit();
                }
            }

            // Delete the category
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            header("Location: category_list.php?success=" . urlencode("Category '$category_name' deleted successfully"));
        } catch(PDOException $e) {
            header("Location: category_list.php?error=" . urlencode("Error deleting category: " . $e->getMessage()));
        }
        exit();

    case 'add_item':
        try {
            $stmt = $conn->prepare("INSERT INTO items (category_id, name, price) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['category_id'], $_POST['item_name'], $_POST['price']]);
            header("Location: dashboard.php?success=" . urlencode("Item added successfully"));
        } catch(PDOException $e) {
            header("Location: dashboard.php?error=" . urlencode("Error adding item: " . $e->getMessage()));
        }
        exit();

    case 'receive_item':
        try {
            $stmt = $conn->prepare("INSERT INTO received_items (item_id, quantity) VALUES (?, ?)");
            $stmt->execute([$_POST['item_id'], $_POST['quantity']]);
            
            $stmt = $conn->prepare("UPDATE items SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$_POST['quantity'], $_POST['item_id']]);
            header("Location: dashboard.php?success=" . urlencode("Items received successfully"));
        } catch(PDOException $e) {
            header("Location: dashboard.php?error=" . urlencode("Error receiving items: " . $e->getMessage()));
        }
        exit();

    case 'create_invoice':
        try {
            $invoice_number = 'INV' . time();
            $total = 0;
            
            $stmt = $conn->prepare("SELECT id, price FROM items WHERE id IN (" . implode(',', array_fill(0, count($_POST['item_id']), '?')) . ")");
            $stmt->execute($_POST['item_id']);
            $items = $stmt->fetchAll();
            $item_prices = array_column($items, 'price', 'id');
            
            foreach ($_POST['item_id'] as $index => $item_id) {
                $total += $item_prices[$item_id] * $_POST['quantity'][$index];
            }
            
            $stmt = $conn->prepare("INSERT INTO invoices (invoice_number, customer_name, total_amount) VALUES (?, ?, ?)");
            $stmt->execute([$invoice_number, $_POST['customer_name'], $total]);
            $invoice_id = $conn->lastInsertId();
            
            foreach ($_POST['item_id'] as $index => $item_id) {
                $stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$invoice_id, $item_id, $_POST['quantity'][$index], $item_prices[$item_id]]);
                
                $stmt = $conn->prepare("UPDATE items SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$_POST['quantity'][$index], $item_id]);
            }
            header("Location: dashboard.php?success=" . urlencode("Invoice created successfully"));
        } catch(PDOException $e) {
            header("Location: dashboard.php?error=" . urlencode("Error creating invoice: " . $e->getMessage()));
        }
        exit();

    default:
        header("Location: dashboard.php?error=" . urlencode("Invalid action"));
        exit();
}
?>
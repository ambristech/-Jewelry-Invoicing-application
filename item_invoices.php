<?php
require_once 'db_connect.php';
$item_id = $_GET['item_id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Item Invoices</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-3">
        <h2>Invoices for Item</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Customer</th>
                    <th>Quantity</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conn->prepare("SELECT i.*, ii.quantity FROM invoices i 
                    JOIN invoice_items ii ON i.id = ii.invoice_id 
                    WHERE ii.item_id = ?");
                $stmt->execute([$item_id]);
                while ($row = $stmt->fetch()) {
                    echo "<tr>
                        <td>{$row['invoice_number']}</td>
                        <td>{$row['customer_name']}</td>
                        <td>{$row['quantity']}</td>
                        <td>{$row['created_at']}</td>
                        <td><a href='invoice.php?id={$row['id']}' class='btn btn-info'>View</a></td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
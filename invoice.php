<?php
require_once 'db_connect.php';
$invoice_id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM invoices WHERE id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

$stmt = $conn->prepare("SELECT ii.*, i.name FROM invoice_items ii JOIN items i ON ii.item_id = i.id WHERE ii.invoice_id = ?");
$stmt->execute([$invoice_id]);
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Invoice <?php echo $invoice['invoice_number']; ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-3">
        <h2>Invoice #<?php echo $invoice['invoice_number']; ?></h2>
        <p>Customer: <?php echo $invoice['customer_name']; ?></p>
        <p>Date: <?php echo $invoice['created_at']; ?></p>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item) { ?>
                    <tr>
                        <td><?php echo $item['name']; ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo $item['price']; ?></td>
                        <td><?php echo $item['quantity'] * $item['price']; ?></td>
                    </tr>
                <?php } ?>
                <tr>
                    <td colspan="3"><strong>Total</strong></td>
                    <td><strong><?php echo $invoice['total_amount']; ?></strong></td>
                </tr>
            </tbody>
        </table>
        
        <button onclick="window.print()" class="btn btn-primary">Print</button>
        <a href="download_invoice.php?id=<?php echo $invoice_id; ?>" class="btn btn-success">Download PDF</a>
    </div>
</body>
</html>
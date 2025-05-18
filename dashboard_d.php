<?php
include 'config.php';
$conn = mysqli_connect("localhost", "root", "", "goldengown");

function sizeDifferenceFlag($products_json, $notes) {
    $sizes = json_decode($products_json, true);
    if (!$sizes || !is_array($sizes)) return 'GREEN';

    $product = $sizes[0]; // First product only
    $top = strtoupper(trim($product['Size']['Top-Size'] ?? ''));
    $bottom = strtoupper(trim($product['Size']['Bottom-Size'] ?? ''));

    $size_order = ["S", "M", "L", "XL"];
    // Add 2XL to 10XL
    for ($i = 2; $i <= 10; $i++) {
        $size_order[] = $i . "XL";
    }

    $map = array_flip($size_order);

    if (!isset($map[$top]) || !isset($map[$bottom])) return 'GREEN';

    $diff = abs($map[$top] - $map[$bottom]);
    if ($diff > 2 && empty(trim($notes))) {
        return 'RED';
    }
    return 'GREEN';
}


$result = mysqli_query($conn, "SELECT * FROM orders ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Dashboard</title>
    <style>
        body { background-color: #1e1e1e; color: white; font-family: Arial; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #444; text-align: center; }
        th { background-color: #007bff; color: white; }
        .btn { padding: 5px 10px; margin: 2px; text-decoration: none; background: green; color: white; border: none; border-radius: 4px; }
        textarea { width: 100%; }
    </style>
</head>
<body>
    <h2>📋 Order Dashboard</h2>

    <table>
        <thead>
            <tr>
                <th>Count</th>
                <th>Date</th>
                <th>Invoice #</th>
                <th>Receiver</th>
                <th>Mobile</th>
                <th>Payment Method</th>
                <th>Booking Status</th>
                <th>Bill Details</th>
                <th>Print</th>
                <th>Karigar Job Card</th>
                <th>Cutting Job Card</th>
                <th>Embroidery Job Card</th>
                <th>Create Booking</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $count = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                $flag = sizeDifferenceFlag($row['products'], $row['notes']);
                $bg_color = $flag === 'RED' ? '#ffcccc' : '#ccffcc';

                $address = json_decode($row['address'], true);
                $receiver = $address['receiver'] ?? '';
                $mobile = $address['mobile'] ?? '';

                echo "<tr style='background-color: $bg_color'>";
                echo "<td>$count</td>";
                echo "<td>" . date('d M Y, h:i A', strtotime($row['created_at'])) . "</td>";
                echo "<td>" . $row['invoice_number'] . "</td>";
                echo "<td>" . $receiver . "</td>";
                echo "<td>" . $mobile . "</td>";
                echo "<td>" . $row['payment_method'] . "</td>";
                echo "<td>" . $row['order_status'] . "</td>";

                // Dummy buttons (replace with real links if needed)
                echo "<td><a href='#' class='btn'>Details</a></td>";
                echo "<td><a href='#' class='btn'>Invoice</a></td>";
                echo "<td><a href='#' class='btn'>Karigar Job Card</a></td>";
                echo "<td><a href='#' class='btn'>Cut Job Card</a></td>";
                echo "<td><a href='#' class='btn'>Embroidery Job Card</a></td>";
                echo "<td><a href='#' class='btn'>Create Booking</a></td>";

                // Notes section
                echo "<td>
                    <form method='POST' action='save_note.php'>
                        <textarea name='note' rows='2'>" . htmlspecialchars($row['notes']) . "</textarea>
                        <input type='hidden' name='orderId' value='" . $row['orderId'] . "'>
                        <button type='submit' class='btn'>Save</button>
                    </form>
                </td>";

                echo "</tr>";
                $count++;
            }
            ?>
        </tbody>
    </table>
</body>
</html>

<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
include 'config.php';


// --- Fetch Orders ---
$sql = "SELECT orderId, products, notes, order_status, address, invoice_number, created_at, payment_method 
        FROM orders 
        WHERE payment_status IN ('Paid', 'COD')
        ORDER BY created_at DESC";

$result = $conn->query($sql);


function sizeDifferenceFlag($products_json, $notes)
{
    $sizes = json_decode($products_json, true);
    if (!$sizes || !is_array($sizes))
        return 'GREEN';

    $product = $sizes[0]; // First product only
    $top = strtoupper(trim($product['Size']['Top-Size'] ?? ''));
    $bottom = strtoupper(trim($product['Size']['Bottom-Size'] ?? ''));

    $size_order = ["S", "M", "L", "XL"];
    // Add 2XL to 10XL
    for ($i = 2; $i <= 10; $i++) {
        $size_order[] = $i . "XL";
    }

    $map = array_flip($size_order);

    if (!isset($map[$top]) || !isset($map[$bottom]))
        return 'GREEN';

    $diff = abs($map[$top] - $map[$bottom]);
    if ($diff > 2 && empty(trim($notes))) {
        return 'RED';
    }
    return 'GREEN';
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Order Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f8f9fa;
        }

        h2 {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        .print-btn {
            padding: 6px 12px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }

        .print-btn:hover {
            background-color: #218838;
        }

        @media screen and (max-width: 768px) {

            table,
            thead,
            tbody,
            th,
            td,
            tr {
                display: block;
                width: 100%;
            }

            thead {
                display: none;
            }

            tr {
                margin-bottom: 15px;
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 10px;
            }

            td {
                padding: 8px;
                border: none;
                display: flex;
                justify-content: space-between;
                font-size: 14px;
            }

            td::before {
                content: attr(data-label);
                font-weight: bold;
                color: #333;
                margin-right: 10px;
            }
        }
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
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $flag = sizeDifferenceFlag($row['products'], $row['notes']);
                    $bg_color = $flag === 'RED' ? '#ffcccc' : '#ccffcc';

                    $orderId = $row['orderId'];
                    $invoice = $row['invoice_number'];
                    $date = new DateTime($row['created_at']);
                    $date->modify('+12 hours 30 minutes'); // Add 11.5 hours
                    $createdAt = $date->format("d M Y, h:i A");

                    $address = json_decode($row['address'], true);
                    $receiver = $address['receiver'] ?? 'N/A';
                    $mobile = $address['mobile'] ?? 'N/A';
                    $city = $address['city'] ?? '';
                    $state = $address['state'] ?? '';
                    $fullAddress = $address['address'] ?? '';
                    $paymentMethod = $row['payment_method'] ?? '';
                    $orderStatus = $row['order_status'];

                    echo "<tr style='background-color: $bg_color'>
                    <td data-label='Count'>$count</td>
                    <td data-label='Date'>$createdAt</td>
                    <td data-label='Invoice #'>$invoice</td>
                    <td data-label='Receiver'>$receiver</td>
                    <td data-label='Mobile'>$mobile</td>
                    <td data-label='PaymentMethod'>$paymentMethod</td>
                    <td data-label='Order Status'>$orderStatus</td>
                     <td data-label='Details'><a class='print-btn' href='https://goldengown.in/tracking?id=$orderId' target='_blank'>Details</a></td>
                    <td data-label='Invoice'><a class='print-btn' href='https://goldengown.in/view-invoice?mode=testing&id=$orderId' target='_blank'>Invoice</a></td>
                    <td data-label='Karigar Job Card'><a class='print-btn' href='karigar_card2.php?order_id=$orderId' target='_blank'>Karigar Job Card</a></td>
                    <td data-label='Cut Job Card'><a class='print-btn' href='cut_card2.php?order_id=$orderId' target='_blank'>Cut Job Card</a></td>
                    <td data-label='Embroidery Job Card'><a class='print-btn' href='embroidery_card.php?order_id=$orderId' target='_blank'>Embroidery Job Card</a></td>
                    <td data-label='Create Booking'><a class='print-btn' href='../web/create_shiprocket_order.php?order_id=$orderId' target='_blank'>Create Booking</a></td>";

                    echo "<td data-label='Notes'>
                            <form method='POST' action='save_note.php'>
                                <textarea name='note' rows='2'>" . htmlspecialchars($row['notes']) . "</textarea>
                                <input type='hidden' name='orderId' value='" . $row['orderId'] . "'>
                                <button type='submit' class='btn'>Save</button>
                            </form>
                        </td>";


                    echo "</tr>";

                    $count = $count + 1;
                }
            } else {
                echo "<tr><td colspan='7'>No orders found.</td></tr>";
            }
            ?>
        </tbody>
    </table>

</body>

</html>
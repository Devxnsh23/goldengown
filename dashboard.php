<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
include 'config.php';

// --- Fetch Orders ---
$sql = "SELECT orderId, products, notes, order_status, address, invoice_number, created_at, payment_method 
        FROM orders 
        WHERE payment_status IN ('Paid', 'COD')
        ORDER BY created_at DESC";

$result = $conn->query($sql);



function sizeDifferenceFlag($products_json) {
    $sizes = json_decode($products_json, true);
    if (!$sizes || !is_array($sizes)) return 'green';

    $p = $sizes[0];
    $top = strtoupper(trim($p['Size']['Top-Size'] ?? ''));
    $bot = strtoupper(trim($p['Size']['Bottom-Size'] ?? ''));

    $size_order = ['S','M','L','XL'];
    for ($i = 2; $i <= 10; $i++) {
        $size_order[] = $i . 'XL';
    }

    $map = array_flip($size_order);
    if (!isset($map[$top]) || !isset($map[$bot])) return 'green';

    return (abs($map[$top] - $map[$bot]) > 2) ? 'red' : 'green';
}


$sql    = "SELECT orderId, products, notes, order_status, address, invoice_number, created_at, payment_method
           FROM orders
           WHERE payment_status IN ('Paid','COD')
           ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Order Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
  <style>
    body { background: #1e1e1e; color: #fff; font-family: sans-serif; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #444; padding: 8px; }
    th { background: #007bff; color: #fff; }
    .color-box { display:inline-block; width:16px; height:16px; border-radius:3px; vertical-align:middle; }
    .color-box.green { background: green; }
    .color-box.red   { background: red; }
    @media (max-width:768px) {
      table, thead, tbody, th, td, tr { display: block; }
      thead tr { display: none; }
      td { position: relative; padding-left: 50%; text-align: left; }
      td::before { position: absolute; top:8px; left:8px; width:45%; white-space:nowrap; font-weight:bold; }
      td[data-label="Count"]::before            { content: "Count"; }
      td[data-label="Date"]::before             { content: "Date"; }
      td[data-label="Invoice #"]::before        { content: "Invoice #"; }
      td[data-label="Receiver"]::before         { content: "Receiver"; }
      td[data-label="Mobile"]::before           { content: "Mobile"; }
      td[data-label="Payment Method"]::before   { content: "Payment Method"; }
      td[data-label="Order Status"]::before     { content: "Order Status"; }
      td[data-label="Size Difference"]::before  { content: "Size Diff"; }
      td[data-label="Print Options"]::before    { content: "Options"; }
    }
  </style>
</head>
<body class="p-3">
  <h2 class="text-center mb-4">📋 Order Dashboard</h2>
  <table>
    <thead>
      <tr>
        <th>Count</th>
        <th>Date</th>
        <th>Invoice #</th>
        <th>Receiver</th>
        <th>Mobile</th>
        <th>Payment Method</th>
        <th>Order Status</th>
        <th>Size Difference</th>
        <th>Print Options</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $count = 1;
      if ($result && $result->num_rows) {
        while ($row = $result->fetch_assoc()) {
          $flag    = sizeDifferenceFlag($row['products'], $row['notes']);
          $bg      = $flag === 'red' ? '#332222' : '#223322';
          $dt      = (new DateTime($row['created_at']))->format('d M Y, h:i A');
          $addr    = json_decode($row['address'], true);
          $recv    = htmlspecialchars($addr['receiver'] ?? 'N/A');
          $mob     = htmlspecialchars($addr['mobile']   ?? 'N/A');
          $inv     = htmlspecialchars($row['invoice_number']);
          $pm      = htmlspecialchars($row['payment_method']);
          $os      = htmlspecialchars($row['order_status']);
          $oid     = htmlspecialchars($row['orderId']);
      ?>
      <tr style="background: <?= $bg ?>;">
        <td data-label="Count"><?= $count ?></td>
        <td data-label="Date"><?= $dt ?></td>
        <td data-label="Invoice #"><?= $inv ?></td>
        <td data-label="Receiver"><?= $recv ?></td>
        <td data-label="Mobile"><?= $mob ?></td>
        <td data-label="Payment Method"><?= $pm ?></td>
        <td data-label="Order Status"><?= $os ?></td>
        <td data-label="Size Difference">
          <span class="color-box <?= $flag ?>"></span>
        </td>
        <td data-label="Print Options">
          <button
            class="btn btn-sm btn-info mb-2"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#opt<?= $inv ?>"
            aria-expanded="false"
          >Toggle</button>
          <div class="collapse" id="opt<?= $inv ?>">
            <div class="d-grid gap-2">
              <a href="tracking.php?id=<?= $oid ?>"       target="_blank" class="print-btn">Details</a>
              <a href="invoice.php?mode=testing&id=<?= $oid ?>" target="_blank" class="print-btn">Invoice</a>
              <a href="karigar_card.php?order_id=<?= $oid ?>" target="_blank" class="print-btn">Karigar Job Card</a>
              <a href="cut_card.php?order_id=<?= $oid ?>"    target="_blank" class="print-btn">Cut Job Card</a>
              <a href="embroidery_card.php?order_id=<?= $oid ?>" target="_blank" class="print-btn">Embroidery Job Card</a>
              <a href="create_shiprocket_order.php?order_id=<?= $oid ?>" target="_blank" class="print-btn">Create Booking</a>
              <form method="POST" action="save_note.php" class="mt-2">
                <textarea
                  name="note"
                  rows="2"
                  class="form-control form-control-sm"
                ><?= htmlspecialchars($row['notes']) ?></textarea>
                <input type="hidden" name="orderId" value="<?= $oid ?>">
                <button type="submit" class="btn btn-sm btn-primary mt-1">Save Note</button>
              </form>
            </div>
          </div>
        </td>
      </tr>
      <?php
          $count++;
        }
      } else {
        echo '<tr><td colspan="9">No orders found.</td></tr>';
      }
      ?>
    </tbody>
  </table>
</body>
</html>

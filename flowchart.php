<?php
// flowchart.php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
include 'config.php';

$orderId = $_GET['orderId'] ?? '';
if (!$orderId) {
    die('No Order ID specified');
}

// fetch invoice, internal_status & qc_fault
$stmt = $conn->prepare("
    SELECT invoice_number, internal_status, qc_fault
      FROM orders
     WHERE orderId = ?
");
$stmt->bind_param('s', $orderId);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    die('Order not found');
}
$row             = $res->fetch_assoc();
$inv             = htmlspecialchars($row['invoice_number']);
$internal_status = strtolower(trim($row['internal_status']  ?? 'created'));
$qc_fault_db     = $row['qc_fault'] ?? 'no';

// full 15-step workflow
$flow = [
  'created',
  'sent for cutting',
  'cutting in process',
  'cutting done',
  'sent for stitching',
  'stitching in progress',
  'stitching done',
  'sent for embroidery, ironing, buttoning',
  'embroidery, ironing, buttoning in process',
  'embroidery, ironing, buttoning done',
  'sent for quality check',
  'quality check done',
  'rework',
  'rework done',
  'dispatched'
];

// map to node IDs
$idMap = [
  'created'                                => 'created',
  'sent for cutting'                       => 'sent_cutting',
  'cutting in process'                     => 'cutting_proc',
  'cutting done'                           => 'cutting_done',
  'sent for stitching'                     => 'sent_stitch',
  'stitching in progress'                  => 'stitch_proc',
  'stitching done'                         => 'stitch_done',
  'sent for embroidery, ironing, buttoning' => 'sent_embro',
  'embroidery, ironing, buttoning in process' => 'embro_proc',
  'embroidery, ironing, buttoning done'     => 'embro_done',
  'sent for quality check'                 => 'sent_qc',
  'quality check done'                     => 'qc_done',
  'rework'                                 => 'rework',
  'rework done'                            => 'rework_done',
  'dispatched'                             => 'dispatched',
];

// find index for coloring
$idx = array_search($internal_status, $flow);
if ($idx === false) $idx = 0;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Flowchart for Order <?= $inv ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
  <script>mermaid.initialize({ startOnLoad: true });</script>
  <style>
    body { background: #1e1e1e; color: #fff; padding: 1rem; }
    h2 { margin-bottom: 1rem; }
    .controls { margin-bottom: 1rem; }
    .mermaid { background: #2e2e2e; padding: 1rem; border-radius: 4px; }
  </style>
</head>
<body>
  <h2>Internal Workflow for Order <?= $inv ?></h2>

  <div class="controls">
    <form method="POST" action="update_internal_status.php">
      <input type="hidden" name="orderId" value="<?= htmlspecialchars($orderId) ?>">

      <?php if ($internal_status === 'quality check done'): ?>
        <label class="me-3">
          <input type="radio" name="qc_fault" value="no" <?= $qc_fault_db==='no'?'checked':'' ?>>
          No Fault
        </label>
        <label>
          <input type="radio" name="qc_fault" value="yes" <?= $qc_fault_db==='yes'?'checked':'' ?>>
          Fault Found
        </label>
      <?php endif; ?>

      <button type="submit" name="direction" value="prev" class="btn btn-secondary">Back</button>
      <button type="submit" name="direction" value="next" class="btn btn-primary ms-2">Next Stage</button>
    </form>
  </div>

  <div class="mermaid">
graph LR
    created["Created"] --> sent_cutting["Sent for Cutting"]
    sent_cutting --> cutting_proc["Cutting in Process"]
    cutting_proc --> cutting_done["Cutting Done"]
    cutting_done --> sent_stitch["Sent for Stitching"]
    sent_stitch --> stitch_proc["Stitching in Progress"]
    stitch_proc --> stitch_done["Stitching Done"]
    stitch_done --> sent_embro["Sent for Embroidery, Ironing, Buttoning"]
    sent_embro --> embro_proc["Embroidery, Ironing, Buttoning in Process"]
    embro_proc --> embro_done["Embroidery, Ironing, Buttoning Done"]
    embro_done --> sent_qc["Sent for Quality Check"]
    sent_qc --> qc_done["Quality Check Done"]
<?php if ($qc_fault_db === 'yes'): ?>
    qc_done -- "fault found" --> rework["Rework"]
    rework --> rework_done["Rework Done"]
    rework_done --> dispatched["Dispatched"]
<?php else: ?>
    qc_done --> dispatched["Dispatched"]
<?php endif; ?>

classDef done    fill:#2ecc71,stroke:#000,stroke-width:1px;
classDef current fill:#00BFFF,stroke:#000,stroke-width:2px;
classDef pending fill:#7f8c8d,stroke:#000,stroke-width:1px;

<?php
// apply colors
foreach ($flow as $i => $status) {
    $node = $idMap[$status];
    if ($i < $idx) {
        echo "class $node done;\n";
    } elseif ($i === $idx) {
        echo "class $node current;\n";
    } else {
        echo "class $node pending;\n";
    }
}
?>
  </div>
</body>
</html>

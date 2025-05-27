<?php
// update_internal_status.php
include 'config.php';

$orderId   = $_POST['orderId']   ?? '';
$direction = $_POST['direction'] ?? 'next';
// only set when the QC‐fault radio is shown and submitted
$userFault = $_POST['qc_fault']  ?? null;

// define your linear workflow
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

// fetch current status & existing qc_fault
$stmt = $conn->prepare(
    "SELECT internal_status, qc_fault 
       FROM orders 
      WHERE orderId = ?"
);
$stmt->bind_param('s', $orderId);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    exit('Order not found');
}
$row      = $res->fetch_assoc();
$current  = $row['internal_status'] ?? 'created';
$oldFault = $row['qc_fault']       ?? 'no';

// decide which flag to use:
// • if user submitted qc_fault (only possible at QC Done), use that
// • otherwise carry forward the old DB value
$qc_fault = ($userFault !== null) 
          ? $userFault 
          : $oldFault;

// determine next status
if ($direction === 'next') {
    if ($current === 'quality check done') {
        // persist qc_fault choice
        $u = $conn->prepare(
            "UPDATE orders SET qc_fault = ? 
              WHERE orderId = ?"
        );
        $u->bind_param('ss', $qc_fault, $orderId);
        $u->execute();

        // branch: only go to rework if fault found
        $nextStatus = ($qc_fault === 'yes')
                    ? 'rework'
                    : 'dispatched';
    } else {
        // linear progression
        $i = array_search($current, $flow);
        $nextStatus = $flow[min($i + 1, count($flow) - 1)];
    }

} else { // BACK
    if ($current === 'rework') {
        // back from rework to QC Done
        $nextStatus = 'quality check done';
    } else {
        $i = array_search($current, $flow);
        $nextStatus = $flow[max($i - 1, 0)];
    }
}

// update internal_status
$u2 = $conn->prepare(
    "UPDATE orders SET internal_status = ? 
      WHERE orderId = ?"
);
$u2->bind_param('ss', $nextStatus, $orderId);
$u2->execute();

// Log this status change with timestamp
$hist = $conn->prepare(
  "INSERT INTO order_internal_history (order_id, status)
   VALUES (?, ?)"
);
$hist->bind_param('ss', $orderId, $nextStatus);
$hist->execute();

// redirect back to flowchart view
header("Location: flowchart.php?orderId=" . urlencode($orderId));
exit;

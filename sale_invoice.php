<?php
include 'topheader.php';
include 'dbb.php';

if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>Invoice ID not found!</div>";
    exit;
}

$id = intval($_GET['id']); // Ensure it's numeric

// Fetch invoice rows with client & book details
$sql = "SELECT s.id, s.invoice_no, s.invoice_date, s.price, s.quantity,      
               s.discount, s.discount_type, s.total_price, s.net_price, 
               b.title AS book_title, c.company_name AS client_name
        FROM sale_invoice s
        LEFT JOIN client c ON s.client_id = c.id
        LEFT JOIN books b ON s.book_id = b.id
        WHERE s.invoice_no = $id";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<div class='alert alert-danger'>Invoice not found!</div>";
    exit;
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    // Ensure total_price & net_price are calculated correctly
    $total = $row['price'] * $row['quantity'];

    if ($row['discount_type'] == 1) {  // 1 = percent, 0 = cash
    $discount_value = ($row['discount'] / 100) * $total;
    $discount_label = '%';
} else {
    $discount_value = $row['discount'];
    $discount_label = 'Rs';
}


    $row['total_price'] = $total;
    $row['net_price']   = $total - $discount_value;

    $rows[] = $row;
}

// Use first row for invoice header
$invoice = $rows[0];
?>

<div class="container my-5">
  <div class="card shadow-lg border-0 rounded-4 p-4">

    <!-- Header -->
    <div class="row mb-4 align-items-center">
      <div class="col-md-6">
        <h2 class="text-primary fw-bold mb-1">SALE INVOICE</h2>
        <p class="fw-semibold mb-1"><?= htmlspecialchars($invoice['client_name']) ?></p>
      </div>
      <div class="col-md-6 text-end">
        <p class="mb-1"><strong>Date:</strong> <?= date('d M Y', strtotime($invoice['invoice_date'])) ?></p>
        <p class="mb-1"><strong>Invoice No:</strong> <?= $invoice['invoice_no'] ?></p>
        <p class="mb-1"><strong>Invoice ID:</strong> <?= $invoice['id'] ?></p>
      </div>
    </div>

    <hr>

    <!-- Invoice Table -->
    <div class="table-responsive mb-4">
      <table class="table table-bordered text-center align-middle table-hover shadow-sm">
        <thead class="table-primary">
          <tr>
            <th>Sr. No</th>
            <th>Book</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Total Price</th>
            <th>Discount</th>
            <th>Net Price</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $grand_total = 0;
          $serial = 1;
          foreach ($rows as $row):
    $total = $row['price'] * $row['quantity'];

    if ($row['discount_type'] == 1) { // ✅ percent
        $discount_value = ($row['discount'] / 100) * $total;
        $discount_label = '%';
    } else {
        $discount_value = $row['discount'];
        $discount_label = 'Rs';
    }

    $net_price = $total - $discount_value;
    $grand_total += $net_price;
?>
<tr>
  <td><?= $serial++ ?></td>
  <td><?= htmlspecialchars($row['book_title']) ?></td>
  <td><?= number_format($row['price'], 2) ?></td>
  <td><?= number_format($row['quantity'], 0) ?></td>
  <td><?= number_format($total, 2) ?></td>
  <td><?= number_format($row['discount'], 2) . ' ' . $discount_label ?></td>
  <td class="fw-bold"><?= number_format($net_price, 2) ?></td>
</tr>
<?php endforeach; ?>

        </tbody>
        <tfoot>
          <tr class="table-light">
            <th colspan="6" class="text-end">Grand Total</th>
            <th class="fw-bold"><?= number_format($grand_total, 2) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="text-center mt-4">
      <button class="btn btn-success" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Print Invoice
      </button>
    </div>

  </div>
</div>

<style>
@media print {
  body * { visibility: hidden; }
  .card, .card * { visibility: visible; }
  .card { position: absolute; left: 0; top: 0; width: 100%; }
  .btn { display: none; }
}
</style>

<?php include 'footer.php'; ?>

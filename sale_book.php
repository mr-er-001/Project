<?php

include 'topheader.php';

if (isset($_POST['save'])) {
    $pub_date   = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['pub_date'])));
    $client_id  = $_POST['client_id'];
    $booksSel   = $_POST['book_id'];
    $prices     = $_POST['price'];
    $quantities = $_POST['quantity'];
    $discounts  = $_POST['discount'];
    $discount_types = $_POST['discount_type']; // ✅ new
    $totals     = $_POST['total_price'];
    $net_prices = $_POST['net_price'];
    $grand_total = $_POST['grand_total']; // ✅ from form

    // ✅ Get next invoice number
    $last_invoice = mysqli_fetch_row(mysqli_query($conn, "SELECT MAX(invoice_no) FROM sale_invoice"));
    $next_invoice = ($last_invoice[0]) + 1;

    foreach ($booksSel as $key => $book_id) {
        $price       = $prices[$key];
        $qty         = $quantities[$key];
        $discount    = $discounts[$key];
       // Convert text discount type to integer (1 = percent, 0 = cash)
$discount_type_text = $discount_types[$key];
$discount_type = ($discount_type_text === 'percent') ? 1 : 0; // ✅ percent = 1, cash = 0



        $total_price = $totals[$key];
        $net_price   = $net_prices[$key];

        // ✅ Insert sale invoice record
       $insert = mysqli_query($conn, "
    INSERT INTO sale_invoice
        (invoice_no, client_id, book_id, quantity, discount, discount_type, invoice_date, price, total_price, net_price)
    VALUES
        ('$next_invoice', '$client_id', '$book_id', '$qty', '$discount', '$discount_type', '$pub_date', '$price', '$total_price', '$net_price')
");

        // ✅ Update book stock after sale
        if ($insert) {
            $updateStock = mysqli_query($conn, "
                UPDATE books 
                SET quantity = quantity - $qty 
                WHERE id = '$book_id'
            ");
            
            if ($updateStock) {
                // 🔹 Check remaining stock
                $checkStock = mysqli_query($conn, "SELECT quantity, title FROM books WHERE id = '$book_id'");
                $bookData = mysqli_fetch_assoc($checkStock);
                $remaining = $bookData['quantity'];
                $title = $bookData['title'];

            } else {
                echo "<script>alert('⚠️ Failed to update stock for book ID: $book_id');</script>";
            }
        } else {
            echo "<script>alert('❌ Failed to insert sale record for book ID: $book_id');</script>";
        }
    }

    // ✅ Calculate running total for client from client_transactions
    $balanceQuery = mysqli_query($conn, "
        SELECT 
            COALESCE(SUM(debit_amount), 0) - COALESCE(SUM(credit_amount), 0) AS balance
        FROM client_transactions
        WHERE client_id = '$client_id'
    ");
    $balanceRow = mysqli_fetch_assoc($balanceQuery);
    $previous_total = $balanceRow ? $balanceRow['balance'] : 0;

    // ✅ Calculate new total after this sale
    $new_total = $previous_total + $grand_total;

    // ✅ Insert client transaction
    $insertTrans = mysqli_query($conn, "
        INSERT INTO client_transactions 
            (invoice_no, client_id, total_amount, debit_amount, credit_amount, tdate)
        VALUES 
            ('$next_invoice', '$client_id', '$new_total', '$grand_total', '0', '$pub_date')
    ");

    if (!$insertTrans) {
        die('Client Transaction Error: ' . mysqli_error($conn));
    }

    // ✅ Update client total balance (optional but recommended)
    $updateClient = mysqli_query($conn, "
        UPDATE client 
        SET total_amount = $new_total
        WHERE id = '$client_id'
    ");

    if (!$updateClient) {
        die('Client Update Error: ' . mysqli_error($conn));
    }

    // ✅ Redirect to sale invoice receipt
    echo "<script>window.open('./sale_invoice.php?id=$next_invoice','_self')</script>";
    exit;
}
?>


<style>
  .list-group-item.active {
    background-color: #e0f2f7;
    color: #045E70;
}


    .field-group .form-control {
    padding: 4px 6px;
    font-size: 1rem;
}
.field-group label {
    font-size: 0.75rem;
    margin-bottom: 2px;
}
.field-group .form-check-inline {
    margin-right: 5px;
}

body { background: #e5f4f9; font-family: 'Open Sans', sans-serif; }
.card-premium { border-radius: 15px; border: none; box-shadow: 0 6px 20px rgba(0,0,0,0.08); background: #fff; }
.card-premium-header { background: #045E70; color: #fff; font-weight: bold; font-size: 1.25rem; padding: 12px 20px; border-top-left-radius: 15px; border-top-right-radius: 15px; }
.field-group { border: 1px solid #d1d8dd; border-radius: 15px; padding: 30px; margin-bottom: 15px; background: #f9fcff; position: relative; }
.form-label { font-weight: bold; font-size: 0.875rem; color: #0890A6; }
.page-title { font-size: 1.5rem; font-weight: bold; color: #045E70; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
.form-control, input, select, textarea { border-radius: 5px !important; border: 1px solid #045E70; font-size: 0.9rem; transition: border 0.3s, box-shadow 0.3s; }
.form-control:focus { border-color: #045E70; box-shadow: 0 0 6px rgba(4,94,112,0.3); outline: none; }
.btn { border-radius: 12px !important; font-weight: 500; padding: 8px 20px; transition: 0.3s; }
.btn:hover { opacity: 0.9; }
#addRow { background-color: #045E70; color: #fff; font-weight: 500; }
button[name="save"] { background-color: #045E70; color: #fff; font-weight: 500; padding: 6px 18px !important; font-size: 0.9rem; border-radius: 12px; }
.result-box { position: absolute; top: 100%; left: 0; right: 0; border: 1px solid #045E70; border-radius: 12px; background: #fff; max-height: 180px; overflow-y: auto; z-index: 999; display: none; }
.result-box .list-group-item { padding: 6px 10px !important; font-size: 0.85rem; cursor: pointer; border-radius: 8px; }
.result-box .list-group-item:hover { background-color: #e0f2f7; }
.total, .net_price, #net_price { background: #f1f6f9; font-weight: 600; text-align: right; border-radius: 10px; }
.field-group .removeRow { position: absolute; top: 10px; right: 10px; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
.form-control:focus {
    border: 1.5px solid #045E70 !important;
    box-shadow: 0 0 12px rgba(4, 94, 112, 0.8) !important;
    background-color: #f0fcff !important;
    outline: none !important;
}

</style>

<div class="container py-4">
    <!-- 🔹 Header styled like Purchases Invoice -->
    <div class="page-title">
        Sale Invoice
    </div>

    <div class="card card-premium">
        <div class="card-body"><br>
            <form method="post">
                <!-- Top Row: Date & Client -->
                <div class="row g-3 mb-4" style="justify-content: center;">
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <!-- ✅ User sees dd-mm-yyyy, DB gets yyyy-mm-dd -->
                        <input type="text" class="form-control date-picker" name="pub_date" 
                               value="<?= date('d-m-Y') ?>" autocomplete="off">
                        <input type="hidden" name="pub_date_mysql" id="pub_date_mysql">
                    </div>
                    <div class="col-md-4 position-relative">
                        <label class="form-label">Client</label>
                        <input type="text" class="form-control search-client" placeholder="Search Client" autocomplete="off" required>
                        <input type="hidden" class="client-id" name="client_id">
                        <div class="clientResults result-box w-100"></div>
                    </div>
                </div>
                <!-- Product Rows -->
                <div id="productRows">
  <div class="field-group productRow">
    <div class="row g-2 align-items-end">
        <div class="col-md-4 position-relative">
            <label class="form-label">Book</label>
            <input type="text" class="form-control search-book" placeholder="Search Book" autocomplete="off">
            <input type="hidden" class="book-id" name="book_id[]">
            <div class="bookResults result-box w-100"></div>
        </div>
        <div class="col-md-1">
            <label class="form-label">Qty</label>
            <input type="number" class="form-control qty" name="quantity[]" required>
        </div>
      <?php $uid = uniqid(); ?>
<div class="col-md-1" style="height: 1.6cm;">
  <label class="form-label">Discount</label>
  <input type="number" name="discount[]" class="form-control discount" value="0" required>

  <!-- Radio buttons -->
  <div class="d-flex justify-content-start mt-1">
    <div class="form-check me-3">
      <input class="form-check-input discountType" 
             type="radio" 
             name="discount_type_<?= $uid ?>" 
             value="percent" 
             checked 
             style="border-radius: 45px !important;">
      <label class="form-check-label">%</label>
    </div>
    <div class="form-check">
      <input class="form-check-input discountType" 
             type="radio"
             name="discount_type_<?= $uid ?>" 
             value="cash" 
             style="border-radius: 45px !important;">
      <label class="form-check-label">₨</label>
    </div>
  </div>

  <!-- Hidden input -->
  <input type="hidden" name="discount_type[]" class="discount-type-hidden" value="percent">
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $(document).ready(function(){
    // Whenever a discount type radio is changed
    $(document).on('change', '.discountType', function(){
      var $row = $(this).closest('.col-md-1'); // row container
      $row.find('.discount-type-hidden').val($(this).val()); // update hidden input
    });
  });
</script>

        <div class="col-md-1">
            <label class="form-label">Price</label>
            <input type="number" class="form-control price" name="price[]" readonly>
        </div>
        <div class="col-md-1">
            <label class="form-label">Current Qty</label>
            <input type="number" class="form-control current_qty" readonly>
        </div>
        
        <div class="col-md-2">
            <label class="form-label">Total</label>
            <input type="number" class="form-control total" name="total_price[]" readonly>
        </div>
     

        <div class="col-md-2">
            <label class="form-label">Net</label>
            <input type="number" class="form-control net_price" name="net_price[]" readonly>
        </div>
        <div class="col-md-1 d-flex justify-content-center">
            <button type="button" class="btn btn-sm  removeRow" style="padding: 0; border: none;">
    <img src="assets/img/cancel.png" alt="Remove" style="width: 28px; height: 28px; object-fit: contain;">
  </button>
        </div>
    </div>
</div>


                </div>

                <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
                    <button type="button" class="btn" id="addRow" style="background:#045E70;color:#fff;">Add Book</button>
                    <div class="text-end">
                        <label class="form-label">Grand Total</label>
                        <input type="text" id="net_price" name="grand_total" class="form-control fw-bold text-end" readonly>
                    </div>
                </div>
    
                <div class="text-end mt-3">
                    <button type="submit" name="save" class="btn" style="background:#045E70;color:#fff;">Save Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ✅ jQuery + Datepicker -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

<script>
// ================= Keyboard navigation for book/client dropdown =================


$(document).ready(function() {
    // ✅ Initialize datepicker
    $('.date-picker').datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true,
        todayHighlight: true
    }).on('changeDate', function(e) {
        var date = e.date;
        var mysqlDate = date.getFullYear() + '-' +
                        ('0' + (date.getMonth() + 1)).slice(-2) + '-' +
                        ('0' + date.getDate()).slice(-2);
        $('#pub_date_mysql').val(mysqlDate);
    });

    // Set today's date in MySQL format
    var today = new Date();
    var mysqlToday = today.getFullYear() + '-' +
                     ('0' + (today.getMonth() + 1)).slice(-2) + '-' +
                     ('0' + today.getDate()).slice(-2);
    $('#pub_date_mysql').val(mysqlToday);
});

// JS logic remains same as before
let rowTemplate = $('.productRow').first().clone();
let rowCount = 1;

// Calculate row totals
function calculateRow(row){
    let price = parseFloat($(row).find('.price').val()) || 0;
    let qty = parseFloat($(row).find('.qty').val()) || 0;
    let discountInput = $(row).find('.discount').val();
    let discount = discountInput === "" ? 0 : parseFloat(discountInput);
    if (isNaN(discount)) discount = 0;

    let total = price * qty;

    // Get selected discount type safely
    let type = $(row).find('.discountType:checked').val();
    if (!type) type = 'percent'; // default fallback

    // Apply discount correctly
    let net;
    if (type === 'percent') {
        net = total - (total * discount / 100);
    } else {
        net = total - discount;
    }

    if (net < 0) net = 0;

    // Update fields
    $(row).find('.total').val(total.toFixed(2));
    $(row).find('.net_price').val(net.toFixed(2));

    // Update grand total
    calculateGrandTotal();
}

// Fix live typing in discount (recalculate properly)
$(document).on('input', '.discount', function(){
    let row = $(this).closest('.productRow');
    calculateRow(row);
});


// Calculate grand total
function calculateGrandTotal(){
    let total = 0;
    $('.productRow').each(function(){ total += parseFloat($(this).find('.net_price').val()) || 0; });
    $('#net_price').val(total.toFixed(2));
}

// Auto calculate
$(document).on('input change', '.qty, .discount, .discountType', function(){ calculateRow($(this).closest('.productRow')); });

$(document).on('change', '.discountType', function(){
    let row = $(this).closest('.productRow');
    row.find('.discount-type-hidden').val(row.find('.discountType:checked').val());
    calculateRow(row);
});

// Client search
$(document).on('keyup','.search-client', function(){
    let input = $(this); 
    let query = input.val();
    let resultsBox = input.siblings('.clientResults');
    if(query.length>0){ 
        $.post('fetch_client.php',{search:query}, function(data){ resultsBox.html(data).show(); }); 
    } else { resultsBox.hide(); }
});
$(document).on('click','.client-item', function(e){
    e.preventDefault();
    let item=$(this);
    $('.search-client').val(item.text());
    $('.client-id').val(item.data('id'));
    $('.clientResults').hide();
});

// Book search
$(document).on('keyup','.search-book', function(){
    let input = $(this); 
    let query = input.val();
    let resultsBox = input.siblings('.bookResults');
    if(query.length>0){ 
        $.post('fetch_books.php',{search:query}, function(data){ resultsBox.html(data).show(); }); 
    } else { resultsBox.hide(); }
});

// Add new product row
// Update hidden discount type when adding new row
$('#addRow').click(function(){
    let newRow = $('.productRow').first().clone();
    let uniqueId = Date.now();

    newRow.find('input').each(function(){
        if($(this).is('[type=radio]')){
            $(this).attr('name', 'discount_type_' + uniqueId);
            $(this).prop('checked', $(this).val() === 'percent');
        } else {
            $(this).val('');
        }
    });

    // Set hidden input to match default checked radio
    newRow.find('.discount-type-hidden').val('percent');

    $('#productRows').append(newRow);
});


// Book search (works for both existing and new rows)
$(document).on('keyup', '.search-book', function() {
    let input = $(this); 
    let query = input.val();
    let resultsBox = input.siblings('.bookResults');
    if(query.length > 0){ 
        $.post('fetch_books.php', {search: query}, function(data){
            resultsBox.html(data).show();
        }); 
    } else { 
        resultsBox.hide(); 
    }
});

// $(document).on('click', '.book-item', function(e){
//     e.preventDefault();

//     let item = $(this);

//     // Works for BOTH `.col-md-4` & `.col-md-3`
//     let parent = item.closest('[class^="col-md"]');
//     parent.find('.search-book').val(item.text());
//     parent.find('.book-id').val(item.data('id'));
//     parent.find('.bookResults').hide();

//     let row = parent.closest('.productRow');

//     // Set all fields
//     row.find('.price').val(item.data('price'));
//     row.find('.current_qty').val(item.data('stock'));
//     row.find('.discount').val(item.data('discount'));  // ✔ Always apply discount

//     calculateRow(row);
// });



// ✅ Prevent form submission if client not selected
$('form').on('submit', function(e) {
    let clientId = $('.client-id').val().trim();

    if (clientId === "") {
        e.preventDefault(); // Stop form submission
        alert("⚠️ Please select a client");
        $('.search-client').focus();
        return false;
    }
});

// Handle Enter in discount input
$(document).on("keydown", ".discount", function(e) {
    if (e.key === "Enter") {
        e.preventDefault(); // Prevent form submission

        let row = $(this).closest('.productRow');
        let qty = parseFloat(row.find('.qty').val()) || 0;
        let discount = parseFloat(row.find('.discount').val()) || 0;

        // Only add new row if quantity is > 0
        if(qty > 0){ 
            calculateRow(row); // Calculate totals for current row

            $("#addRow").trigger("click"); // Add new row

            // Focus the first input (book) of the new row
            let newRow = $("#productRows .productRow").last();
            newRow.find("input.search-book").focus();
        } else {
            alert("⚠️ Please enter quantity before adding a new row.");
            row.find('.qty').focus();
        }
    }
});

// Optional: Enter in qty field jumps to discount field
$(document).on("keydown", ".qty", function(e) {
    if (e.key === "Enter") {
        e.preventDefault();
        $(this).closest('.productRow').find('.discount').focus();
    }
});

$(document).on("keydown", ".search-book", function (e) {
    let input = $(this);
    let box = input.siblings(".bookResults");
    let items = box.find(".book-item");
    if (items.length === 0) return;

    let selected = items.filter(".active");

    switch(e.key) {
        case "ArrowDown":
            e.preventDefault();
            if (selected.length === 0) {
                items.first().addClass("active");
            } else {
                let next = selected.next();
                if (next.length === 0) next = items.first();
                selected.removeClass("active");
                next.addClass("active");
            }
            break;

        case "ArrowUp":
            e.preventDefault();
            if (selected.length === 0) {
                items.last().addClass("active");
            } else {
                let prev = selected.prev();
                if (prev.length === 0) prev = items.last();
                selected.removeClass("active");
                prev.addClass("active");
            }
            break;

        case "Enter":
            e.preventDefault();
            if (selected.length) {
                let item = selected.first();
                let row = input.closest('.productRow');

                row.find('.search-book').val(item.text());
                row.find('.book-id').val(item.data('id'));
                row.find('.price').val(item.data('price'));
                row.find('.current_qty').val(item.data('stock'));
                row.find('.discount').val(item.data('discount'));
                
                calculateRow(row);
                box.hide();
            }
            break;
    }
});

// $(document).on('click', '.book-item', function (e) {
//     e.preventDefault();

//     let item = $(this);

//     // bookResults -> search-book input -> productRow
//     let row = item.closest('.bookResults').closest('.col-md-3, .col-md-4').closest('.productRow');

//     row.find('.search-book').val(item.text());
//     row.find('.book-id').val(item.data('id'));
//     row.find('.bookResults').hide();

//     // Set values from DB
//     row.find('.price').val(item.data('price'));
//     row.find('.current_qty').val(item.data('stock'));
//     row.find('.discount').val(item.data('discount'));   // ✔ NOW WORKS FOR KEYBOARD

//     calculateRow(row);
// });

$(document).on('click', '.book-item', function (e) {
    e.preventDefault();

    let item = $(this);

    // Correct parent tracing
    let row = item.closest('.bookResults')
                  .closest('.col-md-4')
                  .closest('.productRow');

    row.find('.search-book').val(item.text());
    row.find('.book-id').val(item.data('id'));
    row.find('.bookResults').hide();

    // Apply values
    row.find('.price').val(item.data('price'));
    row.find('.current_qty').val(item.data('stock'));
    row.find('.discount').val(item.data('discount')); // ✔ Discount now applies for keyboard too

    calculateRow(row);
});


let typingTimer;
$(document).on("keyup", ".search-book", function() {
    clearTimeout(typingTimer);
    let input = $(this);
    let query = input.val();
    let resultsBox = input.siblings('.bookResults');
    typingTimer = setTimeout(function(){
        if(query.length > 0){
            $.post('fetch_books.php',{search:query}, function(data){
                resultsBox.html(data).show();
            });
        } else {
            resultsBox.hide();
        }
    }, 150); // 150ms delay
});


// Track selected index for each row
$(document).on("keydown", ".search-book", function(e){
    let input = $(this);
    let box = input.siblings(".bookResults");
    let items = box.find(".book-item");
    if(items.length === 0) return;

    // store selectedIndex in the input element itself
    if(input.data('selectedIndex') === undefined) input.data('selectedIndex', -1);
    let index = input.data('selectedIndex');

    switch(e.key){
        case "ArrowDown":
            e.preventDefault();
            index = (index + 1) % items.length;
            input.data('selectedIndex', index);
            highlightBook(items, index);
            break;
        case "ArrowUp":
            e.preventDefault();
            index = (index - 1 + items.length) % items.length;
            input.data('selectedIndex', index);
            highlightBook(items, index);
            break;
        case "Enter":
            e.preventDefault();
            if(index >= 0 && index < items.length){
                selectBook(items.eq(index), input, box);
            }
            break;
        case "Escape":
            box.hide();
            input.data('selectedIndex', -1);
            break;
    }
});

function highlightBook(items, index){
    items.removeClass("active");
    items.eq(index).addClass("active");
    items.eq(index)[0].scrollIntoView({block:'nearest'});
}

function selectBook(item, input, box){
    let row = input.closest('.productRow');
    row.find('.search-book').val(item.text());
    row.find('.book-id').val(item.data('id'));
    row.find('.price').val(item.data('price'));
    row.find('.current_qty').val(item.data('stock'));
    row.find('.discount').val(item.data('discount'));
    calculateRow(row);
    box.hide();
    input.data('selectedIndex', -1); // reset
}

</script>

<?php include 'footer.php'; ?>

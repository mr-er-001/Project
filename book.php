<?php
include 'topheader.php';
if (isset($_POST['save'])) {
    $invoice_no = $_POST['invoice_no'];
    $pub_date = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['pub_date'])));
    $vendor_id = $_POST['vendor_id'];
    $books = $_POST['book_id'];
    $prices = $_POST['price'];
    $quantities = $_POST['quantity'];
    $discounts = $_POST['discount'];
    $totals = $_POST['total_price'];
    $net_prices = $_POST['net_price'];
    $grand_total = $_POST['grand_total']; // ✅ easier reference

    // ✅ Check if invoice already exists
    $check = mysqli_query($conn, "SELECT id FROM purchase_invoice WHERE invoice_no = '$invoice_no' LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('❌ Invoice No already exists. Please use a different number.'); window.history.back();</script>";
        exit;
    }

    $first_insert_id = 0;
    $last_invoice = mysqli_fetch_row(mysqli_query($conn, "SELECT MAX(invoice_no) FROM purchase_invoice"));
    $next_invoice = ($last_invoice[0]) + 1;

    foreach ($books as $key => $book_id) {
    $price = $prices[$key];
    $qty = $quantities[$key];
    $discount = $discounts[$key];
    $total_price = $totals[$key];
    $net = $net_prices[$key];

    // Convert discount type to 1 or 0
    $discountType = isset($_POST['discount_type'][$key]) ? $_POST['discount_type'][$key] : 'percent';
    $discountTypeValue = ($discountType === 'percent') ? 1 : 0;

    $sql = "INSERT INTO purchase_invoice 
        (invoice_no, vendor_id, invoice_date, book_id, price, quantity, discount, discount_type, total_price, net_price) 
        VALUES 
        ('$next_invoice', '$vendor_id', '$pub_date', '$book_id', '$price', '$qty', '$discount', '$discountTypeValue', '$total_price', '$net')";
    if (!$conn->query($sql)) {
        die('SQL Error: ' . $conn->error);
    }



        // ✅ Update stock
        $updateStock = "
            UPDATE books 
            SET quantity = quantity + $qty 
            WHERE id = '$book_id'";
        if (!$conn->query($updateStock)) {
            die('Stock Update Error: ' . $conn->error);
        }

        if ($first_insert_id == 0) {
            $first_insert_id = $conn->insert_id;
        }
    }

    // ✅ Get previous total from vendor table
// ✅ Calculate running total from vendor_transactions instead of vendor table
$balanceQuery = mysqli_query($conn, "
    SELECT 
        COALESCE(SUM(debit_amount), 0) - COALESCE(SUM(credit_amount), 0) AS balance
    FROM vendor_transactions
    WHERE vendor_id = '$vendor_id'
");
$balanceRow = mysqli_fetch_assoc($balanceQuery);
$previous_total = $balanceRow ? $balanceRow['balance'] : 0;

// ✅ Calculate new total after this purchase
$new_total = $previous_total + $grand_total;


    // ✅ Insert vendor transaction
    $insertTrans = "INSERT INTO vendor_transactions 
        (invoice_no, vendor_id, total_amount, debit_amount, credit_amount, tdate)
        VALUES ('$next_invoice', '$vendor_id', '$new_total', '$grand_total', '0', '$pub_date')";
    if (!$conn->query($insertTrans)) {
        die('Transaction Error: ' . $conn->error);
    }

    // ✅ Update vendor total balance
    $updateVendor = "
        UPDATE vendor 
        SET total_amount = total_amount + $grand_total
        WHERE id = '$vendor_id'";
    if (!$conn->query($updateVendor)) {
        die('Vendor Update Error: ' . $conn->error);
    }

    // ✅ Redirect to receipt
    echo "<script>window.open('./receipt.php?invoice_no=$next_invoice','_self')</script>";
    exit;
}
?>




<style>
    .form-control:focus {
    border: 1.5px solid #045E70 !important;
    box-shadow: 0 0 12px rgba(4, 94, 112, 0.8) !important;
    background-color: #f0fcff !important;
    outline: none !important;
}

    body {
        background: #e5f4f9;
        font-family: 'Open Sans', sans-serif;
    }

    .card-premium {
        border-radius: 15px;
        border: none;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        background: #ffffff;
    }

    .card-premium-header {
        background: #045E70;
        color: #ffffff;
        font-weight: bold;
        font-size: 1.25rem;
        padding: 12px 20px;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
    }

    .field-group {
        border: 1px solid #d1d8dd;
        border-radius: 15px;
        padding: 35px;
        margin-bottom: 15px;
        background: #f9fcff;
        position: relative;
        /* needed for ❌ button */
    }

    .form-label {
        font-weight: bold;
        font-size: 0.875rem;
        color: #0890A6;
    }

    .page-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: #045E70;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-control,
    input,
    select,
    textarea {
        border-radius: 5px !important;
        border: 1px solid #045E70;
        /* padding: 8px 12px; */
        font-size: 0.9rem;
        transition: border 0.3s, box-shadow 0.3s;
    }

    .form-control:focus,
    input:focus,
    select:focus,
    textarea:focus {
        border-color: #045E70;
        box-shadow: 0 0 6px rgba(4, 94, 112, 0.3);
        outline: none;
    }

    .btn {
        border-radius: 12px !important;
        font-weight: 500;
        padding: 8px 20px;
        transition: 0.3s;
    }

    .btn:hover {
        opacity: 0.9;
    }

    #addRow {
        background-color: #045E70;
        color: #ffffff;
        font-weight: 500;
        border-radius: 12px;
    }

    button[name="save"] {
        background-color: #045E70;
        color: #ffffff;
        font-weight: 500;
        border-radius: 12px;
    }

    .removeRow {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 0.9rem;
        border-radius: 50%;
        padding: 6px 10px;
        line-height: 1;
    }

    .total,
    #net_price {
        background: #f1f6f9;
        font-weight: 600;
        text-align: right;
        border-radius: 10px;
    }

    .discount-toggle .btn {
        border-radius: 8px !important;
        font-size: 0.85rem;
        padding: 6px 14px;
        font-weight: 500;
        border: 1px solid #045E70;
        color: #045E70;
        background: #fff;
    }

    .discount-toggle .btn.active {
        background: #045E70;
        color: #fff;
    }

    .result-box {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        border: 1px solid #045E70;
        border-radius: 12px;
        background: #ffffff;
        max-height: 180px;
        overflow-y: auto;
        z-index: 999;
        display: none;
    }

    .result-box .list-group-item {
        padding: 6px 10px !important;
        font-size: 0.85rem;
        cursor: pointer;
        border-radius: 8px;
    }

    .result-box .list-group-item:hover {
        background-color: #e0f2f7;
    }

    button[name="save"] {
        background-color: #045E70;
        color: #ffffff;
        font-weight: 500;
        border-radius: 12px;
        padding: 6px 18px !important;
        /* ✅ smaller */
        font-size: 0.9rem;
        /* ✅ slightly smaller text */
    }

    .bookResults {
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        /* ✅ Match input width exactly */
        border: 1px solid #045E70;
        border-radius: 8px;
        background: #fff;
        max-height: 180px;
        overflow-y: auto;
        z-index: 999;
    }
    .active-item {
    background: #0890A6 !important;
    color: white !important;
    }

</style>

<div class="container py-4">

    <!-- 🔹 Heading outside the container card -->
    <div class="page-title">Purchases Invoice</div>

    <div class="card card-premium">
        <div class="card-body">
            <br>
            <form method="post">
                <!-- form content stays the same -->
                <div class="row g-3 mb-4" style="justify-content: center;">

                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <!-- Change type to text -->
                        <input type="text" class="form-control date-picker" name="pub_date" value="<?= date('d-m-Y') ?>"
                            autocomplete="off">
                        <input type="hidden" name="pub_date_mysql" id="pub_date_mysql">
                    </div>

<div class="col-md-4 position-relative">
                        <label class="form-label">Vendor</label>
                        <input type="text" class="form-control search-vendor" placeholder="Search Vendor"
                            autocomplete="off">
                        <input type="hidden" name="vendor_id" class="vendor-id">
                        <div class="vendorResults result-box w-100"></div>
                    </div>

                </div>

                <!-- Product Rows -->
                <div id="productRows">
                    <div class="field-group productRow" style="background-color: white;">
                        <button type="button" class="btn btn-sm  removeRow" style="padding: 0; border: none;">
                            <img src="assets/img/cancel.png" alt="Remove"
                                style="width: 28px; height: 28px; object-fit: contain;">
                        </button>
                        <div class="row g-2 align-items-end">


                            <!-- Book -->
                            <div class="col-md-4 position-relative">
                                <label class="form-label">Book</label>
                                <input type="text" class="form-control search-book" name="book_name[]"
                                    placeholder="Search Book" autocomplete="off">
                                <input type="hidden" class="book_id" name="book_id[]">
                                <div class="bookResults result-box position-absolute w-100" style="z-index: 1000;">
                                </div>
                            </div>

                            <!-- Price -->
                            <div class="col-md-1">
                                <label class="form-label">Price</label>
                                <input type="number" name="price[]" class="form-control price" required>
                            </div>

                            <!-- Quantity -->
                            <div class="col-md-1">
                                <label class="form-label">Qty</label>
                                <input type="number" name="quantity[]" class="form-control quantity" required>
                            </div>

                            <!-- Discount + Type -->
                           <?php $uid = uniqid(); ?>
<div class="col-md-2" style="height: 1.8cm;">
    <label class="form-label">Discount</label>
    <input type="number" name="discount[]" class="form-control discount" value="0">

    <!-- Radio buttons below input -->
    <div class="d-flex justify-content-start mt-1">
        <div class="form-check me-3">
            <input class="form-check-input discountType" type="radio"
                   name="discount_type[]" value="percent" checked
                   style="border-radius: 45px !important;">
            <label class="form-check-label">%</label>
        </div>
        <div class="form-check">
            <input class="form-check-input discountType" type="radio"
                   name="discount_type[]" value="cash"
                   style="border-radius: 45px !important;">
            <label class="form-check-label">₨</label>
        </div>
    </div>
</div>


                            <!-- Total -->
                            <div class="col-md-2">
                                <label class="form-label">Total</label>
                                <input type="text" name="total_price[]" class="form-control total" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class=" form-label mb-0">Net Price</label>
                                <div class="d-flex">
                                    <input type="text" class="form-control fw-bold text-end net_price_display" readonly
                                        style="max-width: 180px;">
                                    <input type="hidden" class="net_price" name="net_price[]">
                                </div>
                            </div>
                            <!-- Remove Button -->


                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button type="button" class="btn" id="addRow" style="background-color: #045E70;color:white;">Add
                        Product</button>


                </div>
                <div class="row mb-3">
                    <div class="col-md-3 ms-auto">
                        <label class="form-label fw-bold">Grand Total</label>
                        <input type="text" id="grand_total" name="grand_total" class="form-control fw-bold text-end" value="0.00" readonly>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn px-4" name="save">Save Invoice</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Include Bootstrap Datepicker CSS & JS -->
<link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

<script>

    
    $(document).ready(function () {
        $('.date-picker').datepicker({
            format: 'dd-mm-yyyy',
            autoclose: true,
            todayHighlight: true
        }).on('changeDate', function (e) {
            // Convert to MySQL format yyyy-mm-dd
            var date = e.date;
            var mysqlDate = date.getFullYear() + '-' +
                ('0' + (date.getMonth() + 1)).slice(-2) + '-' +
                ('0' + date.getDate()).slice(-2);
            $('#pub_date_mysql').val(mysqlDate);
        });

        // Set initial MySQL value on page load
        var today = new Date();
        var mysqlToday = today.getFullYear() + '-' +
            ('0' + (today.getMonth() + 1)).slice(-2) + '-' +
            ('0' + today.getDate()).slice(-2);
        $('#pub_date_mysql').val(mysqlToday);
    });
</script>

<script>
    $(document).on("blur", "input[name='invoice_no']", function () {
        let invoiceNo = $(this).val().trim();
        if (invoiceNo.length > 0) {
            $.post("check_invoice.php", { invoice_no: invoiceNo }, function (data) {
                if (data === "exists") {
                    alert("❌ This Invoice No already exists. Please choose another.");
                    $("input[name='invoice_no']").val("").focus();
                }
            });
        }
    });

    // ✅ New Row Template (same as first row)
    let rowCounter = 0; // <-- add this line before defining rowTemplate
const createRowTemplate = () => {
    rowCounter++;
    return `
<div class="field-group productRow" style="background-color: white;">
  <button type="button" class="btn btn-sm removeRow" style="padding: 0; border: none;">
    <img src="assets/img/cancel.png" alt="Remove" style="width: 28px; height: 28px; object-fit: contain;">
  </button>
  <div class="row g-2 align-items-end">
        <div class="col-md-4 position-relative">
            <label class="form-label">Book</label>
            <input type="text" class="form-control search-book" name="book_name[]" placeholder="Search Book" autocomplete="off">
            <input type="hidden" class="book_id" name="book_id[]">
            <div class="bookResults result-box position-absolute w-100" style="z-index:1000;"></div>
        </div>

        <div class="col-md-1">
            <label class="form-label">Price</label>
            <input type="number" name="price[]" class="form-control price" required>
        </div>

        <div class="col-md-1">
            <label class="form-label">Qty</label>
            <input type="number" name="quantity[]" class="form-control quantity" required>
        </div>

        <div class="col-md-2" style="height: 1.8cm;">
            <label class="form-label">Discount</label>
            <input type="number" name="discount[]" class="form-control discount" value="0">

            <div class="d-flex justify-content-start mt-1">
                <div class="form-check me-3">
                    <input class="form-check-input discountType"
                           type="radio"
                           name="discount_type_${rowCounter}"
                           value="percent"
                           checked
                           style="border-radius:45px !important;">
                    <label class="form-check-label">%</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input discountType"
                           type="radio"
                           name="discount_type_${rowCounter}"
                           value="cash"
                           style="border-radius:45px !important;">
                    <label class="form-check-label">₨</label>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <label class="form-label">Total</label>
            <input type="text" name="total_price[]" class="form-control total" readonly>
        </div>

        <div class="col-md-2">
            <label class="form-label mb-0">Net Price</label>
            <input type="text" class="form-control fw-bold text-end net_price_display" readonly style="max-width: 180px;">
            <input type="hidden" class="net_price" name="net_price[]">
        </div>
  </div>
</div>`;
};


    function calculateGrandTotal() {
        let grandTotal = 0;
        $(".productRow").each(function () {
            let net = parseFloat($(this).find(".net_price").val()) || 0;
            grandTotal += net;
        });
        $("#grand_total").val(grandTotal.toFixed(2));
    }

    function calculateNetPrice() {
        let net = 0;
        $(".productRow").each(function () {
            let price = parseFloat($(this).find(".price").val()) || 0;
            let qty = parseFloat($(this).find(".quantity").val()) || 0;
            let discount = parseFloat($(this).find(".discount").val()) || 0;
            let discountType = $(this).find(".discountType:checked").val();
            //        alert('kin');
            let rowTotal = price * qty;

            if (discountType === "percent") {
                rowTotal = rowTotal - (rowTotal * discount / 100);
            } else if (discountType === "cash") {
                rowTotal = rowTotal - discount;
            }

            net += rowTotal;
        });

        $("#net_price").val(net.toFixed(2));
    }


    // Calculate one row
    // 🔹 Calculate one row 
    function calculateRow(row) {
        let price = parseFloat($(row).find('.price').val()) || 0;
        let qty = parseFloat($(row).find('.quantity').val()) || 0;
        let discount = parseFloat($(row).find('.discount').val()) || 0;
        let total = price * qty;

        // ✅ Show Gross Total (no discount)
        $(row).find('.total').val(total.toFixed(2));

        // ✅ Apply discount for net
        let type = $(row).find('.discountType:checked').val() || 'percent';
        let net = (type === 'percent') ? total - (total * discount / 100) : total - discount;
        if (net < 0) net = 0;

        // ✅ Update hidden + visible net fields
        if ($(row).find('.net_price').length === 0) {
            $(row).append('<input type="hidden" class="net_price" name="net_price[]">');
        }
        $(row).find('.net_price').val(net.toFixed(2));
        $(row).find('.net_price_display').val(net.toFixed(2)); // 👈 show it in UI

        // ✅ Update grand total
        calculateGrandTotal();
    }

    $(document).on("input change", ".price, .quantity, .discount, .discountType", function () {
        let row = $(this).closest(".productRow");
        calculateRow(row);
    });

    // $("#addRow").on("click", function () {
    //     $("#productRows").append(createRowTemplate());
    // });





    // Vendor Search
    $(document).on("keyup", ".search-vendor", function () {
        let input = $(this);
        let query = input.val();
        let resultsBox = input.siblings(".vendorResults");
        if (query.length > 0) {
            $.post("fetch_vendor.php", { search: query }, function (data) {
                resultsBox.html(data).show();
            });
        } else resultsBox.hide();
    });

    $(document).on("click", ".vendor-item", function (e) {
        e.preventDefault();
        $(".search-vendor").val($(this).text());
        $(".vendor-id").val($(this).data("id"));
        $(".vendorResults").hide();
    });

    // Book Search
    $(document).on("keyup", ".search-book", function () {
        let input = $(this);
        let query = input.val().trim();
        let resultsBox = input.siblings(".bookResults");
        if (query.length > 0) {
            $.post("fetch_books.php", { search: query }, function (data) {
                resultsBox.html(data).show();
            });
        } else resultsBox.hide();
    });

    $(document).on("click", ".book-item", function (e) {
        e.preventDefault();

        // Get the parent field group correctly
        let box = $(this).closest(".bookResults");
        let parent = box.closest(".position-relative");
        let row = box.closest(".productRow");

        // Fill data into inputs
        parent.find(".search-book").val($(this).text());
        parent.find(".book_id").val($(this).data("id"));
        box.hide();

        // Auto-fill price if available
        if ($(this).data("price")) {
            row.find(".price").val($(this).data("price"));
        }
    });
    $("#addRow").on("click", function () {
        // Append the new row using the template generator
        $("#productRows").append(createRowTemplate());

        // Trigger recalculation for all rows including new one
        calculateGrandTotal();
    });

    $(document).on("click", ".removeRow", function () {
        $(this).closest(".productRow").remove();
        calculateGrandTotal();
    });

    // ✅ Prevent submit if vendor not selected
$(document).on("submit", "form", function (e) {
    let vendorId = $(".vendor-id").val().trim();
    if (vendorId === "" || vendorId === "0" || vendorId === null) {
        e.preventDefault();
        alert("❌ Please select a vendor");
        $(".search-vendor").focus();
        return false;
    }
});

function enableDropdownKeyboard(inputSelector, listSelector, itemClass) {
    let currentIndex = -1;

    $(document).on("keydown", inputSelector, function (e) {
        let input = $(this);
        let box = input.siblings(listSelector);
        let items = box.find(itemClass);

        if (items.length === 0 || box.is(":hidden")) return;

        // ARROW DOWN
        if (e.key === "ArrowDown") {
            e.preventDefault();
            currentIndex = (currentIndex + 1) % items.length;
            items.removeClass("active-item");
            $(items[currentIndex]).addClass("active-item");
        }

        // ARROW UP
        else if (e.key === "ArrowUp") {
            e.preventDefault();
            currentIndex = (currentIndex - 1 + items.length) % items.length;
            items.removeClass("active-item");
            $(items[currentIndex]).addClass("active-item");
        }

        // ENTER → select item
        else if (e.key === "Enter") {
            e.preventDefault();
            if (currentIndex >= 0) {
                $(items[currentIndex]).trigger("click");
                box.hide();           // hide dropdown immediately
                currentIndex = -1;    // reset index
            }
        }

        // ESC → close dropdown
        else if (e.key === "Escape") {
            box.hide();
            currentIndex = -1;        // reset index
        }
    });
}

// Enable for vendor
enableDropdownKeyboard(".search-vendor", ".vendorResults", ".vendor-item");


// Enable for books

// 🔹 Trigger "Add Row" on Enter inside product inputs
// 🔹 Trigger "Add Row" on Enter inside discount fields
$(document).on("keydown", ".discount", function(e) {
    if (e.key === "Enter") {
        e.preventDefault(); // Prevent form submission
        $("#addRow").trigger("click"); // Add a new row

        // Focus the first input (book) of the new row
        let newRow = $("#productRows .productRow").last();
        newRow.find("input.search-book").focus();
    }
});



</script>

<?php include 'footer.php'; ?>

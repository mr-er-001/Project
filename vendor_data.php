<?php
ob_start(); // ✅ Start output buffering

include "topheader.php";
include "dbb.php";

// 🟢 Handle Update (when Edit Modal is submitted)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $company_name = trim($_POST['company_name']);
    $contact_name = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $mobile = trim($_POST['mobile']);
    $address = trim($_POST['address']);

    if ($id > 0 && $company_name !== '') {
        $sql = "UPDATE vendor 
                SET company_name = ?, 
                    contact_name = ?, 
                    email = ?, 
                    phone = ?, 
                    mobile = ?, 
                    postal_address = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $company_name, $contact_name, $email, $phone, $mobile, $address, $id);

        if ($stmt->execute()) {
            header("Location: vendor_data.php"); // ✅ Safe redirect
            exit;
        } else {
            echo "<script>alert('❌ Error updating vendor.');</script>";
        }
    } else {
        echo "<script>alert('⚠️ Invalid input.');</script>";
    }
}

// 🔴 Handle Delete
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $sql = "DELETE FROM vendor WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        header("Location: vendor_data.php"); // ✅ Safe redirect
        exit;
    } else {
        echo "<script>alert('Error deleting vendor: " . mysqli_error($conn) . "');</script>";
    }
}



// Rest of your HTML table and page content goes here...
ob_end_flush(); // ✅ Send output to browser
?>



<section class="section" style="background-color:#F0FDFF; padding:15px;">
  <div class="pagetitle">
    <h1 class="fw-bold">Vendor</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.html">Home</a></li>
        <li class="breadcrumb-item">Tables</li>
        <li class="breadcrumb-item active">Vendor Data</li>
      </ol>
    </nav>
  </div>

  <div class="d-flex justify-content-end mt-2 me-2 mb-3">
    <a href="vendor_reg.php" class="btn btn-md" style="background-color: #045E70; color: white;">New Vendor</a>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle datatable" style="table-layout: fixed; width: 100%; background-color: white; border:1px solid #dee2e6;">
      <thead>
        <tr>
          <th>#</th>
          <th>Vendor Name</th>
          <th>Contact Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Mobile</th>
          <th>Address</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $sql = "SELECT id, company_name, contact_name, email, phone, mobile, postal_address FROM vendor";
        $result = $conn->query($sql);
        $sr = 1;

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$sr}</td>
                        <td>" . htmlspecialchars($row['company_name']) . "</td>
                        <td>" . htmlspecialchars($row['contact_name']) . "</td>
                        <td>" . htmlspecialchars($row['email']) . "</td>
                        <td>" . htmlspecialchars($row['phone']) . "</td>
                        <td>" . htmlspecialchars($row['mobile']) . "</td>
                        <td>" . htmlspecialchars($row['postal_address']) . "</td>
                        <td>
                          <button class='btn btn-sm btn-success me-1 edit-btn' 
                                  data-id='{$row['id']}'
                                  data-name='" . htmlspecialchars($row['company_name']) . "'
                                  data-username='" . htmlspecialchars($row['contact_name']) . "'
                                  data-email='" . htmlspecialchars($row['email']) . "'
                                  data-phone='" . htmlspecialchars($row['phone']) . "'
                                  data-mobile='" . htmlspecialchars($row['mobile']) . "'
                                  data-address='" . htmlspecialchars($row['postal_address']) . "'>
                            <img src='assets/img/elements.png' alt='Edit' style='width:16px;height:16px;'>
                            
                          </button>
                          
                          <a href='?delete_id={$row['id']}' class='btn btn-sm btn-danger'
                             onclick='return confirm(\"Are you sure you want to delete this vendor?\")'>
                            <img src='assets/img/🦆 icon _trash_.png' alt='Delete' style='width:16px;height:16px;'>
                          </a>
                        </td>
                      </tr>";
                $sr++;
            }
        } else {
            echo "<tr><td colspan='8' class='text-center text-muted'>No vendors found</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</section>

<div class="modal fade" id="editVendorModal" tabindex="-1" aria-labelledby="editVendorLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editVendorForm" method="POST" action="">
        <div class="modal-header" style="background-color:#045E70;color:white;">
          <h5 class="modal-title" id="editVendorLabel">Edit Vendor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body row g-3">
          <input type="hidden" name="id" id="vendor_id">

          <div class="col-md-6">
  <label for="company_name" class="form-label">Company Name</label>
  <input type="text" class="form-control" name="company_name" id="company_name" required>
</div>

<div class="col-md-6">
  <label for="username" class="form-label">Contact Name</label>
  <input type="text" class="form-control" name="username" id="username" required>
</div>

<div class="col-md-6">
  <label for="email" class="form-label">Email</label>
  <input type="email" class="form-control" name="email" id="email" required>
</div>

<div class="col-md-6">
  <label for="phone" class="form-label">Phone</label>
  <input type="tel" class="form-control" name="phone" id="phone" required pattern="[0-9+]{7,15}">
</div>

<div class="col-md-6">
  <label for="mobile" class="form-label">Mobile</label>
  <input type="tel" class="form-control" name="mobile" id="mobile" required pattern="[0-9+]{7,15}">
</div>

<div class="col-md-12">
  <label for="address" class="form-label">Address</label>
  <textarea class="form-control" name="address" id="address" rows="2" required></textarea>
</div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn" style="background-color:#045E70;color:white;">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
  $(".edit-btn").click(function() {
    $("#editVendorForm")[0].reset();
    $("#vendor_id").val($(this).data("id"));
    $("#company_name").val($(this).data("name"));
    $("#username").val($(this).data("username"));
    $("#email").val($(this).data("email"));
    $("#phone").val($(this).data("phone"));
    $("#mobile").val($(this).data("mobile"));
    $("#address").val($(this).data("address"));

    let modal = new bootstrap.Modal(document.getElementById('editVendorModal'));
    modal.show();
  });

  // 🔒 Restrict Phone & Mobile input to only numbers and + sign
  $(document).on("input", "#phone, #mobile", function () {
      this.value = this.value.replace(/[^0-9+]/g, "");
  });

});
</script>

<?php require "footer.php"; ?>

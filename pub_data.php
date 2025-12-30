<?php require "topheader.php"; ?>
<?php include 'dbb.php'; ?>

<?php
// 🟢 Handle UPDATE Publisher (Modal Submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $publisher_name = trim($_POST['publisher_name']);

    if ($id > 0 && $publisher_name !== '') {
        $sql = "UPDATE publisher SET publisher_name = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $publisher_name, $id);

        if ($stmt->execute()) {
            echo "<script>alert('Publisher updated successfully!');</script>";
            echo "<script>window.open('pub_data.php','_self');</script>";
            exit;
        } else {
            echo "<script>alert('Error updating publisher.');</script>";
        }
    } else {
        echo "<script>alert('Invalid input.');</script>";
    }
}


// 🔴 Handle DELETE Publisher
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);

    $sql = "DELETE FROM publisher WHERE id = $del_id";

    if ($conn->query($sql)) {
        echo "<script>alert('Publisher deleted successfully!');</script>";
        echo "<script>window.open('pub_data.php','_self');</script>";
        exit;
    } else {
        echo "<script>alert('Error deleting publisher.');</script>";
    }
}
?>


<section class="section" style="background-color:#F0FDFF; padding:15px;">
  <div class="pagetitle">
    <h1 class="fw-bold">Publisher</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.html">Home</a></li>
        <li class="breadcrumb-item">Tables</li>
        <li class="breadcrumb-item active">Publisher Data</li>
      </ol>
    </nav>
  </div>

  <!-- Add Publisher Button -->
  <div class="d-flex justify-content-end mt-2 me-2" style="margin-bottom: 10px;">
    <a href="pub_reg.php" class="btn btn-md" style="background-color: #045E70; color: white;">New Publisher</a>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle datatable"
           style="table-layout: fixed; width: 100%; background-color: white; border:1px solid #dee2e6;">
      <thead>
        <tr>
          <th style="background-color: #045E70; color: white; width: 10%;">Sr.</th>
          <th style="background-color: #045E70; color: white; width: 65%;">Publisher Name</th>
          <th style="background-color: #045E70; color: white; width: 25%;">Actions</th>
        </tr>
      </thead>
      <tbody>

        <?php
        $sql = "SELECT id, publisher_name FROM publisher ORDER BY id DESC";
        $result = $conn->query($sql);
        $sr = 1;

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                echo "
                <tr>
                    <td>{$sr}</td>
                    <td>" . htmlspecialchars($row['publisher_name']) . "</td>
                    <td>

                        <!-- Edit Button -->
                        <button class='btn btn-sm btn-success me-1 edit-btn'
                                data-id='" . $row['id'] . "'
                                data-name='" . htmlspecialchars($row['publisher_name']) . "'>
                            <img src='assets/img/elements.png' alt='Edit' style='width:16px;height:16px;'>
                        </button>

                        <!-- Delete Button -->
                        <a href='pub_data.php?delete=" . $row['id'] . "'
                           class='btn btn-sm btn-danger'
                           onclick='return confirm(\"Delete this publisher?\")'>
                            <img src='assets/img/🦆 icon _trash_.png' alt='Delete' style='width:16px;height:16px;'>
                        </a>

                    </td>
                </tr>
                ";
                $sr++;
            }
        } else {
            echo "<tr><td colspan='3' class='text-center text-muted'>No publishers found</td></tr>";
        }
        ?>

      </tbody>
    </table>
  </div>
</section>


<!-- Edit Modal -->
<div class="modal fade" id="editPublisherModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header" style="background-color:#045E70;color:white;">
          <h5 class="modal-title">Edit Publisher</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="id" id="publisher_id">
          <label class="form-label">Publisher Name</label>
          <input type="text" class="form-control" name="publisher_name" id="publisher_name" required>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn" style="background-color:#045E70;color:white;">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    // open modal and fill input fields
    $(document).on("click", ".edit-btn", function () {
        $("#publisher_id").val($(this).data("id"));
        $("#publisher_name").val($(this).data("name"));

        let modalEl = document.getElementById('editPublisherModal');
        let modal = new bootstrap.Modal(modalEl);
        modal.show();
    });
});
</script>

<?php require "footer.php"; ?>


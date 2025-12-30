<?php 
require "topheader.php";
include "dbb.php";

/* ----------------------------------------------------
   🔹 1. DELETE AUTHOR (GET Request)
---------------------------------------------------- */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $sql = "DELETE FROM author WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('Author deleted successfully!'); window.location.href='authordata.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error deleting author');</script>";
    }
}

/* ----------------------------------------------------
   🔹 2. ADD AUTHOR (POST Request)
---------------------------------------------------- */
if (isset($_POST['add_author'])) {
    $name = trim($_POST['name']);

    if ($name == "") {
        echo "<script>alert('Author name cannot be empty!');</script>";
    } else {
        // check exists
        $check = $conn->prepare("SELECT id FROM author WHERE LOWER(author_name)=LOWER(?)");
        $check->bind_param("s", $name);
        $check->execute();
        $exist = $check->get_result();

        if ($exist->num_rows > 0) {
            echo "<script>alert('Author already exists!');</script>";
        } else {
            $insert = $conn->prepare("INSERT INTO author (author_name) VALUES (?)");
            $insert->bind_param("s", $name);
            if ($insert->execute()) {
                echo "<script>alert('Author added successfully!'); window.location.href='authordata.php';</script>";
                exit;
            }
        }
    }
}

/* ----------------------------------------------------
   🔹 3. UPDATE AUTHOR (POST Request)
---------------------------------------------------- */
if (isset($_POST['update_author'])) {
    $id = intval($_POST['id']);
    $author_name = trim($_POST['author_name']);

    if ($id > 0 && $author_name !== "") {
        $sql = "UPDATE author SET author_name=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $author_name, $id);

        if ($stmt->execute()) {
            echo "<script>alert('Author updated successfully!'); window.location.href='authordata.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error updating author!');</script>";
        }
    }
}
?>

<!-- ----------------------------------------------------
     🔹 HTML START
----------------------------------------------------- -->

<section class="section" style="background-color:#F0FDFF; padding:15px;">

<div class="pagetitle">
  <h1 class="fw-bold">Author</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.html">Home</a></li>
      <li class="breadcrumb-item">Tables</li>
      <li class="breadcrumb-item active">Author</li>
    </ol>
  </nav>
</div>

<!-- NEW AUTHOR BUTTON -->
<div class="d-flex justify-content-end mt-2 me-2" style="margin-bottom: 10px;">
 <button class="btn btn-md" style="background-color: #045E70; color: white;" 
         data-bs-toggle="modal" data-bs-target="#addAuthorModal">
    New Author
 </button>
</div>

<table class="table table-hover align-middle datatable"
       style="width: 100%; background-color: white; border:1px solid #dee2e6;">
  <thead>
    <tr>
      <th style="background-color:#045E70;color:white;">Sr. No.</th>
      <th style="background-color:#045E70;color:white;">Author Name</th>
      <th style="background-color:#045E70;color:white;">Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $result = $conn->query("SELECT id, author_name FROM author");
    $sr = 1;

    if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        echo "
        <tr>
          <td>{$sr}</td>
          <td>" . htmlspecialchars($row['author_name']) . "</td>
   <td>
    <button class='btn btn-sm btn-success me-1 edit-btn'
            data-id='" . $row['id'] . "'
            data-name='" . htmlspecialchars($row['author_name']) . "'
            data-bs-toggle='modal'
            data-bs-target='#editAuthorModal'
            style=\"padding:4px 8px;\">
        <img src='assets/img/elements.png' alt='Edit' style=\"width:18px;\">
    </button>

    <a href='authordata.php?delete=" . $row['id'] . "'
       class='btn btn-sm btn-danger'
       onclick='return confirm(\"Delete this author?\")'
       style=\"padding:4px 8px;\">
        <img src='assets/img/🦆 icon _trash_.png' alt='Delete' style=\"width:18px;\">
    </a>
</td>
        </tr>";
        $sr++;
      }
    } else {
      echo "<tr><td colspan='3' class='text-center text-muted'>No authors found</td></tr>";
    }
    ?>
  </tbody>
</table>

</section>

<!-- ----------------------------------------------------
     🔹 MODAL: ADD AUTHOR
----------------------------------------------------- -->

<div class="modal fade" id="addAuthorModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header" style="background-color:#045E70;color:white;">
          <h5>Add New Author</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Author Name</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add_author" class="btn" style="background-color:#045E70;color:white;">
            Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ----------------------------------------------------
     🔹 MODAL: EDIT AUTHOR
----------------------------------------------------- -->

<div class="modal fade" id="editAuthorModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header" style="background-color:#045E70;color:white;">
          <h5>Edit Author</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">
          <label class="form-label">Author Name</label>
          <input type="text" name="author_name" id="edit_name" class="form-control" required>
        </div>
        <div class="modal-footer">
          <button type="submit" name="update_author" class="btn" style="background-color:#045E70;color:white;">
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.querySelectorAll(".edit-btn").forEach(btn => {
    btn.addEventListener("click", function () {
        document.getElementById("edit_id").value = this.dataset.id;
        document.getElementById("edit_name").value = this.dataset.name;
    });
});
</script>

<?php require "footer.php"; ?>

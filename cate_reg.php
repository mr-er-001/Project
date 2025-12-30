<?php
include 'topheader.php';
include "dbb.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = trim($_POST['category_name']); // Remove extra spaces

    // 🟡 Validate input
    if (empty($category_name)) {
        echo "<script>alert('Category name cannot be empty!'); window.history.back();</script>";
        exit();
    }

    // 🔍 Check for duplicate category (case-insensitive)
    $check_sql = "SELECT id FROM category WHERE LOWER(category_name) = LOWER(?)";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $category_name);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // ❌ Category already exists
        echo "<script>alert('This category name already exists!'); window.history.back();</script>";
        exit();
    }

    // ✅ Insert new category
    $sql = "INSERT INTO category (category_name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $category_name);

    if ($stmt->execute()) {
        echo "<script>alert('Category created successfully!'); window.location.href='cate_data.php';</script>";
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<div class="container">
  <section class="section register d-flex flex-column align-items-center justify-content-center py-4 mt-5">
    <div class="row justify-content-center w-100">
      <div class="col-lg-9 col-md-8 col-sm-10 col-12">
        <div class="card w-100 shadow-sm mb-3">
          <div class="card-body">

            <div class="pt-4 pb-2 text-center">
              <h5 class="card-title fs-4">Register Category</h5>
            </div>

            <!-- Category Form -->
            <form class="row g-3 needs-validation" method="POST" novalidate>
              <!-- Category Name -->
              <div class="col-12">
                <label for="categoryName" class="form-label">Category Name</label>
                <input type="text" name="category_name" class="form-control" id="categoryName" required>
                <div class="invalid-feedback">Please, enter category name!</div>
              </div>

              <!-- Submit Buttons -->
              <div class="row mt-3" style="align-items: center; justify-content: center;">
                <div class="col-3">
                  <button class="btn btn-primary w-100" type="submit">Create Category</button>
                </div>
                <div class="col-3">
                  <a href="cate_data.php" class="btn btn-secondary w-100">Back</a>
                </div>
              </div>

            </form>

          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
// Bootstrap validation script
(() => {
  'use strict'
  const forms = document.querySelectorAll('.needs-validation')
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add('was-validated')
    }, false)
  })
})();
</script>

<?php require "footer.php"; ?>

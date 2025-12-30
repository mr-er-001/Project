<?php require 'topheader.php'; ?>
<?php
include "dbb.php";

// 🟢 Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']); // Clean input

    // Validate empty name
    if (empty($name)) {
        echo "<script>alert('Author name cannot be empty!'); window.history.back();</script>";
        exit();
    }

    // 🔍 Check if author already exists (case-insensitive)
    $check_sql = "SELECT id FROM author WHERE LOWER(author_name) = LOWER(?)";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // ❌ Author already exists
        echo "<script>alert('This author name already exists!'); window.history.back();</script>";
        exit();
    }

    // ✅ Insert new author
    $insert_sql = "INSERT INTO author (author_name) VALUES (?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("s", $name);

    if ($insert_stmt->execute()) {
        echo "<script>
            alert('✅ Author added successfully!');
            window.location.href = 'authordata.php';
        </script>";
        exit();
    } else {
        echo "<script>alert('Error inserting author!');</script>";
    }
}
?>

<!-- ======================= HTML FORM ======================= -->

<div class="container">
  <section class="section register d-flex flex-column align-items-center justify-content-center py-4 mt-5">
    <div class="container">
      <div class="row justify-content-center">
        
        <div class="col-lg-9 col-md-8 col-sm-10 col-12 d-flex flex-column align-items-center justify-content-center">

          <div class="card w-100 shadow-sm mb-3">
            <div class="card-body">

              <div class="pt-4 pb-2 text-center">
                <h5 class="card-title fs-4">Register Author</h5>
              </div>

              <!-- Form begins -->
              <form class="row g-3 needs-validation" action="" method="POST" novalidate>
                
                <div class="col-12">
                  <label for="yourName" class="form-label">Your Name</label>
                  <input type="text" name="name" class="form-control" id="yourName" required>
                  <div class="invalid-feedback">Please enter author name!</div>
                </div>

                <div class="row mt-4 justify-content-center">
                  <div class="col-3">
                    <button class="btn btn-primary w-100" type="submit">Create Author</button>
                  </div>

                  <div class="col-3">
                    <a href="authordata.php" class="btn btn-secondary w-100">Back</a>
                  </div>
                </div>

              </form>

            </div>
          </div>

        </div>
      </div>
    </div>
  </section>
</div>

<?php require 'footer.php'; ?>

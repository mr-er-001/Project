<?php require 'topheader.php'; ?>
<?php include "dbb.php"; ?>

<?php
// ⭐ Handle form submit — Insert Publisher
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $publisher_name = trim($_POST['publisher_name']);

    // Empty name check
    if (empty($publisher_name)) {
        echo "<script>alert('Publisher name cannot be empty!'); window.history.back();</script>";
        exit();
    }

    // Duplicate check (case-insensitive)
    $check_sql = "SELECT id FROM publisher WHERE LOWER(publisher_name) = LOWER(?)";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $publisher_name);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('This publisher name already exists!'); window.history.back();</script>";
        exit();
    }

    // Insert new publisher
    $insert_sql = "INSERT INTO publisher (publisher_name) VALUES (?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("s", $publisher_name);

    if ($stmt->execute()) {
        echo "<script>
                alert('Publisher added successfully!');
                window.location.href = 'pub_data.php';
              </script>";
        exit();
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }
}
?>

<div class="container">
  <section class="section register d-flex flex-column align-items-center justify-content-center py-4 mt-5">
    <div class="container">
      <div class="row justify-content-center">

        <div class="col-lg-9 col-md-8 col-sm-10 col-12 d-flex flex-column align-items-center justify-content-center">

          <div class="card w-100 shadow-sm mb-3">
            <div class="card-body">

              <div class="pt-4 pb-2 text-center">
                <h5 class="card-title fs-4">Register Publisher</h5>
                <p class="small">Enter your personal details to create account</p>
              </div>

              <form class="row g-3 needs-validation" method="POST" action="" novalidate>

                <div class="col-12">
                  <label for="yourName" class="form-label">Publisher Name</label>
                  <input type="text" name="publisher_name" class="form-control" id="yourName" required>
                  <div class="invalid-feedback">Please, enter publisher name!</div>
                </div>

                <div class="row mt-4" style="align-items: center; justify-content: center;">
                  <div class="col-3">
                    <button class="btn btn-primary w-100" type="submit">Create Publisher</button>
                  </div>

                  <div class="col-3">
                    <a href="pub_data.php" class="btn btn-secondary w-100">Back</a>
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

<?php require "footer.php"; ?>

<?php
// Fetch all main categories
$allCategories = [];
$sql = "SELECT * FROM tbl_sports_category";
$rs = $conn->query($sql);
while ($rs && $row = $rs->fetch_assoc()) {
    $allCategories[] = $row;
}

$maxVisible = 10;
$extraCategories = [];

?>

<!-- Top Bar (Only for Desktop) -->
<div class="top-bar d-none d-lg-block">
  <div class="container d-flex justify-content-between align-items-center">
    <!-- Social Media Links -->
    <div class="social-icons">
      <a href="https://www.facebook.com" target="_blank"><i class="bi bi-facebook"></i></a>
      <a href="https://www.instagram.com" target="_blank"><i class="bi bi-instagram"></i></a>
      <a href="https://www.linkedin.com" target="_blank"><i class="bi bi-linkedin"></i></a>
      <a href="https://www.tiktok.com" target="_blank"><i class="bi bi-tiktok"></i></a>
    </div>

    <!-- Quick Links & Email -->
    <div class="top-bar-links">
      <span class="email"><i class="bi bi-envelope-fill"></i> info@koutoubiaoy.com</span>
    </div>
  </div>
</div>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm custom-navbar">
  <div class="container">
    <!-- Logo -->
    <a class="navbar-brand" href="index.php">
      <img src="assets/images/logo/logo.png" alt="Logo" class="d-inline-block align-top" style="height:50px;">
    </a>
    <!-- Mobile Offcanvas Toggle -->
    <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Desktop Navigation -->
    <div class="collapse navbar-collapse d-none d-lg-block" id="desktopNav">
      <ul class="navbar-nav ms-auto">

        <!-- Products (Opens Offcanvas) -->
        <li class="nav-item">
          <a class="nav-link" href="#" data-bs-toggle="offcanvas" data-bs-target="#mobileNav">
            <i class="bi bi-grid"></i> Products
          </a>
        </li>

        <!-- Services Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="servicesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-tools"></i> Services
          </a>
          <ul class="dropdown-menu" aria-labelledby="servicesDropdown">
            <li><a class="dropdown-item" href="project-management.php">Project Management</a></li>
            <li><a class="dropdown-item" href="installation.php">Installation</a></li>
            <li><a class="dropdown-item" href="maintenance.php">Maintenance</a></li>
          </ul>
        </li>

        <!-- About Us -->
        <li class="nav-item">
          <a class="nav-link" href="about-us.php">
            <i class="bi bi-info-circle"></i> About Us
          </a>
        </li>

        <!-- Contact Us -->
        <li class="nav-item">
          <a class="nav-link" href="contact-us.php">Contact Us</a>
        </li>
      </ul>
    </div>
  </div>
</nav>




<div class="offcanvas offcanvas-start custom-offcanvas" id="mobileNav">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Our Services</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <ul class="navbar-nav">

      <?php
      // Fetch all main categories
      $allCategories = [];
      $sql = "SELECT * FROM tbl_sports_category";
      $rs = $conn->query($sql);
      while ($rs && $row = $rs->fetch_assoc()) {
          $allCategories[] = $row;
      }

      if (empty($allCategories)) {
          echo '<li class="nav-item"><a class="nav-link">No Categories Available</a></li>';
      } else {
          foreach ($allCategories as $row) {
              $id = $row['cat_id'];
              $categoryID = "mobileCategory" . $id;

              // Check if the category has images
              $sqlMain = "SELECT * FROM tbl_images WHERE cat_id='$id'";
              $rsMain = $conn->query($sqlMain);
              if ($rsMain && $rsMain->num_rows > 0) {
                  // If images exist, make it a direct link
                  echo '<li class="nav-item"><a class="nav-link etc" href="page.php?cat_id=' . htmlspecialchars($id) . '">' . htmlspecialchars(ucwords(strtolower($row['cat_name']))) . '</a></li>';
              } else {
                  // Otherwise, it has subcategories (collapsible menu)
                  ?>

                  <li class="nav-item">
                    <a class="nav-link mobile-menu-item" data-bs-toggle="collapse" href="#<?= $categoryID ?>">
                      <?= htmlspecialchars(ucwords(strtolower($row['cat_name']))) ?> <i class="bi bi-chevron-down mobile-arrow"></i>
                    </a>

                    <div class="collapse" id="<?= $categoryID ?>">
                      <ul class="list-unstyled mobile-submenu">

                        <?php
                        $sql_sub = "SELECT * FROM tbl_sub_sports_category WHERE cat_id='$id'";
                        $rs_sub  = $conn->query($sql_sub);

                        if ($rs_sub && $rs_sub->num_rows > 0) {
                            while ($row_sub = $rs_sub->fetch_assoc()) {
                                $sub_id = $row_sub['sub_cat_id'];
                                $subCategoryID = "mobileSubcategory" . $sub_id;

                                // Check if subcategory has images
                                $sqlSub = "SELECT * FROM tbl_images WHERE sub_cat_id='$sub_id'";
                                $rsSub  = $conn->query($sqlSub);
                                if ($rsSub && $rsSub->num_rows > 0) {
                                    // If images exist, make it a direct link
                                    echo '<li><a class="nav-link" style="text-transform:Capitalize;" href="page.php?sub_cat_id=' . htmlspecialchars($sub_id) . '">' . htmlspecialchars(ucwords(strtolower($row_sub['sub_cat_name']))) . '</a></li>';
                                } else {
                                    // Otherwise, it has sub-subcategories (collapsible)
                                    ?>
                                    <li class="nav-item">
                                      <a class="nav-link mobile-menu-item" style="text-transform:Capitalize;" data-bs-toggle="collapse" href="#<?= $subCategoryID ?>">
                                        <?= htmlspecialchars(ucwords(strtolower($row_sub['sub_cat_name']))) ?> <i class="bi bi-chevron-down mobile-arrow"></i>
                                      </a>

                                      <div class="collapse" id="<?= $subCategoryID ?>">
                                        <ul class="list-unstyled mobile-subsubmenu">

                                          <?php
                                          $sql_sub_sub = "SELECT * FROM tbl_super_sub_category WHERE sub_cat_id='$sub_id'";
                                          $rs_sub_sub  = $conn->query($sql_sub_sub);
                                          if ($rs_sub_sub && $rs_sub_sub->num_rows > 0) {
                                              while ($row_sub_sub = $rs_sub_sub->fetch_assoc()) { ?>
                                                <li>
                                                  <a class="nav-link" style="text-transform:Capitalize;" href="page.php?sub_subcategory_id=<?= htmlspecialchars($row_sub_sub['super_sub_cat_id']) ?>">
                                                    <?= htmlspecialchars(ucwords(strtolower($row_sub_sub['super_sub_cat_name']))) ?>
                                                  </a>
                                                </li>
                                              <?php }
                                          } ?>

                                        </ul>
                                      </div>
                                    </li>
                                <?php } ?>
                            <?php } ?>
                        <?php } ?>

                      </ul>
                    </div>
                  </li>

              <?php } ?>
      <?php } } ?>

    </ul>
  </div>
</div>

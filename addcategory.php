

			<!-- Header -->
			<?php include './layouts/header.php'; ?>
			<!-- Header -->

           <!-- Sidebar -->
			<?php include './layouts/sidebar.php'; ?>
			<!-- /Sidebar -->

			<div class="page-wrapper">
				<div class="content">
					<div class="page-header">
						<div class="page-title">
							<h4>Product Add Category</h4>
							<h6>Create new product Category</h6>
						</div>

						<div>
						<a href="addproduct.php"><button type="button" class="btn btn-primary ml-2" >Add product</button></a>
						</div>
					</div>
					<!-- /add -->
					<form class="" action="./backend/add_category.php" method="post" enctype="multipart/form-data">

						<div class="card">
							<div class="card-body">
								<div class="row">
									<div class="col-lg-6 col-sm-6 col-12">
										<div class="form-group">
											<label>Category Name</label>
											<input name="name" type="text" >
										</div>
									</div>

									
									<div class="col-lg-12">
										<button type="submit" class="btn btn-submit me-2">Submit</button>
										<a href="categorylist.php" class="btn btn-cancel">Cancel</a>
									</div>
								</div>
							</div>
						</div>

					</form>
					<!-- /add -->
				</div>
			</div>
        </div>
		<!-- /Main Wrapper -->

		<?php include './layouts/footer.php' ?>

    </body>
</html>



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
							<h4>Add Vendor</h4>
							<h6>Create Vendor</h6>
						</div>
						<div>
						<a href="addproduct.php"><button type="button" class="btn btn-primary ml-2" >Add product</button></a>
						</div>
					</div>

					
					<!-- /add -->
					<form class="" action="./backend/add_vendor.php" method="post" enctype="multipart/form-data">

						<div class="card">
							<div class="card-body">
								<div class="row">
									<div class="col-lg-6 col-sm-6 col-12">
										<div class="form-group">
											<label>Vendor Name</label>
											<input name="name" type="text" >
										</div>
									</div>
                                    <div class="col-lg-6 col-sm-6 col-12">
										<div class="form-group">
											<label>Contact Number</label>
											<input name="contact" type="text" >
										</div>
									</div>
                                    <div class="col-lg-6 col-sm-6 col-12">
										<div class="form-group">
											<label>Address</label>
											<input name="address" type="text" >
										</div>
									</div>
									<div class="col-lg-12">
										<button type="submit" class="btn btn-submit me-2">Submit</button>
										
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

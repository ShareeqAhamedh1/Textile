<?php
	$newBill =0;
 ?>

			<!-- Header -->
			<?php include 'layouts/header.php'; ?>
			<!-- Header -->

			<!-- Sidebar -->
			<?php include 'layouts/sidebar.php'; ?>
			<!-- /Sidebar -->

			<div class="page-wrapper">
				<div class="content">

					<!-- Button trigger modal -->
					<div class="row">
						<div class="col-lg-4">
							<div class="form-group">
							<label for="">Select Product</label>
								<select class=" js-states form-control"  name="" id="product_id">
									<option value="">Select Product</option>
									<?php
										$sql_product = "SELECT * FROM tbl_product";
										$rs_prod = $conn->query($sql_product);

										if($rs_prod->num_rows > 0){
											while($row_prod = $rs_prod->fetch_assoc()){
									 ?>
									<?php
													$subCatId=$row_prod['category_id'];
													$sqlProdName="SELECT * FROM tbl_category WHERE id='$subCatId'";
													$rsProdName=$conn->query($sqlProdName);
													if($rsProdName->num_rows>0){
													$rowsProdName=$rsProdName->fetch_assoc();
													$prodName=$rowsProdName['name'];
													?>

												<option value="<?= $row_prod['id'] ?>"><?= $row_prod['name'] ?> (<?= $row_prod['barcode'] ?>) </option>

													<?php
													}

												?>


								<?php } } ?>
								</select>
						</div>
						</div>
						<div class="col-lg-4">
							<div class="form-group">
								<label for="">From</label>
								<input type="date" class="form-control" id="from_date" name="" value="">
							</div>
						</div>
						<div class="col-lg-4">
							<div class="form-group">
								<label for="">To</label>
								<input type="date" class="form-control" id="to_date" name="" value="">
							</div>
						</div>
						<div class="col-12">
							<button class="btn btn-success" name="button" onclick="searchSlot()">Search Item</button> <br> <br>


						</div>
					</div>

					<div class="row">

						<div class="col-lg-12 col-sm-12 col-12 d-flex">
							<div class="card flex-fill">
								<br><br>
									<div class="table-responsive dataview" id="filter_product">

									</div>
							</div>
						</div>
					</div>

				</div>
			</div>

		</div>
		<!-- /Main Wrapper -->

		<?php include 'layouts/footer.php' ?>

		<script type="text/javascript">


				jQuery(document).ready(function($) {
				// Your code using $ as a shortcut for jQuery
				$('.js-states').select2();
				});

				function searchSlot(){
				 var prodd_id = document.getElementById('product_id').value;
				 var from_date = document.getElementById('from_date').value;
				 var to_date = document.getElementById('to_date').value;

				 $('#filter_product').load('sales_report_table.php',{
					  p_id:prodd_id,
						f_date:from_date,
						t_date:to_date
					}
				);
				}
		</script>
	</body>
</html>

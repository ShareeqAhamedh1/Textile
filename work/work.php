

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
							<h4>Final Order List</h4>
						</div>
					</div>

					<!-- /product list -->
					<div class="card">
						<div class="card-body">
							<div class="row">
								<div class="col-4">
									<input type="text"class="form-control" id="search_key" onkeyup="" value="" placeholder="Search By Order Number,Name,Phone,Description"> <br>
									<button type="button" class="btn btn-warning btn-sm" name="button" onclick="search_orders()">Search</button>
									<hr>
                                    <form action="backend/sel_page.php" method="post">
                                        <input type="hidden" name="back_link" value="2" id="">
                                    <div class="form-group">
                                        <select name="page_id" id="" class="form-control" required>
                                            <option value="">Select Section</option>
                                            <option value="0">Remove Selected Section</option>
                                            <?php
									      $sql_pages ="SELECT * FROM tbl_pages";
									      $rs_pages = $conn->query($sql_pages);

									      if($rs_pages->num_rows > 0){
									        while($row_pages = $rs_pages->fetch_assoc()){
									     ?>
                                                <option value="<?= $row_pages['page_id'] ?>"><?= $row_pages['page_name'] ?></option>
                                         <?php } } ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">Select Section</button>
                                    </form>
                                    <hr>
                                    <form action="backend/sel_item.php" method="post">
                                        <input type="hidden" name="back_link" value="0" id="">
                                    <div class="form-group">
                                        <select name="item_id" id="" class="form-control" required>
                                            <option value="">Select Subject</option>
                                            <option value="0">Remove Selected Subject</option>
                                            <?php
									      $sql_pages ="SELECT * FROM tbl_items";
									      $rs_pages = $conn->query($sql_pages);

									      if($rs_pages->num_rows > 0){
									        while($row_pages = $rs_pages->fetch_assoc()){
									     ?>
                                                <option value="<?= $row_pages['item_name'] ?>"><?= $row_pages['item_name'] ?></option>
                                         <?php } } ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-warning btn-sm">Select Subject</button>
                                    </form> <br>
																		<?php
																				if(isset($_SESSION['item_id'])){
																				$item_name = $_SESSION['item_id'];
																				$i_id=getDataBack($conn,'tbl_items','item_name',$item_name,'item_id');
																			?>
																			<form action="backend/sel_sub_item.php" method="post">
	                                        <input type="hidden" name="back_link" value="0" id="">
	                                    <div class="form-group">
	                                        <select name="sub_item_id" id="" class="form-control" required>
	                                            <option value="">Select Grade</option>
	                                            <option value="0">Remove Grade</option>
	                                            <?php
																					      $sql_pages ="SELECT * FROM tbl_sub_items WHERE item_id='$i_id'";
																					      $rs_pages = $conn->query($sql_pages);

																					      if($rs_pages->num_rows > 0){
																					        while($row_pages = $rs_pages->fetch_assoc()){
																					     ?>
	                                                <option value="<?= $row_pages['sub_name'] ?>"><?= $row_pages['sub_name'] ?></option>
	                                         <?php } } ?>
	                                        </select>
	                                    </div>
	                                    <button type="submit" class="btn btn-success btn-sm">Select Grade</button>
																		</form> <br>
																		<?php } ?>
								</div>
								<div class="col-8">
									<div class="row">
										<div class="col-6">
											<select class="form-control" onchange="setStatus(this.value)" name="">
												<option value="" disabled selected>Select Status</option>
												<option value="1">Confirmed</option>
												<option value="2">Canceled</option>
												<option value="3">No Answer</option>
												<option value="4">Phone Off</option>
												<option value="5">Call Back</option>
												<option value="6">Wrong Number</option>
											</select> <br>
                                            From
											<input type="date" class="form-control" id="from_date" value="">
                                            <br>
                                            To
                                            <input type="date" class="form-control" id="to_date" onchange="selectDate()" value="">
										<br>
										 <hr>
										 <label for="">Filter By Delivery Method</label>
										 <select class="form-control" onchange="setDM(this.value)" name="">
											 <option value="" disabled selected>Select Method</option>
											 <option value="0">Post Office</option>
											 <option value="1">Self Delivery</option>
										 </select>
                                        </div>
										<div class="col-6">
                                            <?php if(isset($_SESSION['page_id_sel'])){
                                                    $page_id = $_SESSION['page_id_sel'];

                                                    $pageName = getDataBack($conn,'tbl_pages','page_id',$page_id,'page_name');
                                                ?>
                                                    <h6> Selected Page <span style="color:#b247ff;"><?= $pageName ?></span> </h6> <hr>
                                                <?php } ?>

																								<?php if(isset($_SESSION['sub_item_id'])){
		                                                    $sub_item = $_SESSION['sub_item_id'];
		                                                ?>
		                                                    <h6> Selected sub item <span style="color:#1c59ff;"><?= $sub_item ?></span> </h6> <hr>
		                                                <?php } ?>

                                                <?php if(isset($_SESSION['item_id'])){
                                                    $item_id = $_SESSION['item_id'];
                                                ?>
                                                    <h6> Selected Item <span style="color:#b247ff;"><?= $item_id ?></span> </h6> <hr>
                                                <?php } ?>
											<h3>Total Orders: <span style="color:orange;" id="Total_Orders">   </span>  </h3>
											<h3 id="status_text" style="color:orange;"></h3>
										</div>
									</div>
								</div>
							</div>

							<div id="all_orders" class="table-responsive">

							</div>
						</div>
					</div>
					<!-- /product list -->
				</div>
			</div>
        </div>
		<!-- /Main Wrapper -->
		<div class="modal fade" style="" id="mark_orders" tabindex="-1" aria-labelledby="mark_orders"  aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document" >
				<div class="modal-content">
					<div class="modal-header">
						 <h5 class="modal-title" >Mark Orders</h5>
						<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">×</span>
						</button>
					</div>
					<div class="modal-body">
						<a onclick="confirmOrder()" class="btn btn-success w-100">Confirm</a> <br><br>
					  <a onclick="markOrderSt(3)" class="btn btn-warning w-100">No Answer</a> <br><br>
					  <a onclick="markOrderSt(2)" class="btn btn-danger w-100">Canceled</a> <br><br>
					  <a onclick="markOrderSt(4)" class="btn btn-secondary w-100">Phone Off</a>  <br><br>
					  <a onclick="markOrderSt(5)" class="btn btn-info w-100">Call Back</a> <br><br>
					  <a onclick="markOrderSt(6)" class="btn btn-dark w-100">Wrong Number</a><br>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" style="" id="track_order" tabindex="-1" aria-labelledby="track_order"  aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document" >
				<div class="modal-content">
					<div class="modal-header">
						 <h5 class="modal-title" >Track Order Details</h5>
						<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">×</span>
						</button>
					</div>
					<div class="modal-body" id="load_order_details">

					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" style="" id="fill_orders" tabindex="-1" aria-labelledby="fill_orders"  aria-hidden="true">
			<div class="modal-dialog" role="document" >
				<div class="modal-content">
					<div class="modal-header">
						 <h5 class="modal-title" >Mark Orders</h5>
						<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">×</span>
						</button>
					</div>
					<div class="modal-body">
            <form  action="backend/confirm_complete.php" method="post">
							<input type="hidden" name="id" value="" id="order_id_confirm">
							<div class="form-group">
								<label for="">Specified Date</label>
								<input type="date" class="form-control" name="confirmed_date_value" value="">
							</div>
							<div class="form-group">
								<label for="">Address</label>
								<textarea name="add" id="address" class="form-control" rows="8" cols="80" required></textarea>
							</div>
							<div class="form-group">
								<label for="">COD Amount</label>
								<input name="cod_amount" id="cod" class="form-control" placeholder="Rs.00" required>
							</div>
                            <div class="form-group">
								<label for="">Payment Type</label>
								<select class="form-control js-example-basic-single select2" name="pay_type" id="pay_type">

									<option value="1">COD</option>
                                    <option value="2">Bank Transfer</option>
                                    <option value="3">Other Payment Method</option>

								</select> <br>
								<label for="">Delivery Method</label>
								<select class="form-control" name="del_method" id="del_method">
									<option value="0">Post Office</option>
									<option value="1">Self Delivery</option>
								</select>
							</div>
              <div class="form-group">
                <select class="form-control js-example-basic-single select2" name="item" id="item" onchange="fetchSubItems()">
                <option value="">Select Subject (Only If you want to change)</option>
                <?php
                $sql_items = "SELECT * FROM tbl_items";
                $rs_items = $conn_new->query($sql_items);
                if ($rs_items->num_rows > 0) {
                while ($row_items = $rs_items->fetch_assoc()) {
                    $item_id = $row_items['item_id']; // Assuming there's an `item_id` column
                    $item_name = $row_items['item_name'];
                ?>
                <option value="<?= $item_id ?>"><?= $item_name ?></option>
                <?php
                }
                }
                ?>
                </select>
                </div>

                <!-- Second Dropdown (Sub Items) -->
                <div class="form-group">
                <select class="form-control js-example-basic-single select2" name="sub_item" id="sub_item">
                <option value="">Select Grade</option>
                </select>
                </div>


							<div class="form-group">
								<label for="">Select a District</label>
								<select class="js-states form-control" name="dis" onchange="selectCity(this.value)" id="district">
									<?php
										$sqlDistric = "SELECT * FROM districts";
										$rsDistric = $conn->query($sqlDistric);
										?>
										<option selected="selected">Selected Distric</option>
										<?php
										if($rsDistric->num_rows > 0){
											while($rowDist = $rsDistric->fetch_assoc()){
									 ?>
									<option value="<?= $rowDist['id'] ?>"><?= $rowDist['name_en'] ?></option>
								<?php } } ?>
								</select>
							</div>
							<div class="form-group">
								<label for="">Select a City</label>
								<select class="js-states form-control" name="city" id="loadCities">
								</select>
							</div><hr>
                            <h5 onclick="getHowToSearchVideo()" style="cursor:pointer;border:1px solid #000;padding:5px 5px 5px 5px;border-radius:5px;"> How To Search?  </h5> <hr>
							<div class="form-group">
								<label for="">Re Mark</label>
								<textarea name="remark" id="remark" class="form-control" rows="4" cols="80"></textarea>
							</div>
							<button type="submit" class="btn btn-success" name="button">Complete Order</button>

						</form>
					</div>
				</div>
			</div>
		</div>



		<?php include 'layouts/footer.php' ?>

		<script type="text/javascript">

		function setDM(id){
			$('#all_orders').load('ajax_pages/marked_orders.php',{
				dm_id:id
			});
		}

			function markOrder(or_id){
				document.getElementById('order_id_confirm').value = or_id;
				$('#mark_orders').modal('show');
			}
			//end
			function confirmOrder(){
				$('#mark_orders').modal('hide');
				$('#fill_orders').modal('show');
			}
			//end
			function selectCity(dist_id){
				$('#loadCities').load('ajax_pages/loadCities.php',{
					d_id:dist_id
				});
			}
			function selectCityEdit(dist_id){
				$('#loadCitiesEdit').load('ajax_pages/loadCities.php',{
					d_id:dist_id
				});
			}
			//end
			function loadAllOrders(){
				$('#all_orders').load('ajax_pages/marked_orders.php');
			}
			//end
			function search_orders(){
				var search_key = document.getElementById('search_key').value;
				$('#all_orders').load('ajax_pages/marked_orders.php',{
					skey:search_key
				});
			}
			//end
			function selectDate(){
                var from_date = document.getElementById('from_date').value;
                var to_date = document.getElementById('to_date').value;

                if(from_date == ""){
                    alert('You have to select from date');
                    return;
                }

				$('#all_orders').load('ajax_pages/marked_orders.php',{
					f_date:from_date,
                    t_date:to_date
				});
				location.reload();
			}
			// selectDate End
			window.addEventListener('load', function() {
			  loadAllOrders();
				<?php if(isset($_REQUEST['eid'])){
					$orid = $_REQUEST['eid'];
					 ?>
					editOrder(<?= $orid ?>);
					<?php } ?>
			});
			//end
			function editOrder(or_id){
				$('#edit_order').modal('show');
				$('#load_edit').load('ajax_pages/edit_order.php',{
					order_id:or_id
				});
			}
			//end
			function deleteOrder(or_id){

				if(confirm('Are you sure you want to delete this order')){
					$.ajax({
						url: "backend/del_order.php",
						method: "POST",
						data: { id: or_id	},
						success: function(response) {
							if(response == "sucess"){
								$('#all_orders').load('ajax_pages/marked_orders.php');
								alert('successfully deleted');
							}
						}
					});
				}
			}

			function markOrderSt(status){
				var oid = document.getElementById('order_id_confirm').value;
				$.ajax({
					url: "backend/status.php",
					method: "POST",
					data: { st:status,or_id:oid	},
					success: function(response) {
						if(response == "success"){
							$('#all_orders').load('ajax_pages/marked_orders.php');
							$('#mark_orders').modal('hide');
						}
						else if (response =="error_login") {
							alert('Please Refresh Your page since you got some break from your work');
						}
					}
				});
			}

			function setStatus(or_st){
				$('#all_orders').load('ajax_pages/marked_orders.php',{
					or_status:or_st
				});
				location.reload();
			}

			function load_order_details(oid){
				$('#track_order').modal('show');
				$('#load_order_details').load('ajax_pages/load_order_details.php',{
					order_id:oid
				});
			}



					function getSelectedCheckboxValues() {
						var checkboxes = document.querySelectorAll('input[type="checkbox"][id="checkBoxSet"]:checked');
						var selectedValues = [];


						checkboxes.forEach(function(checkbox) {
						 selectedValues.push(checkbox.value);
						});


										if(selectedValues == ""){
											alert('You have to select atleast one order');
											return;
										}

						$.ajax({
						 url: "backend/convert.php",
						 method: "POST",
						 data: {
							 ck_id: selectedValues
						 },
						 success: function(response) {
							 var csvData = new Blob([response], { type: 'text/csv' });
							 var csvUrl = URL.createObjectURL(csvData);
							 var tempLink = document.createElement('a');
							 tempLink.href = csvUrl;
							 tempLink.setAttribute('download', 'confirmedlist.csv');
							 tempLink.click();
							 URL.revokeObjectURL(csvUrl);
						 }
						});
						}

						function markDeliveryMethod(){
							var checkboxes = document.querySelectorAll('input[type="checkbox"][id="checkBoxSet"]:checked');
							var selectedValues = [];


							checkboxes.forEach(function(checkbox) {
							 selectedValues.push(checkbox.value);
							});


											if(selectedValues == ""){
												alert('You have to select atleast one order');
												return;
											}

							$.ajax({
							 url: "backend/mark_del_method.php",
							 method: "POST",
							 data: {
								 ck_id: selectedValues
							 },
							 success: function(response) {
								 $('#all_orders').load('ajax_pages/marked_orders.php');
							 }
							});
						}

						function print_bill() {
						  var checkboxes = document.querySelectorAll('input[type="checkbox"][id="checkBoxSet"]:checked');
						  var selectedValues = [];

						  checkboxes.forEach(function(checkbox) {
						    selectedValues.push(checkbox.value);
						  });

						  if (selectedValues.length === 0) {
						    alert('You have to select at least one order');
						    return;
						  }

						  // Create a form element
						  var form = document.createElement('form');
						  form.method = 'POST';
						  form.action = 'print_bill.php';

						  // Create an input field for each selected value
						  selectedValues.forEach(function(value) {
						    var input = document.createElement('input');
						    input.type = 'hidden';
						    input.name = 'selectedValues[]';
						    input.value = value;
						    form.appendChild(input);
						  });

						  // Append the form to the document body and submit it
						  document.body.appendChild(form);
						  form.submit();
						}

                        function print_bill_test() {
						  var checkboxes = document.querySelectorAll('input[type="checkbox"][id="checkBoxSet"]:checked');
						  var selectedValues = [];

						  checkboxes.forEach(function(checkbox) {
						    selectedValues.push(checkbox.value);
						  });

						  if (selectedValues.length === 0) {
						    alert('You have to select at least one order');
						    return;
						  }

						  // Create a form element
						  var form = document.createElement('form');
						  form.method = 'POST';
						  form.action = 'test_bill.php';

						  // Create an input field for each selected value
						  selectedValues.forEach(function(value) {
						    var input = document.createElement('input');
						    input.type = 'hidden';
						    input.name = 'selectedValues[]';
						    input.value = value;
						    form.appendChild(input);
						  });

						  // Append the form to the document body and submit it
						  document.body.appendChild(form);
						  form.submit();
						}
            function fetchSubItems() {
            const itemId = document.getElementById("item").value; // Get selected item ID
            const subItemDropdown = document.getElementById("sub_item");

            // Show "Loading..." while fetching data
            subItemDropdown.innerHTML = '<option value="">Loading...</option>';

            // Check if an item is selected
            if (!itemId) {
              subItemDropdown.innerHTML = '<option value="">Select Sub Item</option>';
              return;
            }

            // AJAX request to fetch sub-items
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "ajax_pages/fetch_sub_items.php", true); // Create a new file for backend processing
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function () {
              if (xhr.status === 200) {
                  subItemDropdown.innerHTML = xhr.responseText; // Update with fetched data
              } else {
                  subItemDropdown.innerHTML = '<option value="">Failed to load sub-items</option>';
              }
            };
            xhr.send("item_id=" + itemId); // Send the selected item ID to the server
            }


		</script>

    </body>
</html>

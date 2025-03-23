<?php include './layouts/header.php'; ?>

<?php include './layouts/sidebar.php'; ?>


			<div class="page-wrapper">
				<div class="content">
					<div class="page-header">
						<div class="page-title">
							<h4>Vendor Management</h4>
							<h6>Return Products To Vendor</h6>
						</div>
					</div>
					<!-- /add -->
					<div class="card">
						<div class="card-body">

              <div class="row">
                <div class="col-lg-4">

                  <div class="row">
                      <div class="col-md-6">
                          <label class="form-label">Select Vendor</label>
                          <select class="form-select" id="vendor_id" required>
                              <option value="">Choose Vendor</option>
                              <?php
                                  $vendors = $conn->query("SELECT * FROM tbl_vendors");
                                  while($vendor = $vendors->fetch_assoc()): ?>
                              <option value="<?= $vendor['vendor_id'] ?>">
                                  <?= htmlspecialchars($vendor['vendor_name']) ?>
                              </option>
                              <?php endwhile; ?>
                          </select>
                          <br>
                      </div>
                      <div class="col-md-6">
                          <label class="form-label">Select Product</label>
                          <select class="form-select" id="product_id" required>
                              <option value="">Choose Vendor</option>
                              <?php
                                  $product = $conn->query("SELECT * FROM tbl_product");
                                  while($productSearch = $product->fetch_assoc()): ?>
                              <option value="<?= $productSearch['id'] ?>">
                                  <?= htmlspecialchars($productSearch['name'])." ".$productSearch['barcode'] ?>
                              </option>
                              <?php endwhile; ?>
                          </select> <br>
                      </div>
                      <div class="col-md-12"> <br>
                          <label class="form-label">Enter Return Quantity</label>
                           <input type="text" id="qnty" class="form-control" name="" value="">
                      </div>

                      <div class="col-md-12"> <br>
                          <label class="form-label">NOTE (optional) </label>
                           <textarea name="name" id="note" class="form-control" rows="4" cols="80"></textarea>
                      </div>

                      <div class="col-md-12"> <br>
                        <button type="button" id="addReturn" onclick="addReturn()" class="btn btn-secondary btn-sm" name="button">Add Return</button>
                      </div>
                  </div>

                </div>
                <div class="col-lg-8">
                  <table id="returnTable" class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>#</th>
      <th>Vendor</th>
      <th>Product</th>
			<th>Return Qty</th>
			<th>Product Cost</th>
			<th>Total Value</th>
      <th>Note</th>
      <th>Action</th>

    </tr>
  </thead>
  <tbody>
    <?php
    $i = 1;
    $query = $conn->query("SELECT r.*, v.vendor_name, p.name AS product_name, p.barcode,p.cost_price
                           FROM tbl_item_return r
                           LEFT JOIN tbl_vendors v ON r.vendor_id = v.vendor_id
                           LEFT JOIN tbl_product p ON r.p_id = p.id
                           ORDER BY r.p_id DESC");
    while ($row = $query->fetch_assoc()):
			$note = htmlspecialchars($row['extra_note']);
	 $shortNote = strlen($note) > 15 ? substr($note, 0, 15) . '...' : $note;
  ?>
    <tr>
      <td><?= $i++ ?></td>
      <td><?= htmlspecialchars($row['vendor_name']) ?></td>
      <td><?= htmlspecialchars($row['product_name']) . ' (' . $row['barcode'] . ')' ?></td>
			<td><?= htmlspecialchars($row['ret_qty']) ?></td>
			<td><?= "LKR ".number_format($row['cost_price'],2) ?></td>
			<td><?= "LKR ".number_format($row['cost_price'] * $row['ret_qty'],2) ?></td>
      <td><span class="text-primary note-preview" style="cursor: pointer;" data-note="<?= $note ?>">
    <?= $shortNote ?>
  </span></td>
      <td>
  <button class="btn btn-danger btn-sm deleteReturn" data-id="<?= $row['ret_i_id'] ?>">Delete</button>
</td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>

                </div>


              </div>

						</div>
					</div>
					<!-- /add -->


				</div>
			</div>
        </div>
		<!-- /Main Wrapper -->
		<div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="noteModalLabel">Full Note</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="noteModalBody">
        <!-- Full note will appear here -->
      </div>
    </div>
  </div>
</div>


    <?php include './layouts/footer.php'; ?>
    <script>
$(document).ready(function(){
  $('.note-preview').on('click', function() {
    const fullNote = $(this).data('note');
    $('#noteModalBody').text(fullNote);
    $('#noteModal').modal('show');
  });
});

  $(document).ready(function() {
    $('#returnTable').DataTable();
  });
</script>

    <script>
    $(document).on('click', '.deleteReturn', function () {
  const p_id = $(this).data('id');
  if (confirm('Are you sure you want to delete this return?')) {
    $.ajax({
      url: 'backend/delete_return_p.php',
      type: 'POST',
      data: { p_id },
      success: function(response) {
        location.reload();
 // remove row from table
      },
      error: function() {
        alert('Failed to delete return item.');
      }
    });
  }
});

function addReturn() {
  const vendor_id = $('#vendor_id').val();
  const product_id = $('#product_id').val();
  const qnty = $('#qnty').val();
  const note = $('#note').val();

  if (!vendor_id || !product_id || !qnty) {
    alert('Please fill all required fields.');
    return;
  }

  $.ajax({
    url: 'backend/add_return_p.php',
    method: 'POST',
    data: {
      vendor_id: vendor_id,
      product_id: product_id,
      qnty: qnty,
      note: note
    },
    beforeSend: function() {
      $('#addReturn').prop('disabled', true).text('Processing...');
    },
    success: function(response) {
      // Optional: handle backend response


      $('#addReturn').prop('disabled', false).text('Add Return');
      $('#vendor_id').val('').trigger('change');
      $('#product_id').val('').trigger('change');
      $('#qnty').val('');
      $('#note').val('');
      location.reload();

      // Clear the form

    },
    error: function(xhr, status, error) {
      console.error('AJAX Error:', error);
      alert('Something went wrong. Please try again.');
      $('#addReturn').prop('disabled', false).text('Add Return');
    }
  });
}
</script>

    <script>
    $(document).ready(function(){
  $('#vendor_id').select2({
    theme: 'bootstrap-5',
    placeholder: 'Choose Vendor',
    width: '100%'
  });

  $('#product_id').select2({
    theme: 'bootstrap-5',
    placeholder: 'Choose Product',
    width: '100%'
  });

  // Focus search input on open
    $('#vendor_id, #product_id').on('select2:open', function () {
      setTimeout(() => {
        document.querySelector('.select2-container--open .select2-search__field')?.focus();
      }, 0);
    });
  });

    </script>

    </body>
</html>

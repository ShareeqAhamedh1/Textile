<?php include './backend/conn.php'; ?>

<!-- Search Box -->
<div class="d-flex mb-3">
  <input type="text" id="search" class="form-control me-2" placeholder="Search by Customer Name or Reference Number">
  <button id="clearSearch" class="btn btn-secondary">Clear</button>
</div>
<table class="table datanew" id="orderTable">
  <thead>
    <tr>
      <th>Reference Number</th>
      <th>Customer Name</th>
      <th>Customer Phone</th>
      <th>Date</th>
      <th>Payment Type</th>
      <th>Total Bill</th>
      <th>View Details</th>
      <th>Delete</th>
    </tr>
  </thead>
  <tbody>
    <!-- Data will be populated by AJAX -->
  </tbody>
</table>

<script type="text/javascript">
 $(document).ready(function () {

// Function to load the table data based on search
function loadTableData(query = '') {
  $.ajax({
    url: 'get_orders.php',  // This is the file that handles the search query
    type: 'GET',
    data: { search: query },
    success: function(data) {
      $('#orderTable tbody').html(data);
    }
  });
}

// Trigger search function on keyup event (as you type)
$('#search').keyup(function () {
  var query = $(this).val();
  loadTableData(query); // Load the table data based on the query
});

// Clear the search box and reload all data when the Clear button is clicked
$('#clearSearch').click(function() {
  $('#search').val(''); // Clear the input field
  loadTableData(); // Reload the table with all data (no search)
});

// Initially load all data
loadTableData();
});
</script>


<?php
include './backend/conn.php';

$grm_ref = $_REQUEST['grm_ref'];

// $customer_id = $_REQUEST['customer_id']; ?>

<div class="row">


  <div class="col-lg-12 col-sm-6 col-12">
    <input type="hidden" id="grm_ref_modal" name="order_id" value="<?= $grm_ref ?>">

    <br>
  <div class="">
    <div class="form-group">

      <button onclick="update(<?= $grm_ref ?>)" type="button" class="btn btn-primary btn-sm">Update Table</button>
    </div>
  </div>
</div>

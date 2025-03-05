<div class="sidebar" id="sidebar">
  <div class="sidebar-inner slimscroll">
    <div id="sidebar-menu" class="sidebar-menu">
      <ul>
        <?php $pg_name = basename($_SERVER['PHP_SELF']); ?>
        <li class="<?php if($pg_name=='index.php'){echo('active');} ?>" >
          <a href="index.php" ><img src="assets/img/icons/dashboard.svg" alt="img"><span> Dashboard</span> </a>
        </li>



        <li class="<?php if($pg_name=='pos.php'){echo('active');} ?>" >
          <a href="pos.php" ><i class="fa fa-desktop"></i><span> POS</span> </a>
        </li>
        <li class="<?php if($pg_name=='pos_grm.php'){echo('active');} ?>">
    <a href="pos_grm.php"><i class="fas fa-cash-register"></i><span> Bills</span></a>
</li>
<li class="<?php if($pg_name=='manage_expenses.php'){echo('active');} ?>">
    <a href="manage_expenses.php"><i class="fas fa-money-bill-wave"></i><span> Expenses</span></a>
</li>
<li class="<?php if($pg_name=='drawer_report.php'){echo('active');} ?>">
    <a href="drawer_report.php"><i class="fa fa-archive"></i><span> Drawer Report</span></a>
</li>



        <li class="<?php if($pg_name=='vendorlist.php'){echo('active');} ?>" >
          <a href="vendor.php" ><i class="fa fa-desktop"></i><span> Vendor Management</span> </a>
        </li>
        <li class="<?php if($pg_name=='customers.php'){echo('active');} ?>">
    <a href="customers.php"><i class="fa fa-users"></i><span> Customer Management</span></a>
</li>



        <?php if($u_id==1){
                  ?>
                  <li class="<?php if($pg_name=='sales_report.php'){echo('active');} ?>" >
          <a href="sales_report.php" ><img src="assets/img/icons/sale.svg" alt="img"><span> Sales Report</span> </a>
        </li>






        <li class="submenu">
          <a href="javascript:void(0);"><i class="fa fa-user" data-bs-toggle="tooltip" title="" data-bs-original-title="fa fa-anchor" aria-label="fa fa-anchor"></i>
            <span> User Management</span> <span class="menu-arrow"></span></a>
          <ul>
            <li><a class="<?php if($pg_name=='add_user.php'){echo('active');} ?>" href="add_user.php">User List</a></li>
            <li><a class="<?php if($pg_name=='user_access.php'){echo('active');} ?>" href="user_access.php">Manage Users</a></li>
          </ul>
        </li>

      </ul>

      <?php
                } ?>
    </div>
  </div>
</div>

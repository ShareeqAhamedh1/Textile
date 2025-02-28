<?php
    include '../backend/conn.php';

    $id = $_REQUEST['user_id'];

    $sqlUsers = "SELECT * FROM tbl_user WHERE user_id='$id'";
    $rsUsers = $conn->query($sqlUsers);

    if($rsUsers->num_rows > 0){
       $rowUsers = $rsUsers->fetch_assoc();
      ?>
      
<div class="container">
                           <form action="backend/edit_user.php" method="POST">
                            <input type="hidden" name="u_id" id="" value="<?= $rowUsers['user_id'] ?>">
                             <div class="form-group">
                                <label for=""> User Name </label>
                                <input type="text" class="form-control" name="u_name" id="" value="<?= $rowUsers['username'] ?>">
                             </div>
                             <div class="form-group">
                                <label for=""> Password </label>
                                <input type="text" class="form-control" name="u_pass" id="" value="<?= $rowUsers['password'] ?>">
                             </div>
                             <div class="form-group">
										<label>Sales Point</label>
										
							    </div>
                                <button type="submit" class="btn btn-primary btn-sm"> Update User Details  </button>
                           </form>
                        </div>


                        <?php
    }
?>
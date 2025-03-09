<?php
    include '../backend/conn.php';
    if (isset($_REQUEST['sel_date_f'])) {
        $date_sel_one = $_REQUEST['sel_date_f'];
        $date_sel_two = $_REQUEST['sel_date_t'];
    } else {
        $date_sel_one = date("Y-m-d");
        $date_sel_two = date("Y-m-d");
    }

    $totalExpenses=0;

    $sql="SELECT * FROM tbl_expenses WHERE expense_date BETWEEN '$date_sel_one' AND '$date_sel_two' AND cash_in_out=2";
    $rs=$conn->query($sql);

    if($rs->num_rows > 0){

        ?>
        <table class="table datatable" id="expense_report_id">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Added Date</th>
                </tr>
            </thead>
            <tbody>
              <?php
              while($row=$rs->fetch_assoc()){
                  $expense_id = $row['expense_id'];
                  $totalExpenses +=$row['amount'];
               ?>
               <tr>
                 <td> <?= $row['description'] ?> </td>
                 <td> <?= $row['amount'] ?> </td>
                 <td> <?= $row['expense_date'] ?> </td>
                 <td> <?= $row['created_at'] ?> </td>
               </tr>
                  <?php } ?>
            </tbody>
        </table>

        <br>
        <div class="alert alert-primary">
          <h6 class="text-dark">Total Expenses Between <?= $date_sel_one ?> & <?= $date_sel_one ?> : <span style="font-weight:bold;">LKR <?= number_format($totalExpenses,2) ?></span> </h6>
        </div>

        <?php

    }else{
?>
<tr>
    <td colspan="6" class="text-center">
        <strong style="font-size: 25px;">NO DATA FOUND</strong>
    </td>
</tr>

<?php } ?>

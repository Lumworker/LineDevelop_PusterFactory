<?php
 include('./db_connect.php');
 $BillName = "";
 $sql = "SELECT `Bill_Intent` FROM `Bill_Intent_Temp` WHERE `userToken` = 'Ua06b0581575ec70b0a121ea8e9bc2f8a' ";
 $result = $conn->query($sql);
 while ($row = $result->fetch_assoc()) {
     $BillName  =  $row["Bill_Intent"];
 }

 $Pathname = 'tmp_image/' .date("Y") ."/". date("m") ."/".$BillName."/";
 define('UPLOAD_DIR', $Pathname);
 //Set File Save To
 $filename = uniqid();
 $file = UPLOAD_DIR . $filename . '.png';
 //Set File name 
 //file_put_contents($file, $Image);
 $sql_insertTran = "INSERT INTO `Bill_Transaction`( `ImagePath`, `Insert_Date`, `userToken`) VALUES ('$file',('Select now()'),'Ua06b0581575ec70b0a121ea8e9bc2f8a')";
//  $result2 = $conn->query($sql_insertTran);
//  if (mysqli_num_rows($result2)) {
//      return "Update Bill_Intent Complete";
//  }
 echo $sql_insertTran;
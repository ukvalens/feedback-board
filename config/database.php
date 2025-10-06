<?php

 $host = "localhost";
 $db_name = "feedback_board";
 $username = "root";
 $password = "";
 $conn=mysqli_connect($host,$username,$password,$db_name);
    if(!$conn){
        die("Connection failed: " . mysqli_connect_error());
    }
    else{
        //  echo "Connected successfully";
    }


?>
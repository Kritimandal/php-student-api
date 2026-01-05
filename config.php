<?php

$host_name = 'localhost';
$username = 'root';
$password = '';
$db_name = 'Api_test';

$conn = mysqli_connect($host_name, $username, $password, $db_name);

if(! $conn )  
{  
  die('Could not connect: ' . mysqli_error());  
}  else{
    echo "successfull";
}



?>
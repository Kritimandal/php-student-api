<?php

header('Content-Type:application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Method: POST');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Credentials, Access-Control-Allow-Method, Authorization, X-Requested-With');

$data = json_decode(file_get_contents("php://input"), true);


$name = $data['sname'];
$age = $data['sage'];
$city = $data['scity'];

include "config.php";

$sql = "INSERT INTO students( name, age, city) VALUES ( '{$name}', {$age}, '{$city}') ";



if(mysqli_query( $conn, $sql)) {

    echo json_encode(array('message'=> 'student record inserted', 'status'=> true));

}else{

    echo json_encode(array('message'=> 'student record not inserted', 'status'=> false));

}





?>
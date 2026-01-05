<?php

header('Content-Type:application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Method: POST');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Credentials, Access-Control-Allow-Method, Authorization, X-Requested-With');

// Decode the JSON data from the request body
$data = json_decode(file_get_contents("php://input"), true);

include "config.php";

// Loop through each item in the data array
foreach($data as $item) {
    $name = $item['sname'];  // Corrected from $data to $item
    $age = $item['sage'];    // Corrected from $data to $item
    $city = $item['scity'];  // Corrected from $data to $item

    // Prepare the SQL query
    $sql = "INSERT INTO students(name, age, city) VALUES ('{$name}', {$age}, '{$city}')";

    // Execute the query and provide feedback
    if(mysqli_query($conn, $sql)) {
        $response[] = array('message'=> 'student record inserted', 'status'=> true);
    } else {
        $response[] = array('message'=> 'student record not inserted', 'status'=> false);
    }
}

// Output the JSON response
echo json_encode($response);

?>

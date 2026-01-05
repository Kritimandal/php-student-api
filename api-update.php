<?php



header('Content-Type:application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Method: PUT');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Credentials, Access-Control-Allow-Method, Authorization, X-Requested-With');

// Decode the JSON data from the request body
$data = json_decode(file_get_contents("php://input"), true);

$id = $data['sid'];

if (!isset($id)) {
    echo json_encode(array('message' => 'ID is required', 'status' => false));
    exit;
}

include "config.php";

// Initialize an array to store the parts of the SQL query
$updates = [];

if (isset($data['sname'])) {
    $name = $data['sname'];
    $updates[] = "name = '{$name}'";
}

if (isset($data['sage'])) {
    $age = $data['sage'];
    $updates[] = "age = {$age}";
}

if (isset($data['scity'])) {
    $city = $data['scity'];
    $updates[] = "city = '{$city}'";
}

// Check if there are any updates to make
if (count($updates) > 0) {
    // Join the updates array into a string separated by commas
    $updateString = implode(",", $updates);
    var_dump($updateString);
    // Prepare the SQL query
    $sql = "UPDATE students SET {$updateString} WHERE id = {$id}";

    // Execute the query and provide feedback
    if (mysqli_query($conn, $sql)) {
        echo json_encode(array('message' => 'Student record updated', 'status' => true));
    } else {
        echo json_encode(array('message' => 'Student record not updated', 'status' => false, 'error' => mysqli_error($conn)));
    }
} else {
    echo json_encode(array('message' => 'No valid fields to update', 'status' => false));
}

?>



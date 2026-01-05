<?php



header('Content-Type:application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Method: DELETE');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Credentials, Access-Control-Allow-Method, Authorization, X-Requested-With');

$data = json_decode(file_get_contents("php://input"), true );

$id = $data['sid'] ?? null;
if (!isset($id)) {
    echo json_encode(array('message' => 'ID is required', 'status' => false));
    exit;
}

include "config.php";

$sql = "DELETE FROM students WHERE id = {$id}";

if(mysqli_query($conn, $sql)){

    echo json_encode(array('message' => 'Successfully Deleted', 'status' => true));

}else{

    echo json_encode(array('message' => 'Not Deleted', 'status' => false));
}

?>
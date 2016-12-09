<?php 
require_once "server/Api.php";

header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Headers:key");

error_reporting(E_ALL);
ini_set('display_errors', 'on');

$api = new ClientApi();

$func = $api->action();
$result = $api->$func();


if ($result === 0){
    $result = array("error"=>"Error. Result 0");
    echo json_encode($result);
}
else if (!is_array($result)){
    echo $result;
}
else if (count($result) == 0){
   echo "";
}
else{
    echo json_encode($result);
}
die();
<?php


// Определяем создание таблиц
function create_tables($db){

    $db->create_table("trainings", array("name"=>$db->type_char(100),
        "active"=>"BOOLEAN"));

    $db->create_table("training_stats",
       array(
            "trainingId" => "int(11)",
            "distance" => "float(5,1)",
            "dateBegin" => "DATETIME",
            "dateEnd" => "DATETIME",
            "dataReceived" => "DATETIME",
           "speed" => "float(5,1)",
           "avgPace" => $db->type_char(100),
           "avgSpeed" => "float(5,1)",
           "time" => "int(11)"

        ));
    
    $db->create_table("coordinates",
        array(
            "number" => "int(5)",
            "trainingId" => "int(11)",
            "latitude" => $db->type_char(20),
            "longitude" => $db->type_char(20)
        ));
}
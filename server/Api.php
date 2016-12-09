<?php

require_once "Database/BaseApi.php";
require_once "Entity.php";

$key = "tC19K25M624DO4PXF1s9uwrZayQtbwP0";

class ClientApi extends BaseApi{

    public function __construct(){
        parent::__construct(array("0_1","0_2"));
    }

    //получение активной тренировки
    public function active_training(){

        $query = "SELECT trainings.id as id, 
        trainings.name as name, trainings.type, 
        trainings.active as active, 
        training_stats.distance, 
        training_stats.dateBegin,
        (CASE WHEN training_stats.dateEnd IS null THEN '' ELSE training_stats.dateEnd END)  as dateEnd, 
        training_stats.dataReceived, 
        training_stats.speed 
        FROM trainings left join training_stats on trainings.id = training_stats.trainingId 
        WHERE trainings.active = true ORDER by training_stats.dateBegin DESC";

        return $this->db->get($query);        
    }

    public function delete(){
        $id = $this->data["id"];

         $this->db->insert_update("DELETE FROM training_stats WHERE trainingId = ?", [$id]);
         $this->db->insert_update("DELETE FROM coordinates WHERE trainingId = ?", [$id]);
         $this->db->insert_update("DELETE FROM trainings WHERE id = ?", [$id]);

         return "ok";
    }

    public function trainings(){

        $get = 10;
        $skip = ($this->data["count"] - 1) * $get;

        $begin = $this->to_date_str($this->data["begin"]);
        $end = $this->to_date_str($this->data["end"]);
        $q = "SELECT trainings.id, 
        trainings.name, trainings.type,
        trainings.active, 
        training_stats.distance as dist, 
        training_stats.dateBegin as date, 
        training_stats.dateEnd, 
        training_stats.dataReceived, 
        training_stats.speed 
        FROM trainings left join training_stats on trainings.id = training_stats.trainingId 
        WHERE trainings.active = 0 
        and training_stats.dateBegin BETWEEN ? and ?
        ORDER by date DESC LIMIT ".$skip.",".$get;

        return $this->db->get($q, [$begin,$end]);
    }

    //создание тренировки
    public function create_training(){
        if (!$this->is_post()){
            return 0;
        }
        //сделаем автивные тренировки завершенными
        $this->db->insert_update("UPDATE trainings SET active = 0 WHERE trainings.active = 1");
        
        $q = "INSERT INTO trainings (name, active,type) VALUES(?,?,?)";

        $name = $this->data["name"];
        $date = $this->to_date_str($this->data["date"]);
        $type = $this->data["type"];

        //добавляем активную тренировку
        $this->db->insert_update($q, [$name, true, $type]);
        $index = $this->db->last_id();

        $q = "INSERT INTO training_stats (trainingId, dataReceived, dateBegin, distance, speed)
        VALUES(?,?,?,?,?)";

        $this->db->insert_update($q, [$index, $date,$date,0,0]);

        return $index;
    }

    //остановка тренировки
    public function stop_training(){
        if (!$this->is_post()){
            return 0;
        }


        $id = $this->data["id"];
        $this->db->insert_update("UPDATE trainings SET active = 0 WHERE trainings.id = ?",[$id]);

        $date = $this->to_date_str($this->data["date"]);
        $this->db->insert_update("UPDATE training_stats SET dateEnd = ?, speed = 0 WHERE trainingId = ?",[$date,$id]);

        return "ok";
    }

    //добавление параметров
    public function add_stats(){

        if (!$this->is_post()){
            return 0;
        }

        $id = 1;
        $date = $this->to_date_str("2016.12.02 10:08:10");
        $speed = 10;
        $distance = 5;

        $q = "INSERT INTO training_stats (dataReceived, distance, speed)
        VALUES(?,?,?) WHERE trainingId = ? ";

        $this->db->insert_update($q, [$date, $distance,$speed,$id]);

        return "ok";
    }

    //Получение координат
    public function get_coords(){

        $skip = $this->data["skip"];

        $q = "SELECT longitude, latitude, number FROM coordinates WHERE trainingId = ? order by number LIMIT 9999 OFFSET ".$skip;
        return $this->db->get($q,[$this->data["id"]]);
    }

    //добавление координат
    public function add_coord(){
        if (!$this->is_post()){
            return 0;
        }

        $data = $this->data["coord"];

        $q = "INSERT INTO coordinates (latitude, longitude, number,trainingId) 
        VALUES(?,?,?,?)";

        foreach($data as $k){
            $this->db->insert_update($q, [$k["latitude"], $k["longitude"],$k["number"],$k["id"]]);
        }
        
        return "ok";
    }

    public function statistic(){
        if (!$this->is_post()){
            return 0;
        }
    
        $this->db->insert_update("UPDATE training_stats SET speed = ?, 
        distance = ?,
        training_stats.time = ?,
        avgSpeed = ?,
        avgPace = ? WHERE training_stats.trainingId = ?",
        [$this->data["speed"],
        $this->data["distance"],
        $this->data["time"],
        $this->data["avgSpeed"],
        $this->data["avgPace"],
        $this->data["id"]]);
        
        return "ok";   
    }

    //получение информации о тренировке
    public function training_info(){
        $id = $this->data["id"];

        $q = "SELECT trainings.id, 
        trainings.name, trainings.type,
        trainings.active, 
        training_stats.distance as dist, 
        training_stats.dateBegin as date, 
        training_stats.dateEnd, 
        training_stats.dataReceived, 
        training_stats.speed, training_stats.time, training_stats.avgPace, training_stats.avgSpeed 
        FROM trainings left join training_stats on trainings.id = training_stats.trainingId 
        WHERE trainings.id = ?";

        return $this->db->get($q,[$id]);

    }

    //количество тренировок
    public function count(){

        $begin = $this->to_date_str($this->data["begin"]);
        $end = $this->to_date_str($this->data["end"]);

        $q = "SELECT COUNT(*) as count FROM trainings
        left join training_stats on trainings.id = training_stats.trainingId 
        WHERE active = 0 and training_stats.dateBegin BETWEEN ? and ?";
        return $this->db->get($q,[$begin, $end]);
    }

    


}
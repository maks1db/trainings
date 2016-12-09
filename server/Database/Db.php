<?php

//Адрес сервера баз данных: 195.128.120.144
//База данных: maks1db_73017_3
//Логин: maks1db_73017_3
//Пароль: PDnE2XgSRm

require_once "DbConnect.php";

class Db{

    protected $pdo;
    public function __construct(){

        $options = array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        );
        global $host,$base,$user,$password;
        $this->pdo = new PDO("mysql:host=".$host.";dbname=".$base,$user,$password, $options);
    }

    //логический тип
    public function type_bool(){
        return "boolean";
    }

    //тип varchar
    public function type_char($l = 0){
        if ($l == 0){
            $l = 255;
        }
        return "varchar(".$l.")";
    }

    public function create_table($name, $keys){

        $s_keys = "";
        foreach($keys as $k => $v){
            $s_keys = $s_keys.$k." ".$v.", ";
        }
        $q = "CREATE TABLE IF NOT EXISTS ".$name." (id int(11) NOT NULL AUTO_INCREMENT, ".$s_keys."PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

        $result = $this->pdo->query($q);

        return $result;
    }

    public function get($str, $params = array()){

        $obj = $this->pdo->prepare(str_replace("\r\n", " ", $str) );

        if (isset($params) && is_array($params)){
            $obj->execute($params);
        }

        $data = $obj->fetchAll();
        if (count($data) == 1){
            return $data[0];
        }

        $arr = array();
        foreach($data as $d=>$k){
            $arr[] = $k;
        }

        return $arr;
    }

    public function insert_update($str, $params = array()){
        $obj = $this->pdo->prepare($str);
        $obj->execute($params);
    }

    public function last_id(){
        return $this->pdo->lastInsertId();   
    }
}
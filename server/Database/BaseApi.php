<?php

require_once "Db.php";
require_once "Migrations.php";

class BaseApi{
    protected $db;
    public $data;

    private $migrations = array();   

    //парметры $migrations - массив миграций
    public function __construct($migrations){
        $this->db = new Db();
        $this->migrations = $migrations;        
        
        //создадим таблицу миграций, если нет
        $this->db->create_table("migrations", 
                array("name"=>$this->db->type_char(100)));

        //получим последнюю миграцию и выполним автомиграцию
        $data = $this->db->get(
            "SELECT name from migrations order by id DESC Limit 1"
        ); 
        $migration_version = "empty";
        if (count($data) > 0){
            $migration_version = $data["name"];
        }

        $run_migration = false; $migration_before = $migration_version;
        $obj = new MigrationsApi($this->db);
        foreach($this->migrations as $migration){

            if ($migration_version == "empty"){
                $run_migration = true;
            }

            if ($run_migration == true){
                $migration_version = $migration;
                $m = "migration_" . $migration;
                $obj->$m();

                if ($migration_before != $migration){
                    $this->db->insert_update(
                        "INSERT INTO migrations (name) VALUES(?)", [$migration]
                    );
                    $migration_before = $migration;
                }
            }

            if ($migration_version == $migration){
                $run_migration = true;   
            }
        }

        if ($this->is_post()){
            $this->data = $_POST;
        }
        else{
            $this->data = $_GET;
        }
    }

    public function action(){
        return $this->data["action"];
    }

    function is_post(){
        return $_SERVER['REQUEST_METHOD'] == "POST";
    }

    //Необходимые действия для конвертации полученной переменной в дату для mySQL
    function to_date_str($val){
        
        $data = explode(" ", $val);
        $date_arr = explode(".", $data[0]);

        $new_val = $date_arr[2]."-".$date_arr[1]."-".$date_arr[0]." ".$data[1];

        return $new_val;
    }
}
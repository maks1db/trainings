<?php

require_once "BaseMigration.php";

class MigrationsApi extends BaseMigration{    
    
    public function __construct($db){
        parent::__construct($db);
    } 

    //Создание основных таблиц системы
    public function migration_0_1(){
        create_tables($this->db);  
    }

    //Добавление колонки вид тренировки
    public function migration_0_2(){
        $this->db->insert_update("ALTER TABLE trainings ADD COLUMN type VARCHAR(100)");   

        //заполним все типы как бег
        $this->db->insert_update("UPDATE trainings SET type = ?",["Бег"]); 
    }
}
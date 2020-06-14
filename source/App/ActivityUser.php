<?php

namespace Source\App;

require __DIR__ . "/../../vendor/autoload.php";


use CoffeeCode\DataLayer\DataLayer;

class ActivityUser extends DataLayer{
    
    public function __construct()
    {
        parent::__construct("activity_users",["iduser","drink"],"id",true);        
    }

    public function user(){
        return (new User())->find("iduser = :id","id={$this->iduser}")->fetch();
    }
}
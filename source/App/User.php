<?php

namespace Source\App;

require __DIR__ . "/../../vendor/autoload.php";


use CoffeeCode\DataLayer\DataLayer;

class User extends DataLayer{
    
    public function __construct()
    {
        parent::__construct("users",["name","email","password"],"iduser",true);        
    }
}
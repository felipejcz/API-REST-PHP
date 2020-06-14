<?php
require_once __DIR__ . '/vendor/autoload.php';

use CoffeeCode\Router\Router;

$router = new Router(URL_BASE);

$router->namespace("Source\App\Controllers");

$router->group("api");

$router->post("/users", "UserController:store");//CRIA USUARIO
$router->post("/login", "UserController:login");//AUTENTICA USUARIO
$router->get("/users/{iduser}","UserController:show");//OBTEM UM USUARIO
$router->get("/users","UserController:list");//OBTEM LISTA DE USUARIOS
$router->put("/users/{iduser}","UserController:update");//EDITA SEU USUARIO
$router->delete("/users/{iduser}","UserController:destroy");//DELETA SEU USUARIO
$router->post("/users/{iduser}/drink","UserController:drink");//INCREMENTA CONTADOR
$router->get("/users/{iduser}/history", "UserController:history");//LISTA HISTORICO DO USUARIO
$router->get("/users/ranking", "UserController:ranking");//RETORNAR USUARIO QUE MAIS BEBEU NO DIA


$router->group("whoops");
$router->get("/{errcode}","UserController:error");


$router->dispatch();


if($router->error()){
    $router->redirect("/whoops/{$router->error()}");
}

die();
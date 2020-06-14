<?php

namespace Source\App\Controllers;

require __DIR__ . "/../../../vendor/autoload.php";

header('Content-Type: application/json');


use Source\App\User;
use Source\App\ActivityUser;

class UserController {
    private $_user;

    public function store($data){
        if(empty($data)){
            http_response_code(400);
            echo json_encode(["message" => "Dados incompletos."]);
            die(1);
        }
        $model = new User();
        $result = $model->find("email = :email","email={$data['email']}")->fetch();
        if(!is_null($result)){
            http_response_code(400);
            echo json_encode(["message" => "Usuario já cadastrado no sistema."]);
            die(1);
        }
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->drink_counter = 0;
        $user->token = md5($data['name'].time().random_bytes(5));
        
        if($user->save()){
            http_response_code(200);
            echo json_encode(["message" => "Usuario {$user->name} cadastrado com sucesso."]);
        }else{
            http_response_code(500);
            echo json_encode(["message" => "Erro ao tentar cadastrar usuario: {$user->fail()->getMessage()}"]);
        }
    }

    public function login($data){
        if(empty($data) || !isset($data['email']) || !isset($data['password'])){
            http_response_code(400);
            echo json_encode(["message" => "Dados incompletos."]);
            die(1);
        }
        $model = new User();
        
        $user = $model->find("email = '{$data['email']}' AND password = '{$data['password']}'")->fetch();
        if($user){
            $user->token = md5(time().$user->name);
            $user->save();
            $array = [
                "token" => $user->token,
                "iduser" => $user->iduser,
                "email" => $user->email,
                "name" => $user->name,
                "drink_counter" => $user->drink_counter
                ];
                http_response_code(200);
            echo json_encode($array);
        }else{
            http_response_code(400);
            echo json_encode(["message" => "O usuário não existe ou que a senha está inválida."]);
        }
    }

    public function show($data){
        $this->tokenValidated();
        if(empty($data) || !isset($data['iduser'])){
            http_response_code(400);
            echo json_encode(["message" => "Dados incompletos."]);
            die(1);
        }
        $model = new User();
        $user = $model->findById(intval($data['iduser']));
        if($user){
            
            $array = [
                "iduser" => $user->iduser,
                "name" => $user->name,
                "email" => $user->email,
                "drink_counter" => $user->drink_counter
                ];
                http_response_code(200);
            echo json_encode($array);
        }else{
            http_response_code(400);
            echo json_encode(["message" => "Não foi encontrado usuario com esse id!"]);
        }
    }

    public function list($data){
        $model = new User();
        $this->tokenValidated();
        $list = $model->find()->fetch(true);
        $array = [];
        foreach ($list as $v) {
            //no enunciado diz que a saida é (array de usuários), então vou retornar todas as colunas.
            //o correto seria ocultar o token e o password.
            array_push($array,[
                "iduser" => $v->iduser,
                "name" => $v->name,
                "email" => $v->email,
                "password" => $v->password,
                "drink_counter" => $v->drink_counter,
                "token" => $v->token,
                "created_at" => $v->created_at,
                "updated_at" => $v->updated_at
                ]
            );
        }
        http_response_code(200);
        echo json_encode($array);
    }
    
    public function update($data){
        $this->tokenValidated();
        if(!isset($data['name']) || !isset($data['email']) || !isset($data['password']) || !isset($data['iduser'])){
            http_response_code(400);
            echo json_encode(["message" => "Dados invalidos ou faltantes."]);
            die(1);
        }
        if($this->_user->iduser != intval($data['iduser'])){
            http_response_code(401);
            echo json_encode(["message" => "Não é possivel editar outros usuarios."]);
            die(1);
        }
        $this->_user->name = $data['name'];
        $this->_user->email = $data['email'];
        $this->_user->password = $data['password'];
        
        if($this->_user->save()){
            http_response_code(200);
            echo json_encode(["message" => "Usuario {$this->_user->name} atualizado com sucesso."]);
        }else{
            http_response_code(500);
            echo json_encode(["message" => "Erro ao tentar atualizar usuario: {$this->_user->fail()->getMessage()}"]);
        }
    }

    public function destroy($data){
        $this->tokenValidated();
        if($this->_user->iduser != intval($data['iduser'])){
            http_response_code(401);
            echo json_encode(["message" => "Não é possivel excluir outros usuarios."]);
            die(1);
        }
        if($this->_user->destroy()){
            http_response_code(200);
            echo json_encode(["message" => "Usuario {$this->_user->name} excluido com sucesso."]);
        }else{
            http_response_code(500);
            echo json_encode(["message" => "Erro ao tentar excluir usuario: {$this->_user->fail()->getMessage()}"]);
        }
    }

    public function drink($data){
        $this->tokenValidated();
        if(!isset($data) || empty($data['drink_ml'])){
            http_response_code(400);
            echo json_encode(["message" => "Falta informações."]);
            die(1);
        }
        $user = $this->_user;
        $activity = new ActivityUser();
        $user->drink_counter += $activity->drink = intval($data['drink_ml']);
        $activity->iduser = $user->iduser;
        if($user->save()){
            if($activity->save()){
                http_response_code(200);
                echo json_encode([
                "iduser" => $user->iduser,
                "email" => $user->email,
                "name" => $user->name,
                "drink_counter" => $user->drink_counter
                ]);
            }else{
                http_response_code(500);
                echo json_encode(["message" => "Erro ao salvar historico de drink: {$user->fail()->getMessage()}"]);
            }
            
        }else{
            http_response_code(500);
            echo json_encode(["message" => "Erro ao tentar incrementar drink: {$user->fail()->getMessage()}"]);
        }
    }

    public function history($data){
        
        $this->tokenValidated();
        $activity = new ActivityUser();
        $id = intval($data['iduser']);
        $history = $activity->find("iduser = :user", "user={$id}")->order("created_at DESC")->fetch(true);
        
        if($history){
            $array = [];
            foreach ($history as $v) {
                array_push($array,[
                    "created_at" => $v->created_at,
                    "drink" => $v->drink
                    ]
                );
            }
            http_response_code(200);
            echo json_encode($array);
        }else{
            http_response_code(400);
            echo json_encode(["message" => "Id incorreto ou sem registros."]);
        }
    }

    public function ranking($data){
        $hoje = date("Y-m-d");
        $model = new ActivityUser();
        $res = $model->find("DATE(created_at) = :dia", "dia={$hoje}","iduser,sum(drink) as drink")->group("iduser")->order("sum(drink) desc")->fetch();
        if(is_null($res)){
            http_response_code(500);
            echo json_encode(["message" => "Não foi retornado nenhum registro."]);
            die(1);
        }
        http_response_code(200);
        echo json_encode([
            "name" => $res->user()->name,
            "quant" => $res->drink
        ]);
    }

    public function error($data){
        echo "<h1>error: {$data['errcode']}</h1>";
    }

    private function tokenValidated(){
        $model = new User();
        $headers = getallheaders();
        $user = null;
        if(isset($headers['token']) && !empty($headers['token'])){
            $user = $model->find("token = :token","token={$headers['token']}")->fetch();
            if(is_null($user)){
                http_response_code(403);
                echo json_encode(["message" => "Token invalindo, faça login novamente."]);
                die(1);
            }
        }else{
            http_response_code(400);
            echo json_encode(["message" => "Token inexistente ou vazio."]);
            die(1);
        }
        $this->_user = $user;
    }
}
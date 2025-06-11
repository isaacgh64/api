<?php
    require_once("../../connection.php");
    $connection = connectionBD();
    header('Content-Type: application/json');
    header('Content-Type: application/json');
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    if($_SERVER["REQUEST_METHOD"]=="POST"){
        $response=[];
        $inputJSON = file_get_contents("php://input");
        $input = json_decode($inputJSON, true);
        $token= $input["token"];
        $idFav = $input["idFav"];
        $idShow = $input["idShow"];
        if(!empty($token)&&!empty($idFav)&&!empty($idShow)){
                try{
                    //Insert data in BD
                    $insert = $connection->prepare("UPDATE `user` SET `id_stream_fav` = :id_stream_fav, `id_stream_show` = :id_stream_show WHERE `token` = :token");
                    $insert->bindParam(":id_stream_fav", $idFav);
                    $insert->bindParam(":id_stream_show", $idShow);
                    $insert->bindParam(":token", $token);
                    $insert->execute();
                    //Returns the response
                    http_response_code(201);
                    $response = array(
                        'code' => 201,
                        'status' => 'success',
                        'message' => 'List successfully registered',
                    );    
                }catch(Exception $e){
                    http_response_code(400);
                    $response = array(
                        'code' => 400,
                        'status' => 'Server Error',
                        'message' => 'The server response a bad request',
                        'error'=> $e,
                    );
                }
        }else{
            http_response_code(400);
                $response = array(
                    'code' => 400,
                    'status' => 'Bad Request',
                    'message' => 'The server is waiting for variables', 
                );
        }
        echo json_encode($response);
    }else{
        http_response_code(500);
        $response = array(
            'code' => 500,
            'status' => 'Server Error',
            'message' => 'The server response a bad request',
            'error'=> $e,
        );
        echo json_encode($response);
    }
    
    //Create AuthToken
    function createToken(){
        $token = bin2hex(random_bytes(16));
        return $token;
    }
?>
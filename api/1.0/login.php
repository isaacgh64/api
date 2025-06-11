<?php
    require_once("../../connection.php");
    $connection = connectionBD();
    header('Content-Type: application/json');
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $response=[];
        $inputJSON = file_get_contents("php://input");
        $input = json_decode($inputJSON, true);
        $username = $input["username"];
        $password = (string) $input["password"];
        
        if(!empty($username) && !empty($password)){
            try{
                $stmt = $connection->prepare("SELECT mail,password,token FROM user WHERE mail = :mail");
                $stmt->bindParam(':mail', $username);
                $stmt->execute();
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                if(!empty($data)){
                    if(password_verify($password,$data["password"])){
                        http_response_code(200);
                        $response = array(
                            'code' => 200,
                            'status' => 'success',
                            'message' => 'LogIn successfully',
                            'token' => $data["token"],
                        );    
                    }else{
                        http_response_code(401);
                        $response = array(
                            'code' => 401,
                            'status' => 'Error',
                            'message' => 'Invalid credentials',
                        );    
                    }
                }else{
                    http_response_code(409);
                    $response = array(
                        'code' => 409,
                        'status' => 'Bad Request',
                        'message' => 'LogIn failure',
                    );    
                }
            }catch(Exception $e){
                http_response_code(500);
                $response = array(
                    'code' => 500,
                    'status' => 'Server Error',
                    'message' => 'The server encountered an unexpected error',
                    'error'=> isset($e) ? $e->getMessage() : null,
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
        http_response_code(405);
        $response = array(
            'code' => 405,
            'status' => 'Error',
            'message' => 'Method not allowed',
        );
        echo json_encode($response);
    }
?>
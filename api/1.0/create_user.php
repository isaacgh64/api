<?php
    require_once ('/libs/src/PHPMailer.php');
    require_once ('/libs/src/SMTP.php');
    require_once ('/libs/src/Exception.php');

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

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
        $name= $input["name"];
        $username = $input["username"];
        $password = (string) $input["password"];
        if(!empty($name)&&!empty($username)&&!empty($password)){
            $stmt = $connection->prepare("SELECT mail FROM user WHERE mail = :mail");
            $stmt->bindParam(':mail', $username);
            $stmt->execute();
            $checkEmail = $stmt->fetch(PDO::FETCH_ASSOC);
            if(empty($checkEmail)){
                $token = createToken();
                $hash = password_hash($password, PASSWORD_DEFAULT);
                try{
                    //Insert data in BD
                    $insert = $connection->prepare("INSERT INTO `user` (`name`, `mail`, `password`, `token`) VALUES (:name, :mail, :password, :token)");
                    $insert->bindParam(":name", $name);
                    $insert->bindParam(":mail", $username);
                    $insert->bindParam(":password", $hash);
                    $insert->bindParam(":token", $token);
                    $insert->execute();
                    //Returns the response
                    http_response_code(201);
                    $response = array(
                        'code' => 201,
                        'status' => 'success',
                        'message' => 'User successfully registered',
                        'token' => $token,
                    );
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'isaacgallardo@iesflorenciopintado.es';
                        $mail->Password   = 'qfkg vkke bssa jxym ';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        $mail->setFrom('isaacgallardo@iesflorenciopintado.es', 'Welcome KineStream');
                        $mail->addAddress($username);

                        $mail->isHTML(true);
                        $mail->Subject = 'New account KineStream';
                        $mail->Body    = "
                            <h3>Bienvenido a KineStream</h3>
                            <p>A partir de ahora podrás guardar en tu usuario películas que quieras tener como <strong>favoritos</strong> o películas que quieras <strong>ver más tarde</strong></p>
                            <p>Gracias por crear una cuenta con nosotros</p>
                        ";

                        $mail->send();
                    } catch (Exception $e) {
                        $response['mailStatus'] = 'error';
                        $response['mailError'] = 'Dont sent mail';
                    }
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
                http_response_code(409);
                    $response = array(
                        'code' => 409,
                        'status' => 'success',
                        'message' => 'The mail is already registered',
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
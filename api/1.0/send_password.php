<?php
    require_once __DIR__ . '/libs/src/PHPMailer.php';
    require_once __DIR__ . '/libs/src/SMTP.php';
    require_once __DIR__ . '/libs/src/Exception.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

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

    function generePassword($long = 10) {
        $char = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        return substr(str_shuffle(str_repeat($char, ceil($long / strlen($char)))), 0, $long);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $response = [];

        $inputJSON = file_get_contents("php://input");
        $input = json_decode($inputJSON, true);
        $username = $input["username"] ?? '';

        if (!empty($username)) {
            try {
                $password = generePassword();
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $connection->prepare("UPDATE user SET password = :password WHERE mail = :mail");
                $stmt->bindParam(':password', $passwordHash);
                $stmt->bindParam(':mail', $username);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    http_response_code(202);
                    $response = [
                        'code' => 202,
                        'message' => 'Correct password change'
                    ];
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'isaacgallardo@iesflorenciopintado.es';
                        $mail->Password   = 'qfkg vkke bssa jxym ';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        $mail->setFrom('isaacgallardo@iesflorenciopintado.es', 'Password KineStream');
                        $mail->addAddress($username);

                        $mail->isHTML(true);
                        $mail->Subject = 'New password KineStream';
                        $mail->Body    = "
                            <h3>Cambio de contraseña en KineStream</h3>
                            <p>Tu nueva contraseña temporal es: <strong>$password</strong></p>
                            <p>Por favor, cámbiala después de iniciar sesión.</p>
                        ";

                        $mail->send();
                    } catch (Exception $e) {
                        $response['mailStatus'] = 'error';
                        $response['mailError'] = 'Dont sent mail';
                    }
                } else {
                    http_response_code(401);
                    $response = [
                        'code' => 401,
                        'message' => 'User not found'
                    ];
                }
            } catch (Exception $e) {
                http_response_code(500);
                $response = [
                    'code' => 500,
                    'message' => 'Internal Server Error'
                ];
            }
        } else {
            http_response_code(400);
            $response = [
                'code' => 400,
                'message' => 'Post mail'
            ];
        }

        echo json_encode($response);
    } else {
        http_response_code(405);
        echo json_encode([
            'code' => 405,
            'message' => 'Method not allowed'
        ]);
    }

?>

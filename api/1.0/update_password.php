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

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $response = [];
        $inputJSON = file_get_contents("php://input");
        $input = json_decode($inputJSON, true);
        $token = $input["token"];
        $password = (string) $input["password"];
        if (!empty($token) && !empty($password)) {
            try {
                $stmt = $connection->prepare("SELECT name, mail FROM user WHERE token = :token");
                $stmt->bindParam(':token', $token);
                $stmt->execute();
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!empty($data)) {
                    $mailUser = $data["mail"];
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $connection->prepare("UPDATE user SET password = :password WHERE token = :token");
                    $stmt->bindParam(':password', $passwordHash);
                    $stmt->bindParam(':token', $token);
                    $stmt->execute();

                    http_response_code(202);
                    $response = array(
                        'code' => 202,
                        'message' => 'Password updated successfully',
                    );

                    if (!empty($mailUser) && filter_var($mailUser, FILTER_VALIDATE_EMAIL)) {
                        $mail = new PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'isaacgallardo@iesflorenciopintado.es';
                            $mail->Password   = 'qfkg vkke bssa jxym ';
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = 587;
                            $mail->addAddress($mailUser);
                            $mail->setFrom('isaacgallardo@iesflorenciopintado.es', 'Security Alert KineStream');
                            $mail->isHTML(true);
                            $mail->Subject = 'Security Alert: Unusual Activity Detected on Your Account';
                            $mail->Body    = "
                                <h3>Se ha cambiado su contraseña en KineStream</h3>
                                <p>Su contraseña ha sido modificada en KineStream.</p>
                                <p>Si no ha sido usted, le recomendamos cambiarla de inmediato.</p>
                            ";

                            $mail->send();
                        } catch (Exception $e) {
                            error_log("Error al enviar correo: " . $e->getMessage());
                            $response['mailStatus'] = 'error';
                            $response['mailError'] = 'No se pudo enviar el correo.';
                        }
                    }
                } else {
                    http_response_code(401);
                    $response = array(
                        'code' => 401,
                        'status' => 'Error',
                        'message' => 'Invalid credentials',
                    );
                }
            } catch (Exception $e) {
                error_log("Error en el servidor: " . $e->getMessage());
                http_response_code(500);
                $response = array(
                    'code' => 500,
                    'status' => 'Server Error',
                    'message' => 'The server encountered an unexpected error',
                    'error' => $e->getMessage(),
                );
            }
        } else {
            http_response_code(400);
            $response = array(
                'code' => 400,
                'status' => 'Bad Request',
                'message' => 'The server is waiting for a valid token and password',
            );
        }

        echo json_encode($response);
    } else {
        http_response_code(405);
        $response = array(
            'code' => 405,
            'status' => 'Error',
            'message' => 'Method not allowed',
        );
        echo json_encode($response);
    }
?>

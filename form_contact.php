<?php

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$mail = new PHPMailer();
$mail->SMTPDebug = SMTP::DEBUG_SERVER;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["lastname"], $_POST["firstname"], $_POST["mail"], $_POST["message"])) {
    $lname = transform($_POST["lastname"]);
    $fname = transform($_POST["firstname"]);
    $inmail = transform($_POST["mail"]);
    $msg = transform($_POST["message"]);

    try {
        // Configuration SMTP via les variables d'environnement
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = getenv('SMTP_HOST');
        $mail->Username = getenv('SMTP_USERNAME');
        $mail->Password = getenv('SMTP_PASSWORD');
        $mail->SMTPSecure = getenv('SMTP_SECURE') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = getenv('SMTP_PORT');

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Expéditeur et Destinataire
        $mail->setFrom(getenv('SMTP_FROM_ADDRESS'), getenv('SMTP_FROM_NAME'));
        $mail->addAddress(getenv('SMTP_TO_ADDRESS'));

        // Contenu du mail
        $mail->isHTML(true);
        $mail->Subject = 'Demande de contact de ' . $fname . ' ' . $lname;
        $mail->Body = '<b>' . $fname . ' ' . $lname . '</b> a tenté de vous joindre, en laissant le message suivant :<br/><br/>' .
            $msg . '<br/><br/>Voici son mail pour pouvoir le joindre : <b>' . $inmail . '</b>.';
        $mail->AltBody = $fname . ' ' . $lname . ' a tenté de vous joindre, en laissant le message suivant :' .
            $msg . 'Voici son mail pour pouvoir le joindre : ' . $inmail . '.';

        // Envoi du mail
        if ($mail->send()) {
            echo 1;
        } else {
            echo 0;
        }
    } catch (Exception $e) {
        // En production, on logge l'erreur sans exposer de détails sensibles à l'utilisateur
        error_log('Mailer Error: ' . $e->getMessage());
        echo "Erreur lors de l'envoi de l'email.";
    }
} else {
    echo 0;
}

function transform($string)
{
    return htmlspecialchars(stripslashes(trim($string)), ENT_QUOTES, 'UTF-8');
}
?>
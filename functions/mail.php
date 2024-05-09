<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

class SendMail{
    public function sendValidationMail($data, $token){
        $name = $data['name'] . ' '. $data['lastname'];
        $email = $data["datauser"];
        $url = 'http://localhost:4200/login/mail-confirm';

            // SMTP config
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host= 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username   = 'tuintichat@gmail.com';                     //SMTP username
            $mail->Password   = 'r l f g j b h p s t a m m k h h';
            $mail->SMTPSecure= PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $emailEncoded = $email;
            $tokenEncoded = $token;
            $link = "{$url}?email={$emailEncoded}&token={$tokenEncoded}";
            //Message
            $message = '<!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-1">
                <title>Activación de Cuenta</title>
                <style>
                    /* Estilos personalizados */
                    body {
                        font-family: Arial, sans-serif;
                        background-color: #f5f5f5;
                    }
            
                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                        background-color: #fff;
                        border-radius: 4px;
                        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                    }
            
                    h1 {
                        text-align: center;
                        color: #333;
                    }
            
                    p {
                        margin-bottom: 20px;
                        line-height: 1.5;
                        color: #555;
                    }
            
                    .message {
                        background-color: #f9f9f9;
                        padding: 10px;
                        border-radius: 4px;
                    }
            
                    .bold {
                        font-weight: bold;
                    }
            
                    .center {
                        text-align: center;
                    }
            
                    .button {
                        display: inline-block;
                        padding: 10px 20px;
                        background-color: #428bca;
                        color: #fff;
                        text-decoration: none;
                        border-radius: 27px;
                    }
            
                    .button:hover {
                        background-color: #3071a9;
                    }
                </style>
            </head>
            <body>
            <div class="container">
                <h1>Activaci&oacute;n de Cuenta</h1>
                <p class="center">&iexcl;Hola!</p>
                <p>Tu cuenta ha sido creada con &eacute;xito en Tuintichat. Para activar tu cuenta, por favor haz clic en el siguiente enlace:</p>
                <p class="center">
                <a class="button" target="_blank" href="'.$link.'">Activar Cuenta</a>   
                </p>
                <p class="message">Por razones de seguridad, te recomendamos no compartir tu informaci&oacute;n de cuenta con nadie.</p>
                <p>Si tienes alguna pregunta o necesitas asistencia, no dudes en ponerte en contacto con nuestro equipo de soporte.</p>
                
                <p class="center">Atentamente,</p>
                <p class="center">El equipo de Tuintichat</p>
            </div>
            </body>
            </html>
                         
            ';
            // Mail config
            $mail->setFrom('alexanderpulido01@gmail.com', 'TUINTICHAT');
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject =  "Activacion Cuenta";
            $mail->Body = $message;
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
            //send mail

            $mail->send();
            

    }

    public function sendOTPValidationMail($email, $otp){
        //$name = $data['name'] . ' '. $data['lastname'];
        //$url = 'http://localhost:4200/login/mail-confirm';

            // SMTP config
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host= 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username   = 'tuintichat@gmail.com';
            $mail->Password   = 'r l f g j b h p s t a m m k h h';
            $mail->SMTPSecure= PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $emailEncoded = $email;
            $otpEncoded = $otp;
            //Message
            $message = '<!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-1">
                <title>Activación de Cuenta</title>
                <style>
                    /* Estilos personalizados */
                    body {
                        font-family: Arial, sans-serif;
                        background-color: #f5f5f5;
                    }
            
                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                        background-color: #fff;
                        border-radius: 4px;
                        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                    }
            
                    h1 {
                        text-align: center;
                        color: #333;
                    }
            
                    p {
                        margin-bottom: 20px;
                        line-height: 1.5;
                        color: #555;
                    }
            
                    .message {
                        background-color: #f9f9f9;
                        padding: 10px;
                        border-radius: 4px;
                    }
            
                    .bold {
                        font-weight: bold;
                    }
            
                    .center {
                        text-align: center;
                    }
            
                    .button {
                        display: inline-block;
                        padding: 10px 20px;
                        background-color: #428bca;
                        color: #fff;
                        text-decoration: none;
                        border-radius: 27px;
                    }
            
                    .button:hover {
                        background-color: #3071a9;
                    }
                    .otp{
                        background-color: #f9f9f9;
                        padding: 10px;
                        border-radius: 4px;
                        text-align: center;
                    }
                </style>
            </head>
            <body>
            <div class="container">
                <h1>Activaci&oacute;n de Cuenta</h1>
                <p class="center">&iexcl;Hola!</p>
                <p>Tu c&oacute;digo de verificaci&oacute;n es:</p>
                <p class="otp">'.$otp.'</p>
                <p class="message">Por razones de seguridad, te recomendamos no compartir tu informaci&oacute;n de cuenta con nadie.</p>
                <p>Si tienes alguna pregunta o necesitas asistencia, no dudes en ponerte en contacto con nuestro equipo de soporte.</p>
                
                <p class="center">Atentamente,</p>
                <p class="center">El equipo de Tuintichat</p>
            </div>
            </body>
            </html> ';
            // Mail config
            $mail->setFrom('tuintichat@gmail.com', 'TUINTICHAT');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject =  "Verificacion OTP";
            $mail->Body = $message;
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            //send mail
            $mail->send();
    }
}
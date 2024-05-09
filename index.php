<?php
header("Access-Control-Allow-Origin:*");
header("Content-type: aplication/json; charset=UTF-8");
header("Access-Control-Max-Ager:3600");
// Permitir solicitudes con los métodos GET, POST, OPTIONS
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");

// Permitir los encabezados especificados
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, origin");

// Terminar la ejecución del script si es una solicitud OPTIONS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit();
}

// require 'flight/Flight.php';
require 'flight/autoload.php';
require 'functions/mail.php';

define("OPT_VALID", "15");


//db config
Flight::register('db', 'PDO', array(
    'mysql:host=localhost;dbname=tuintichatxtreme',
    'root',
    '',
    [
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8mb4\'',
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
)
);

Flight::route('/', function () {
    echo 'che';
});

Flight::group('/api', function () {
    Flight::route('GET /', function () {
        echo 'che';
    });
    Flight::group('/users', function () {
        Flight::route('GET /', 'getUsers');
        Flight::route('POST /', 'saveUser');
        Flight::route('POST /login', 'login');
    });
    Flight::group('/create', function () {
        Flight::route('GET /', function () {
            echo 'drivers';
        });
        Flight::post('/sendotp/', 'generate');
        Flight::post('/challenge/', 'chanllege');
        Flight::post('/driver/', 'createdirver');
    });
});
// user functions
function getUsers(){
    $users = Flight::db()->query('SELECT * FROM users');
    $users->execute();
    $userdata = $users->fetchAll();
    return Flight::json($userdata);
}
function saveUser(){
   
}

function otp(){
    $otp = rand(100000,999999);
    return $otp;
}
/**
 * Funcion para generar un OTP y enviarlo por correo
 * 
 */
function generate(){
    $email = Flight::request()->data->email;
    //response
    $response = array();
    // check existing otp request
    $otp = Flight::db()->prepare('SELECT * FROM otp WHERE email = ?');
    $otp->execute([$email]);
    $otpdata = $otp->fetch();
    if(is_array($otpdata) && (strtotime("now")<strtotime($otpdata['timestamp'])+(OPT_VALID*60))){
        // send message that already exists a pending OTP
        $response['status'] = 'error';
        $response['message'] = 'Ya existe una solicitud de OTP pendiente';
        Flight::halt(400, json_encode($response));
    }
    // generate otp
    $otpCode = otp();
    // save otp in db
    $saveOtp = Flight::db()->prepare('INSERT INTO otp (email, pass) VALUES (?, ?)');
    $success =$saveOtp->execute([$email, $otpCode]);
    // send mail
    if($success){
        $mailclass = new SendMail();
        $mailclass->sendOTPValidationMail($email, $otpCode);
        $response['status'] = 'success';
        $response['message'] = 'OTP generado';
        return Flight::json($response);
    }else{
        $response['status'] = 'error';
        $response['message'] = 'Error al generar OTP';
        Flight::halt(400, json_encode($response));
    }
    


        
}
/**
 * Funcion para validar el OTP
 * 
 */
function chanllege(){
    $email = Flight::request()->data->email;
    $otp = Flight::request()->data->otp;
    $sentence = Flight::db()->prepare('SELECT * FROM otp WHERE email = ? AND pass = ?');
    $sentence->execute([$email, $otp]);
    $otpdata = $sentence->fetch();
    // check not found
    if(!is_array($otpdata)){
        $response['status'] = 'error';
        $response['message'] = 'OTP no encontrado';
        Flight::halt(400, json_encode($response));
    }
    // check expired
    if(strtotime("now")>strtotime($otpdata['timestamp'])+60000){
        $response['status'] = 'error';
        $response['message'] = 'OTP expirado';
        Flight::halt(400, json_encode($response));
    }
    // check incorrect
    if($otpdata['pass']!=$otp){
        $response['status'] = 'error';
        $response['message'] = 'OTP incorrecto';
        Flight::halt(400, json_encode($response));
    }
    // OK - delete otp
    $deleteOtp = Flight::db()->prepare('DELETE FROM otp WHERE email = ?');
    $deleteOtp->execute([$email]);
    // response ok
    $response['status'] = 'success';
    $response['message'] = 'OTP validado';
    return Flight::json($response);
}
/** 
 * function para registrar usuario con sus datos adicionales
 * las t ablas para la info adicional son: user_additional_info
 * y vechicle_additional_info
 * recibe un json con los datos del usuario y los datos adicionales
 * la tabla users se debe enviar: email, password
 * la tabla user_additional_info se debe enviar: first_name, last_name, id_type, social_security_number
 * la tabla user_vehicle_info se debe enviar: user_id, vehicleBrand, vehicleColor, vehicleIdentification, vehiclePlates, vehicleInsurance
 * la estructura del json es:
 * {
 *     "email": "correo@correo",
 *     "password": "password",
 *     "user_additional_info": {
 *         "name": "nombre",
 *         "lastName": "apellido",
 *         "selectedValue": "tipo de identificacion",
 *         "identification": "numero de identificacion",
 *         "socialSecurity": "numero de seguro social"
 *     },
 *     "user_vechicle_info": {
 *         "vehicleBrand": "marca del vehiculo",
 *         "vehicleColor": "color del vehiculo",
 *         "vehicleIdentification": "numero de identificacion del vehiculo",
 *         "vehiclePlates": "placas del vehiculo",
 *         "vehicleInsurance": "seguro del vehiculo"
 *     }
 *}
*/
function createdirver(){
    $data = Flight::request()->data;
    $user = $data;
    $user_additional_info = $data->user_additional_info;
    $vehicle_additional_info = $data->user_vehicle_info;
    // se genera un token para que la app guarde el token despues 
    $token = md5(uniqid(rand(), true));
    // save user y se genera un token para que la app guarde el token despues
    // de su primer login y se pueda usar para autenticar 
    // se encripta la contraseña con md5
    $user->password = md5($user->password);
    $saveUser = Flight::db()->prepare('INSERT INTO users (email, password, status, token) VALUES (?, ?, ?, ?)');
    $saveUser->execute([$user->email, $user->password, true, $token]);
    $user_id = Flight::db()->lastInsertId();
    // save user additional info
    $saveUserAdditionalInfo = Flight::db()->prepare('INSERT INTO user_additional_info (user_id, first_name, last_name, id_type, id_number, social_security_number) VALUES (?, ?, ?, ?, ?, ?)');
    $saveUserAdditionalInfo->execute([
        $user_id,
        $user_additional_info['name'],
        $user_additional_info['lastName'],
        $user_additional_info['selectedValue'],
        $user_additional_info['identification'],
        $user_additional_info['socialSecurity']
    ]);
    // get user_additional_info id
    $user_additional_info_id = Flight::db()->lastInsertId();
    // save vechicle additional info
    $saveVechicleAdditionalInfo = Flight::db()->prepare('INSERT INTO vehicle_additional_info(user_id, vehicle_make, vehicle_color, vehicle_id_number, vehicle_plate, vehicle_insurance) VALUES (?, ?, ?, ?, ?, ?)');
    $saveVechicleAdditionalInfo->execute([
        $user_additional_info_id,
        $vehicle_additional_info['vehicleBrand'],
        $vehicle_additional_info['vehicleColor'],
        $vehicle_additional_info['vehicleIdentification'],
        $vehicle_additional_info['vehiclePlates'],
        $vehicle_additional_info['vehicleInsurance']
    ]);
    // response
    $response['status'] = 'success';
    $response['message'] = 'Usuario registrado';

    return Flight::json($response);
    
    
}

/**
 * function para loguear usuario
 * recibe un json con los datos del usuario
 * la estructura del json es:
 * {
 *    "email": "correo@correo",
 *   "password": "password"
 * }
 * la contraseña en la base de datos esta encriptada con md5
 */
function login(){
    $data = Flight::request()->data;
    $email = $data->email;
    // valida la contraseña de la base de datos 
    // trae el usuario con el email
    $sentence = Flight::db()->prepare('SELECT * FROM users WHERE email = ?');
    $sentence->execute([$email]);
    $userdata = $sentence->fetch();
    // valida la contraseña
    if(is_array($userdata) && md5($data->password)==$userdata['password']){
        $response['status'] = 'success';
        $response['message'] = 'Usuario encontrado';
        $response['data'] = $userdata;
        return Flight::json($response);
    }else{
        $response['status'] = 'error';
        $response['message'] = 'Usuario no encontrado';
        Flight::halt(400, json_encode($response));
    }
}
Flight::start();

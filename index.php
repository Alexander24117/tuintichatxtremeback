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
Flight::register(
    'db',
    'PDO',
    array(
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
        Flight::route('POST /databank', 'getBankData');
        Flight::route('POST /order', 'getOrders');
        Flight::route('POST /earnings', 'getEarnings');
        Flight::route('POST /checkorder', 'checkOrderStatus');
    });
    Flight::group('/create', function () {
        Flight::route('GET /', function () {
            echo 'drivers';
        });
        Flight::post('/sendotp/', 'generate');
        Flight::post('/challenge/', 'chanllege');
        Flight::post('/driver/', 'createdirver');
        Flight::post('/bankdata/', 'saveBankData');
        Flight::post('/order/', 'saveOrder');
        Flight::post('/message/', 'saveMessage');
        Flight::post('/orderstatus/', 'setOrderStatus');
    });
});
// user functions
function getUsers()
{
    $users = Flight::db()->query('SELECT * FROM users');
    $users->execute();
    $userdata = $users->fetchAll();
    return Flight::json($userdata);
}
function saveUser()
{
}

function otp()
{
    $otp = rand(100000, 999999);
    return $otp;
}
/**
 * Funcion para generar un OTP y enviarlo por correo
 * 
 */
function generate()
{
    $email = Flight::request()->data->email;
    //response
    $response = array();
    // check existing otp request
    $otp = Flight::db()->prepare('SELECT * FROM otp WHERE email = ?');
    $otp->execute([$email]);
    $otpdata = $otp->fetch();
    if (is_array($otpdata) && (strtotime("now") < strtotime($otpdata['timestamp']) + (OPT_VALID * 60))) {
        // send message that already exists a pending OTP
        $response['status'] = 'error';
        $response['message'] = 'Ya existe una solicitud de OTP pendiente';
        Flight::halt(400, json_encode($response));
    }
    // generate otp
    $otpCode = otp();
    // save otp in db
    $saveOtp = Flight::db()->prepare('INSERT INTO otp (email, pass) VALUES (?, ?)');
    $success = $saveOtp->execute([$email, $otpCode]);
    // send mail
    if ($success) {
        $mailclass = new SendMail();
        $mailclass->sendOTPValidationMail($email, $otpCode);
        $response['status'] = 'success';
        $response['message'] = 'OTP generado';
        return Flight::json($response);
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Error al generar OTP';
        Flight::halt(400, json_encode($response));
    }
}
/**
 * Funcion para validar el OTP
 * 
 */
function chanllege()
{
    $email = Flight::request()->data->email;
    $otp = Flight::request()->data->otp;
    $sentence = Flight::db()->prepare('SELECT * FROM otp WHERE email = ? AND pass = ?');
    $sentence->execute([$email, $otp]);
    $otpdata = $sentence->fetch();
    // check not found
    if (!is_array($otpdata)) {
        $response['status'] = 'error';
        $response['message'] = 'OTP no encontrado';
        Flight::halt(400, json_encode($response));
    }
    // check expired
    if (strtotime("now") > strtotime($otpdata['timestamp']) + 60000) {
        $response['status'] = 'error';
        $response['message'] = 'OTP expirado';
        Flight::halt(400, json_encode($response));
    }
    // check incorrect
    if ($otpdata['pass'] != $otp) {
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
function createdirver()
{
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
function login()
{
    $data = Flight::request()->data;
    $email = $data->email;
    // valida la contraseña de la base de datos 
    // trae el usuario con el email
    $sentence = Flight::db()->prepare('SELECT * FROM users WHERE email = ?');
    $sentence->execute([$email]);
    $userdata = $sentence->fetch();
    // valida la contraseña
    // si la contraseña es correcta retorna el usuario
    if (is_array($userdata) && md5($data->password) == $userdata['password']) {
        // se envia la informacion adicional del usuario nombres y apellidos
        $sentence = Flight::db()->prepare('SELECT * FROM user_additional_info WHERE user_id = ?');
        $sentence->execute([$userdata['user_id']]);
        $user_additional_info = $sentence->fetch();
        // validar si el usuario tiene informacion adicional
        if (is_array($user_additional_info)) {
            $response['status'] = 'success';
            $response['message'] = 'Usuario logueado';
            $response['data'] = [
                'user_id' => $userdata['user_id'],
                'email' => $userdata['email'],
                'first_name' => $user_additional_info['first_name'],
                'last_name' => $user_additional_info['last_name']
            ];
            return Flight::json($response);
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Usuario no tiene informacion adicional';
            Flight::halt(400, json_encode($response));
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Usuario no encontrado';
        Flight::halt(400, json_encode($response));
    }
}
/**
 * function para obtener la informacion adicional de un usuario por medio del correo
 * recibe un json con el correo del usuario
 */
function getUserAdditionalInfo()
{
    $data = Flight::request()->data;
    $email = $data->email;
    $sentence = Flight::db()->prepare('SELECT * FROM users WHERE email = ?');
    $sentence->execute([$email]);
    $userdata = $sentence->fetch();
    if (is_array($userdata)) {
        $sentence = Flight::db()->prepare('SELECT * FROM user_additional_info WHERE user_id = ?');
        $sentence->execute([$userdata['id']]);
        $user_additional_info = $sentence->fetch();
        $sentence = Flight::db()->prepare('SELECT * FROM vehicle_additional_info WHERE user_id = ?');
        $sentence->execute([$user_additional_info['id']]);
        $vehicle_additional_info = $sentence->fetch();
        $response['status'] = 'success';
        $response['message'] = 'Informacion adicional encontrada';
        $response['data'] = [
            'user_additional_info' => $user_additional_info,
            'vehicle_additional_info' => $vehicle_additional_info
        ];
        return Flight::json($response);
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Usuario no encontrado';
        Flight::halt(400, json_encode($response));
    }
}

/**
 * function para almacenar los datos bancarios de un usuario
 * recibe un json con los datos del usuario
 * la estructura del json es:
 * {
 *  user_id,
 *  id_type,
 *  document_number,
 *  bank_type,
 *  account_type,
 *  account_number,
 * }
 */
function saveBankData()
{
    $data = Flight::request()->data;
    $user_id = $data->user_id;
    $names = $data->names;
    $id_type = $data->id_type;
    $document_number = $data->document_number;
    $bank_type = $data->bank_type;
    $account_type = $data->account_type;
    $account_number = $data->account_number;
    $saveBankData = Flight::db()->prepare('INSERT INTO bank_details (user_id, names, id_type, document_number, bank_type, account_type, account_number) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $saveBankData->execute([$user_id, $names, $id_type, $document_number, $bank_type, $account_type, $account_number]);
    if (!$saveBankData) {
        $response['status'] = 'error';
        $response['message'] = 'Error al guardar datos bancarios';
        Flight::halt(400, json_encode($response));
    }
    $response['status'] = 'success';
    $response['message'] = 'Datos bancarios guardados';
    return Flight::json($response);
}

/**
 * function para obtener los datos bancarios de un usuario por medio del correo
 * retorna un json con los datos bancarios del usuario
 */
function getBankData()
{
    $data = Flight::request()->data;
    $email = $data->email;
    $sentence = Flight::db()->prepare('SELECT * FROM users WHERE email = ?');
    $sentence->execute([$email]);
    $userdata = $sentence->fetch();
    if (is_array($userdata)) {
        $sentence = Flight::db()->prepare('SELECT * FROM bank_details WHERE user_id = ?');
        $sentence->execute([$userdata['user_id']]);
        $bank_data = $sentence->fetch();
        $response['status'] = 'success';
        $response['message'] = 'Datos bancarios encontrados';
        // el data es un array ya que puede tener mas de una cuenta bancaria
        // y este toca agregar el nombre y apellido del usuario
        // este debe recorrerse para obtener los datos de cada cuenta bancaria\
        // mediante un foreach
        $response['data'] =
            [$bank_data];
        return Flight::json($response);
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Usuario: ' . $email . ' no encontrado';
        Flight::halt(400, json_encode(Flight::request()));
    }
}

/**
 * function para alamacenar las ordenes aceptadas por un usuario
 * recibe un json con los datos de la orden
 * la tabla es delivery_history
 * las columnas que se deben enviar son:
 * user_id, origin_address, origin_phone, origin_name, destination_address, destination_phone, destination_name, fee_amount
 */
function saveOrder()
{
    $data = Flight::request()->data;
    $user_id = $data->user_id;
    $origin_address = $data->origin_address;
    $origin_phone = $data->origin_phone;
    $origin_name = $data->origin_name;
    $destination_address = $data->destination_address;
    $destination_phone = $data->destination_phone;
    $destination_name = $data->destination_name;
    $fee_amount = $data->fee_amount;
    $saveOrder = Flight::db()->prepare('INSERT INTO delivery_history (user_id, origin_address, origin_phone, origin_name, destination_address, destination_phone, destination_name, fee_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $saveOrder->execute([$user_id, $origin_address, $origin_phone, $origin_name, $destination_address, $destination_phone, $destination_name, $fee_amount]);
    if (!$saveOrder) {
        $response['status'] = 'error';
        $response['message'] = 'Error al guardar la orden';
        Flight::halt(400, json_encode($response));
    }
    $response['status'] = 'success';
    $response['message'] = 'Orden guardada';
    return Flight::json($response);
}

/**
 * function para obtener las ordenes de un usuario por medio del id
 */
function getOrders()
{
    $data = Flight::request()->data;
    $user_id = $data->user_id;
    $sentence = Flight::db()->prepare('SELECT * FROM delivery_history WHERE user_id = ?');
    $sentence->execute([$user_id]);
    $orders = $sentence->fetchAll();
    // validar si el usuario tiene ordenes
    if (!is_array($orders)) {
        $response['status'] = 'error';
        $response['message'] = 'Usuario no tiene ordenes';
        Flight::halt(400, json_encode($response));
    }
    $response['status'] = 'success';
    $response['message'] = 'Ordenes encontradas';
    $response['data'] = $orders;
    return Flight::json($response);
}

/**
 * function para obtener las ganancias de un usuario por medio del id
 * las ganancias se sacan mediante el filtro de las ordenes que esten completadas
 */
function getEarnings()
{
    $data = Flight::request()->data;
    $user_id = $data->user_id;
    $sentence = Flight::db()->prepare('SELECT * FROM delivery_history WHERE user_id = ? AND status = ?');
    $sentence->execute([$user_id, 'completed']);
    $orders = $sentence->fetchAll();
    // validar si el usuario tiene ordenes
    if (!is_array($orders)) {
        $response['status'] = 'error';
        $response['message'] = 'Usuario no tiene ganancias';
        Flight::halt(400, json_encode($response));
    }
    $response['status'] = 'success';
    $response['message'] = 'Ganancias encontradas';
    $response['data'] = $orders;
    return Flight::json($response);
}

/**
 * function para cambiar el estado de la orden data un json con el id de la orden
 * y el estado al que se va a cambiar
 */
function setOrderStatus()
{
    $data = Flight::request()->data;
    $order_id = $data->order_id;
    $status = $data->status;
    $sentence = Flight::db()->prepare('UPDATE delivery_history SET status = ? WHERE id = ?');
    $sentence->execute([$status, $order_id]);
    if (!$sentence) {
        $response['status'] = 'error';
        $response['message'] = 'Error al cambiar el estado de la orden';
        Flight::halt(400, json_encode($response));
    }
    $response['status'] = 'success';
    $response['message'] = 'Estado de la orden cambiado';
    return Flight::json($response);
}
/**
 * function para validar si la orden ya fue aceptada por un usuario
 * recibe un json con el id de la orden
 * si la orden ya fue aceptada retorna un json con el estado de la orden
 * si la orden no ha sido aceptada retorna un json con el estado de la orden
 */
function checkOrderStatus()
{
    $data = Flight::request()->data;
    $order_id = $data->order_id;
    $sentence = Flight::db()->prepare('SELECT * FROM delivery_history WHERE id = ?');
    $sentence->execute([$order_id]);
    $order = $sentence->fetch();
    if (!is_array($order)) {
        $response['status'] = 'error';
        $response['message'] = 'Orden no encontrada';
        Flight::halt(400, json_encode($response));
    }
    $response['status'] = 'success';
    $response['message'] = 'Orden encontrada';
    $response['data'] = $order;
    return Flight::json($response);
}
/**
 * function para guardar un mensaje para se historial de chat
 * recibe un json con la siguiente estructura:
 *   message = {
        typemessage: 1,
        type: "driver" || "ecomerce,
        message: newMessages[0].text,
        names: `${userJson.first_name}${userJson.last_name}`,
        hour: new Date().toLocaleTimeString("en-US", {
          hour: "2-digit",
          minute: "2-digit",
        }),
        to: users[0].socket_id,
        from: socket.id,
        id_to: users[0].id,
        id_from: userJson.user_id,
      }
 */
function saveMessage()
{
    $data = Flight::request()->data;
    $typemessage = $data->typemessage;
    $type = $data->type;
    $message = $data->message;
    $names = $data->names;
    $hour = $data->hour;
    $to = $data->to;
    $from = $data->from;
    $id_to = $data->id_to;
    $id_from = $data->id_from;
    $saveMessage = Flight::db()->prepare('INSERT INTO message (typemessage, type, message, names, hour, to, from, id_to, id_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $saveMessage->execute([$typemessage, $type, $message, $names, $hour, $to, $from, $id_to, $id_from]);
    if (!$saveMessage) {
        $response['status'] = 'error';
        $response['message'] = 'Error al guardar el mensaje';
        Flight::halt(400, json_encode($response));
    }
    $response['status'] = 'success';
    $response['message'] = 'Mensaje guardado';
    return Flight::json($response);
}
Flight::start();

<?php
require_once(__DIR__ . '/config.php');
session_start();

$method = $_SERVER['REQUEST_METHOD'];

//если что-нить кроме POST - посылаем
if ($method !== "POST") {
    header("HTTP/1.0 404 Not Found");
    $message = ["status"=>"error", "message"=>"Not Found Method"];
    echo json_encode($message);
    exit;
}

//обрабатываем роутинг
switch (trim($_SERVER['REQUEST_URI'])) {
    case "/api/login":
        //Проверяем заолнены ли вообще данные
        if (!isset($_POST['login']) || !isset($_POST['password'])) {
            header('HTTP/1.0 401 Unauthorized');
            $message = ["status"=>"error", "message"=>"Unauthorized"];
            echo json_encode($message);
            exit;
        }
        //данные есть - пробуем логинится
        $ch = curl_init($configClickhouse["host"] . ":" . $configClickhouse["port"] . "/?query=SELECT%20'login%20success'");
        curl_setopt($ch, CURLOPT_USERPWD, $_POST['login'] . ":" . $_POST['password']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        //Ответ пришел - принимаем решение

        if ($info['http_code'] != 200) {
            header('HTTP/1.0 401 Unauthorized');
            $message = ["status"=>"error", "message"=>"Unauthorized"];
            echo json_encode($message);
            exit;
        }
        //Логиним пользователя
        $_SESSION['login_user'] = $_POST['login'];
        $message = ["status"=>"ok", "message"=>"authorized"];
        echo json_encode($message);
        break;
    case "/api/logout":
        unset($_SESSION['login_user']);
        session_destroy();
        $message = ["status"=>"ok", "message"=>"logout"];
        echo json_encode($message);
        break;
    case "/api/user":
        if (isAutorized()) {
            $message = ["status"=>"ok", "login"=>$_SESSION['login_user']];
            echo json_encode($message);
        }
        else{
            $message = ["status"=>"error", "login"=>"Unauthorized"];
            echo json_encode($message);
        }
        break;
    default:
        header("HTTP/1.0 404 Not Found");
        $message = ["status"=>"error", "message"=>"Not Found"];
        echo json_encode($message);
        exit;
}

function isAutorized()
{
    if (isset($_SESSION['login_user'])) {
        return true;
    } else {
        header('HTTP/1.0 401 Unauthorized');
        return false;
    }
}
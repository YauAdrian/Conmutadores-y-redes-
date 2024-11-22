<?php
session_start();


if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}


require_once '../lib/routeros_api.class.php';


$host = $_POST['host'] ?? null;
$username = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;

if ($host && $username && $password) {
    $api = new RouterosAPI();

   
    if ($api->connect($host, $username, $password)) {
        
        $_SESSION['host'] = $host; 

        
        header("Location: dashboard.php");
        exit();
    } else {
       
        echo "No se pudo conectar a la API de MikroTik. Verifica tus credenciales.";
    }

   
    $api->disconnect();
} else {
    echo "Faltan datos para conectarse.";
}

<?php
session_start(); 


if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $host = $_POST['host']; 
    $username = $_POST['username'];
    $password = $_POST['password'];


    require('../lib/routeros_api.class.php'); 

    $API = new RouterosAPI();

    
    if ($API->connect($host, $username, $password)) {
        $_SESSION['username'] = $username; 
        $_SESSION['host'] = $host; 
        $_SESSION['password'] = $password;
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos."; 
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MikroTik</title>
    <link rel="stylesheet" href="../assets/css/styles.css"> 
</head>
<body>

    <div class="mikrotik-background"></div> 
    <div class="login-container">
        <img src="../assets/img/mikro.png" alt="MikroTik Logo" class="mikrotik-image">
        <h1>Iniciar Sesión</h1>
        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
        <form method="POST" action="login.php">
            <div class="input-group">
                <label for="host">Dirección IP</label>
                <input type="text" id="host" name="host" required placeholder="Ej: 192.168.88.1">
            </div>
            <div class="input-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" required placeholder="Usuario">
            </div>
            <div class="input-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required placeholder="Contraseña">
            </div>
            <button type="submit">Iniciar Sesión</button>
        </form>
    </div>
</body>
</html>


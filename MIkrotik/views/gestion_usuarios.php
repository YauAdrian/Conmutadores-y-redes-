<?php
session_start();


require('../lib/routeros_api.class.php');


if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}


$username = $_SESSION['username'];


$error = "";


if (isset($_POST['action']) && $_POST['action'] === 'guardar') {
    $data = json_decode($_POST['data'], true);
    
   
    $jsonFile = 'mikrotik_data.json';
    $existingData = [];

    if (file_exists($jsonFile)) {
        $existingData = json_decode(file_get_contents($jsonFile), true);
        if (!is_array($existingData)) {
            $existingData = []; 
        }
    }

    
    $existingData[] = $data;

  
    file_put_contents($jsonFile, json_encode($existingData, JSON_PRETTY_PRINT));

 
    header("Location: gestion_usuarios.php");
    exit();
}


if (isset($_POST['action']) && $_POST['action'] === 'conectar') {
 
    $ip = $_POST['ip_mikrotik'];
    $usuario = $_POST['usuario_mikrotik'];
    $password = $_POST['password_mikrotik'];

    $api = new RouterosAPI();
    
    
    if ($api->connect($ip, $usuario, $password)) {
        
        $_SESSION['mikrotik_connected'] = true;
        header("Location: dashboard.php"); 
        exit();
    } else {
        
        $error = "Credenciales incorrectas o no se pudo conectar a MikroTik.";
    }
}


include('sidebar.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - MikroTik</title>
    <link rel="stylesheet" href="../assets/css/gestion_usuarios.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        function guardarDatos() {
    const sessionName = document.getElementById('nombre_sesion').value;
    const ip = document.getElementById('ip_mikrotik').value;
    const usuario = document.getElementById('usuario_mikrotik').value;
    const password = document.getElementById('password_mikrotik').value;
    const nombreHotspot = document.getElementById('nombre_hotspot').value;
    const dnsName = document.getElementById('dns_name').value;
    const autoLoad = document.getElementById('auto_load').value;
    const idleTimeout = document.getElementById('idle_timeout').value;
    const interfazGrafico = document.getElementById('interfaz_grafico').value;
    const reporteVivo = document.getElementById('reporte_en_vivo').value;

    const data = {
        session: {
            nombre: sessionName
        },
        mikrotik: {
            ip: ip,
            usuario: usuario,
            password: password
        },
        sistema: {
            nombre_hotspot: nombreHotspot,
            dns_name: dnsName,
            auto_load: autoLoad,
            idle_timeout: idleTimeout,
            interfaz_grafico: interfazGrafico,
            reporte_en_vivo: reporteVivo
        }
    };

    const formData = new FormData();
    formData.append('action', 'guardar');
    formData.append('data', JSON.stringify(data));

    fetch('gestion_usuarios.php', {
        method: 'POST',
        body: formData
    }).then(response => {
        if (response.ok) {
            alert('Datos guardados con éxito');
            location.reload(); 
        } else {
            alert('Hubo un problema al guardar los datos');
        }
    }).catch(error => {
        alert('Error de conexión: ' + error);
    });
    
}
    </script>
</head>
<body>

    <div class="main-content">
        <div class="gestion-container">
            <div class="izquierda">
                <div class="sesion-container">
                    <h2>Sesión</h2>
                    <div class="input-group">
                        <label for="nombre_sesion">Nombre de Sesión:</label>
                        <input type="text" id="nombre_sesion" name="nombre_sesion" required>
                    </div>
                </div>
                <div class="mikrotik-container">
                    <h2>MikroTik</h2>
                    <form id="conexionForm" method="post" action="gestion_usuarios.php">
                        <div class="input-group">
                            <label for="ip_mikrotik">IP o MAC MikroTik:</label>
                            <input type="text" id="ip_mikrotik" name="ip_mikrotik" required>
                        </div>
                        <div class="input-group">
                            <label for="usuario_mikrotik">Usuario:</label>
                            <input type="text" id="usuario_mikrotik" name="usuario_mikrotik" required>
                        </div>
                        <div class="input-group">
                            <label for="password_mikrotik">Contraseña:</label>
                            <input type="password" id="password_mikrotik" name="password_mikrotik" required>
                        </div>
                        <div class="acciones">
                            <button type="button" class="btn-guardar" onclick="guardarDatos()">Guardar</button>
                            <button type="submit" name="action" value="conectar" class="btn-conectar">Conectar</button>
                            <button type="button" class="btn-ping">Ping</button>
                        </div>
                    </form>
                    <?php if ($error): ?>
                        <div class="error-message" style="color: red;">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="derecha">
                <div class="sistema-container">
                    <h2>Datos del Sistema</h2>
                    <div class="input-group">
                        <label for="nombre_hotspot">Nombre Hotspot:</label>
                        <input type="text" id="nombre_hotspot" name="nombre_hotspot" required>
                    </div>
                    <div class="input-group">
                        <label for="dns_name">DNS Name:</label>
                        <input type="text" id="dns_name" name="dns_name" required>
                    </div>
                    <div class="input-group">
                        <label for="auto_load">Auto Load:</label>
                        <input type="text" id="auto_load" name="auto_load" required>
                    </div>
                    <div class="input-group">
                        <label for="idle_timeout">Idle Timeout:</label>
                        <input type="text" id="idle_timeout" name="idle_timeout" required>
                    </div>
                    <div class="input-group">
                        <label for="interfaz_grafico">Interfaz de Gráfico:</label>
                        <input type="text" id="interfaz_grafico" name="interfaz_grafico" required>
                    </div>
                    <div class="input-group">
                        <label for="reporte_en_vivo">Reporte en Vivo:</label>
                        <input type="text" id="reporte_en_vivo" name="reporte_en_vivo" required>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>











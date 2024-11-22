<?php
require_once '../lib/routeros_api.class.php';
session_start();


if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}


$json_file = '../views/mikrotik_data.json';


$routers = [];
if (file_exists($json_file)) {
    $routers = json_decode(file_get_contents($json_file), true);
}


$username = $_SESSION['username'];
$password = $_SESSION['password'];
$host = $_SESSION['host'];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $nuevo_username = $_POST['username'];
    $nuevo_password = $_POST['password'];

    
    $_SESSION['username'] = $nuevo_username;
    $_SESSION['password'] = $nuevo_password;

   
    header("Location: configuracion.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'abrir_sesion') {
        $ip = $_POST['ip'];

        

        echo 'Sesión abierta en ' . htmlspecialchars($ip);
        exit(); 
    }

    if ($action === 'editar_router') {
        $id = $_POST['id'];
        $nuevo_nombre = $_POST['nuevo_nombre'];
        $ip = $_POST['ip'];

        
        foreach ($routers as &$router) {
            if ($router['session']['nombre'] === $id) {
                $router['session']['nombre'] = $nuevo_nombre; 
                break;
            }
        }
        file_put_contents($json_file, json_encode($routers, JSON_PRETTY_PRINT));
        echo 'Router editado con éxito: ' . htmlspecialchars($nuevo_nombre);
        exit(); 
    }

    if ($action === 'eliminar_router') {
        $id = $_POST['id'];

   
        foreach ($routers as $key => $router) {
            if ($router['session']['nombre'] === $id) {
                unset($routers[$key]);
                break;
            }
        }
        file_put_contents($json_file, json_encode(array_values($routers), JSON_PRETTY_PRINT)); // Reindexar
        echo 'Router eliminado con éxito: ' . htmlspecialchars($id);
        exit(); 
    }
}

include('sidebar.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="../assets/css/configuracion.css">
</head>
<body>

   
    <div class="main-content">
        <h1>Bienvenido, <?php echo htmlspecialchars($username); ?>!</h1>
        <div class="configuracion-container">
            <div class="lista-router">
                <h2>Lista de Routers</h2>
                <div class="router-cards">
                    <?php if (!empty($routers)): ?>
                        <?php foreach ($routers as $router): ?>
                            <div class="router-item">
                                <p><strong>Nombre Hotspot:</strong> <?php echo htmlspecialchars($router['sistema']['nombre_hotspot']); ?></p>
                                <p><strong>Nombre de la Sesión:</strong> <?php echo htmlspecialchars($router['session']['nombre']); ?></p>
                                <div class="actions">
                                    <a href="#" class="btn-abrir" data-ip="<?php echo htmlspecialchars($router['mikrotik']['ip']); ?>">Abrir</a>
                                    <a href="#" class="btn-editar" data-id="<?php echo htmlspecialchars($router['session']['nombre']); ?>" data-ip="<?php echo htmlspecialchars($router['mikrotik']['ip']); ?>">Editar</a>
                                    <a href="#" class="btn-eliminar" data-id="<?php echo htmlspecialchars($router['session']['nombre']); ?>">Eliminar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No hay routers disponibles.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="inicio-sesion">
                <h2>Inicio de Sesión</h2>
                <form method="POST" action="configuracion.php">
                    <div class="input-group">
                        <label for="username">Usuario</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" value="<?php echo htmlspecialchars($password); ?>" required>
                    </div>
                    <button type="submit" class="btn-guardar">Guardar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        
        $('.btn-abrir').on('click', function(e) {
            e.preventDefault();
            const ip = $(this).data('ip');
            
            $.post('configuracion.php', { action: 'abrir_sesion', ip: ip }, function(response) {
                alert(response);
             
            }).fail(function(xhr) {
                alert('Error: ' + xhr.responseText);
            });
        });

      
        $('.btn-editar').on('click', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const ip = $(this).data('ip');
            
            const nuevoNombre = prompt('Edita el nombre de la sesión:', id);
            if (nuevoNombre) {
                $.post('configuracion.php', { action: 'editar_router', id: id, nuevo_nombre: nuevoNombre, ip: ip }, function(response) {
                    alert(response);
                    location.reload(); 
                }).fail(function(xhr) {
                    alert('Error: ' + xhr.responseText);
                });
            }
        });

      
        $('.btn-eliminar').on('click', function(e) {
            e.preventDefault();
            const id = $(this).data('id');

            if (confirm('¿Estás seguro de que deseas eliminar el router: ' + id + '?')) {
                $.post('configuracion.php', { action: 'eliminar_router', id: id }, function(response) {
                    alert(response);
                    location.reload(); 
                }).fail(function(xhr) {
                    alert('Error: ' + xhr.responseText);
                });
            }
        });
    });
    </script>
</body>
</html>








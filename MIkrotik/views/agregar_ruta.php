<?php
session_start();


if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$password = $_SESSION['password'];
$host = $_SESSION['host'];


require_once '../lib/routeros_api.class.php';
$router = new RouterosAPI();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $action_type = $_POST['action_type'];
    $gateway = $_POST['gateway'] ?? '';


    if ($action_type === 'add_route' && !filter_var($gateway, FILTER_VALIDATE_IP)) {
        $_SESSION['message'] = 'Gateway no válido.';
        header("Location: agregar_ruta.php");
        exit();
    }

    if ($router->connect($host, $username, $password)) {
        
        $router->comm('/ip/route/add', [
            'dst-address' => '0.0.0.0/0',
            'gateway' => $gateway,
            'distance' => 1, 
        ]);
        $_SESSION['message'] = 'Ruta por defecto agregada correctamente.';
        $router->disconnect();
    } else {
        $_SESSION['message'] = 'Error al conectar a MikroTik.';
    }
    header("Location: agregar_ruta.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_route'])) {
    $route_id = $_POST['route_id'] ?? '';

    if ($router->connect($host, $username, $password)) {
        
        $router->comm('/ip/route/remove', [
            '.id' => $route_id,
        ]);
        $_SESSION['message'] = 'Ruta eliminada correctamente.';
        $router->disconnect();
    } else {
        $_SESSION['message'] = 'Error al conectar a MikroTik.';
    }
    header("Location: agregar_ruta.php");
    exit();
}


$routes = [];
if ($router->connect($host, $username, $password)) {
    $routes = $router->comm('/ip/route/print');
    $router->disconnect();
} else {
    $_SESSION['message'] = 'Error al conectar a MikroTik para obtener rutas.';
    header("Location: agregar_ruta.php");
    exit();
}

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
include('sidebar.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Ruta</title>
    <link rel="stylesheet" href="../assets/css/agregar_ips.css"><!-- Enlace al CSS -->
</head>
<body>
    <div class="main-content">
        <div class="container">
            <h1>Agregar Ruta por Defecto</h1>
            <form method="POST" action="agregar_ruta.php">
                <input type="hidden" name="action_type" value="add_route">
                <div class="input-group">
                    <label for="gateway">Gateway</label>
                    <input type="text" id="gateway" name="gateway" placeholder="Ej: 192.168.88.1" required>
                </div>
                <button type="submit" class="btn-agregar">Agregar Ruta</button>
            </form>

            <h2>Rutas Existentes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Gateway</th>
                        <th>Clasificación</th>
                        <th>Distancia</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($routes)): ?>
                        <?php foreach ($routes as $route): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($route['gateway']); ?></td>
                                <td>
                                    <?php
                                   
                                    $distance = $route['distance'] ?? 0; 
                                    if ($distance == 0) {
                                        echo 'DAC'; 
                                    } elseif ($distance == 1) {
                                       
                                        if (isset($route['is_dynamic']) && $route['is_dynamic']) {
                                            echo 'DAd'; 
                                            echo 'USHI'; 
                                        }
                                    } else {
                                        echo 'USHI'; 
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($distance); ?></td>
                                <td>
                                    <form method="POST" action="agregar_ruta.php" style="display:inline;">
                                        <input type="hidden" name="delete_route" value="1">
                                        <input type="hidden" name="route_id" value="<?php echo htmlspecialchars($route['.id']); ?>">
                                        <button type="submit" class="btn-eliminar">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No hay rutas disponibles.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        
        <?php if ($message): ?>
            <div class="modal" id="myModal">
                <div class="modal-content">
                    <span class="close-button" onclick="closeModal()">&times;</span>
                    <p><?php echo $message; ?></p>
                </div>
            </div>
        <?php endif; ?>

        <script>
            function closeModal() {
                document.getElementById("myModal").style.display = "none";
            }
            window.onload = function() {
                <?php if ($message): ?>
                    document.getElementById("myModal").style.display = "block";
                <?php endif; ?>
            }
        </script>
    </div>
</body>
</html>



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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $action_type = $_POST['action_type'];
    $name = $_POST['name'] ?? '';
    $target = $_POST['target'] ?? ''; 
    $upload_limit = $_POST['upload_limit'] ?? ''; 
    $download_limit = $_POST['download_limit'] ?? ''; 

    $router = new RouterosAPI();

    if ($router->connect($host, $username, $password)) {
        if ($action_type === 'add_bandwidth') {
           
            $router->comm('/queue/simple/add', [
                'name' => $name,
                'target' => $target, 
                'max-limit' => $download_limit . '/' . $upload_limit, 
            ]);
            $_SESSION['message'] = 'Configuración de ancho de banda agregada correctamente.';
        } elseif ($action_type === 'delete_bandwidth' && isset($_POST['bandwidth_id'])) {
           
            $bandwidth_id = $_POST['bandwidth_id'];
            $router->comm('/queue/simple/remove', [
                '.id' => $bandwidth_id,
            ]);
            $_SESSION['message'] = 'Configuración de ancho de banda eliminada correctamente.';
        }
        $router->disconnect();
    } else {
        $_SESSION['message'] = 'Error al conectar a MikroTik.';
    }
    
    header("Location: agregar_ancho_banda.php");
    exit();
}


$bandwidth_list = [];
$router = new RouterosAPI();
if ($router->connect($host, $username, $password)) {
    $bandwidth_list = $router->comm('/queue/simple/print');
    $router->disconnect();
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
    <title>Agregar Ancho de Banda</title>
    <link rel="stylesheet" href="../assets/css/ancho_banda.css"><!-- Enlace al CSS -->
</head>
<body>
    <div class="main-content">
        <div class="container">
            <h1>Agregar Ancho de Banda</h1>
            <form method="POST" action="agregar_ancho_banda.php">
                <input type="hidden" name="action_type" value="add_bandwidth">
                
                <div class="input-group">
                    <label for="name">Nombre</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="input-group">
                    <label for="target">Red (Ej: 192.168.1.0/24 o 192.168.1.10)</label>
                    <input type="text" id="target" name="target" required>
                </div>
                
                <div class="input-group">
                    <label for="upload_limit">Target Upload (Ej: 5M)</label>
                    <input type="text" id="upload_limit" name="upload_limit" placeholder="Ej: 5M" required>
                </div>
                
                <div class="input-group">
                    <label for="download_limit">Target Download (Ej: 10M)</label>
                    <input type="text" id="download_limit" name="download_limit" placeholder="Ej: 10M" required>
                </div>

                <button type="submit" class="btn-agregar">Agregar Ancho de Banda</button>
            </form>
        </div>

     
        <div class="container">
            <h2>Configuraciones de Ancho de Banda</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Red</th>
                        <th>Límite (Bajada/Subida)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bandwidth_list as $bandwidth): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($bandwidth['name']); ?></td>
                            <td><?php echo htmlspecialchars($bandwidth['target']); ?></td>
                            <?php
                             
                                $limits = explode('/', $bandwidth['max-limit']);
                                $download_limit = number_format($limits[0] / 1000000, 1) . 'M'; 
                                $upload_limit = number_format($limits[1] / 1000000, 1) . 'M'; 
                            ?>
                            <td><?php echo "$download_limit / $upload_limit"; ?></td>
                            <td>
                                <form method="POST" action="agregar_ancho_banda.php" style="display:inline;">
                                    <input type="hidden" name="action_type" value="delete_bandwidth">
                                    <input type="hidden" name="bandwidth_id" value="<?php echo htmlspecialchars($bandwidth['.id']); ?>">
                                    <button type="submit" class="btn-eliminar">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
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






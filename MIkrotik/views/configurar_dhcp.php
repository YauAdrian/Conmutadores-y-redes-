<?php
require_once '../lib/routeros_api.class.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}


$username = $_SESSION['username'];
$password = $_SESSION['password'];
$host = $_SESSION['host'];
$dhcp_entries = [];
$interfaces = [];

require_once '../lib/routeros_api.class.php';
$router = new RouterosAPI();


if ($router->connect($host, $username, $password)) {

    $dhcp_entries = $router->comm('/ip/dhcp-server/print');
    $interfaces = $router->comm('/interface/print');
    $dhcp_networks = $router->comm('/ip/dhcp-server/network/print');
    $dhcp_pools = $router->comm('/ip/pool/print');

 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'delete_dhcp_network') {
    $network_id = $_POST['network_id'];

   
    if ($network_id) {
     
        $delete_network_result = $router->comm('/ip/dhcp-server/network/remove', [
            '.id' => $network_id,
        ]);

   
        if (isset($delete_network_result['!trap'])) {
            $_SESSION['message'] = 'Error al eliminar la red DHCP: ' . htmlspecialchars($network_id);
        } else {
            $_SESSION['message'] = 'Red DHCP eliminada correctamente.';
        }

       
        $dhcp_networks = $router->comm('/ip/dhcp-server/network/print');
    }
}


    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'delete_pool') {
        $pool_id = $_POST['pool_id'];

        if ($pool_id) {
           
            $delete_pool_result = $router->comm('/ip/pool/remove', [
                '.id' => $pool_id,
            ]);

          
            if (isset($delete_pool_result['!trap'])) {
                $_SESSION['message'] = 'Error al eliminar el pool de direcciones: ' . htmlspecialchars($pool_id);
            } else {
                $_SESSION['message'] = 'Pool de direcciones eliminado correctamente.';
            }

           
            $dhcp_pools = $router->comm('/ip/pool/print');
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'delete_dhcp') {
        $dhcp_name = $_POST['dhcp_name'];
        $network_address = $_POST['network_address'];

        
        if ($dhcp_name) {
     
            $delete_dhcp_result = $router->comm('/ip/dhcp-server/remove', [
                '.id' => $dhcp_name,
            ]);
         
            if (isset($delete_dhcp_result['!trap']) || isset($delete_network_result['!trap'])) {
                $_SESSION['message'] = 'Error al eliminar el servidor DHCP o la red asociada.';
            } else {
                $_SESSION['message'] = 'Servidor DHCP y red asociados eliminados correctamente.';
            }

            $dhcp_entries = $router->comm('/ip/dhcp-server/print');
        }
    }

   
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'setup_dhcp') {
        $interface = $_POST['interface'];
        $address_pool = $_POST['address_pool'];
        $dns_server = $_POST['dns_server'];
        $gateway = $_POST['gateway'];
        $network = $_POST['network'];
        $address_range = $_POST['address_range'];
        $lease_time = $_POST['lease_time'];

        
        $existingPools = $router->comm('/ip/pool/print');
        $poolExists = array_filter($existingPools, fn($pool) => $pool['name'] === $address_pool);
        
        if (!$poolExists) {
            $pool_result = $router->comm('/ip/pool/add', [
                'name' => $address_pool,
                'ranges' => $address_range,
            ]);
        }

        
        $existingNetworks = $router->comm('/ip/dhcp-server/network/print');
        $networkExists = array_filter($existingNetworks, fn($net) => $net['address'] === $network);

        if (!$networkExists) {
            $network_result = $router->comm('/ip/dhcp-server/network/add', [
                'address' => $network,
                'gateway' => $gateway,
                'dns-server' => $dns_server,
            ]);
        }


        $dhcpExists = array_filter($dhcp_entries, fn($dhcp) => $dhcp['name'] === 'DHCP_' . $address_pool);
        
        if (!$dhcpExists) {
            $dhcp_result = $router->comm('/ip/dhcp-server/add', [
                'name' => 'DHCP_' . $address_pool,
                'interface' => $interface,
                'address-pool' => $address_pool,
                'lease-time' => $lease_time,
            ]);
        }

        
        $errorMessages = [];

        if (isset($pool_result['!trap'])) {
            $errorMessages[] = 'Error al crear el pool de direcciones.';
        }
        if (isset($network_result['!trap'])) {
            $errorMessages[] = 'Error al configurar la red DHCP.';
        }
        if (isset($dhcp_result['!trap'])) {
            $errorMessages[] = 'Error al crear el servidor DHCP.';
        }

        if (empty($errorMessages)) {
            $_SESSION['message'] = 'Servidor DHCP configurado correctamente en la interfaz ' . htmlspecialchars($interface) . '.';
        } else {
            $_SESSION['message'] = implode(' ', $errorMessages);
        }

        

       
        $dhcp_entries = $router->comm('/ip/dhcp-server/print'); 
        $dhcp_pools = $router->comm('/ip/pool/print');
        $dhcp_networks = $router->comm('/ip/dhcp-server/network/print');
        
    }


    $router->disconnect();
} else {
    $_SESSION['message'] = 'No se pudo conectar a MikroTik. Verifica la dirección IP, el usuario y la contraseña.';
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
    <title>Configurar DHCP</title>
    <link rel="stylesheet" href="../assets/css/configurar_dhcp.css">
</head>
<body>
    <div class="main-content">
        <div class="container">
            <h1>Configurar Servidor DHCP</h1>
            <form id="dhcpForm" method="POST">
                <input type="hidden" name="action_type" value="setup_dhcp">
                <div class="input-group">
                    <label for="interface">Interfaz</label>
                    <select id="interface" name="interface" required>
                        <option value="">Seleccione una interfaz</option>
                        <?php foreach ($interfaces as $interface): ?>
                            <option value="<?php echo htmlspecialchars($interface['name']); ?>"><?php echo htmlspecialchars($interface['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="network">Red DHCP</label>
                    <input type="text" id="network" name="network" placeholder="Ej: 192.168.100.0/24" required>
                </div>
                <div class="input-group">
                    <label for="address_pool">Nombre del Pool de Direcciones</label>
                    <input type="text" id="address_pool" name="address_pool" placeholder="Ej: dhcp_pool" required>
                </div>
                <div class="input-group">
                    <label for="address_range">Rango de Direcciones</label>
                    <input type="text" id="address_range" name="address_range" placeholder="Ej: 192.168.100.10-192.168.100.100" required>
                </div>
                <div class="input-group">
                    <label for="dns_server">Servidor DNS</label>
                    <input type="text" id="dns_server" name="dns_server" placeholder="Ej: 8.8.8.8" required>
                </div>
                <div class="input-group">
                    <label for="gateway">Gateway</label>
                    <input type="text" id="gateway" name="gateway" placeholder="Ej: 192.168.100.1" required>
                </div>
                <div class="input-group">
                    <label for="lease_time">Tiempo de Conexión</label>
                    <input type="text" id="lease_time" name="lease_time" placeholder="Ej: 3d/00:30:00" required>
                </div>
                <button type="submit" class="btn-configurar">Configurar DHCP</button>
            </form>

           
            <?php if ($message): ?>
                <div class="modal" id="myModal">
                    <div class="modal-content">
                        <span class="close-button" onclick="closeModal()">&times;</span>
                        <p><?php echo htmlspecialchars($message); ?></p>
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

            
<h2>Configuraciones del Servidor DHCP</h2>
<div id="tablaDHCP">
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Interfaz</th>
                <th>Tiempo de Conexión</th>
                <th>Pool de Direcciones</th>
                <th>Acciones</th> 
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($dhcp_entries)): ?>
                <?php foreach ($dhcp_entries as $entry): ?>
    <tr>
        <td><?php echo htmlspecialchars($entry['name'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($entry['interface'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($entry['lease-time'] ?? ''); ?></td>
        <td><?php 
   
            $pool_info = array_filter($dhcp_pools, fn($pool) => $pool['name'] === $entry['address-pool']);
            $pool_info = reset($pool_info);
            echo htmlspecialchars($pool_info['ranges'] ?? ''); 
        ?></td>
        <td>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action_type" value="delete_dhcp">
                <input type="hidden" name="dhcp_name" value="<?php echo htmlspecialchars($entry['name'] ?? ''); ?>">
                <input type="hidden" name="network_address" value="<?php echo htmlspecialchars($entry['network-id'] ?? ''); ?>">
                <button type="submit" class="btn-eliminar" aria-label="Eliminar configuración DHCP">Eliminar</button>
            </form>
        </td>
    </tr>
<?php endforeach; ?>

            <?php else: ?>
                <tr>
                    <td colspan="5">No hay configuraciones de DHCP disponibles.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>



<h2>Pools de Direcciones DHCP</h2>
<div id="tablaPools">
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Rango</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($dhcp_pools)): ?>
                <?php foreach ($dhcp_pools as $pool): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pool['name']); ?></td>
                        <td><?php echo htmlspecialchars($pool['ranges']); ?></td>
                        <td>
                            <form method="POST" action=""> 
                                <input type="hidden" name="pool_id" value="<?php echo htmlspecialchars($pool['.id']); ?>">
                                <input type="hidden" name="action_type" value="delete_pool">
                                <button type="submit" class="btn-eliminar" onclick="return confirm('¿Está seguro de que desea eliminar este pool?');">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No hay pools de direcciones configurados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<h2>Información de Redes y Pools de Direcciones</h2>
<div id="tablaRedes">
    <table>
        <thead>
            <tr>
                <th>Red</th>
                <th>Gateway</th>
                <th>Servidor DNS</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($dhcp_networks)): ?>
                <?php foreach ($dhcp_networks as $network): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($network['address']); ?></td>
                        <td><?php echo htmlspecialchars($network['gateway']); ?></td>
                        <td><?php echo htmlspecialchars($network['dns-server']); ?></td>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="network_id" value="<?php echo htmlspecialchars($network['.id']); ?>">
                                <input type="hidden" name="action_type" value="delete_dhcp_network">
                                <button type="submit" class="btn-eliminar" onclick="return confirm('¿Está seguro de que desea eliminar esta red?');">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No hay redes DHCP disponibles.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>



















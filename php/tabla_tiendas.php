<?php
session_start();

$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "usuariostheyshop";

$mysqli = null;
$message = '';
$message_type = '';

try {
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, 3306);

    if ($mysqli->connect_error) {
        $message = "Error de Conexión a la Base de Datos: " . $mysqli->connect_error;
        $message_type = 'error';
    }

    if (isset($_GET['mensaje'])) {
        if ($_GET['mensaje'] === 'tienda_eliminada') {
            $message = "Tienda eliminada correctamente.";
            $message_type = 'success';
        } elseif ($_GET['mensaje'] === 'error_eliminar') {
            $message = "Error al eliminar la tienda: " . htmlspecialchars($_GET['error'] ?? 'Desconocido');
            $message_type = 'error';
        } elseif ($_GET['mensaje'] === 'tienda_actualizada') {
            $message = "Tienda actualizada correctamente.";
            $message_type = 'success';
        } elseif ($_GET['mensaje'] === 'error_actualizar') {
            $message = "Error al actualizar la tienda: " . htmlspecialchars($_GET['error'] ?? 'Desconocido');
            $message_type = 'error';
        } elseif ($_GET['mensaje'] === 'tienda_agregada') {
            $message = "Tienda agregada correctamente.";
            $message_type = 'success';
        } elseif ($_GET['mensaje'] === 'error_agregar') {
            $message = "Error al agregar la tienda: " . htmlspecialchars($_GET['error'] ?? 'Desconocido');
            $message_type = 'error';
        }
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_new_store"])) {
        $nombre = $_POST["nombre"] ?? '';
        $ubicacion = $_POST["ubicacion"] ?? '';
        $direccion = $_POST["direccion"] ?? '';
        $telefono = $_POST["telefono"] ?? '';
        $horario_apertura = $_POST["horario_apertura"] ?? '';
        $horario_cierre = $_POST["horario_cierre"] ?? '';

        if (empty($nombre) || empty($ubicacion) || empty($direccion) || empty($telefono) || empty($horario_apertura) || empty($horario_cierre)) {
            $message = "Todos los campos obligatorios para la nueva tienda deben ser válidos.";
            $message_type = 'error';
        } else {
            $stmt_insert = $mysqli->prepare("INSERT INTO tiendas (nombre, ubicacion, direccion, telefono, horario_apertura, horario_cierre) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($stmt_insert === false) {
                header("Location: /Proyecto/php/tabla_tiendas.php?mensaje=error_agregar&error=" . urlencode($mysqli->error));
                exit();
            } else {
                $stmt_insert->bind_param("ssssss",
                    $nombre,
                    $ubicacion,
                    $direccion,
                    $telefono,
                    $horario_apertura,
                    $horario_cierre
                );

                if ($stmt_insert->execute()) {
                    header("Location: /Proyecto/php/tabla_tiendas.php?mensaje=tienda_agregada");
                    exit();
                } else {
                    header("Location: /Proyecto/php/tabla_tiendas.php?mensaje=error_agregar&error=" . urlencode($stmt_insert->error));
                    exit();
                }
                $stmt_insert->close();
            }
        }
    }

    $search_id = '';
    $tiendas = [];

    if (isset($_GET['search_id']) && $_GET['search_id'] !== '') {
        $search_id = intval($_GET['search_id']);
        $query_sql = "SELECT id, nombre, ubicacion, direccion, telefono, horario_apertura, horario_cierre FROM tiendas WHERE id = ?";
        $stmt = $mysqli->prepare($query_sql);

        if ($stmt) {
            $stmt->bind_param("i", $search_id);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $message = "Error al preparar la consulta de búsqueda: " . $mysqli->error;
            $message_type = 'error';
            $result = false;
        }
    } else {
        $query_sql = "SELECT id, nombre, ubicacion, direccion, telefono, horario_apertura, horario_cierre FROM tiendas ORDER BY id DESC";
        $result = $mysqli->query($query_sql);
    }

    if (isset($result) && $result) {
        while ($row = $result->fetch_assoc()) {
            $tiendas[] = $row;
        }
        $result->free();
        if (isset($stmt)) {
            $stmt->close();
        }
    } else {
        if (empty($message)) {
            $message = "Error al obtener las tiendas: " . $mysqli->error;
            $message_type = 'error';
        }
    }

} catch (Exception $e) {
    $message = "Error en el procesamiento: " . $e->getMessage();
    $message_type = 'error';
} finally {
    if (isset($mysqli) && $mysqli->ping()) {
        $mysqli->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiendas</title>
    <link rel="icon" href="/Proyecto/img/logotheyshop.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lobster&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background-color: #E6FFE6;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }

        .container {
            background-color: #FFFFFF;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1000px;
            border: 2px solid #A7D9EB;
            text-align: center;
        }

        h2 {
            font-family: 'Lobster', cursive;
            color: #66BB6A;
            margin-bottom: 25px;
            font-size: 2.8em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            font-weight: 400;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            width: 90%;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .message.success {
            background-color: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }

        .message.error {
            background-color: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }

        .top-controls {
            display: flex;
            justify-content: flex-start;
            width: 100%;
            max-width: 1000px;
            margin-bottom: 10px;
            padding-left: 30px;
            box-sizing: border-box;
        }

        .search-form {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-form label {
            font-weight: 600;
            color: #4CAF50;
            margin-right: 5px;
        }

        .search-form input[type="text"] {
            padding: 10px;
            border: 1px solid #B0E0B0;
            border-radius: 8px;
            font-family: 'Quicksand', sans-serif;
            font-size: 1em;
            box-sizing: border-box;
            width: 200px;
        }

        .search-form button, .button {
            background-color: #A5D6A7;
            color: #1B5E20;
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-decoration: none;
            display: inline-block;
        }

        .search-form button:hover, .button:hover {
            background-color: #81C784;
            transform: translateY(-1px);
        }

        .search-form button[type="button"] {
            background-color: #ADD8E6;
            color: #2196F3;
        }

        .search-form button[type="button"]:hover {
            background-color: #87CEEB;
        }

        .add-store-form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            overflow: hidden;
            background-color: #F5FFF5;
        }

        .add-store-form-table th,
        .add-store-form-table td {
            border: 1px solid #D0F0C0;
            padding: 8px;
            text-align: left;
        }

        .add-store-form-table th {
            background-color: #C8E6C9;
            color: #1B5E20;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        .add-store-form-table input[type="text"],
        .add-store-form-table input[type="time"] {
            width: calc(100% - 16px);
            padding: 6px;
            margin-bottom: 0;
            border: 1px solid #B0E0B0;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 0.9em;
            font-family: 'Quicksand', sans-serif;
            color: #333;
        }

        .add-store-form-table .form-actions-cell {
            text-align: center;
            padding-top: 10px;
        }

        .add-store-form-table button[type="submit"],
        .add-store-form-table .cancel-button {
            background-color: #A5D6A7;
            color: #1B5E20;
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 600;
            margin: 5px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            display: inline-block;
        }

        .add-store-form-table .cancel-button {
            background-color: #FFCDD2;
            color: #B71C1C;
        }
        .add-store-form-table .cancel-button:hover {
            background-color: #EF9A9A;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
            background-color: #F5FFF5;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #E0E0E0;
            border-right: 1px solid #E0E0E0;
            word-wrap: break-word;
        }

        th:last-child, td:last-child {
            border-right: none;
        }

        th {
            background-color: #A5D6A7;
            color: #1B5E20;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        tr:nth-child(even) {
            background-color: #F8FFF8;
        }

        tr:hover {
            background-color: #E6FFE6;
            transition: background-color 0.2s ease;
        }

        th:first-child { border-top-left-radius: 12px; }
        th:last-child { border-top-right-radius: 12px; }
        tr:last-child td:first-child { border-bottom-left-radius: 12px; }
        tr:last-child td:last-child { border-bottom-right-radius: 12px; }

        .action-buttons a {
            background-color: #ADD8E6;
            color: #2196F3;
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9em;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .action-buttons a:hover {
            background-color: #87CEEB;
            transform: translateY(-1px);
        }
        .action-buttons a.delete-button {
            background-color: #FFCDD2;
            color: #B71C1C;
        }
        .action-buttons a.delete-button:hover {
            background-color: #EF9A9A;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            h2 {
                font-size: 2em;
            }
            .top-controls {
                padding-left: 0;
                justify-content: center;
            }
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }
            .search-form input[type="text"] {
                width: 100%;
            }
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            tr {
                margin-bottom: 15px;
                border: 1px solid #E0E0E0;
                border-radius: 8px;
                overflow: hidden;
            }
            td {
                border: none;
                border-bottom: 1px solid #EEEEEE;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }
            td::before {
                content: attr(data-label);
                position: absolute;
                left: 0;
                width: 45%;
                padding-left: 10px;
                font-weight: 600;
                text-align: left;
                color: #4CAF50;
            }
            td:last-child {
                border-bottom: none;
            }

            .add-store-form-table th,
            .add-store-form-table td {
                padding: 8px;
            }
            .add-store-form-table input[type="text"],
            .add-store-form-table input[type="time"] {
                width: calc(100% - 16px);
                padding: 6px;
            }
            .add-store-form-table .form-actions-cell {
                flex-direction: column;
                align-items: center;
                padding-left: 0;
            }
            .add-store-form-table button,
            .add-store-form-table .cancel-button {
                width: calc(100% - 20px);
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-controls">
            <a href="/Proyecto/php/panel_administrador.php" class="button">Regresar</a>
        </div>
        <h2>Tabla de Administración de Tiendas</h2>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <h3 style="font-family: 'Quicksand', sans-serif; color: #388E3C; font-size: 1.5em; margin-bottom: 15px;">Agregar Nueva Tienda</h3>
        <form method="POST" action="" class="add-store-form-table">
            <input type="hidden" name="add_new_store" value="1">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Ubicación</th>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td data-label="Nombre">
                            <input type="text" id="new_nombre" name="nombre" placeholder="Nombre" required>
                        </td>
                        <td data-label="Ubicación">
                            <input type="text" id="new_ubicacion" name="ubicacion" placeholder="Ubicación" required>
                        </td>
                        <td data-label="Dirección">
                            <input type="text" id="new_direccion" name="direccion" placeholder="Dirección" required>
                        </td>
                        <td data-label="Teléfono">
                            <input type="text" id="new_telefono" name="telefono" placeholder="Teléfono" required>
                        </td>
                        <td data-label="Apertura">
                            <input type="time" id="new_horario_apertura" name="horario_apertura" required>
                        </td>
                        <td data-label="Cierre">
                            <input type="time" id="new_horario_cierre" name="horario_cierre" required>
                        </td>
                        <td data-label="Acciones" class="form-actions-cell">
                            <button type="submit">Guardar</button>
                            
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>

        <form method="GET" action="" class="search-form">
            <label for="search_id">Buscar por ID:</label>
            <input type="text" id="search_id" name="search_id" value="<?= htmlspecialchars($_GET['search_id'] ?? '') ?>" placeholder="Introduce el ID de la tienda">
            <button type="submit">Buscar</button>
            <?php if (!empty($_GET['search_id'])): ?>
                <button type="button" onclick="window.location.href='<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>'">Limpiar búsqueda</button>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Ubicación</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                    <th>Horario de Apertura</th>
                    <th>Horario de Cierre</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tiendas)): ?>
                    <?php foreach ($tiendas as $tienda): ?>
                        <tr>
                            <td data-label="ID"><?= htmlspecialchars($tienda['id']) ?></td>
                            <td data-label="Nombre"><?= htmlspecialchars($tienda['nombre']) ?></td>
                            <td data-label="Ubicación"><?= htmlspecialchars($tienda['ubicacion']) ?></td>
                            <td data-label="Dirección"><?= htmlspecialchars($tienda['direccion']) ?></td>
                            <td data-label="Teléfono"><?= htmlspecialchars($tienda['telefono']) ?></td>
                            <td data-label="Horario de Apertura"><?= htmlspecialchars($tienda['horario_apertura']) ?></td>
                            <td data-label="Horario de Cierre"><?= htmlspecialchars($tienda['horario_cierre']) ?></td>
                            <td data-label="Acciones" class="action-buttons">
                                <a href="editar_tienda.php?id=<?= htmlspecialchars($tienda['id']) ?>">Editar</a>
                                <a href="eliminar_tienda.php?id=<?= htmlspecialchars($tienda['id']) ?>" class="delete-button" onclick="return confirm('¿Estás seguro de que quieres eliminar la tienda <?= htmlspecialchars($tienda['nombre']) ?>?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No se encontraron tiendas que coincidan con la búsqueda.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

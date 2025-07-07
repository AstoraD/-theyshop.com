<?php
session_start();

// Conexión a la base de datos
$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "usuariostheyshop"; // Asegúrate de que este sea el nombre correcto de tu base de datos

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, 3306);

// Verificar errores en la conexión
if ($mysqli->connect_error) {
    die("Error de Conexión a la Base de Datos: " . $mysqli->connect_error);
}

// Mensajes de operación (éxito o error)
$message = '';
$message_type = ''; // 'success' o 'error'

if (isset($_GET['mensaje'])) {
    if ($_GET['mensaje'] === 'usuario_eliminado') {
        $message = "Usuario eliminado correctamente.";
        $message_type = 'success';
    } elseif ($_GET['mensaje'] === 'error_eliminar') {
        $message = "Error al eliminar el usuario: " . htmlspecialchars($_GET['error'] ?? 'Desconocido');
        $message_type = 'error';
    } elseif ($_GET['mensaje'] === 'usuario_actualizado') {
        $message = "Usuario actualizado correctamente.";
        $message_type = 'success';
    } elseif ($_GET['mensaje'] === 'error_actualizar') {
        $message = "Error al actualizar el usuario: " . htmlspecialchars($_GET['error'] ?? 'Desconocido');
        $message_type = 'error';
    }
}

// Variable para almacenar el ID de búsqueda
$search_id = '';
$users = []; // Inicializa el array de usuarios

// Si se ha enviado un ID de búsqueda
if (isset($_GET['search_id']) && $_GET['search_id'] !== '') {
    $search_id = intval($_GET['search_id']); // Asegúrate de que sea un entero para seguridad
    // Modifica la consulta para buscar por ID
    $query_sql = "SELECT id_cuenta, nombre_usuario, email, rol, profile_picture_path FROM cuentas WHERE id_cuenta = ? ORDER BY id_cuenta DESC";
    $stmt = $mysqli->prepare($query_sql);

    if ($stmt) {
        $stmt->bind_param("i", $search_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $message = "Error al preparar la consulta de búsqueda: " . $mysqli->error;
        $message_type = 'error';
        $result = false; // Indica que no hay resultados válidos
    }
} else {
    // Consulta original para obtener todos los usuarios si no hay búsqueda
    $query_sql = "SELECT id_cuenta, nombre_usuario, email, rol, profile_picture_path FROM cuentas ORDER BY id_cuenta DESC";
    $result = $mysqli->query($query_sql);
}

// Array para almacenar los usuarios
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free(); // Libera el resultado
    if (isset($stmt)) { // Si se usó un prepared statement, ciérralo
        $stmt->close();
    }
} else {
    // El mensaje de error ya debería estar establecido si hubo un problema con la preparación o ejecución
    if (empty($message)) { // Solo si no hay un mensaje de error previo
        $message = "Error al obtener los usuarios: " . $mysqli->error;
        $message_type = 'error';
    }
}

$mysqli->close(); // Cierra la conexión a la base de datos
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lobster&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background-color: #E6FFE6; /* Verde claro */
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
            border: 2px solid #A7D9EB; /* Borde azul pastel */
            text-align: center;
        }

        h1 {
            font-family: 'Pacifico', cursive;
            color: #66BB6A; /* Verde */
            margin-bottom: 25px;
            font-size: 2.8em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
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
            background-color: #D4EDDA; /* Verde claro */
            color: #155724; /* Verde oscuro */
            border: 1px solid #C3E6CB;
        }

        .message.error {
            background-color: #F8D7DA; /* Rojo claro */
            color: #721C24; /* Rojo oscuro */
            border: 1px solid #F5C6CB;
        }

        /* Estilos para el formulario de búsqueda y el botón de regresar */
        .top-controls {
            display: flex;
            justify-content: flex-start; /* Alinea los elementos a la izquierda */
            width: 100%;
            max-width: 1000px; /* Mismo ancho que el contenedor principal */
            margin-bottom: 10px; /* Espacio entre el botón y el título */
            padding-left: 30px; /* Alinea con el padding del contenedor */
            box-sizing: border-box; /* Incluye padding en el ancho */
        }

        .search-form {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
            gap: 10px; /* Espacio entre elementos */
            flex-wrap: wrap; /* Permite que los elementos se envuelvan en pantallas pequeñas */
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
            width: 200px; /* Ancho fijo para el input */
        }

        .search-form button, .button { /* Añadido .button para el estilo del enlace */
            background-color: #A5D6A7; /* Verde claro para botones */
            color: #1B5E20; /* Texto verde oscuro */
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-decoration: none; /* Para los enlaces que parecen botones */
            display: inline-block; /* Para los enlaces que parecen botones */
        }

        .search-form button:hover, .button:hover { /* Añadido .button:hover */
            background-color: #81C784; /* Tono más oscuro al pasar el ratón */
            transform: translateY(-1px);
        }

        .search-form button[type="button"] { /* Estilo para el botón de limpiar búsqueda */
            background-color: #ADD8E6; /* Azul claro */
            color: #2196F3;
        }

        .search-form button[type="button"]:hover {
            background-color: #87CEEB;
        }


        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden; /* Para que los bordes redondeados se apliquen al contenido */
            background-color: #F5FFF5; /* Fondo de tabla muy claro */
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #E0E0E0; /* Borde suave entre filas */
            border-right: 1px solid #E0E0E0; /* Borde suave entre columnas */
        }

        th:last-child, td:last-child {
            border-right: none; /* Eliminar borde derecho de la última columna */
        }

        th {
            background-color: #A5D6A7; /* Verde suave para encabezados */
            color: #1B5E20; /* Texto verde oscuro */
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        tr:nth-child(even) {
            background-color: #F8FFF8; /* Un tono aún más claro para filas pares */
        }

        tr:hover {
            background-color: #E6FFE6; /* Resaltado al pasar el ratón */
            transition: background-color 0.2s ease;
        }

        /* Bordes redondeados para la tabla */
        th:first-child { border-top-left-radius: 12px; }
        th:last-child { border-top-right-radius: 12px; }
        tr:last-child td:first-child { border-bottom-left-radius: 12px; }
        tr:last-child td:last-child { border-bottom-right-radius: 12px; }

        .action-buttons a {
            background-color: #ADD8E6; /* Azul claro para botones de acción */
            color: #2196F3; /* Azul más oscuro */
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
            background-color: #87CEEB; /* Azul más oscuro al pasar el ratón */
            transform: translateY(-1px);
        }

        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #B0E0B0;
        }

        /* Responsive table */
        @media (max-width: 768px) {
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-controls">
            <a href="/Proyecto/php/panel_administrador.php" class="button">Regresar</a>
        </div>
        <h1>Tabla de Administración de Usuarios</h1>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="GET" action="" class="search-form">
            <label for="search_id">Buscar por ID:</label>
            <input type="text" id="search_id" name="search_id" value="<?= htmlspecialchars($_GET['search_id'] ?? '') ?>" placeholder="Introduce el ID del usuario">
            <button type="submit">Buscar</button>
            <?php if (!empty($_GET['search_id'])): // Muestra el botón de "Limpiar búsqueda" solo si hay una búsqueda activa ?>
                <button type="button" onclick="window.location.href='tabla_usuarios.php'">Limpiar búsqueda</button>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Foto de Perfil</th>
                    <th>Nombre de Usuario</th>
                    <th>Correo Electrónico</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td data-label="ID"><?= htmlspecialchars($user['id_cuenta']) ?></td>
                            <td data-label="Foto de Perfil">
                                <?php if (!empty($user['profile_picture_path']) && file_exists($user['profile_picture_path'])): ?>
                                    <img src="<?= htmlspecialchars($user['profile_picture_path']) ?>" alt="Foto de Perfil" class="profile-pic">
                                <?php else: ?>
                                    <img src="https://placehold.co/40x40/B0E0B0/FFFFFF?text=User" alt="Placeholder" class="profile-pic">
                                <?php endif; ?>
                            </td>
                            <td data-label="Nombre de Usuario"><?= htmlspecialchars($user['nombre_usuario']) ?></td>
                            <td data-label="Correo Electrónico"><?= htmlspecialchars($user['email']) ?></td>
                            <td data-label="Rol"><?= htmlspecialchars($user['rol']) ?></td>
                            <td data-label="Acciones" class="action-buttons">
                                <a href="/Proyecto/php/editar_usuario.php?id=<?= htmlspecialchars($user['id_cuenta']) ?>">Editar</a>
                                <a href="delete_user.php?id=<?= htmlspecialchars($user['id_cuenta']) ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar a <?= htmlspecialchars($user['nombre_usuario']) ?>?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No hay usuarios registrados que coincidan con la búsqueda.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

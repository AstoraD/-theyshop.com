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

$user_data = null; // Variable para almacenar los datos del usuario a editar
$message = '';      // Mensajes de operación
$message_type = ''; // 'success' o 'error'

// --- Lógica para PROCESAR LA ACTUALIZACIÓN (cuando se envía el formulario POST) ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_user"])) {
    $id_cuenta = intval($_POST["id_cuenta"]);
    $nombre_usuario = $_POST["nombre_usuario"] ?? '';
    $email = $_POST["email"] ?? '';
    $rol = $_POST["rol"] ?? '';
    

    // Prepara la consulta SQL para actualizar el registro
    $stmt_update = $mysqli->prepare("UPDATE cuentas SET nombre_usuario=?, email=?, rol=? WHERE id_cuenta=?");
    
    if ($stmt_update === false) {
        $message = "Error al preparar la consulta de actualización: " . $mysqli->error;
        $message_type = 'error';
    } else {
        // Vincula los parámetros a la consulta
        $stmt_update->bind_param("sssi",
            $nombre_usuario,
            $email,
            $rol,
            $id_cuenta
        );

        // Ejecuta la consulta
        if ($stmt_update->execute()) {
            // Redirige de vuelta a la página de tabla_usuarios.php con un mensaje de éxito
            header("Location: /Proyecto/php/tabla_usuarios.php?mensaje=usuario_actualizado");
            exit(); // Detiene la ejecución del script
        } else {
            // Redirige de vuelta a la página de tabla_usuarios.php con un mensaje de error
            header("Location: /Proyecto/php/tabla_usuarios.php?mensaje=error_actualizar&error=" . urlencode($stmt_update->error));
            exit(); // Detiene la ejecución del script
        }
        $stmt_update->close(); // Cierra el statement
    }
}

// --- Lógica para MOSTRAR EL FORMULARIO DE EDICIÓN (cuando se accede vía GET) ---
// Se ejecuta si se proporciona un ID en la URL
if (isset($_GET["id"])) {
    $id_cuenta_to_edit = intval($_GET["id"]); // Convierte el ID a entero

    // Prepara la consulta para obtener los datos del usuario a editar
    $stmt_select_user = $mysqli->prepare("SELECT id_cuenta, nombre_usuario, email, rol, profile_picture_path FROM cuentas WHERE id_cuenta=?");
    
    if ($stmt_select_user === false) {
        $message = "Error al preparar la consulta de selección: " . $mysqli->error;
        $message_type = 'error';
    } else {
        $stmt_select_user->bind_param("i", $id_cuenta_to_edit);
        $stmt_select_user->execute();
        $result_user = $stmt_select_user->get_result();

        // Si se encontró el usuario, guarda los datos
        if ($result_user && $result_user->num_rows > 0) {
            $user_data = $result_user->fetch_assoc();
        } else {
            $message = "Usuario no encontrado para editar.";
            $message_type = 'error';
        }
        $stmt_select_user->close(); // Cierra el statement
    }
} else {
    // Si no se proporcionó un ID, muestra un mensaje de error
    $message = "No se ha especificado un ID de usuario para editar.";
    $message_type = 'error';
}

// --- Lógica para mostrar mensajes de éxito/error después de la redirección ---
// Esta parte ya no es estrictamente necesaria aquí si siempre rediriges,
// pero se mantiene por si se quiere mostrar un mensaje en la misma página antes de una redirección.
// Si la redirección es inmediata, el mensaje lo mostrará la página de destino.
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $message = "¡Actualización exitosa!";
        $message_type = 'success';
    } elseif ($_GET['status'] === 'error') {
        $message = "Error en la actualización: " . htmlspecialchars($_GET['error_msg'] ?? 'Desconocido');
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
    <title>Editar Usuario - Panel de Administración</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&family=Pacifico&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background-color: #E6FFE6; /* Verde claro */
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
            box-sizing: border-box;
        }

        .edit-container {
            background-color: #FFFFFF;
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            border: 2px solid #A7D9EB; /* Borde azul pastel */
            text-align: center;
        }

        h1 {
            font-family: 'Pacifico', cursive;
            color: #66BB6A; /* Verde */
            margin-bottom: 25px;
            font-size: 2.5em;
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

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4CAF50; /* Verde para las etiquetas */
            text-align: left;
        }

        input[type="text"],
        input[type="email"],
        select {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #B0E0B0;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1em;
            font-family: 'Quicksand', sans-serif;
            color: #333;
        }

        .profile-pic-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #A5D6A7;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        button {
            background-color: #A5D6A7; /* Verde claro para el botón principal */
            color: #1B5E20; /* Texto verde oscuro */
            padding: 12px 25px;
            border: none;
            border-radius: 25px; /* Botones redondeados */
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            margin: 10px 5px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        button:hover {
            background-color: #81C784; /* Tono más oscuro al pasar el ratón */
            transform: translateY(-2px); /* Pequeño efecto de elevación */
        }

        .cancel-button {
            background-color: #ADD8E6; /* Azul claro para el botón de cancelar */
            color: #2196F3; /* Azul más oscuro */
        }

        .cancel-button:hover {
            background-color: #87CEEB;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <h1>Editar Usuario</h1>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($user_data): ?>
            <form method="POST" action="">
                <input type="hidden" name="id_cuenta" value="<?= htmlspecialchars($user_data['id_cuenta']) ?>">
                <input type="hidden" name="update_user" value="1">

                <?php if (!empty($user_data['profile_picture_path']) && file_exists($user_data['profile_picture_path'])): ?>
                    <img src="<?= htmlspecialchars($user_data['profile_picture_path']) ?>" alt="Foto de Perfil" class="profile-pic-preview">
                <?php else: ?>
                    <img src="https://placehold.co/80x80/B0E0B0/FFFFFF?text=User" alt="Placeholder" class="profile-pic-preview">
                <?php endif; ?>
                <br>

                <label for="nombre_usuario">Nombre de Usuario:</label>
                <input type="text" id="nombre_usuario" name="nombre_usuario" value="<?= htmlspecialchars($user_data['nombre_usuario']) ?>" required>

                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_data['email']) ?>" required>

                <label for="rol">Rol:</label>
                <select id="rol" name="rol" required>
                    <option value="user" <?= ($user_data['rol'] === 'user') ? 'selected' : '' ?>>Usuario</option>
                    <option value="admin" <?= ($user_data['rol'] === 'admin') ? 'selected' : '' ?>>Administrador</option>
                </select>
                <br>

                <button type="submit">Guardar Cambios</button>
                <button type="button" class="cancel-button" onclick="window.location.href='/Proyecto/php/tabla_usuarios.php'">Cancelar</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

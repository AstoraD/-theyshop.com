<?php
session_start();


$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "usuariostheyshop";

$mysqli = null;
$message = '';
$message_type = '';

$user_data = [
    'nombre_usuario' => '',
    'email' => '',
    'rol' => '',
    'profile_picture_path' => '',
    'contrasena' => ''
];

$profile_pic_src = 'https://placehold.co/100x100/66BB6A/FFFFFF?text=User';

try {
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, 3306);

    if ($mysqli->connect_error) {
        throw new Exception("Error de Conexión a la Base de Datos: " . $mysqli->connect_error);
    }

    $current_username_session = $_SESSION['nombre_usuario'];

    $stmt_user = $mysqli->prepare("SELECT nombre_usuario, email, rol, profile_picture_path, contrasena FROM cuentas WHERE nombre_usuario = ?");
    if ($stmt_user === false) {
        throw new Exception("Error al preparar la consulta de usuario: " . $mysqli->error);
    }
    $stmt_user->bind_param("s", $current_username_session);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($result_user->num_rows === 1) {
        $user_data = $result_user->fetch_assoc();
        $user_data['rol'] = trim($user_data['rol']);
        
        if (!empty($user_data['profile_picture_path'])) {
            $session_image_path = $user_data['profile_picture_path'];

            if (filter_var($session_image_path, FILTER_VALIDATE_URL)) {
                $profile_pic_src = htmlspecialchars($session_image_path);
            } else {
                $project_root_server_path = dirname(dirname(__FILE__));

                $full_server_path_to_image = $project_root_server_path . '/' . $session_image_path;

                $project_folder_name = basename($project_root_server_path);
                $web_url_for_image = '/' . $project_folder_name . '/' . $session_image_path;

                if (file_exists($full_server_path_to_image)) {
                    $profile_pic_src = htmlspecialchars($web_url_for_image);
                }
            }
        }
    } else {
        $message = "No se encontraron los datos del usuario.";
        $message_type = 'error';
    }
    $stmt_user->close();

    function is_username_unique($mysqli, $username, $exclude_username) {
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM cuentas WHERE nombre_usuario = ? AND nombre_usuario != ?");
        if ($stmt === false) { return false; }
        $stmt->bind_param("ss", $username, $exclude_username);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        return $count === 0;
    }

    function is_email_unique($mysqli, $email, $exclude_username) {
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM cuentas WHERE email = ? AND nombre_usuario != ?");
        if ($stmt === false) { return false; }
        $stmt->bind_param("ss", $email, $exclude_username);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        return $count === 0;
    }


    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {
        $new_username = trim($_POST['nombre_usuario'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $new_contrasena = $_POST['contrasena'] ?? '';

        $update_fields = [];
        $bind_params = [];
        $bind_types = '';
        $errors = [];

        if ($new_username !== $user_data['nombre_usuario']) {
            if (empty($new_username)) {
                $errors[] = "El nombre de usuario no puede estar vacío.";
            } elseif (!is_username_unique($mysqli, $new_username, $current_username_session)) {
                $errors[] = "El nombre de usuario ya está en uso.";
            } else {
                $update_fields[] = "nombre_usuario = ?";
                $bind_params[] = $new_username;
                $bind_types .= 's';
            }
        }

        if ($new_email !== $user_data['email']) {
            if (empty($new_email)) {
                $errors[] = "El correo electrónico no puede estar vacío.";
            } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Formato de correo electrónico inválido.";
            } elseif (!is_email_unique($mysqli, $new_email, $current_username_session)) {
                $errors[] = "El correo electrónico ya está en uso.";
            } else {
                $update_fields[] = "email = ?";
                $bind_params[] = $new_email;
                $bind_types .= 's';
            }
        }

        if (isset($_POST['rol'])) {
            $new_rol = trim($_POST['rol']);
            if ($_SESSION['rol'] === 'admin') {
                if ($new_rol !== $user_data['rol']) {
                    $update_fields[] = "rol = ?";
                    $bind_params[] = $new_rol;
                    $bind_types .= 's';
                }
            } else {
                if ($new_rol !== $user_data['rol']) {
                    $errors[] = "No tienes permiso para cambiar tu rol.";
                }
            }
        }


        if (!empty($new_contrasena)) {
            $hashed_contrasena = password_hash($new_contrasena, PASSWORD_DEFAULT);
            $update_fields[] = "contrasena = ?";
            $bind_params[] = $hashed_contrasena;
            $bind_types .= 's';
        }

        if (!empty($errors)) {
            $message = implode("<br>", $errors);
            $message_type = 'error';
        } 
        
        if (!empty($update_fields)) {
            $query = "UPDATE cuentas SET " . implode(", ", $update_fields) . " WHERE nombre_usuario = ?";
            $bind_params[] = $current_username_session;
            $bind_types .= 's';

            $stmt_update = $mysqli->prepare($query);
            if ($stmt_update === false) {
                throw new Exception("Error al preparar la consulta de actualización: " . $mysqli->error);
            }

            $stmt_update->bind_param($bind_types, ...$bind_params);

            if ($stmt_update->execute()) {
                if (empty($errors)) {
                    $message = "Perfil actualizado correctamente.";
                    $message_type = 'success';
                } else {
                    $message = "Perfil actualizado parcialmente. " . $message;
                    $message_type = 'warning';
                }

                if (in_array("nombre_usuario = ?", $update_fields)) {
                    $_SESSION['username'] = $new_username;
                    $user_data['nombre_usuario'] = $new_username;
                }
                if (in_array("email = ?", $update_fields)) {
                    $_SESSION['email'] = $new_email;
                    $user_data['email'] = $new_email;
                }
                if ($_SESSION['rol'] === 'admin' && isset($_POST['rol']) && trim($_POST['rol']) !== $user_data['rol']) {
                    $_SESSION['rol'] = trim($_POST['rol']);
                    $user_data['rol'] = trim($_POST['rol']);
                }
            } else {
                $message = "Error al actualizar el perfil: " . $stmt_update->error;
                $message_type = 'error';
            }
            $stmt_update->close();
        } elseif (empty($errors)) {
            $message = "No se detectaron cambios.";
            $message_type = 'info';
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
    <title>THEYSHOP - Mi Perfil</title>
    <link rel="icon" href="/Proyecto/img/logotheyshop.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background-color: #E6FFE6;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
            box-sizing: border-box;
        }

        .profile-container {
            background-color: #FFFFFF;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
            border: 2px solid #A7D9EB;
        }

        .profile-container h1 {
            font-family: 'Lobster', cursive;
            font-size: 2.8em;
            color: #66BB6A;
            margin-bottom: 30px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .profile-picture-area {
            margin-bottom: 30px;
        }

        .profile-picture-area img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #66BB6A;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .profile-form .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .profile-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 1.1em;
        }

        .profile-form input[type="text"],
        .profile-form input[type="email"],
        .profile-form input[type="password"],
        .profile-form select {
            width: calc(100% - 20px);
            padding: 12px;
            border: 1px solid #B0E0B0;
            border-radius: 8px;
            font-size: 1em;
            font-family: 'Quicksand', sans-serif;
            color: #333;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .profile-form input[type="text"]:focus,
        .profile-form input[type="email"]:focus,
        .profile-form input[type="password"]:focus,
        .profile-form select:focus {
            border-color: #66BB6A;
            box-shadow: 0 0 0 3px rgba(102, 187, 106, 0.3);
            outline: none;
        }

        .profile-form .button-group {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .profile-form button,
        .profile-form .cancel-button {
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            font-size: 1.1em;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .profile-form button[type="submit"] {
            background-color: #66BB6A;
            color: #FFFFFF;
        }

        .profile-form button[type="submit"]:hover {
            background-color: #4CAF50;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .profile-form .cancel-button {
            background-color: #FFCDD2;
            color: #B71C1C;
        }

        .profile-form .cancel-button:hover {
            background-color: #EF9A9A;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .message-container {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .message-container.success {
            background-color: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }

        .message-container.error {
            background-color: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }
        .message-container.info {
            background-color: #CCE5FF;
            color: #004085;
            border: 1px solid #B8DAFF;
        }
        .message-container.warning {
            background-color: #FFF3CD;
            color: #856404;
            border: 1px solid #FFECB5;
        }


        @media (max-width: 600px) {
            .profile-container {
                padding: 25px;
                margin: 20px;
            }

            .profile-container h1 {
                font-size: 2.2em;
            }

            .profile-form button,
            .profile-form .cancel-button {
                width: 100%;
                margin-bottom: 10px;
            }

            .profile-form .button-group {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <h1>Mi Perfil</h1>

        <?php if (!empty($message)): ?>
            <div class="message-container <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="profile-picture-area">
            <img src="<?= $profile_pic_src ?>" alt="Foto de Perfil del Usuario">
            <!-- Aquí podrías añadir un formulario para subir/cambiar la foto -->
            <!-- <form action="upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="profile_pic" id="profile_pic">
                <button type="submit">Subir nueva foto</button>
            </form> -->
        </div>

        <form class="profile-form" action="" method="POST">
            <div class="form-group">
                <label for="nombre_usuario">Nombre de Usuario:</label>
                <input type="text" id="nombre_usuario" name="nombre_usuario" value="<?= htmlspecialchars($user_data['nombre_usuario']) ?>">
            </div>
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_data['email']) ?>">
            </div>
            <div class="form-group">
                <label for="rol">Rol:</label>
                <select id="rol" name="rol" <?= ($_SESSION['rol'] !== 'admin') ? 'disabled' : '' ?>>
                    <option value="user" <?= ($user_data['rol'] === 'user') ? 'selected' : '' ?>>Usuario</option>
                    <option value="admin" <?= ($user_data['rol'] === 'admin') ? 'selected' : '' ?>>Administrador</option>
                </select>
                <?php if ($_SESSION['rol'] !== 'admin'): ?>
                    <small style="display: block; margin-top: 5px; color: #888;">Solo los administradores pueden cambiar el rol.</small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="contrasena">Contraseña:</label>
                <input type="password" id="contrasena" name="contrasena" placeholder="Deja en blanco para no cambiar.">
                <small style="display: block; margin-top: 5px; color: #888;">Ingresa una nueva contraseña si deseas cambiarla.</small>
            </div>
            
            <div class="button-group">
                <button type="submit" name="update_profile">Guardar Cambios</button>
                <a href="/Proyecto/php/panel_usuario.php" class="cancel-button">Volver</a>
            </div>
        </form>
    </div>
</body>
</html>

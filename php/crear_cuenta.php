<?php
session_start();

$display_message = '';
$redirect_url = '';
$is_success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_usuario = $_POST['nombre_usuario'] ?? '';
    $email = $_POST['email_cuenta'] ?? '';
    $contrasena = $_POST['contrasena_cuenta'] ?? '';
    $confirmar_contrasena = $_POST['confirmar_contrasena_cuenta'] ?? '';
    $profile_picture_path = null;

    if (empty($nombre_usuario) || empty($email) || empty($contrasena) || empty($confirmar_contrasena)) {
        $display_message = "Todos los campos son obligatorios.";
    } elseif ($contrasena !== $confirmar_contrasena) {
        $display_message = "Las contraseñas no coinciden. Por favor, inténtalo de nuevo.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $display_message = "Formato de correo electrónico inválido.";
    } else {
        $db_host = "127.0.0.1";
        $db_user = "root";
        $db_pass = "";
        $db_name = "usuariostheyshop";

        try {
            $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, 3306);

            if ($mysqli->connect_error) {
                $display_message = "Error de Conexión a la Base de Datos: " . $mysqli->connect_error;
            } else {
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $target_dir = "uploads/profiles/";
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }

                    $original_file_name = basename($_FILES["profile_picture"]["name"]);
                    $file_extension = pathinfo($original_file_name, PATHINFO_EXTENSION);
                    $unique_file_name = uniqid('profile_') . "." . $file_extension;
                    $target_file = $target_dir . $unique_file_name;

                    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                        $profile_picture_path = $target_file;
                    } else {
                        $display_message = "Error al subir la imagen de perfil. Inténtalo de nuevo.";
                    }
                } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $display_message = "Error en la subida de la imagen: Código " . $_FILES['profile_picture']['error'];
                }

                $hashed_password = password_hash($contrasena, PASSWORD_DEFAULT);

                $stmt = $mysqli->prepare("INSERT INTO cuentas (nombre_usuario, email, contrasena, rol, profile_picture_path) VALUES (?, ?, ?, 'user', ?)");

                if ($stmt === false) {
                    $display_message = "Error al preparar la consulta: " . $mysqli->error;
                } else {
                    $stmt->bind_param("ssss", $nombre_usuario, $email, $hashed_password, $profile_picture_path);

                    if ($stmt->execute()) {
                        $display_message = "¡Cuenta creada exitosamente! Ahora puedes iniciar sesión.";
                        $is_success = true;
                        $redirect_url = '/Proyecto/php/iniciar_sesión.php';
                    } else {
                        if ($mysqli->errno === 1062) {
                            $display_message = "El nombre de usuario o correo electrónico ya existen. Por favor, elige otros.";
                        } else {
                            $display_message = "Error al crear la cuenta: " . $stmt->error;
                        }
                    }

                    $stmt->close();
                }
                $mysqli->close();
            }
        } catch (Exception $e) {
            $display_message = "Error en el procesamiento: " . $e->getMessage();
        }
    }
    
    $_SESSION['display_message'] = $display_message;
    $_SESSION['message_is_success'] = $is_success;
    if ($is_success) {
        $_SESSION['redirect_after_message'] = $redirect_url;
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - They Shop</title>
    <link rel="icon" href="/Proyecto/img/logotheyshop.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lobster&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #E6FFE6;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
            box-sizing: border-box;
            animation: fadeInUp 0.8s ease-out forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .create-account-container {
            background-color: #FFFFFF;
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            border: 2px solid #A7D9EB;
            text-align: center;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s ease-out forwards;
        }

        h2 {
            font-family: 'Pacifico', cursive;
            color: #66BB6A;
            margin-top: 10px; /* Ajustado para acercar al logo */
            margin-bottom: 25px;
            font-size: 2.5em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4CAF50;
            text-align: left;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="file"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #B0E0B0;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1em;
            font-family: 'Inter', sans-serif;
            color: #333;
        }

        button {
            background-color: #A5D6A7;
            color: #1B5E20;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            margin: 10px 5px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        button[type="submit"]:hover {
            background-color: #81C784;
            transform: translateY(-2px);
        }

        button[type="reset"] {
            background-color: #ADD8E6;
            color: #2196F3;
        }

        button[type="reset"]:hover {
            background-color: #87CEEB;
            transform: translateY(-2px);
        }

        .mensaje {
            background-color: #D6ECF0;
            color: #4A6D7F;
            border: 1px solid #B0D9E0;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .mensaje.error {
            background-color: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .custom-message-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .custom-message-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .custom-message-box {
            background-color: #FFFFFF;
            color: #2C3E50;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.4);
            max-width: 350px;
            text-align: center;
            transform: scale(0.9);
            transition: transform 0.3s ease;
            border: 2px solid #A7D9EB;
        }

        .custom-message-overlay.show .custom-message-box {
            transform: scale(1);
        }

        .custom-message-box p {
            margin-bottom: 20px;
            font-size: 1.1em;
            line-height: 1.5;
        }

        .custom-message-box button {
            background-color: #66BB6A;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        .custom-message-box button:hover {
            opacity: 0.9;
            background-color: #388E3C;
        }

        .header-image {
            width: 80px;
            height: auto;
            margin-bottom: 5px; /* Ajustado para acercar al título */
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="create-account-container">
        <img src="/Proyecto/img/logotheyshop.png" alt="They Shop" class="header-image">
        <h2>Crear Cuenta</h2>

        <form method="POST" action="" enctype="multipart/form-data">
            <label for="nombre_usuario">Nombre de Usuario:</label>
            <input type="text" id="nombre_usuario" name="nombre_usuario" required>

            <label for="email_cuenta">Correo Electrónico:</label>
            <input type="email" id="email_cuenta" name="email_cuenta" required>

            <label for="contrasena_cuenta">Contraseña:</label>
            <input type="password" id="contrasena_cuenta" name="contrasena_cuenta" required>

            <label for="confirmar_contrasena_cuenta">Confirmar Contraseña:</label>
            <input type="password" id="confirmar_contrasena_cuenta" name="confirmar_contrasena_cuenta" required>

            <label for="profile_picture">Foto de Perfil (Opcional):</label>
            <input type="file" id="profile_picture" name="profile_picture" accept="image/*">

            <button type="submit">Crear Cuenta</button>
            <button type="reset">Limpiar</button>
        </form>
    </div>

    <div class="custom-message-overlay" id="customMessageOverlay">
        <div class="custom-message-box">
            <p id="customMessageText"></p>
            <button onclick="closeCustomMessage()">Cerrar</button>
        </div>
    </div>

    <script>
        function showCustomMessage(message) {
            document.getElementById('customMessageText').textContent = message;
            document.getElementById('customMessageOverlay').classList.add('show');
        }

        function closeCustomMessage() {
            document.getElementById('customMessageOverlay').classList.remove('show');
        }

        document.addEventListener('DOMContentLoaded', function() {
            <?php
            if (isset($_SESSION['display_message'])) {
                echo "showCustomMessage(" . json_encode($_SESSION['display_message']) . ");";
                
                if (isset($_SESSION['message_is_success']) && $_SESSION['message_is_success'] === true) {
                    echo "setTimeout(function() {";
                    echo "    window.location.href = " . json_encode($_SESSION['redirect_after_message'] ?? '') . ";";
                    echo "}, 2000);";
                }
                
                unset($_SESSION['display_message']);
                unset($_SESSION['message_is_success']);
                unset($_SESSION['redirect_after_message']);
            }
            ?>
        });
    </script>
</body>
</html>

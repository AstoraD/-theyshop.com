<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $db_host = "127.0.0.1";
    $db_user = "root";
    $db_pass = "";
    $db_name = "iniciosesion";

    try {
        $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

        if ($mysqli->connect_error) {
            
            $_SESSION['error_message'] = "Error de Conexión a la Base de Datos: " . $mysqli->connect_error;
        } else {
            $stmt = $mysqli->prepare("SELECT contrasena FROM cuentas WHERE nombre_usuario = ? OR email = ?");
            
            if ($stmt === false) {
                $_SESSION['error_message'] = "Error al preparar la consulta: " . $mysqli->error;
            } else {
                $stmt->bind_param("ss", $username, $username);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows === 1) {
                    $stmt->bind_result($hashed_password_from_db);
                    $stmt->fetch();

                    if (password_verify($password, $hashed_password_from_db)) {
                        // Credenciales correctas
                        $_SESSION['loggedin'] = true;
                        $_SESSION['username'] = $username;
                        $_SESSION['show_login_success_modal'] = true;
                        $_SESSION['redirect_target'] = '/PracticasForms/Forms%20php/01.%20Formulario%20reservaciones%20kitty.html';
                        
                    } else {
                        $_SESSION['error_message'] = "Usuario o contraseña incorrectos.";
                    }
                } else {
                    $_SESSION['error_message'] = "Usuario o contraseña incorrectos.";
                }

                $stmt->close();
            }
            $mysqli->close();
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error en el procesamiento: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso de Administrador - Hello Kitty Parties</title>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #E6E6FA;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #4B0082;
        }

        .login-container {
            background-color: #FFFFFF;
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            border: 2px solid #DDA0DD;
            text-align: center;
        }

        h2 {
            font-family: 'Pacifico', cursive;
            color: #9370DB;
            margin-bottom: 25px;
            font-size: 2.5em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #6B5B95;
            text-align: left;
        }

        input[type="text"],
        input[type="password"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #DDA0DD;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1em;
            font-family: 'Inter', sans-serif;
        }

        button {
            background-color: #9370DB;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            margin-top: 10px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        button:hover {
            background-color: #8A2BE2;
            transform: translateY(-2px);
        }

        .mensaje.error {
            background-color: #FFFFFF; /* Fondo blanco */
            color: #9370DB; /* Texto morado */
            border: 2px solid #DDA0DD; /* Borde morado */
            padding: 10px;
            border-radius: 8px; /* Esquinas ligeramente más redondeadas */
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Sombra sutil */
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
            border: 2px solid #9370DB;
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
            background-color: #9370DB;
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
            background-color: #8A2BE2;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>

        <?php
        //verifica si la variable fue definida o no es nula
        if (isset($_SESSION['error_message'])) {
            echo "<p class='mensaje error'>" . htmlspecialchars($_SESSION['error_message']) . "</p>";
            unset($_SESSION['error_message']);
        }
        ?>

        <form method="POST" action="">
            <label for="username">Usuario (o Email):</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Iniciar Sesión</button>
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
            
            if (isset($_SESSION['show_login_success_modal']) && $_SESSION['show_login_success_modal']) {
                echo "showCustomMessage('¡Inicio de sesión exitoso!');";
                echo "setTimeout(function() {";
                echo "    window.location.href = '" . $_SESSION['redirect_target'] . "';"; 
                echo "}, 2000);"; // Redirige después de 2 segundos
                unset($_SESSION['show_login_success_modal']); 
                unset($_SESSION['redirect_target']);
            }
            ?>
        });
    </script>
</body>
</html>

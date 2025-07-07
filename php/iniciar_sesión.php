<?php
session_start();

// Configuración de la base de datos
$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "usuariostheyshop";

$mysqli = null;
$message = ''; // Usamos $message y $message_type para consistencia
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username_or_email = $_POST['username_or_email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validar que los campos no estén vacíos
    if (empty($username_or_email) || empty($password)) {
        $message = "Por favor, introduce tu nombre de usuario/correo y contraseña.";
        $message_type = 'error';
    } else {
        try {
            $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, 3306);

            if ($mysqli->connect_error) {
                throw new Exception("Error de Conexión a la Base de Datos: " . $mysqli->connect_error);
            }

            // Prepara la consulta para buscar el usuario por nombre de usuario o email
            // Selecciona todos los campos necesarios para la sesión, usando 'contrasena'
            $stmt = $mysqli->prepare("SELECT id_cuenta, nombre_usuario, email, contrasena, rol, profile_picture_path FROM cuentas WHERE nombre_usuario = ? OR email = ?");
            
            if ($stmt === false) {
                throw new Exception("Error al preparar la consulta: " . $mysqli->error);
            }

            $stmt->bind_param("ss", $username_or_email, $username_or_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verificar la contraseña usando 'contrasena'
                if (password_verify($password, $user['contrasena'])) {
                    // Contraseña correcta, iniciar sesión
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id_cuenta'] = $user['id_cuenta'];
                    $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['rol'] = $user['rol'];
                    $_SESSION['profile_picture_path'] = $user['profile_picture_path'];

                    // Redirigir según el rol
                    if ($user['rol'] === 'admin') {
                        header("Location: /Proyecto/php/panel_administrador.php");
                    } else {
                        // Redirige a los usuarios normales al panel de productos
                        header("Location: /Proyecto/php/panel_usuario.php"); 
                    }
                    exit();
                } else {
                    // Contraseña incorrecta
                    $message = "Nombre de usuario/correo o contraseña incorrectos.";
                    $message_type = 'error';
                }
            } else {
                // Usuario no encontrado en la base de datos
                $message = "Usuario no encontrado, favor de crear su cuenta.";
                $message_type = 'error';
            }

            $stmt->close();

        } catch (Exception $e) {
            // Captura cualquier otra excepción
            $message = "Error en el procesamiento: " . $e->getMessage();
            $message_type = 'error';
        } finally {
            if (isset($mysqli) && $mysqli->ping()) {
                $mysqli->close();
            }
        }
    }
}
// Si hay un mensaje de error en la URL (para redirecciones previas)
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'credenciales_invalidas') {
        $message = "Nombre de usuario/correo o contraseña incorrectos.";
        $message_type = 'error';
    } elseif ($_GET['error'] === 'campos_vacios') {
        $message = "Por favor, introduce tu nombre de usuario/correo y contraseña.";
        $message_type = 'error';
    } elseif ($_GET['error'] === 'no_sesion') {
        $message = "Necesitas iniciar sesión para acceder a esa página.";
        $message_type = 'error';
    } elseif ($_GET['error'] === 'acceso_denegado') {
        $message = "No tienes permisos para acceder a esa página.";
        $message_type = 'error';
    } elseif ($_GET['error'] === 'usuario_no_encontrado') {
        $message = "Usuario no encontrado, favor de crear su cuenta.";
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - THEYSHOP</title>
    <link rel="icon" href="/Proyecto/img/logotheyshop.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lobster&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Quicksand', sans-serif; /* Cambiado a Quicksand para consistencia */
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

        .login-container {
            background-color: #FFFFFF;
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            border: 2px solid #A7D9EB;
            text-align: center;
        }

        .header-image {
            width: 80px;
            height: auto;
            margin-bottom: 5px;
            opacity: 0.7;
        }

        h1 {
            font-family: 'Lobster', cursive;
            color: #66BB6A;
            margin-top: 10px;
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

        input[type="text"], /* Cambiado de email a text para username_or_email */
        input[type="password"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #B0E0B0;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1em;
            font-family: 'Quicksand', sans-serif; /* Cambiado a Quicksand para consistencia */
            color: #333;
        }

        button[type="submit"] {
            background-color: #A5D6A7;
            color: #1B5E20;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            margin: 10px auto;
            display: block;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 200px;
        }

        button[type="submit"]:hover {
            background-color: #81C784;
            transform: translateY(-2px);
        }

        .message { /* Clase para mensajes de éxito/error, ahora unificada */
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            text-align: center;
            font-weight: 600;
        }
        .message.error {
            background-color: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }
        .message.success { /* Añadido para mensajes de éxito si se usan */
            background-color: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }

        .link-back {
            display: block;
            margin-top: 1.5rem;
            color: #4CAF50;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .link-back:hover {
            color: #388E3C;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="/Proyecto/img/logotheyshop.png" alt="They Shop" class="header-image">
        <h1>Iniciar Sesión</h1>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="" method="post">
            <label for="username_or_email">Nombre de Usuario o Correo:</label>
            <input type="text" id="username_or_email" name="username_or_email" required>

            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Entrar</button>
        </form>

        <a href="/Proyecto/Index.html" class="link-back">Volver a la página de inicio</a>
    </div>
</body>
</html>

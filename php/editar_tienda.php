<?php
session_start();

// Configuración de la base de datos
$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "usuariostheyshop"; // Asegúrate de que este sea el nombre correcto de tu base de datos

$mysqli = null; // Inicializar $mysqli a null
$tienda_data = null; // Variable para almacenar los datos de la tienda a editar
$message = '';      // Mensajes de operación
$message_type = ''; // 'success' o 'error'

try {
    // Conexión a la base de datos
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, 3306);

    // Verificar errores en la conexión
    if ($mysqli->connect_error) {
        throw new Exception("Error de Conexión a la Base de Datos: " . $mysqli->connect_error);
    }

    // --- Lógica para PROCESAR LA ACTUALIZACIÓN (cuando se envía el formulario POST) ---
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_tienda"])) {
        $id = intval($_POST["id"]);
        $nombre = $_POST["nombre"] ?? '';
        $ubicacion = $_POST["ubicacion"] ?? '';
        $direccion = $_POST["direccion"] ?? '';
        $telefono = $_POST["telefono"] ?? '';
        $horario_apertura = $_POST["horario_apertura"] ?? '';
        $horario_cierre = $_POST["horario_cierre"] ?? '';

        // Prepara la consulta SQL para actualizar el registro
        $stmt_update = $mysqli->prepare("UPDATE tiendas SET nombre=?, ubicacion=?, direccion=?, telefono=?, horario_apertura=?, horario_cierre=? WHERE id=?");
        
        if ($stmt_update === false) {
            header("Location: /Proyecto/php/tabla_tiendas.php?mensaje=error_actualizar&error=" . urlencode($mysqli->error));
            exit();
        } else {
            // Vincula los parámetros a la consulta
            $stmt_update->bind_param("ssssssi",
                $nombre,
                $ubicacion,
                $direccion,
                $telefono,
                $horario_apertura,
                $horario_cierre,
                $id
            );

            // Ejecuta la consulta
            if ($stmt_update->execute()) {
                // Redirige de vuelta a la página de tabla_tiendas.php con un mensaje de éxito
                header("Location: /Proyecto/php/tabla_tiendas.php?mensaje=tienda_actualizada");
                exit(); // Detiene la ejecución del script
            } else {
                // Redirige de vuelta a la página de tabla_tiendas.php con un mensaje de error
                header("Location: /Proyecto/php/tabla_tiendas.php?mensaje=error_actualizar&error=" . urlencode($stmt_update->error));
                exit(); // Detiene la ejecución del script
            }
            $stmt_update->close(); // Cierra el statement
        }
    }

    // --- Lógica para MOSTRAR EL FORMULARIO DE EDICIÓN (cuando se accede vía GET) ---
    // Se ejecuta si se proporciona un ID en la URL
    if (isset($_GET["id"])) {
        $id_tienda_to_edit = intval($_GET["id"]); // Convierte el ID a entero

        // Prepara la consulta para obtener los datos de la tienda a editar
        $stmt_select_tienda = $mysqli->prepare("SELECT id, nombre, ubicacion, direccion, telefono, horario_apertura, horario_cierre FROM tiendas WHERE id=?");
        
        if ($stmt_select_tienda === false) {
            $message = "Error al preparar la consulta de selección: " . $mysqli->error;
            $message_type = 'error';
        } else {
            $stmt_select_tienda->bind_param("i", $id_tienda_to_edit);
            $stmt_select_tienda->execute();
            $result_tienda = $stmt_select_tienda->get_result();

            // Si se encontró la tienda, guarda los datos
            if ($result_tienda && $result_tienda->num_rows > 0) {
                $tienda_data = $result_tienda->fetch_assoc();
            } else {
                $message = "Tienda no encontrada para editar.";
                $message_type = 'error';
            }
            $stmt_select_tienda->close(); // Cierra el statement
        }
    } else {
        // Si no se proporcionó un ID, muestra un mensaje de error
        $message = "No se ha especificado un ID de tienda para editar.";
        $message_type = 'error';
    }

} catch (Exception $e) {
    $message = "Error en el procesamiento: " . $e->getMessage();
    $message_type = 'error';
} finally {
    // Cierra la conexión a la base de datos si está abierta
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
    <title>Editar Tienda - Panel de Administración</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&family=Lobster&display=swap" rel="stylesheet">
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
            font-family: 'Lobster', cursive;
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
        input[type="tel"],
        input[type="time"] {
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
        <h1>Editar Tienda</h1>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($tienda_data): ?>
            <form method="POST" action="">
                <input type="hidden" name="id" value="<?= htmlspecialchars($tienda_data['id']) ?>">
                <input type="hidden" name="update_tienda" value="1">

                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($tienda_data['nombre']) ?>" required>

                <label for="ubicacion">Ubicación:</label>
                <input type="text" id="ubicacion" name="ubicacion" value="<?= htmlspecialchars($tienda_data['ubicacion']) ?>" required>

                <label for="direccion">Dirección:</label>
                <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($tienda_data['direccion']) ?>" required>

                <label for="telefono">Teléfono:</label>
                <input type="tel" id="telefono" name="telefono" value="<?= htmlspecialchars($tienda_data['telefono']) ?>" required>

                <label for="horario_apertura">Horario de Apertura:</label>
                <input type="time" id="horario_apertura" name="horario_apertura" value="<?= htmlspecialchars($tienda_data['horario_apertura']) ?>" required>

                <label for="horario_cierre">Horario de Cierre:</label>
                <input type="time" id="horario_cierre" name="horario_cierre" value="<?= htmlspecialchars($tienda_data['horario_cierre']) ?>" required>
                <br>

                <button type="submit">Guardar Cambios</button>
                <button type="button" class="cancel-button" onclick="window.location.href='/Proyecto/php/tabla_tiendas.php'">Cancelar</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

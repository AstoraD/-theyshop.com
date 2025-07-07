<?php
session_start();

// Configuración de la base de datos
$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "usuariostheyshop"; // Asegúrate de que este sea el nombre correcto de tu base de datos

$mysqli = null; // Inicializar $mysqli a null

if (isset($_GET['id'])) {
    $tienda_id = intval($_GET['id']);

    try {
        // Conexión a la base de datos
        $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, 3306);

        // Verificar la conexión
        if ($mysqli->connect_error) {
            throw new Exception("Error de Conexión a la Base de Datos: " . $mysqli->connect_error);
        }

        // Preparar la consulta para eliminar la tienda
        $stmt = $mysqli->prepare("DELETE FROM tiendas WHERE id = ?");

        if ($stmt === false) {
            throw new Exception("Error al preparar la consulta de eliminación: " . $mysqli->error);
        }

        $stmt->bind_param("i", $tienda_id);

        if ($stmt->execute()) {
            // Redirigir con mensaje de éxito
            header("Location: /Proyecto/php/tabla_tiendas.php?mensaje=tienda_eliminada");
            exit();
        } else {
            // Redirigir con mensaje de error
            header("Location: /Proyecto/php/tabla_tiendas.php?mensaje=error_eliminar&error=" . urlencode($stmt->error));
            exit();
        }

        $stmt->close();

    } catch (Exception $e) {
        // Redirigir con mensaje de error general
        header("Location: /Proyecto/php/tabla_tiendas.php?mensaje=error_eliminar&error=" . urlencode($e->getMessage()));
        exit();
    } finally {
        // Cierra la conexión a la base de datos si está abierta
        if (isset($mysqli) && $mysqli->ping()) {
            $mysqli->close();
        }
    }
} else {
    // Si no se proporcionó un ID de tienda, redirigir con un mensaje de error
    header("Location: /Proyecto/php/tabla_tiendas.php?mensaje=error_eliminar&error=" . urlencode("ID de tienda no proporcionado."));
    exit();
}
?>

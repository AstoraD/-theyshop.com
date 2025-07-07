<?php
session_start();

// Configuración de la base de datos
$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "usuariostheyshop"; // Asegúrate de que este sea el nombre correcto de tu base de datos

$mysqli = null; // Inicializar $mysqli a null

// Check if 'id' parameter is set in the URL
if (isset($_GET['id'])) {
    // Sanitize and validate the product ID
    $id_producto = intval($_GET['id']);

    try {
        // Database connection
        $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, 3306);

        // Check for connection errors
        if ($mysqli->connect_error) {
            throw new Exception("Error de Conexión a la Base de Datos: " . $mysqli->connect_error);
        }

        // Prepare the SQL query to delete the product
        // Use 'id_producto' as the primary key for products table
        $stmt = $mysqli->prepare("DELETE FROM productos WHERE id_producto = ?");

        // Check if the statement preparation was successful
        if ($stmt === false) {
            throw new Exception("Error al preparar la consulta de eliminación: " . $mysqli->error);
        }

        // Bind the product ID parameter
        $stmt->bind_param("i", $id_producto);

        // Execute the statement
        if ($stmt->execute()) {
            // Redirect with success message to tabla_productos.php
            header("Location: /Proyecto/php/tabla_productos.php?mensaje=producto_eliminado");
            exit();
        } else {
            // Redirect with error message if execution fails
            header("Location: /Proyecto/php/tabla_productos.php?mensaje=error_eliminar&error=" . urlencode($stmt->error));
            exit();
        }

        // Close the statement
        $stmt->close();

    } catch (Exception $e) {
        // Catch any exceptions during database operations and redirect with an error
        header("Location: /Proyecto/php/tabla_productos.php?mensaje=error_eliminar&error=" . urlencode($e->getMessage()));
        exit();
    } finally {
        // Close the database connection if it's open
        if (isset($mysqli) && $mysqli->ping()) {
            $mysqli->close();
        }
    }
} else {
    // If no product ID is provided, redirect with an error message
    header("Location: /Proyecto/php/tabla_productos.php?mensaje=error_eliminar&error=" . urlencode("ID de producto no proporcionado."));
    exit();
}
?>
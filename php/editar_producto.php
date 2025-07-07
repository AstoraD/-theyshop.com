<?php
session_start(); 
$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "usuariostheyshop"; 

$mysqli = null; // Inicializar $mysqli a null
$message = '';      // Mensajes de operación
$message_type = ''; // 'success' o 'error'

try {
    // Conexión a la base de datos
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // Verificar la conexión
    if ($mysqli->connect_error) {
        throw new Exception("Error de Conexión a la Base de Datos: " . $mysqli->connect_error);
    }

    $product_data = null;

   
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_product"])) {
        $id_producto = intval($_POST["id_producto"]);
        $nombre_producto = $_POST["nombre_producto"] ?? '';
        $descripcion = $_POST["descripcion"] ?? '';
        $precio = $_POST["precio"] ?? '';
        $stock = $_POST["stock"] ?? '';
        $id_tienda = $_POST["id_tienda"] ?? '';
        $ruta_imagen_producto = $_POST["ruta_imagen_producto"] ?? '';

        // Validación básica de campos
        if (empty($nombre_producto) || empty($descripcion) || !is_numeric($precio) || $precio < 0 || !is_numeric($stock) || $stock < 0 || !is_numeric($id_tienda) || $id_tienda < 0) {
            $message = "Todos los campos obligatorios deben ser válidos.";
            $message_type = 'error';
        } else {
            // Prepara la consulta SQL para actualizar el registro
            $stmt_update = $mysqli->prepare("UPDATE productos SET nombre_producto=?, descripcion=?, precio=?, stock=?, id_tienda=?, ruta_imagen_producto=? WHERE id_producto=?");
            
            if ($stmt_update === false) {
                // Redirige con mensaje de error si la preparación falla
                header("Location: /Proyecto/php/tabla_productos.php?mensaje=error_actualizar&error=" . urlencode($mysqli->error)); // Redirección actualizada
                exit();
            } else {
                // Vincula los parámetros a la consulta
                $stmt_update->bind_param("ssdiisi",
                    $nombre_producto,
                    $descripcion,
                    $precio,
                    $stock,
                    $id_tienda,
                    $ruta_imagen_producto,
                    $id_producto
                );

                // Ejecuta la consulta
                if ($stmt_update->execute()) {
                    // Redirige de vuelta a la página de tabla_productos.php con un mensaje de éxito
                    header("Location: /Proyecto/php/tabla_productos.php?mensaje=producto_actualizado"); // Redirección actualizada
                    exit(); // Detiene la ejecución del script
                } else {
                    // Redirige de vuelta a la página de tabla_productos.php con un mensaje de error
                    header("Location: /Proyecto/php/tabla_productos.php?mensaje=error_actualizar&error=" . urlencode($stmt_update->error)); // Redirección actualizada
                    exit(); // Detiene la ejecución del script
                }
                $stmt_update->close(); // Cierra el statement
            }
        }
    }

    // --- Lógica para MOSTRAR EL FORMULARIO DE EDICIÓN (cuando se accede vía GET) ---
    // Se ejecuta si se proporciona un ID en la URL
    if (isset($_GET["id"])) {
        $id_producto_to_edit = intval($_GET["id"]); // Convierte el ID a entero

        // Prepara la consulta para obtener los datos del producto a editar
        $stmt_select_product = $mysqli->prepare("SELECT id_producto, nombre_producto, descripcion, precio, stock, id_tienda, ruta_imagen_producto FROM productos WHERE id_producto=?");
        
        if ($stmt_select_product === false) {
            $message = "Error al preparar la consulta de selección: " . $mysqli->error;
            $message_type = 'error';
        } else {
            $stmt_select_product->bind_param("i", $id_producto_to_edit);
            $stmt_select_product->execute();
            $result_product = $stmt_select_product->get_result();

            // Si se encontró el producto, guarda los datos
            if ($result_product && $result_product->num_rows > 0) {
                $product_data = $result_product->fetch_assoc();
            } else {
                $message = "Producto no encontrado para editar.";
                $message_type = 'error';
            }
            $stmt_select_product->close(); // Cierra el statement
        }
    } else {
        // Si no se proporcionó un ID, muestra un mensaje de error
        $message = "No se ha especificado un ID de producto para editar.";
        $message_type = 'error';
    }

} catch (Exception $e) {
    $message = "Error en el procesamiento: " . $e->getMessage();
    $message_type = 'error';
} finally {
    // Cierra la conexión a la base de datos si está abierta
    if ($mysqli && $mysqli->ping()) {
        $mysqli->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - Panel de Administración</title>
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
        input[type="number"],
        textarea { /* Added textarea for description */
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

        textarea {
            resize: vertical; /* Allow vertical resizing */
            min-height: 80px; /* Minimum height for description */
        }

        .product-image-preview {
            width: 100px; /* Larger preview for product image */
            height: 100px;
            border-radius: 8px; /* Slightly rounded corners */
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
            background-color: #FFCDD2; /* Rojo claro para el botón de cancelar */
            color: #B71C1C; /* Rojo oscuro */
        }

        .cancel-button:hover {
            background-color: #EF9A9A;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <h1>Editar Producto</h1>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($product_data): ?>
            <form method="POST" action="">
                <input type="hidden" name="id_producto" value="<?= htmlspecialchars($product_data['id_producto']) ?>">
                <input type="hidden" name="update_product" value="1">

                <?php if (!empty($product_data['ruta_imagen_producto'])): ?>
                    <img src="<?= htmlspecialchars($product_data['ruta_imagen_producto']) ?>" alt="Imagen del Producto" class="product-image-preview" onerror="this.onerror=null;this.src='https://placehold.co/100x100/B0E0B0/FFFFFF?text=No+Img';">
                <?php else: ?>
                    <img src="https://placehold.co/100x100/B0E0B0/FFFFFF?text=No+Img" alt="Sin Imagen" class="product-image-preview">
                <?php endif; ?>
                <br>

                <label for="nombre_producto">Nombre del Producto:</label>
                <input type="text" id="nombre_producto" name="nombre_producto" value="<?= htmlspecialchars($product_data['nombre_producto']) ?>" required>

                <label for="descripcion">Descripción:</label>
                <textarea id="descripcion" name="descripcion" required><?= htmlspecialchars($product_data['descripcion']) ?></textarea>

                <label for="precio">Precio (MXN):</label>
                <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?= htmlspecialchars($product_data['precio']) ?>" required>

                <label for="stock">Stock:</label>
                <input type="number" id="stock" name="stock" min="0" value="<?= htmlspecialchars($product_data['stock']) ?>" required>

                <label for="id_tienda">ID de Tienda:</label>
                <input type="number" id="id_tienda" name="id_tienda" min="0" value="<?= htmlspecialchars($product_data['id_tienda']) ?>" required>

                <label for="ruta_imagen_producto">Ruta de Imagen (URL/Path):</label>
                <input type="text" id="ruta_imagen_producto" name="ruta_imagen_producto" value="<?= htmlspecialchars($product_data['ruta_imagen_producto']) ?>">
                
                <br>

                <button type="submit">Guardar Cambios</button>
                <button type="button" class="cancel-button" onclick="window.location.href='/Proyecto/php/tabla_productos.php'">Cancelar</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

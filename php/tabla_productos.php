<?php
session_start();

$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "usuariostheyshop";

$mysqli = null;
$message = '';
$message_type = '';

try {
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($mysqli->connect_error) {
        throw new Exception("Error de Conexión a la Base de Datos: " . $mysqli->connect_error);
    }

    if (isset($_GET['mensaje'])) {
        if ($_GET['mensaje'] === 'producto_eliminado') {
            $message = "Producto eliminado correctamente.";
            $message_type = 'success';
        } elseif ($_GET['mensaje'] === 'error_eliminar') {
            $message = "Error al eliminar el producto: " . htmlspecialchars($_GET['error'] ?? 'Desconocido');
            $message_type = 'error';
        } elseif ($_GET['mensaje'] === 'producto_actualizado') {
            $message = "Producto actualizado correctamente.";
            $message_type = 'success';
        } elseif ($_GET['mensaje'] === 'error_actualizar') {
            $message = "Error al actualizar el producto: " . htmlspecialchars($_GET['error'] ?? 'Desconocido');
            $message_type = 'error';
        } elseif ($_GET['mensaje'] === 'producto_agregado') {
            $message = "Producto agregado correctamente.";
            $message_type = 'success';
        } elseif ($_GET['mensaje'] === 'error_agregar') {
            $message = "Error al agregar el producto: " . htmlspecialchars($_GET['error'] ?? 'Desconocido');
            $message_type = 'error';
        }
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_new_product"])) {
        $nombre_producto = $_POST["nombre_producto"] ?? '';
        $descripcion = $_POST["descripcion"] ?? '';
        $precio = $_POST["precio"] ?? '';
        $stock = $_POST["stock"] ?? '';
        $id_tienda = $_POST["id_tienda"] ?? '';
        $ruta_imagen_producto = '';

        if (isset($_FILES['imagen_producto_file']) && $_FILES['imagen_producto_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['imagen_producto_file']['tmp_name'];
            $file_name = $_FILES['imagen_producto_file']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($file_ext, $allowed_ext)) {
                header("Location: /Proyecto/php/tabla_productos.php?mensaje=error_agregar&error=" . urlencode("Tipo de archivo no permitido. Solo se permiten JPG, JPEG, PNG, GIF."));
                exit();
            }

            $upload_dir = __DIR__ . '/../img/productos/';
            
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    header("Location: /Proyecto/php/tabla_productos.php?mensaje=error_agregar&error=" . urlencode("No se pudo crear el directorio de subida."));
                    exit();
                }
            }

            $new_file_name = uniqid('product_', true) . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_name, $destination)) {
                $ruta_imagen_producto = '/Proyecto/img/productos/' . $new_file_name;
            } else {
                header("Location: /Proyecto/php/tabla_productos.php?mensaje=error_agregar&error=" . urlencode("Error al mover el archivo subido. Verifique permisos de la carpeta 'productos'."));
                exit();
            }
        }

        if (empty($nombre_producto) || empty($descripcion) || !is_numeric($precio) || $precio < 0 || !is_numeric($stock) || $stock < 0 || !is_numeric($id_tienda) || $id_tienda < 0) {
            header("Location: /Proyecto/php/tabla_productos.php?mensaje=error_agregar&error=" . urlencode("Todos los campos obligatorios para el nuevo producto deben ser válidos."));
            exit();
        } else {
            $stmt_insert = $mysqli->prepare("INSERT INTO productos (nombre_producto, descripcion, precio, stock, id_tienda, ruta_imagen_producto) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($stmt_insert === false) {
                header("Location: /Proyecto/php/tabla_productos.php?mensaje=error_agregar&error=" . urlencode($mysqli->error));
                exit();
            } else {
                $stmt_insert->bind_param("ssdiis",
                    $nombre_producto,
                    $descripcion,
                    $precio,
                    $stock,
                    $id_tienda,
                    $ruta_imagen_producto
                );

                if ($stmt_insert->execute()) {
                    header("Location: /Proyecto/php/tabla_productos.php?mensaje=producto_agregado");
                    exit();
                } else {
                    header("Location: /Proyecto/php/tabla_productos.php?mensaje=error_agregar&error=" . urlencode($stmt_insert->error));
                    exit();
                }
                $stmt_insert->close();
            }
        }
    }

    $search_id = '';
    $productos = [];

    if (isset($_GET['search_id']) && $_GET['search_id'] !== '') {
        $search_id = intval($_GET['search_id']);
        $query_sql = "SELECT id_producto, nombre_producto, descripcion, precio, stock, id_tienda, ruta_imagen_producto, fecha_creacion FROM productos WHERE id_producto = ?";
        $stmt = $mysqli->prepare($query_sql);

        if ($stmt) {
            $stmt->bind_param("i", $search_id);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $message = "Error al preparar la consulta de búsqueda: " . $mysqli->error;
            $message_type = 'error';
            $result = false;
        }
    } else {
        $query_sql = "SELECT id_producto, nombre_producto, descripcion, precio, stock, id_tienda, ruta_imagen_producto, fecha_creacion FROM productos ORDER BY id_producto DESC";
        $result = $mysqli->query($query_sql);
    }

    if (isset($result) && $result) {
        while ($row = $result->fetch_assoc()) {
            $productos[] = $row;
        }
        $result->free();
        if (isset($stmt)) {
            $stmt->close();
        }
    } else {
        if (empty($message)) {
            $message = "Error al obtener los productos: " . $mysqli->error;
            $message_type = 'error';
        }
    }

} catch (Exception $e) {
    $message = "Error en el procesamiento: " . $e->getMessage();
    $message_type = 'error';
} finally {
    if ($mysqli && $mysqli->ping()) {
        $mysqli->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Productos</title>
    <link rel="icon" href="/Proyecto/img/logotheyshop.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lobster&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background-color: #E6FFE6;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
            color: #333333;
        }

        .container {
            background-color: #FFFFFF;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1000px;
            border: 2px solid #A7D9EB;
            text-align: center;
        }

        h2 {
            font-family: 'Lobster', cursive;
            color: #66BB6A;
            margin-bottom: 25px;
            font-size: 2.8em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            font-weight: 400;
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
            background-color: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }

        .message.error {
            background-color: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }

        .top-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1000px;
            margin-bottom: 10px;
            padding-left: 30px;
            padding-right: 30px;
            box-sizing: border-box;
        }

        .search-form {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-form label {
            font-weight: 600;
            color: #4CAF50;
            margin-right: 5px;
        }

        .search-form input[type="number"] {
            padding: 10px;
            border: 1px solid #B0E0B0;
            border-radius: 8px;
            font-family: 'Quicksand', sans-serif;
            font-size: 1em;
            box-sizing: border-box;
            width: 200px;
        }

        .search-form button, .button {
            background-color: #A5D6A7;
            color: #1B5E20;
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-decoration: none;
            display: inline-block;
        }

        .search-form button:hover, .button:hover {
            background-color: #81C784;
            transform: translateY(-1px);
        }

        .search-form button[type="button"] {
            background-color: #ADD8E6;
            color: #2196F3;
        }

        .search-form button[type="button"]:hover {
            background-color: #87CEEB;
        }

        .add-product-form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            overflow: hidden;
            background-color: #F5FFF5;
        }

        .add-product-form-table th,
        .add-product-form-table td {
            border: 1px solid #D0F0C0;
            padding: 8px;
            text-align: left;
        }

        .add-product-form-table th {
            background-color: #C8E6C9;
            color: #1B5E20;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        .add-product-form-table input[type="text"],
        .add-product-form-table input[type="number"],
        .add-product-form-table textarea {
            width: calc(100% - 16px);
            padding: 6px;
            margin-bottom: 0;
            border: 1px solid #B0E0B0;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 0.9em;
            font-family: 'Quicksand', sans-serif;
            color: #333;
        }

        .add-product-form-table textarea {
            resize: vertical;
            min-height: 50px;
        }

        .add-product-form-table .file-input-wrapper {
            position: relative;
            display: block;
            width: 100%;
            margin-bottom: 0;
            text-align: left;
        }

        .add-product-form-table .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .add-product-form-table .file-input-wrapper .file-upload-button {
            background-color: #A5D6A7;
            color: #1B5E20;
            padding: 6px 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8em;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: inline-block;
            width: calc(100% - 16px);
            text-align: center;
            box-sizing: border-box;
        }

        .add-product-form-table .file-input-wrapper .file-upload-button:hover {
            background-color: #81C784;
            transform: translateY(-1px);
        }

        .add-product-form-table .file-input-wrapper .file-name {
            display: block;
            margin-top: 3px;
            font-size: 0.8em;
            color: #666;
            text-align: left;
        }

        .add-product-form-table .form-actions-cell {
            text-align: center;
            padding-top: 10px;
        }

        .add-product-form-table button[type="submit"],
        .add-product-form-table .cancel-button {
            background-color: #A5D6A7;
            color: #1B5E20;
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 600;
            margin: 5px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            display: inline-block;
        }

        .add-product-form-table .cancel-button {
            background-color: #FFCDD2;
            color: #B71C1C;
        }
        .add-product-form-table .cancel-button:hover {
            background-color: #EF9A9A;
        }


        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
            background-color: #F5FFF5;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #E0E0E0;
            border-right: 1px solid #E0E0E0;
            word-wrap: break-word;
        }

        th:last-child, td:last-child {
            border-right: none;
        }

        th {
            background-color: #A5D6A7;
            color: #1B5E20;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        tr:nth-child(even) {
            background-color: #F8FFF8;
        }

        tr:hover {
            background-color: #E6FFE6;
            transition: background-color 0.2s ease;
        }

        th:first-child { border-top-left-radius: 12px; }
        th:last-child { border-top-right-radius: 12px; }
        tr:last-child td:first-child { border-bottom-left-radius: 12px; }
        tr:last-child td:last-child { border-bottom-right-radius: 12px; }

        .product-image {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #D0F0C0;
        }

        .action-buttons a {
            background-color: #ADD8E6;
            color: #2196F3;
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

        .action-buttons a.delete-button {
            background-color: #FFCDD2;
            color: #B71C1C;
        }

        .action-buttons a:hover {
            background-color: #87CEEB;
            transform: translateY(-1px);
        }
        .action-buttons a.delete-button:hover {
            background-color: #EF9A9A;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            h2 {
                font-size: 2em;
            }
            .top-controls {
                flex-direction: column;
                align-items: flex-start;
                padding-left: 0;
                padding-right: 0;
                margin-bottom: 20px;
            }
            .top-controls .button {
                width: 100%;
                margin-bottom: 10px;
                text-align: center;
            }
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }
            .search-form input[type="number"] {
                width: 100%;
            }
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

            .add-product-form-table th,
            .add-product-form-table td {
                padding: 8px;
            }
            .add-product-form-table input[type="text"],
            .add-product-form-table input[type="number"],
            .add-product-form-table textarea {
                width: calc(100% - 16px);
                padding: 6px;
            }
            .add-product-form-table .file-input-wrapper .file-upload-button {
                width: calc(100% - 16px);
                padding: 6px 10px;
            }
            .add-product-form-table .form-actions-cell {
                flex-direction: column;
                align-items: center;
                padding-left: 0;
            }
            .add-product-form-table button,
            .add-product-form-table .cancel-button {
                width: calc(100% - 20px);
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-controls">
            <a href="/Proyecto/php/panel_administrador.php" class="button">Regresar</a>
        </div>
        <h2>Tabla de Administración de productos</h2>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <h3 style="font-family: 'Quicksand', sans-serif; color: #388E3C; font-size: 1.5em; margin-bottom: 15px;">Agregar Nuevo Producto</h3>
        <form method="POST" action="" enctype="multipart/form-data" class="add-product-form-table">
            <input type="hidden" name="add_new_product" value="1">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>ID Tienda</th>
                        <th>Imagen</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td data-label="Nombre">
                            <input type="text" id="new_nombre_producto" name="nombre_producto" placeholder="Nombre" required>
                        </td>
                        <td data-label="Descripción">
                            <textarea id="new_descripcion" name="descripcion" placeholder="Descripción" required></textarea>
                        </td>
                        <td data-label="Precio">
                            <input type="number" id="new_precio" name="precio" step="0.01" min="0" placeholder="Precio" required>
                        </td>
                        <td data-label="Stock">
                            <input type="number" id="new_stock" name="stock" min="0" placeholder="Stock" required>
                        </td>
                        <td data-label="ID Tienda">
                            <input type="number" id="new_id_tienda" name="id_tienda" min="0" placeholder="ID Tienda" required>
                        </td>
                        <td data-label="Imagen">
                            <div class="file-input-wrapper">
                                <input type="file" id="imagen_producto_file" name="imagen_producto_file" accept="image/*">
                                <label for="imagen_producto_file" class="file-upload-button">Seleccionar archivo</label>
                                <span class="file-name" id="selectedFileName">Sin archivos</span>
                            </div>
                        </td>
                        <td data-label="Acciones" class="form-actions-cell">
                            <button type="submit">Guardar</button>
                            
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>

        <form method="GET" action="" class="search-form">
            <label for="search_id">Buscar por ID de Producto:</label>
            <input type="number" id="search_id" name="search_id" value="<?= htmlspecialchars($_GET['search_id'] ?? '') ?>" placeholder="Introduce el ID del producto">
            <button type="submit">Buscar</button>
            <?php if (!empty($_GET['search_id'])): ?>
                <button type="button" onclick="window.location.href='<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>'">Limpiar búsqueda</button>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>ID Tienda</th>
                    <th>Fecha Creación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($productos)): ?>
                    <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td data-label="ID"><?= htmlspecialchars($producto['id_producto']) ?></td>
                            <td data-label="Imagen">
                                <?php
                                $image_path_on_server = $_SERVER['DOCUMENT_ROOT'] . $producto['ruta_imagen_producto'];
                                
                                if (!empty($producto['ruta_imagen_producto']) && file_exists($image_path_on_server)):
                                ?>
                                    <img src="<?= htmlspecialchars($producto['ruta_imagen_producto']) ?>" alt="<?= htmlspecialchars($producto['nombre_producto']) ?>" class="product-image">
                                <?php else: ?>
                                    <img src="https://placehold.co/70x70/CCCCCC/FFFFFF?text=No+Img" alt="No Image" class="product-image">
                                <?php endif; ?>
                            </td>
                            <td data-label="Nombre"><?= htmlspecialchars($producto['nombre_producto']) ?></td>
                            <td data-label="Descripción"><?= htmlspecialchars($producto['descripcion']) ?></td>
                            <td data-label="Precio">$<?= htmlspecialchars(number_format($producto['precio'], 2)) ?></td>
                            <td data-label="Stock"><?= htmlspecialchars($producto['stock']) ?></td>
                            <td data-label="ID Tienda"><?= htmlspecialchars($producto['id_tienda']) ?></td>
                            <td data-label="Fecha Creación"><?= htmlspecialchars($producto['fecha_creacion']) ?></td>
                            <td data-label="Acciones" class="action-buttons">
                                <a href="/Proyecto/php/editar_producto.php?id=<?= htmlspecialchars($producto['id_producto']) ?>">Editar</a>
                                <a href="/Proyecto/php/eliminar_producto.php?id=<?= htmlspecialchars($producto['id_producto']) ?>" class="delete-button" onclick="return confirm('¿Estás seguro de que quieres eliminar el producto <?= htmlspecialchars($producto['nombre_producto']) ?>?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">No se encontraron productos registrados.
                            <?php if (!empty($_GET['search_id'])): ?>
                                (ID de búsqueda: <?= htmlspecialchars($_GET['search_id']) ?>)
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        document.getElementById('imagen_producto_file').addEventListener('change', function() {
            var fileName = this.files[0] ? this.files[0].name : 'Sin archivos';
            document.getElementById('selectedFileName').textContent = fileName;
        });
    </script>
</body>
</html>

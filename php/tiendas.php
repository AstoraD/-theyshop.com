<?php
session_start();

$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "usuariostheyshop";

$mysqli = null;
$message = '';
$message_type = '';
$stores = []; // Array to store stores and their products

$profile_pic_src = 'https://placehold.co/40x40/B0E0B0/FFFFFF?text=User';

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && !empty($_SESSION['profile_picture_path'])) {
    $session_image_path = $_SESSION['profile_picture_path'];

    if (filter_var($session_image_path, FILTER_VALIDATE_URL)) {
        $profile_pic_src = htmlspecialchars($session_image_path);
    } else {
        $project_root_server_path = dirname(dirname(__FILE__));
        $server_file_path = $project_root_server_path . '/' . $session_image_path;
        $project_folder_name = basename($project_root_server_path);
        $web_path_for_image = '/' . $project_folder_name . '/' . $session_image_path;

        if (file_exists($server_file_path)) {
            $profile_pic_src = htmlspecialchars($web_path_for_image);
        } else {
            // Placeholder remains if file doesn't exist
        }
    }
}

try {
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, 3306);

    if ($mysqli->connect_error) {
        throw new Exception("Error de Conexión a la Base de Datos: " . $mysqli->connect_error);
    }

    // Fetch all stores using the provided column names
    $query_stores = "SELECT id, nombre, ubicacion, direccion, telefono, horario_apertura, horario_cierre FROM tiendas ORDER BY nombre ASC";
    $stmt_stores = $mysqli->prepare($query_stores);
    if ($stmt_stores === false) {
        throw new Exception("Error al preparar la consulta de tiendas: " . $mysqli->error);
    }
    $stmt_stores->execute();
    $result_stores = $stmt_stores->get_result();

    while ($row_store = $result_stores->fetch_assoc()) {
        $stores[$row_store['id']] = $row_store;
        $stores[$row_store['id']]['products'] = []; // Initialize products array for each store
    }
    $stmt_stores->close();

    // Fetch all products and associate them with their stores
    $search_query = $_GET['search_query'] ?? '';
    $query_products_sql = "SELECT id_producto, nombre_producto, descripcion, precio, stock, ruta_imagen_producto, id_tienda FROM productos";
    $params = [];
    $types = '';

    if (!empty($search_query)) {
        $query_products_sql .= " WHERE nombre_producto LIKE ? OR descripcion LIKE ?";
        $search_param = '%' . $search_query . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ss';
    }
    $query_products_sql .= " ORDER BY id_producto DESC";

    $stmt_products = $mysqli->prepare($query_products_sql);
    if ($stmt_products === false) {
        throw new Exception("Error al preparar la consulta de productos: " . $mysqli->error);
    }

    if (!empty($params)) {
        $stmt_products->bind_param($types, ...$params);
    }
    
    $stmt_products->execute();
    $result_products = $stmt_products->get_result();

    if ($result_products) {
        while ($row_product = $result_products->fetch_assoc()) {
            if (isset($stores[$row_product['id_tienda']])) {
                $stores[$row_product['id_tienda']]['products'][] = $row_product;
            }
        }
        $result_products->free();
    } else {
        $message = "Error al obtener los productos: " . $mysqli->error;
        $message_type = 'error';
    }
    $stmt_products->close();

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
    <title>THEY SHOP - Tiendas y Productos</title>
    <link rel="icon" href="/Proyecto/img/logotheyshop.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background-color: #E6FFE6; /* Fondo verde menta claro */
            margin: 0;
            padding: 0;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .header {
            background-color: #FFFFFF; /* Encabezado blanco */
            padding: 15px 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header .logo {
            font-family: 'Lobster', cursive;
            font-size: 2.2em;
            color: #66BB6A; /* Verde vibrante */
            text-decoration: none;
            font-weight: 400;
            display: flex;
            align-items: center;
        }

        .header .logo img {
            height: 30px;
            margin-right: 10px;
        }

        .header .nav-links {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .header .nav-links a {
            text-decoration: none;
            color: #555;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .header .nav-links a:hover {
            color: #66BB6A; /* Verde vibrante al pasar el ratón */
        }

        .header .search-bar {
            display: flex;
            align-items: center;
            flex-grow: 1;
            max-width: 400px;
            background-color: #F5F5F5; /* Barra de búsqueda gris claro */
            border-radius: 25px;
            padding: 8px 15px;
            border: 1px solid #B0E0B0; /* Borde verde claro */
        }

        .header .search-bar select {
            border: none;
            background: transparent;
            padding-right: 10px;
            border-right: 1px solid #CCC; /* Separador gris */
            margin-right: 10px;
            font-family: 'Quicksand', sans-serif;
            font-size: 0.9em;
            color: #555;
            outline: none;
        }

        .header .search-bar input[type="text"] {
            border: none;
            background: transparent;
            flex-grow: 1;
            padding: 0 10px;
            font-family: 'Quicksand', sans-serif;
            font-size: 0.9em;
            color: #333;
            outline: none;
        }
        .header .search-bar input[type="text"]::placeholder {
            color: #888; /* Color para el placeholder */
        }

        .header .search-bar button {
            background: none;
            border: none;
            color: #66BB6A; /* Icono de búsqueda verde vibrante */
            cursor: pointer;
            font-size: 1.1em;
            padding: 5px;
            transition: color 0.3s ease;
        }

        .header .search-bar button:hover {
            color: #4CAF50; /* Verde más oscuro al pasar el ratón */
        }

        .header .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header .user-info .phone-number {
            font-weight: 600;
            color: #555;
        }

        .header .user-info .user-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #66BB6A; /* Borde verde vibrante */
        }
        
        /* Dropdown para el perfil de usuario */
        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .user-dropdown-content {
            display: none;
            position: absolute;
            background-color: #F9F9F9; /* Fondo de menú desplegable claro */
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 8px;
            right: 0; /* Alinea el dropdown a la derecha del icono */
            top: 50px; /* Posiciona debajo del icono */
            border: 1px solid #D0F0C0; /* Borde verde claro */
        }

        .user-dropdown-content a {
            color: black !important; /* Sobrescribe el color de nav-links */
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
            font-weight: 500;
            transition: background-color 0.2s ease;
            white-space: nowrap;
        }

        .user-dropdown-content a:hover {
            background-color: #E6FCE6; /* Verde claro al pasar el ratón */
            color: #66BB6A !important;
        }

        .user-dropdown:hover .user-dropdown-content {
            display: block;
        }


        .main-content {
            flex-grow: 1;
            padding: 30px;
            max-width: 1200px;
            width: 100%;
            margin: 20px auto;
            background-color: #FFFFFF; /* Contenido principal blanco */
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            border: 2px solid #A7D9EB; /* Borde azul claro */
        }

        .main-content h1 {
            font-family: 'Quicksand', sans-serif;
            font-size: 2.2em;
            color: #388E3C; /* Título verde oscuro */
            margin-bottom: 30px;
            text-align: left;
            border-bottom: 2px solid #E6FFE6; /* Borde verde claro */
            padding-bottom: 10px;
        }

        .message {
            padding: 10px;
            margin: 20px auto;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            width: 90%;
            max-width: 800px;
        }

        .message.error {
            background-color: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }
        .message.success {
            background-color: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }

        .store-section {
            margin-top: 40px;
        }

        .store-card {
            background-color: #F8FFF8; /* Fondo de tarjeta verde muy claro */
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 30px;
            border: 1px solid #D0F0C0; /* Borde verde claro */
        }

        .store-header {
            display: flex;
            align-items: center;
            padding: 20px;
            background-color: #E6FCE6; /* Fondo de encabezado de tienda verde claro */
            border-bottom: 1px solid #D0F0C0; /* Borde verde claro */
        }

        .store-logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #66BB6A; /* Borde verde vibrante */
        }

        .store-info h2 {
            font-size: 1.8em;
            color: #388E3C; /* Título de tienda verde oscuro */
            margin: 0;
        }

        .store-info p {
            font-size: 0.9em;
            color: #555;
            margin: 5px 0 0;
        }
        .store-info p .fas {
            color: #66BB6A; /* Iconos verdes */
            margin-right: 5px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .product-card {
            background-color: #FFFFFF; /* Fondo de tarjeta de producto blanco */
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            text-align: left;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid #E6FFE6; /* Borde verde muy claro */
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .product-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-bottom: 1px solid #F0FFF0; /* Borde inferior de imagen verde muy claro */
        }

        .product-card-content {
            padding: 15px;
        }

        .product-card-content h4 {
            font-size: 1.1em;
            margin-top: 0;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
        }

        .product-card-content p {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 5px;
            line-height: 1.3;
        }

        .product-card-content .price {
            font-size: 1.2em;
            font-weight: 700;
            color: #4CAF50; /* Verde */
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px 20px;
            }
            .header .search-bar {
                width: 100%;
                max-width: none;
                order: 1;
            }
            .header .nav-links {
                width: 100%;
                justify-content: center;
                margin-top: 10px;
                order: 2;
            }
            .header .user-info {
                width: 100%;
                justify-content: flex-end;
                margin-top: 10px;
                order: 0;
            }
            .main-content {
                padding: 20px;
                margin: 15px auto;
            }
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
            }
            .store-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px;
            }
            .store-logo {
                margin-bottom: 10px;
            }
        }

        @media (max-width: 480px) {
            .header .nav-links {
                flex-direction: column;
                align-items: center;
            }
            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="/Proyecto/Pagina_Principal.html" class="logo">
            <img src="/Proyecto/img/logotheyshop.png" alt="THEYSHOP Logo"></a>
        <form action="" method="GET" class="search-bar">
            <select name="category">
                <option value="all"></option>
                
            </select>
            <input type="text" name="search_query" placeholder="Buscar" value="<?= htmlspecialchars($search_query) ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
        <div class="nav-links">
            <a href="/Proyecto/php/panel_usuario.php">Inicio</a>
            <a href="/Proyecto/php/productos.php">Productos</a>
            <a href="/Proyecto/php/tiendas.php">Tiendas</a>
            <a href="#">Tickets</a>
            <a href="#">Recompensas</a>
            <a href="/Proyecto/php/blog.php">Comentarios</a>
            <a href="#">Lista de Compras</a>
            <a href="/Proyecto/01.%20Mary%20Sam.html">Nosotros</a>
        </div>
        <div class="user-info">
            
            
            <div class="user-dropdown">
                <?php
                $profile_pic_src_header = 'https://placehold.co/40x40/B0E0B0/FFFFFF?text=User';

                if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && !empty($_SESSION['profile_picture_path'])) {
                    $session_image_path_header = $_SESSION['profile_picture_path'];

                    if (filter_var($session_image_path_header, FILTER_VALIDATE_URL)) {
                        $profile_pic_src_header = htmlspecialchars($session_image_path_header);
                    } else {
                        $project_root_server_path_header = dirname(dirname(__FILE__));
                        $server_file_path_header = $project_root_server_path_header . '/' . $session_image_path_header;

                        $project_folder_name_header = basename($project_root_server_path_header);
                        $web_path_for_image_header = '/' . $project_folder_name_header . '/' . $session_image_path_header;

                        if (file_exists($server_file_path_header)) {
                            $profile_pic_src_header = htmlspecialchars($web_path_for_image_header);
                        }
                    }
                }
                ?>
                <img src="<?= $profile_pic_src_header ?>" alt="Admin Profile" class="user-icon" onerror="this.onerror=null; this.src='https://placehold.co/40x40/66BB6A/FFFFFF?text=AD';">
                <div class="user-dropdown-content">
                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                        <a href="#">Hola, <?= htmlspecialchars($_SESSION['nombre_usuario']) ?></a>
                        <a href="/Proyecto/php/panel_perfil_usuario.php">Mi Perfil</a>
                        <a href="/Proyecto/php/cierre_sesión.php">Cerrar Sesión</a>
                    <?php else: ?>
                        <a href="/Proyecto/php/iniciar_sesión.php">Iniciar Sesión</a>
                        <a href="/Proyecto/php/crear_cuenta.php">Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <h1>Nuestras Tiendas y Productos</h1>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($stores)): ?>
            <?php foreach ($stores as $store): ?>
                <div class="store-card">
                    <div class="store-header">
                        <?php
                        // Placeholder for store logo. If you add a 'logo_path' column to 'tiendas' later,
                        // you can uncomment and adapt the logic below to fetch the actual logo.
                        $store_logo_src = 'https://placehold.co/60x60/B0E0B0/FFFFFF?text=Tienda';
                        /*
                        if (!empty($store['logo_path'])) {
                            $store_logo_full_path = dirname(dirname(__FILE__)) . '/' . $store['logo_path'];
                            $project_folder_name = basename(dirname(dirname(__FILE__)));
                            $store_logo_web_path = '/' . $project_folder_name . '/' . $store['logo_path'];
                            if (file_exists($store_logo_full_path)) {
                                $store_logo_src = htmlspecialchars($store_logo_web_path);
                            }
                        }
                        */
                        ?>
                        <img src="<?= $store_logo_src ?>" alt="Logo de <?= htmlspecialchars($store['nombre']) ?>" class="store-logo" onerror="this.onerror=null; this.src='https://placehold.co/60x60/B0E0B0/FFFFFF?text=Tienda';">
                        <div class="store-info">
                            <h2><?= htmlspecialchars($store['nombre']) ?></h2>
                            <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($store['direccion']) ?> (<?= htmlspecialchars($store['ubicacion']) ?>)</p>
                            <p><i class="fas fa-phone"></i> <?= htmlspecialchars($store['telefono']) ?></p>
                            <p><i class="fas fa-clock"></i> Horario: <?= htmlspecialchars($store['horario_apertura']) ?> - <?= htmlspecialchars($store['horario_cierre']) ?></p>
                        </div>
                    </div>
                    <?php if (!empty($store['products'])): ?>
                        <div class="products-grid">
                            <?php foreach ($store['products'] as $product): ?>
                                <div class="product-card">
                                    <?php
                                    $product_image_src = 'https://placehold.co/150x150/CCCCCC/FFFFFF?text=No+Img';
                                    if (!empty($product['ruta_imagen_producto'])) {
                                        $product_image_full_path = dirname(dirname(__FILE__)) . '/' . $product['ruta_imagen_producto'];
                                        $project_folder_name = basename(dirname(dirname(__FILE__)));
                                        $product_image_web_path = '/' . $project_folder_name . '/' . $product['ruta_imagen_producto'];
                                        if (file_exists($product_image_full_path)) {
                                            $product_image_src = htmlspecialchars($product_image_web_path);
                                        }
                                    }
                                    ?>
                                    <img src="<?= $product_image_src ?>" alt="<?= htmlspecialchars($product['nombre_producto']) ?>" onerror="this.onerror=null; this.src='https://placehold.co/150x150/CCCCCC/FFFFFF?text=No+Img';">
                                    <div class="product-card-content">
                                        <h4><?= htmlspecialchars($product['nombre_producto']) ?></h4>
                                        <p><?= htmlspecialchars($product['descripcion']) ?></p>
                                        <p class="price">$<?= htmlspecialchars(number_format($product['precio'], 2)) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; padding: 20px; color: #666;">No hay productos disponibles para esta tienda.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; margin-top: 50px; color: #666;">No se encontraron tiendas.</p>
        <?php endif; ?>
    </div>
</body>
</html>

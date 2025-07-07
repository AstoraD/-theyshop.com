<?php
session_start();

$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "usuariostheyshop";

$mysqli = null;
$message = '';
$message_type = '';
$productos = [];

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
            
        }
    }
}


try {
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, 3306);

    if ($mysqli->connect_error) {
        throw new Exception("Error de Conexión a la Base de Datos: " . $mysqli->connect_error);
    }

    $search_query = $_GET['search_query'] ?? '';

    
    $query_sql = "SELECT p.id_producto, p.nombre_producto, p.descripcion, p.precio, p.stock, p.ruta_imagen_producto, t.nombre AS nombre_tienda
                    FROM productos p
                    LEFT JOIN tiendas t ON p.id_tienda = t.id";

    $params = [];
    $types = '';

    if (!empty($search_query)) {
        $query_sql .= " WHERE p.nombre_producto LIKE ? OR p.descripcion LIKE ?";
        $search_param = '%' . $search_query . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ss';
    }

    $query_sql .= " ORDER BY p.id_producto DESC";

    $stmt = $mysqli->prepare($query_sql);

    if ($stmt === false) {
        throw new Exception("Error al preparar la consulta de productos: " . $mysqli->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $productos[] = $row;
        }
        $result->free();
    } else {
        $message = "Error al obtener los productos: " . $mysqli->error;
        $message_type = 'error';
    }

    $stmt->close();

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
    <title>THEY SHOP</title>
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

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
        }

        .product-card {
            background-color: #F8FFF8; /* Fondo de tarjeta verde muy claro */
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            text-align: left;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid #D0F0C0; /* Borde verde claro */
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.12);
        }

        .product-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 1px solid #E6FFE6;
        }

        .product-card-content {
            padding: 15px;
        }

        .product-card-content h3 {
            font-size: 1.2em;
            margin-top: 0;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        .product-card-content p {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
            line-height: 1.4;
        }

        .product-card-content .price {
            font-size: 1.3em;
            font-weight: 700;
            color: #4CAF50; /* Verde */
            margin-top: 10px;
            margin-bottom: 5px;
        }

        .product-card-content .origin {
            font-size: 0.85em;
            color: #999;
            margin-top: 5px;
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

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px 20px;
            }
            .header .search-bar {
                width: 100%;
                max-width: none;
                order: 1; /* Pone la barra de búsqueda debajo del logo */
            }
            .header .nav-links {
                width: 100%;
                justify-content: center;
                margin-top: 10px;
                order: 2; /* Pone los enlaces de navegación debajo de la barra de búsqueda */
            }
            .header .user-info {
                width: 100%;
                justify-content: flex-end; /* Alinea a la derecha */
                margin-top: 10px;
                order: 0; /* Mantiene la info de usuario arriba a la derecha */
            }
            .main-content {
                padding: 20px;
                margin: 15px auto;
            }
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 480px) {
            .header .nav-links {
                flex-direction: column;
                align-items: center;
            }
            .product-grid {
                grid-template-columns: 1fr; /* Una columna en pantallas muy pequeñas */
            }
            .product-card img {
                height: 150px;
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
        <h1>Explora Nuestros Productos</h1>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($productos)): ?>
            <div class="product-grid">
                <?php foreach ($productos as $producto): ?>
                    <div class="product-card">
                        <?php
                        $image_src = 'https://placehold.co/200x200/CCCCCC/FFFFFF?text=No+Img'; // Placeholder por defecto
                        if (!empty($producto['ruta_imagen_producto'])) {
                            $product_image_full_path = $_SERVER['DOCUMENT_ROOT'] . $producto['ruta_imagen_producto'];
                            if (file_exists($product_image_full_path)) {
                                $image_src = htmlspecialchars($producto['ruta_imagen_producto']);
                            }
                        }
                        ?>
                        <img src="<?= $image_src ?>" alt="<?= htmlspecialchars($producto['nombre_producto']) ?>" onerror="this.onerror=null; this.src='https://placehold.co/200x200/CCCCCC/FFFFFF?text=No+Img';">
                        <div class="product-card-content">
                            <h3><?= htmlspecialchars($producto['nombre_producto']) ?></h3>
                            <p><?= htmlspecialchars($producto['descripcion']) ?></p>
                            <p class="price">$<?= htmlspecialchars(number_format($producto['precio'], 2)) ?></p>
                            <p class="origin">Tienda: <?= htmlspecialchars($producto['nombre_tienda'] ?? 'Desconocida') ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; margin-top: 50px; color: #666;">No se encontraron productos que coincidan con su búsqueda.</p>
        <?php endif; ?>
    </div>
</body>
</html>

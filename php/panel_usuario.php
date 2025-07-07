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
            background-color: #E6FFE6; /* Light mint background */
            margin: 0;
            padding: 0;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .header {
            background-color: #FFFFFF; /* White header */
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
            color: #66BB6A; /* Vibrant green */
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
            color: #66BB6A; /* Vibrant green on hover */
        }

        .header .search-bar {
            display: flex;
            align-items: center;
            flex-grow: 1;
            max-width: 400px;
            background-color: #F5F5F5; /* Light grey search bar */
            border-radius: 25px;
            padding: 8px 15px;
            border: 1px solid #B0E0B0; /* Light green border */
        }

        .header .search-bar select {
            border: none;
            background: transparent;
            padding-right: 10px;
            border-right: 1px solid #CCC; /* Grey separator */
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
            color: #66BB6A; /* Vibrant green for search icon */
            cursor: pointer;
            font-size: 1.1em;
            padding: 5px;
            transition: color 0.3s ease;
        }

        .header .search-bar button:hover {
            color: #4CAF50; /* Darker green on hover */
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
            border: 2px solid #66BB6A; /* Vibrant green border */
        }
        
        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .user-dropdown-content {
            display: none;
            position: absolute;
            background-color: #F9F9F9; /* Light dropdown background */
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 8px;
            right: 0;
            top: 50px;
            border: 1px solid #D0F0C0; /* Light green border */
        }

        .user-dropdown-content a {
            color: #333 !important; /* Dark text */
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
            font-weight: 500;
            transition: background-color 0.2s ease;
            white-space: nowrap;
        }

        .user-dropdown-content a:hover {
            background-color: #E6FCE6; /* Lighter green hover */
            color: #66BB6A !important; /* Vibrant green on hover */
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
            background-color: #FFFFFF; /* White main content */
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
        }

        .main-content h1 {
            font-family: 'Quicksand', sans-serif;
            font-size: 2.2em;
            color: #388E3C; /* Dark green heading */
            margin-bottom: 30px;
            text-align: left;
            border-bottom: 2px solid #E6FFE6; /* Light green border */
            padding-bottom: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .info-card {
            background-color: #F8FFF8; /* Very light green card background */
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            text-align: left;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid #D0F0C0; /* Light green border */
            padding: 20px;
        }

        .info-card h3 {
            font-size: 1.5em;
            color: #66BB6A; /* Vibrant green */
            margin-top: 0;
            margin-bottom: 15px;
            text-align: center;
        }

        .info-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-card ul li {
            font-size: 0.95em;
            color: #555;
            margin-bottom: 10px;
            line-height: 1.4;
            display: flex;
            align-items: flex-start;
        }

        .info-card ul li i {
            color: #4CAF50; /* Darker vibrant green icon */
            margin-right: 10px;
            font-size: 1.1em;
            margin-top: 3px;
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


        .faq-section {
            background-color: #FFFFFF; /* White FAQ section */
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            max-width: 1200px;
            width: 100%;
            margin: 20px auto;
        }

        .faq-section h2 {
            font-family: 'Quicksand', sans-serif;
            font-size: 2.2em;
            color: #388E3C; /* Dark green heading */
            margin-bottom: 30px;
            text-align: center;
            border-bottom: 2px solid #E6FFE6; /* Light green border */
            padding-bottom: 10px;
        }

        .faq-item {
            background-color: #F8FFF8; /* Very light green FAQ item background */
            border: 1px solid #D0F0C0; /* Light green border */
            border-radius: 10px;
            margin-bottom: 15px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .faq-item h3 {
            font-size: 1.2em;
            color: #66BB6A; /* Vibrant green */
            margin-top: 0;
            margin-bottom: 10px;
        }

        .faq-item p {
            font-size: 0.95em;
            color: #555;
            line-height: 1.5;
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
            .info-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .faq-section {
                padding: 20px;
                margin: 15px auto;
            }
        }

        @media (max-width: 480px) {
            .header .nav-links {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="/Proyecto/Index.html" class="logo">
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
        <h1>¡Te damos la bienvenida a They Shop!</h1>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="info-grid">
            <div class="info-card">
                <h3>Ventajas para ti</h3>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Accede a las promociones y rebajas más destacadas.</li>
                    <li><i class="fas fa-check-circle"></i> Gana en sorteos por cada recibo que registres.</li>
                    <li><i class="fas fa-check-circle"></i> Revisa tu historial de gastos y tus estadísticas individuales.</li>
                    <li><i class="fas fa-check-circle"></i> Obtén sugerencias anónimas confirmadas por otros usuarios.</li>
                </ul>
            </div>
            <div class="info-card">
                <h3>Nuestros Planes TheyShop</h3>
                <ul>
                    <li><i class="fas fa-gem"></i> **Plan Básico:** Compara precios, sube tus recibos y consulta tu actividad.</li>
                    <li><i class="fas fa-crown"></i> **Plan Premium:** Recibe recomendaciones personalizadas, rutas de compra eficientes y ofertas exclusivas.</li>
                </ul>
            </div>
            <div class="info-card">
                <h3>La Tecnología Detrás de TheyShop</h3>
                <ul>
                    <li><i class="fas fa-robot"></i> Reconocimiento Óptico de Caracteres (OCR) con IA para procesar recibos.</li>
                    <li><i class="fas fa-map-marked-alt"></i> Conexión con la API de Google Maps.</li>
                    <li><i class="fas fa-database"></i> Almacenamiento en la nube (Firebase/MongoDB).</li>
                    <li><i class="fas fa-trophy"></i> Un sistema de recompensas tipo juego.</li>
                    <li><i class="fas fa-shield-alt"></i> Validación y sugerencia de precios automatizada por IA.</li>
                </ul>
            </div>
            <div class="info-card">
                <h3>¿Por Qué Elegir TheyShop?</h3>
                <ul>
                    <li><i class="fas fa-store"></i> No requieres múltiples cuentas de tienda.</li>
                    <li><i class="fas fa-lock"></i> Tu información sensible permanece privada.</li>
                    <li><i class="fas fa-clock"></i> Ahorra tiempo y dinero con nuestra ayuda.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="faq-section">
        <h2>Preguntas Comunes</h2>
        <div class="faq-item">
            <h3>¿Qué es TheyShop exactamente?</h3>
            <p>TheyShop es una plataforma que te permite cotejar precios de artículos utilizando recibos de compra auténticos, indicándote dónde adquirir productos a menor costo.</p>
        </div>
        <div class="faq-item">
            <h3>¿Es necesario registrarse para usar TheyShop?</h3>
            <p>Sí, es indispensable. Una cuenta es requerida para acceder a funcionalidades esenciales como la comparación de precios.</p>
        </div>
        <div class="faq-item">
            <h3>¿Cómo opera el sistema de recompensas?</h3>
            <p>Por cada recibo que subas, acumularás puntos que podrás canjear en sorteos o para desbloquear características premium.</p>
        </div>
        <div class="faq-item">
            <h3>¿Mi información se comparte con terceros?</h3>
            <p>No. Toda tu información es anonimizada y está protegida. TheyShop no comparte datos personales con entidades externas.</p>
        </div>
        <div class="faq-item">
            <h3>¿Qué sucede si mi recibo no es legible?</h3>
            <p>Tienes la opción de introducir los productos manualmente. La comunidad de usuarios verificará los datos para garantizar su exactitud.</p>
        </div>
    </div>
</body>
</html>

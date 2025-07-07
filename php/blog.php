<?php
session_start();

$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "usuariostheyshop";

$mysqli = null;
$message = '';
$message_type = '';

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

$blog_posts = [
    [
        'user_name' => 'María',
        'date' => '03 jul 2025 12:23',
        'content' => 'Recuerden estar atentos a las recompensas!, cambian cada semana',
    ],
   
];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['publish_post'])) {
    $new_post_content = trim($_POST['post_content'] ?? '');
    if (!empty($new_post_content)) {
        $current_user = $_SESSION['username'] ?? 'Usuario Anónimo';
        $current_date = date('d M Y H:i');
        
        array_unshift($blog_posts, [
            'user_name' => $current_user,
            'date' => $current_date,
            'content' => $new_post_content
        ]);
        $message = "Tu publicación ha sido agregada.";
        $message_type = 'success';
    } else {
        $message = "El contenido de la publicación no puede estar vacío.";
        $message_type = 'error';
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>THEY SHOP - Blog</title>
    <link rel="icon" href="/Proyecto/img/logotheyshop.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .header {
            background-color: #FFFFFF;
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
            color: #66BB6A;
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
            color: #66BB6A;
        }

        .header .search-bar {
            display: flex;
            align-items: center;
            flex-grow: 1;
            max-width: 400px;
            background-color: #f5f5f5;
            border-radius: 25px;
            padding: 8px 15px;
        }

        .header .search-bar select {
            border: none;
            background: transparent;
            padding-right: 10px;
            border-right: 1px solid #ccc;
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
            color: #66BB6A;
            cursor: pointer;
            font-size: 1.1em;
            padding: 5px;
            transition: color 0.3s ease;
        }

        .header .search-bar button:hover {
            color: #4CAF50;
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
            border: 2px solid #66BB6A;
        }
        
        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .user-dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 8px;
            right: 0;
            top: 50px;
        }

        .user-dropdown-content a {
            color: black !important;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
            font-weight: 500;
            transition: background-color 0.2s ease;
            white-space: nowrap;
        }

        .user-dropdown-content a:hover {
            background-color: #f1f1f1;
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
            background-color: #FDFDFD; /* Ligeramente diferente del blanco puro */
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08); /* Sombra más suave */
        }

        .main-content h1 {
            font-family: 'Quicksand', sans-serif;
            font-size: 2.2em;
            color: #388E3C;
            margin-bottom: 30px;
            text-align: left;
            border-bottom: 2px solid #E0F2F1; /* Color de borde diferente */
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

        .blog-post-form {
            background-color: #FFFFFF; /* Más claro */
            border-radius: 12px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.06); /* Sombra más sutil */
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #E0F2F1; /* Borde más claro */
        }

        .blog-post-form textarea {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #C8E6C9; /* Borde más suave */
            border-radius: 8px;
            font-family: 'Quicksand', sans-serif;
            font-size: 1em;
            color: #333;
            resize: vertical;
            min-height: 80px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        .blog-post-form textarea:focus {
            border-color: #66BB6A;
            outline: none;
        }

        .blog-post-form button {
            background-color: #66BB6A; /* Verde en lugar de amarillo */
            color: #FFFFFF;
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .blog-post-form button:hover {
            background-color: #4CAF50; /* Verde más oscuro al pasar el ratón */
            transform: translateY(-2px);
        }

        .blog-posts-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .blog-card {
            background-color: #FFFFFF; /* Más claro */
            border-radius: 12px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.06); /* Sombra más sutil */
            padding: 20px;
            border: 1px solid #E0F2F1; /* Borde más claro */
        }

        .blog-card .post-meta {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px; /* Añadir padding inferior */
            border-bottom: 1px solid #F0F0F0; /* Separador sutil */
        }

        .blog-card .post-meta .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            border: 2px solid #A5D6A7; /* Borde de avatar más suave */
        }

        .blog-card .post-meta .user-info h3 {
            margin: 0;
            font-size: 1.1em;
            color: #388E3C;
        }

        .blog-card .post-meta .user-info p {
            margin: 0;
            font-size: 0.85em;
            color: #777; /* Color de fecha más oscuro */
        }

        .blog-card .post-content {
            font-size: 1em;
            color: #555;
            line-height: 1.6;
            margin-top: 10px; /* Espacio superior para el contenido */
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
            .blog-post-form textarea {
                width: calc(100% - 20px);
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
            <img src="/Proyecto/img/logotheyshop.png" alt="THEYSHOP Logo">THEYSHOP</a>
        <form action="" method="GET" class="search-bar">
            <select name="category">
                <option value="all">Todos</option>
                
            </select>
            <input type="text" name="search_query" placeholder="Buscar más de 2000 productos" value="">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
        <div class="nav-links">
            <a href="/Proyecto/php/panel_usuario.php">Inicio</a>
            <a href="/Proyecto/php/productos.php">Productos</a>
            <a href="/Proyecto/php/tiendas.php">Tiendas</a>
            <a href="#">Tickets</a>
            <a href="#">Recompensas</a>
            <a href="/Proyecto/php/blog.php">Blog</a>
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
                        <a href="#">Hola, <?= htmlspecialchars($_SESSION['username']) ?></a>
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
        <h1>Blog</h1>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="blog-post-form">
            <form action="" method="POST">
                <textarea name="post_content" placeholder="Recomienda algún producto o tienda"></textarea>
                <button type="submit" name="publish_post">Publicar</button>
            </form>
        </div>

        <div class="blog-posts-container">
            <?php if (!empty($blog_posts)): ?>
                <?php foreach ($blog_posts as $post): ?>
                    <div class="blog-card">
                        <div class="post-meta">
                            <img src="https://placehold.co/40x40/B0E0B0/FFFFFF?text=User" alt="Avatar de Usuario" class="user-avatar">
                            <div class="user-info">
                                <h3><?= htmlspecialchars($post['user_name']) ?></h3>
                                <p><?= htmlspecialchars($post['date']) ?></p>
                            </div>
                        </div>
                        <div class="post-content">
                            <p><?= htmlspecialchars($post['content']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #666;">No hay publicaciones en el blog todavía.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

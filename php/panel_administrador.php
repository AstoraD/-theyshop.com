<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
 
    header('Location: /Proyecto/php/iniciar_sesión.php?error=acceso_denegado');
    exit();
}

$admin_username = $_SESSION['nombre_usuario']; 
$profile_picture_path = 'https://placehold.co/40x40/66BB6A/FFFFFF?text=AD'; 

$message = '';
$message_type = '';

$form_action = 'add'; 
$plan_id_to_edit = null;
$plan_name_to_edit = '';
$plan_duration_to_edit = '';
$plan_costo_mxn_to_edit = '';

// Conexión a la base de datos
$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "usuariostheyshop"; 

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, 3306);

// Verificar errores en la conexión
if ($mysqli->connect_error) {
    error_log("Error de Conexión a la Base de Datos en panel_administrador.php: " . $mysqli->connect_error);
    $message = "Error de conexión a la base de datos. Por favor, inténtalo más tarde.";
    $message_type = 'error';
} else {
    
    $stmt_profile = $mysqli->prepare("SELECT profile_picture_path FROM cuentas WHERE nombre_usuario = ?");
    
    if ($stmt_profile === false) {
        error_log("Error al preparar la consulta de foto de perfil: " . $mysqli->error);
    } else {
        $stmt_profile->bind_param("s", $admin_username);
        $stmt_profile->execute();
        $stmt_profile->bind_result($fetched_path);
        $stmt_profile->fetch();

        if ($fetched_path && file_exists($fetched_path)) {
            $profile_picture_path = htmlspecialchars($fetched_path);
        }
        $stmt_profile->close();
    }

    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $plan_id_to_delete = intval($_GET['id']);
        $stmt_delete = $mysqli->prepare("DELETE FROM planes_premium WHERE id = ?");
        if ($stmt_delete === false) {
            $message = "Error al preparar la consulta de eliminación: " . $mysqli->error;
            $message_type = 'error';
        } else {
            $stmt_delete->bind_param("i", $plan_id_to_delete);
            if ($stmt_delete->execute()) {
                $message = "Plan premium eliminado correctamente.";
                $message_type = 'success';
            } else {
                $message = "Error al eliminar el plan: " . $stmt_delete->error;
                $message_type = 'error';
            }
            $stmt_delete->close();
        }
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_plan'])) {
        $nombre_plan = $_POST['nombre_plan'] ?? '';
        $duracion = $_POST['duracion'] ?? '';
        $costo_mxn = $_POST['costo_mxn'] ?? ''; 
        $plan_id_from_form = $_POST['plan_id'] ?? null; 

        if (empty($nombre_plan) || empty($duracion) || empty($costo_mxn)) {
            $message = "Todos los campos son obligatorios.";
            $message_type = 'error';
        } else {
            if (!filter_var($duracion, FILTER_VALIDATE_INT) || $duracion <= 0) {
                $message = "La duración debe ser un número entero positivo.";
                $message_type = 'error';
            } elseif (!filter_var($costo_mxn, FILTER_VALIDATE_FLOAT) || $costo_mxn <= 0) { 
                $message = "El costo debe ser un número positivo.";
                $message_type = 'error';
            } else {
                if ($plan_id_from_form) { // Es una edición
                    $stmt_update = $mysqli->prepare("UPDATE planes_premium SET nombre_plan = ?, duracion = ?, costo_mxn = ? WHERE id = ?"); 
                    if ($stmt_update === false) {
                        $message = "Error al preparar la consulta de actualización: " . $mysqli->error;
                        $message_type = 'error';
                    } else {
                        $stmt_update->bind_param("sidi", $nombre_plan, $duracion, $costo_mxn, $plan_id_from_form); 
                        if ($stmt_update->execute()) {
                            $message = "Plan premium actualizado correctamente.";
                            $message_type = 'success';
                        } else {
                            $message = "Error al actualizar el plan: " . $stmt_update->error;
                            $message_type = 'error';
                        }
                        $stmt_update->close();
                    }
                } else { // Es una adición
                    $stmt_insert = $mysqli->prepare("INSERT INTO planes_premium (nombre_plan, duracion, costo_mxn) VALUES (?, ?, ?)"); 
                    if ($stmt_insert === false) {
                        $message = "Error al preparar la consulta de inserción: " . $mysqli->error;
                        $message_type = 'error';
                    } else {
                        $stmt_insert->bind_param("sid", $nombre_plan, $duracion, $costo_mxn); 
                        if ($stmt_insert->execute()) {
                            $message = "Plan premium agregado correctamente.";
                            $message_type = 'success';
                        } else {
                            $message = "Error al agregar el plan: " . $stmt_insert->error;
                            $message_type = 'error';
                        }
                        $stmt_insert->close();
                    }
                }
            }
        }
    }

    // 3. Cargar datos para edición si se solicita
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $plan_id_to_edit = intval($_GET['id']);
        $stmt_select_edit = $mysqli->prepare("SELECT id, nombre_plan, duracion, costo_mxn FROM planes_premium WHERE id = ?"); 
        if ($stmt_select_edit === false) {
            $message = "Error al preparar la consulta para editar: " . $mysqli->error;
            $message_type = 'error';
        } else {
            $stmt_select_edit->bind_param("i", $plan_id_to_edit);
            $stmt_select_edit->execute();
            $result_edit = $stmt_select_edit->get_result();
            if ($result_edit->num_rows === 1) {
                $plan_data = $result_edit->fetch_assoc();
                $form_action = 'edit';
                $plan_name_to_edit = $plan_data['nombre_plan'];
                $plan_duration_to_edit = $plan_data['duracion'];
                $plan_costo_mxn_to_edit = $plan_data['costo_mxn']; 
            } else {
                $message = "Plan no encontrado para edición.";
                $message_type = 'error';
                $plan_id_to_edit = null; 
            }
            $stmt_select_edit->close();
        }
    }
   
    $search_plan_name = $_GET['search_plan_name'] ?? '';
    $premium_plans = [];

    $query_plans = "SELECT id, nombre_plan, duracion, costo_mxn FROM planes_premium"; 
    $params = [];
    $types = "";

    if (!empty($search_plan_name)) {
        $query_plans .= " WHERE nombre_plan LIKE ?";
        $params[] = "%" . $search_plan_name . "%";
        $types .= "s";
    }
    $query_plans .= " ORDER BY id DESC"; 
    $stmt_plans = $mysqli->prepare($query_plans);

    if ($stmt_plans === false) {
        error_log("Error al preparar la consulta de planes premium: " . $mysqli->error);
        $message = "Error al cargar los planes premium.";
        $message_type = 'error';
    } else {
        if (!empty($params)) {
            $stmt_plans->bind_param($types, ...$params);
        }
        $stmt_plans->execute();
        $result_plans = $stmt_plans->get_result();

        while ($row = $result_plans->fetch_assoc()) {
            $premium_plans[] = $row;
        }
        $stmt_plans->close();
    }

    $mysqli->close(); 
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador - They Shop</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lobster&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background-color: #E6FFE6; 
            padding: 0; 
            margin: 0; 
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            color: #333;
            box-sizing: border-box;
        }
        
        .admin-header {
            width: 100%;
            background-color: #F8F8F8; 
            padding: 15px 80px; 
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 20px; 
        }

        .admin-header .logo {
            display: flex;
            align-items: center;
            font-family: 'Lobster', cursive;
            font-size: 1.8em;
            color: #66BB6A;
            text-decoration: none;
        }

        .admin-header .logo img {
            height: 30px; 
            margin-right: 10px;
        }

        .admin-header .search-bar {
            display: flex;
            align-items: center;
            background-color: #EFEFEF;
            border-radius: 20px;
            padding: 8px 15px;
            flex-grow: 1; 
            max-width: 500px; 
            margin: 0 40px; 
        }

        .admin-header .search-bar .icon {
            color: #888;
            margin-right: 10px;
            font-size: 1.2em;
        }

        .admin-header .search-bar input[type="text"] {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-size: 1em;
            color: #333;
            padding: 0; 
            margin: 0; 
        }
        
        .admin-header .search-bar select {
            border: none;
            background: transparent;
            outline: none;
            font-size: 1em;
            color: #333;
            margin-right: 10px;
            padding: 0;
            cursor: pointer;
        }

        .admin-header .search-bar button {
            background-color: #A5D6A7;
            color: #1B5E20;
            border: none;
            border-radius: 15px;
            padding: 5px 12px;
            font-size: 0.9em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-left: 10px;
        }

        .admin-header .search-bar button:hover {
            background-color: #81C784;
        }

        .admin-header .profile-section {
            position: relative; 
            display: flex;
            align-items: center;
            cursor: pointer; 
        }

        .admin-header .profile-section img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #66BB6A;
        }

        .dropdown-menu {
            display: none; 
            position: absolute;
            top: 50px; 
            right: 0;
            background-color: #FFFFFF;
            border: 1px solid #D0F0C0;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            min-width: 150px;
            z-index: 100; 
            padding: 10px 0;
        }

        .dropdown-menu.show {
            display: block; 
        }

        .dropdown-menu a {
            color: #333;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            text-align: left;
            font-size: 0.95em;
            transition: background-color 0.2s ease;
        }

        .dropdown-menu a:hover {
            background-color: #F0FFF0;
            color: #388E3C;
        }

        .admin-panel-container {
            background-color: #FFFFFF;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
            border: 2px solid #A7D9EB; 
            text-align: center;
            flex-grow: 1; 
            margin-bottom: 20px; 
        }
        h1 {
            font-family: 'Lobster', cursive; 
            color: #66BB6A; 
            margin-bottom: 25px;
            font-size: 2.8em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        .welcome-message {
            font-size: 1.5em;
            margin-bottom: 40px;
            color: #388E3C; /* Verde oscuro */
            font-weight: 600;
        }
        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .option-card {
            background-color: #F0FFF0; 
            border: 1px solid #C8E6C9; 
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .option-card img {
            width: 60px;
            height: auto;
            margin-bottom: 15px;
            opacity: 0.8;
        }
        .option-card a {
            display: block;
            font-size: 1.2em;
            font-weight: 700;
            color: #4CAF50; 
            text-decoration: none;
            margin-top: auto; 
            padding-top: 10px; 
        }
        .option-card a:hover {
            color: #388E3C; 
        }
        .option-card p {
            color: #666;
            font-size: 0.9em;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .logout-link { 
            display: block;
            margin-top: 40px;
            color: #D32F2F; 
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .logout-link:hover {
            color: #B71C1C; 
        }

        .premium-plans-container {
            background-color: #FFFFFF;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
            border: 2px solid #A7D9EB;
            text-align: center;
            margin-top: 40px; 
        }

        .premium-plans-container h2 {
            font-family: 'Lobster', cursive; 
            color: #66BB6A;
            margin-bottom: 25px;
            font-size: 2.2em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .premium-plans-container table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .premium-plans-container th,
        .premium-plans-container td {
            border: 1px solid #D0F0C0;
            padding: 12px;
            text-align: left;
        }

        .premium-plans-container th {
            background-color: #C8E6C9;
            color: #1B5E20;
            font-weight: 700;
            text-transform: uppercase;
        }

        .premium-plans-container tr:nth-child(even) {
            background-color: #F0FFF0;
        }

        .premium-plans-container tr:hover {
            background-color: #E6FCE6;
        }

        .action-buttons-small {
            display: flex;
            gap: 5px;
            justify-content: center;
            align-items: center;
        }

        .action-buttons-small .small-button {
            background-color: #A5D6A7;
            color: #1B5E20;
            padding: 5px 10px;
            border: none;
            border-radius: 10px;
            font-size: 0.8em;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .action-buttons-small .small-button.delete-button {
            background-color: #FFCDD2; 
            color: #B71C1C;
        }

        .action-buttons-small .small-button:hover {
            background-color: #81C784;
            transform: translateY(-1px);
        }

        .action-buttons-small .small-button.delete-button:hover {
            background-color: #EF9A9A;
        }

        .message-container {
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .message-container.success {
            background-color: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }

        .message-container.error {
            background-color: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }

        .search-premium-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-premium-form input[type="text"] {
            flex-grow: 1;
            max-width: 300px;
            padding: 8px 15px;
            border: 1px solid #D0F0C0;
            border-radius: 20px;
            font-size: 1em;
            box-sizing: border-box;
        }

        .search-premium-form button {
            background-color: #A5D6A7;
            color: #1B5E20;
            border: none;
            border-radius: 15px;
            padding: 8px 15px;
            font-size: 0.9em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .search-premium-form button:hover {
            background-color: #81C784;
        }

        .add-edit-plan-form-container {
            background-color: #FFFFFF;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            border: 2px solid #A7D9EB;
            text-align: center;
            margin-top: 40px;
            margin-bottom: 40px; 
            display: block; 
        }

        .add-edit-plan-form-container h2 {
            font-family: 'Lobster', cursive;
            color: #66BB6A;
            margin-bottom: 25px;
            font-size: 2.2em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .add-edit-plan-form-container table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .add-edit-plan-form-container th,
        .add-edit-plan-form-container td {
            border: 1px solid #D0F0C0;
            padding: 8px; /* Reducido para hacer las celdas más pequeñas */
            text-align: left;
        }

        .add-edit-plan-form-container th {
            background-color: #C8E6C9;
            color: #1B5E20;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9em; /* Reducir tamaño de fuente de encabezado */
        }

        .add-edit-plan-form-container tr:nth-child(even) {
            background-color: #F0FFF0;
        }

        .add-edit-plan-form-container tr:hover {
            background-color: #E6FCE6;
        }

        .add-edit-plan-form-container input[type="text"],
        .add-edit-plan-form-container input[type="number"] {
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

        .add-edit-plan-form-container .form-actions-cell {
            text-align: center;
            padding-top: 10px; 
        }

        .add-edit-plan-form-container button[type="submit"],
        .add-edit-plan-form-container .cancel-button {
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

        .add-edit-plan-form-container button[type="submit"]:hover,
        .add-edit-plan-form-container .cancel-button:hover {
            background-color: #81C784;
            transform: translateY(-2px);
        }

        .add-edit-plan-form-container .cancel-button {
            background-color: #FFCDD2;
            color: #B71C1C;
        }
        .add-edit-plan-form-container .cancel-button:hover {
            background-color: #EF9A9A;
        }

        @media (max-width: 768px) {
            .admin-header {
                padding: 15px 20px; 
                flex-wrap: wrap; 
                justify-content: center;
            }
            .admin-header .logo {
                width: 100%;
                justify-content: center;
                margin-bottom: 10px;
            }
            .admin-header .search-bar {
                width: 100%;
                margin: 10px 0;
            }
            .admin-header .profile-section {
                width: 100%;
                justify-content: center;
                margin-top: 10px;
            }
            .admin-panel-container {
                padding: 20px;
            }
            h1 {
                font-size: 2.5em;
            }
            .options-grid {
                grid-template-columns: 1fr; 
            }
            .premium-plans-container table,
            .premium-plans-container thead,
            .premium-plans-container tbody,
            .premium-plans-container th,
            .premium-plans-container td,
            .premium-plans-container tr {
                display: block;
            }
            .premium-plans-container thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            .premium-plans-container tr {
                margin-bottom: 15px;
                border: 1px solid #D0F0C0;
                border-radius: 10px;
                overflow: hidden;
            }
            .premium-plans-container td {
                border: none;
                border-bottom: 1px solid #D0F0C0;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }
            .premium-plans-container td::before {
                content: attr(data-label);
                position: absolute;
                left: 0;
                width: 48%;
                padding-left: 15px;
                font-weight: 600;
                text-align: left;
                color: #666;
            }
            .premium-plans-container tr td:last-child {
                border-bottom: 0;
            }
            .search-premium-form {
                flex-direction: column;
            }
            .search-premium-form input[type="text"] {
                max-width: 100%;
            }

            .add-edit-plan-form-container table,
            .add-edit-plan-form-container thead,
            .add-edit-plan-form-container tbody,
            .add-edit-plan-form-container th,
            .add-edit-plan-form-container td,
            .add-edit-plan-form-container tr {
                display: block;
            }
            .add-edit-plan-form-container thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            .add-edit-plan-form-container tr {
                margin-bottom: 15px;
                border: 1px solid #D0F0C0;
                border-radius: 10px;
                overflow: hidden;
            }
            .add-edit-plan-form-container td {
                border: none;
                border-bottom: 1px solid #D0F0C0;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }
            .add-edit-plan-form-container td::before {
                content: attr(data-label);
                position: absolute;
                left: 0;
                width: 48%;
                padding-left: 15px;
                font-weight: 600;
                text-align: left;
                color: #666;
            }
            .add-edit-plan-form-container tr td:last-child {
                border-bottom: 0;
            }
            .add-edit-plan-form-container .form-actions-cell {
                text-align: center;
                padding-top: 10px;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding-left: 0; 
            }
            .add-edit-plan-form-container .form-actions-cell button,
            .add-edit-plan-form-container .form-actions-cell .cancel-button {
                width: calc(100% - 20px);
                margin-left: 0;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <a href="/Proyecto/index.html" class="logo">
            <img src="/Proyecto/img/logotheyshop.png" alt="They Shop Logo">
        </a>
        <div class="search-bar">
            <select id="searchCategory">
                <option value="usuarios">Usuarios</option>
                <option value="productos">Productos</option>
                <option value="tiendas">Tiendas</option>
            </select>
            <i class="fas fa-search icon"></i>
            <input type="text" id="searchInput" placeholder="Buscar por nombre de usuario...">
            <button onclick="performSearch()">Buscar</button>
        </div>
        <div class="profile-section" onclick="toggleDropdown()">
            <img src="<?= $profile_picture_path ?>" alt="Admin Profile" onerror="this.onerror=null; this.src='https://placehold.co/40x40/66BB6A/FFFFFF?text=AD';">
            <div class="dropdown-menu" id="profileDropdown">
                <a href="/Proyecto/php/cierre_sesión.php">Cerrar Sesión</a>
            </div>
        </div>
    </div>

    <div class="admin-panel-container">
        <h1>Panel de Administración</h1>
        <p class="welcome-message">Bienvenido <strong><?= htmlspecialchars($admin_username) ?></strong>, ¿qué quieres hacer el día de hoy?</p>

        <?php if (!empty($message)): ?>
            <div class="message-container <?= $message_type ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="options-grid">
            <div class="option-card">
                <img src="/Proyecto/img/usuarios.png" alt="Usuarios">
                <p>Gestiona las cuentas de usuario registradas.</p>
                <a href="/Proyecto/php/tabla_usuarios.php">Ir a la tabla de usuarios</a>
            </div>
            <div class="option-card">
                <img src="/Proyecto/img/Productos.jpg" alt="Productos">
                <p>Administra los productos disponibles en la tienda.</p>
                <a href="/Proyecto/php/tabla_productos.php">Ir a la tabla de productos</a>
            </div>
            <div class="option-card">
                <img src="/Proyecto/img/tiendas.jpg" alt="Tiendas">
                <p>Visualiza y gestiona las sucursales de la tienda.</p>
                <a href="/Proyecto/php/tabla_tiendas.php">Ir a la tabla de tiendas</a>
            </div>
        </div>
    </div>

    <div class="premium-plans-container">
        <h2><i class="fas fa-star icon"></i> Planes Premium Disponibles</h2>

        <div class="add-edit-plan-form-container" style="
            background-color: transparent;
            padding: 0;
            border: none;
            box-shadow: none;
            max-width: none; 
            margin-top: 0;
            margin-bottom: 20px; 
        ">
            <h3 style="font-family: 'Quicksand', sans-serif; color: #388E3C; font-size: 1.5em; margin-bottom: 15px;"><?= ($form_action === 'add') ? 'Agregar Nuevo Plan' : 'Editar Plan Actual' ?></h3>
            <form method="POST" action="panel_administrador.php">
                <?php if ($form_action === 'edit'): ?>
                    <input type="hidden" name="plan_id" value="<?= htmlspecialchars($plan_id_to_edit) ?>">
                <?php endif; ?>
                <input type="hidden" name="submit_plan" value="1">

                <table style="margin-top: 0;">
                    <thead>
                        <tr>
                            <th>NOMBRE DEL PLAN</th>
                            <th>DURACIÓN (meses)</th>
                            <th>COSTO (MXN)</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td data-label="Nombre del Plan">
                                <input type="text" id="nombre_plan" name="nombre_plan" placeholder="Nombre del Plan" value="<?= htmlspecialchars($plan_name_to_edit) ?>" required>
                            </td>
                            <td data-label="Duración (meses)">
                                <input type="number" id="duracion" name="duracion" min="1" placeholder="Duración (meses)" value="<?= htmlspecialchars($plan_duration_to_edit) ?>" required>
                            </td>
                            <td data-label="Costo (MXN)">
                                <input type="number" id="costo_mxn" name="costo_mxn" step="0.01" min="0" placeholder="Costo (MXN)" value="<?= htmlspecialchars($plan_costo_mxn_to_edit) ?>" required>
                            </td>
                            <td data-label="Acciones" class="form-actions-cell">
                                <button type="submit"><?= ($form_action === 'add') ? 'Agregar Plan' : 'Actualizar Plan' ?></button>
                    
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>

        <div class="search-premium-form">
            <form method="GET" action="panel_administrador.php">
                <input type="text" id="search_plan_name" name="search_plan_name" 
                       placeholder="Buscar plan por nombre..." 
                       value="<?= htmlspecialchars($search_plan_name) ?>">
                <button type="submit"><i class="fas fa-search"></i> Buscar</button>
                <?php if (!empty($search_plan_name)): ?>
                    <button type="button" onclick="window.location.href='panel_administrador.php'">
                        <i class="fas fa-times"></i> Limpiar
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>NOMBRE DEL PLAN</th>
                    <th>DURACIÓN (meses)</th>
                    <th>COSTO (MXN)</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($premium_plans)): ?>
                    <?php foreach ($premium_plans as $plan): ?>
                        <tr>
                            <td data-label="ID"><?= htmlspecialchars($plan['id']) ?></td>
                            <td data-label="NOMBRE DEL PLAN"><?= htmlspecialchars($plan['nombre_plan']) ?></td>
                            <td data-label="DURACIÓN (meses)"><?= htmlspecialchars($plan['duracion']) ?></td>
                            <td data-label="COSTO (MXN)">$<?= htmlspecialchars(number_format($plan['costo_mxn'], 2)) ?></td>
                            <td data-label="ACCIONES" class="action-buttons-small">
                                <a href="panel_administrador.php?action=edit&id=<?= htmlspecialchars($plan['id']) ?>" class="small-button">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <a href="panel_administrador.php?action=delete&id=<?= htmlspecialchars($plan['id']) ?>" class="small-button delete-button" onclick="return confirm('¿Estás seguro de que quieres eliminar este plan?');">
                                    <i class="fas fa-trash-alt"></i> Eliminar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No hay planes premium disponibles.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function toggleDropdown() {
            document.getElementById("profileDropdown").classList.toggle("show");
        }

        // Cerrar el dropdown si el usuario hace clic fuera de él
        window.onclick = function(event) {
            if (!event.target.matches('.profile-section img')) {
                var dropdowns = document.getElementsByClassName("dropdown-menu");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }

    </script>
</body>
</html>
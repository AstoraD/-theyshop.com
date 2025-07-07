<?php
// ¡Esta línea debe ser la PRIMERA del archivo, sin espacios ni saltos de línea antes!
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CONSULTA, EDITA Y ELIMINA Reservas Hello Kitty</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background-color: #F0FFF0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            color: #388E3C;
        }

        h2 {
            color: #66BB6A;
            margin-top: 20px;
            margin-bottom: 15px;
            font-size: 2em;
        }

        .boton-tabla, button[type="submit"] {
            background-color: #66BB6A;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
            transition: background-color 0.3s ease;
        }

        .boton-tabla:hover, button[type="submit"]:hover {
            background-color: #388E3C;
        }

        .mensaje {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
            width: 80%;
            max-width: 600px;
        }

        .mensaje.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        form {
            background-color: #FFFFFF;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 600px;
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        form label {
            font-weight: bold;
            color: #555;
            text-align: left;
        }

        form input[type="text"],
        form input[type="email"],
        form input[type="tel"],
        form input[type="number"],
        form input[type="date"],
        form input[type="time"],
        form input[type="color"],
        form textarea {
            width: calc(100% - 22px); /* Ajustado para padding */
            padding: 10px;
            margin-bottom: 5px; /* Reducido para formularios más compactos */
            border: 1px solid #D0F0C0;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 1em;
        }
        
        form input[type="color"] {
            width: 80px;
            height: 40px;
            padding: 0;
        }

        form textarea {
            resize: vertical;
            min-height: 80px;
        }

        hr {
            border: 0;
            height: 1px;
            background-color: #D0F0C0;
            margin: 30px 0;
            width: 100%;
        }

        .registro-lista {
            background-color: #F5FFF5;
            border: 1px solid #D0F0C0;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            width: 90%;
            max-width: 600px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .registro-lista strong {
            color: #66BB6A;
        }

        .registro-lista .action-buttons-small {
            margin-top: 10px;
        }

        .registro-lista .action-buttons-small .small-button {
            background-color: #B2DFDB; /* Un tono más claro para estos botones */
            color: #388E3C; /* Texto más oscuro */
            padding: 5px 10px;
            border: 1px solid #A5D6A7; /* Borde suave */
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em; /* Tamaño de fuente más pequeño */
            text-decoration: none;
            display: inline-block;
            margin-right: 8px; /* Espacio entre los botones */
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .registro-lista .action-buttons-small .small-button:hover {
            background-color: #81C784; /* Un tono más oscuro al pasar el ratón */
            transform: translateY(-1px);
        }

        .registro-lista a { /* Asegurar que los estilos de enlace no sobrescriban */
            text-decoration: none;
        }

        .search-form {
            background-color: #E6FFE6;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
            width: 90%;
            max-width: 600px;
        }

        .search-form input[type="number"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #B0E0B0;
            border-radius: 5px;
            font-size: 1em;
        }

        .search-form button {
            padding: 10px 15px;
            font-size: 1em;
        }
    </style>
</head>
<body>

<h2>REGISTRO DE RESERVACIONES</h2>

<a href="/PracticasForms/Forms%20php/php/01.%20Tablita.php" class="boton-tabla">Ir a tabla de reservas</a>

<!-- Sección de búsqueda -->
<div class="search-form">
    <form method="GET" action="">
        <label for="search_id">Buscar por ID:</label>
        <input type="number" id="search_id" name="search_id" placeholder="Ingresa ID" value="<?= htmlspecialchars($_GET['search_id'] ?? '') ?>">
        <button type="submit">Buscar</button>
        <?php if (isset($_GET['search_id'])): ?>
            <button type="button" onclick="window.location.href='<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>'">Mostrar Todos</button>
        <?php endif; ?>
    </form>
</div>

<?php
// Mostrar mensajes de operaciones (éxito o error)
if (isset($_GET['mensaje'])) {
    if ($_GET['mensaje'] === 'eliminado') {
        echo "<p class='mensaje'>Registro eliminado correctamente.</p>";
    } elseif ($_GET['mensaje'] === 'error_eliminar') {
        echo "<p class='mensaje error'>ERROR AL ELIMINAR: " . htmlspecialchars($_GET['error'] ?? 'Desconocido') . "</p>";
    } elseif ($_GET['mensaje'] === 'actualizado') {
        echo "<p class='mensaje'>Registro actualizado correctamente.</p>";
    } elseif ($_GET['mensaje'] === 'error_actualizar') {
        echo "<p class='mensaje error'>ERROR AL ACTUALIZAR: " . htmlspecialchars($_GET['error'] ?? 'Desconocido') . "</p>";
    } elseif ($_GET['mensaje'] === 'no_id_eliminar') {
        echo "<p class='mensaje error'>No se proporcionó un ID de registro para eliminar.</p>";
    }
}

// Conexión a la base de datos 
$mysqli = new mysqli("127.0.0.1", "root", "", "reservación", 3306);

if ($mysqli->connect_error) {
    echo "<p class='mensaje error'>Error de Conexión a la Base de Datos: " . $mysqli->connect_error . "</p>";
    exit();
}

$search_id = $_GET['search_id'] ?? null;
$query = "SELECT id, host_name, guests_number, reservation_date, reservation_time FROM reservaciones WHERE NOT (guests_number = 10 AND reservation_date = '0000-00-00' AND reservation_time = '00:00:00')";

$stmt = null;
if ($search_id !== null && $search_id !== '') {
    $query .= " AND id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $search_id);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $query .= " ORDER BY id DESC";
    $res = $mysqli->query($query);
}


if ($res) {
    // Almacenar todos los resultados para usarlos en ambas secciones
    $reservations = [];
    while ($fila = $res->fetch_assoc()) {
        $reservations[] = $fila;
    }
    
    // Cerrar el resultado de la consulta original
    if (isset($stmt)) {
        $stmt->close();
    } else {
        $res->close();
    }

    echo "<h2>Editar reservaciones</h2>";
    if (!empty($reservations)) {
        foreach ($reservations as $fila) {
            echo "<div class='registro-lista'>";
            echo "<strong>" . htmlspecialchars($fila['host_name']) . "</strong> (ID: " . htmlspecialchars($fila['id']) . ")<br>";
            echo "Invitados: " . htmlspecialchars($fila['guests_number']) . "<br>";
            echo "Fecha: " . htmlspecialchars($fila['reservation_date']) . " Hora: " . htmlspecialchars($fila['reservation_time']) . "<br>";
            echo "<div class='action-buttons-small'>";
            // Archivo actualizar registro
            echo "<a href='/PracticasForms/Forms%20php/php/01.Actualizar_registro.php? id=" . htmlspecialchars($fila['id']) . "' class='small-button'>EDITAR</a>";
            echo "</div>";
            echo "</div>";
        }
    } else {
        echo "<p class='mensaje'>No hay reservaciones para editar.</p>";
    }

    echo "<hr>"; 

    echo "<h2>Eliminar reservaciones</h2>";
    if (!empty($reservations)) {
        foreach ($reservations as $fila) {
            echo "<div class='registro-lista'>";
            echo "<strong>" . htmlspecialchars($fila['host_name']) . "</strong> (ID: " . htmlspecialchars($fila['id']) . ")<br>";
            echo "Invitados: " . htmlspecialchars($fila['guests_number']) . "<br>";
            echo "Fecha: " . htmlspecialchars($fila['reservation_date']) . " Hora: " . htmlspecialchars($fila['reservation_time']) . "<br>";
            echo "<div class='action-buttons-small'>";
            // Archivo eliminar registro
            echo "<a href='/PracticasForms/Forms%20php/php/01.Eliminar_registro.php?id=" . htmlspecialchars($fila['id']) . "' class='small-button' onclick=\"return confirm('¿Seguro que quieres eliminar el registro de " . htmlspecialchars($fila['host_name']) . "?')\">ELIMINAR</a>";
            echo "</div>";
            echo "</div>";
        }
    } else {
        echo "<p class='mensaje'>No hay reservaciones para eliminar.</p>";
    }

} else {
    echo "<p class='mensaje error'>Error al obtener los registros: " . $mysqli->error . "</p>";
}

$mysqli->close(); // Cierra la conexión a la base de datos
?>
</body>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservaciones de Fiestas Hello Kitty</title>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8F0FF;
            margin: 0;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            color: #4A2B1E;
        }

        h2 {
            font-family: 'Pacifico', cursive;
            color: #B19CD9;
            margin-bottom: 10px;
            font-size: 2.8em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            text-align: center;
        }

        p {
            color: #8A6BAA;
            margin-bottom: 25px;
            font-size: 1.1em;
            text-align: center;
        }

        table {
            width: 95%;
            max-width: 1200px;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
            background-color: #FFFFFF;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #DCD0FF;
            font-size: 0.95em;
            word-wrap: break-word;
        }

        th {
            background-color: #C0B2D2;
            color: #FFFFFF;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        tr:nth-child(even) {
            background-color: #F0E0FF;
        }

        tr:hover {
            background-color: #E0D0F0;
        }

        .color-preview {
            width: 25px;
            height: 25px;
            border-radius: 5px;
            border: 1px solid #DCD0FF;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        td img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #DCD0FF;
        }

        td a {
            color: #B19CD9;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        td a:hover {
            color: #8A6BAA;
        }

        .error-message {
            color: #CC3333;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            background-color: #FFDCDC;
            border: 1px solid #FF9999;
            border-radius: 8px;
            margin-top: 30px;
            width: 90%;
            max-width: 600px;
        }

        @media (max-width: 768px) {
            table {
                border-radius: 0;
                box-shadow: none;
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
                border: 1px solid #DCD0FF;
                border-radius: 8px;
                overflow: hidden;
            }
            td {
                border: none;
                border-bottom: 1px solid #DCD0FF;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }
            td::before {
                content: attr(data-label);
                position: absolute;
                left: 0;
                width: 48%;
                padding-left: 15px;
                font-weight: 600;
                text-align: left;
                color: #8A6BAA;
            }
            tr td:last-child {
                border-bottom: 0;
            }
        }

        .options-button-container {
            text-align: center;
            margin-top: 30px;
            margin-bottom: 20px;
        }

        .options-button {
            background-color: #B19CD9;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-decoration: none;
            display: inline-block;
        }

        .options-button:hover {
            background-color: #8A6BAA;
            transform: translateY(-2px);
        }

        .search-form {
            background-color: #F0E0FF;
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

        .search-form form {
            display: flex;
            gap: 10px;
            align-items: center;
            width: 100%;
            padding: 0;
            box-shadow: none;
            background-color: transparent;
            margin-top: 0;
        }

        .search-form label {
            font-weight: 600;
            color: #555;
            text-align: left;
            margin-bottom: 0;
        }

        .search-form input[type="number"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #DCD0FF;
            border-radius: 5px;
            font-size: 1em;
            margin-bottom: 0;
        }

        .search-form button {
            padding: 10px 15px;
            font-size: 1em;
            margin: 0;
            background-color: #B19CD9;
            color: white;
            border-radius: 8px;
            box-shadow: none;
            transform: none;
        }

        .search-form button:hover {
            background-color: #8A6BAA;
            transform: none;
        }
    </style>
</head>
<body>

<h2>Reservaciones de Fiestas Hello Kitty</h2>
<p>Aquí se encuentran los datos de nuestros clientes que han reservado su fiesta.</p>

<div class="options-button-container">
    <a href="/PracticasForms/Forms%20php/php/01.Opciones%20(borrar,actualizar).php" class="options-button">Opciones</a>
</div>

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

<table>
    <thead>
        <tr>
            <th>Nombre de Quien Reserva</th>
            <th>Correo Electrónico</th>
            <th>Teléfono</th>
            <th>Número de Invitados</th>
            <th>Fecha de la Fiesta</th>
            <th>Hora Preferida</th>
            <th>Color de Decoración</th>
            <th>Estilo de Fiesta</th>
            <th>Servicios Adicionales</th>
            <th>Peticiones Especiales</th>
            <th>Personaje Favorito</th>
            <th>Canción Especial</th>
            <th>Número de Tarjeta (Placeholder)</th>
        </tr>
    </thead>
    <tbody>
        
        <?php
        try {
            $mysqli = new mysqli("127.0.0.1", "root", '', "reservación", 3306);

            if ($mysqli->connect_error) {
                throw new Exception("Conexión fallida: " . $mysqli->connect_error);
            }

            $search_id = $_GET['search_id'] ?? null;
            $query = "SELECT * FROM reservaciones 
                      WHERE NOT (guests_number = 10 
                      AND reservation_date = '0000-00-00' 
                      AND reservation_time = '00:00:00')";

            $stmt = null;

            if ($search_id !== null && $search_id !== '') {
                $query .= " AND id = ?";
                $stmt = $mysqli->prepare($query);
                if ($stmt === false) {
                    throw new Exception("Error al preparar la consulta SQL: " . $mysqli->error);
                }
                $stmt->bind_param("i", $search_id);
                $stmt->execute();
                $resultado = $stmt->get_result();
            } else {
                $query .= " ORDER BY created_at DESC";
                $resultado = $mysqli->query($query);
                if ($resultado === false) {
                    throw new Exception("Error al ejecutar la consulta SQL: " . $mysqli->error);
                }
            }
            
            if ($resultado->num_rows > 0) {
                while ($fila = $resultado->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td data-label='Nombre de Quien Reserva'>" . htmlspecialchars($fila['host_name']) . "</td>";
                    echo "<td data-label='Correo Electrónico'>" . htmlspecialchars($fila['email_contact']) . "</td>";
                    echo "<td data-label='Teléfono'>" . htmlspecialchars($fila['phone_number']) . "</td>";
                    echo "<td data-label='Número de Invitados'>" . htmlspecialchars($fila['guests_number']) . "</td>";
                    echo "<td data-label='Fecha de la Fiesta'>" . htmlspecialchars($fila['reservation_date']) . "</td>";
                    echo "<td data-label='Hora Preferida'>" . htmlspecialchars($fila['reservation_time']) . "</td>";
                    echo "<td data-label='Color de Decoración'><div class='color-preview' style='background-color:" . htmlspecialchars($fila['fav_color_decor']) . "'></div>" . htmlspecialchars($fila['fav_color_decor']) . "</td>";
                    echo "<td data-label='Estilo de Fiesta'>" . htmlspecialchars($fila['kitty_style']) . "</td>";
                    echo "<td data-label='Servicios Adicionales'>" . htmlspecialchars($fila['addons']) . "</td>";
                    echo "<td data-label='Peticiones Especiales'>" . nl2br(htmlspecialchars($fila['special_requests'])) . "</td>";
                    echo "<td data-label='Personaje Favorito'>" . htmlspecialchars($fila['sanrio_favorite_character']) . "</td>";
                    
                    echo "<td data-label='Canción Especial'>";
                    if (!empty($fila['song_file_path'])) {
                        $songFileName = basename($fila['song_file_path']);
                        echo "<a href='" . htmlspecialchars($fila['song_file_path']) . "' target='_blank'>" . htmlspecialchars($songFileName) . "</a>";
                    } else {
                        echo "N/A";
                    }
                    echo "</td>";

                    echo "<td data-label='Número de Tarjeta (Placeholder)'>";
                    if (!empty($fila['card_number_placeholder'])) {
                        echo "**********";
                    } else {
                        echo "N/A";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='13'>No hay reservaciones registradas aún.";
                if ($search_id !== null && $search_id !== '') {
                    echo " ID inexistente: " . htmlspecialchars($search_id);
                }
                echo "</td></tr>";
            }

            if ($stmt) {
                $stmt->close();
            }
            $mysqli->close();
        } catch (Exception $e) {
            echo "<tr><td colspan='13' class='error-message'>Error al cargar las reservas: {$e->getMessage()}</td></tr>";
        }
        ?>
    </tbody>
</table>

</body>
</html>

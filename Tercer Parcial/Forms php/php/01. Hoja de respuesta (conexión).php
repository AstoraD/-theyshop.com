<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Salón reservado!</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #ffccd5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            flex-direction: column;
        }

        .container {
            background-color: #ff8fa3;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 4px 8px #ff758f;
            max-width: 500px;
            width: 90%;
            box-sizing: border-box;
        }

        h1 {
            color: #c9184a;
            margin-bottom: 20px;
            font-size: 2.5em;
        }

        p {
            color: white;
            margin-bottom: 15px;
            font-size: 1.1em;
        }

        .capy-image {
            width: 200px;
            height: auto;
            border-radius: 10px;
            margin-top: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px #ff758f;
        }

        .result-message {
            font-size: 1.3em;
            font-weight: bold;
            color: #c9184a;
            margin-top: 20px;
            margin-bottom: 20px; 
        }

        a {
            color: #c9184a;
            text-decoration: none;
            font-weight: bold;
            margin-top: 10px;
            display: inline-block;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #ff0a54;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>¡En hora buena!</h1>
        <p>Ha logrado hacer su reservación con exito</p>
        <p>¡Esperamos se la pase excelente!</p>
        <img src="/PracticasForms/gif/kittycelebrando.gif" alt="Hello Kitty celebra" class="capy-image">
        <p>Lo esperaremos con ansias</p>

        <?php
        try {
            $mysqli = new mysqli("127.0.0.1", "root", '', "reservación", 3306);
            if ($mysqli->connect_error) {
                throw new Exception("Error de Conexión a la Base de Datos: " . $mysqli->connect_error);
            }

            $host_name = $_POST["host_name"] ?? '';
            $email_contact = $_POST["email_contact"] ?? '';
            $phone_number = $_POST["phone_number"] ?? '';
            
            $guests_number = $_POST["guests_number"] ?? 10;
            
            $reservation_date = $_POST["reservation_date"] ?? '';
            $reservation_time = $_POST["reservation_time"] ?? '';
            $fav_color_decor = $_POST["fav_color_decor"] ?? '';
            $kitty_style = $_POST["kitty_style"] ?? '';
            $addons = isset($_POST["addons"]) ? implode(", ", (array)$_POST["addons"]) : '';
            $special_requests = $_POST["special_requests"] ?? '';
            $sanrio_favorite_character = $_POST["username"] ?? '';
            $card_number_placeholder = $_POST["pwd"] ?? '';

            $song_file_path = null; 

            if (isset($_FILES["myfile"]) && $_FILES["myfile"]["error"] === UPLOAD_ERR_OK) {
                $directorio = "CANCIONES/";
                if (!file_exists($directorio)) {
                    if (!mkdir($directorio, 0777, true)) {
                        throw new Exception("Error al crear el directorio de canciones: Asegúrate de tener permisos de escritura en la ruta: " . $directorio);
                    }
                }

                $nombreArchivoOriginal = basename($_FILES["myfile"]["name"]);
                $song_file_path = $directorio . time() . "_" . $nombreArchivoOriginal;
                
                if (!move_uploaded_file($_FILES["myfile"]["tmp_name"], $song_file_path)) {
                    throw new Exception("Error al subir el archivo de canción: No se pudo mover el archivo subido. Verifica los permisos del directorio.");
                }
            } elseif (isset($_FILES["myfile"]) && $_FILES["myfile"]["error"] !== UPLOAD_ERR_NO_FILE) {
                throw new Exception("Error desconocido al subir el archivo de canción. Código de error: " . $_FILES["myfile"]["error"]);
            }

            $stmt = $mysqli->prepare("INSERT INTO reservaciones (host_name, email_contact, phone_number, guests_number, reservation_date, reservation_time, fav_color_decor, kitty_style, addons, special_requests, sanrio_favorite_character, song_file_path, card_number_placeholder) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt === false) {
                throw new Exception("Error al preparar la consulta SQL: " . $mysqli->error . ". Verifica que la tabla 'reservaciones' exista y que los nombres de las columnas sean correctos.");
            }

            $stmt->bind_param("sssisssssssss", 
                $host_name,
                $email_contact,
                $phone_number,
                $guests_number,
                $reservation_date,
                $reservation_time,
                $fav_color_decor,
                $kitty_style,
                $addons,
                $special_requests,
                $sanrio_favorite_character,
                $song_file_path,
                $card_number_placeholder
            );

            if ($stmt->execute()) {
                
                echo "<p><a href='/PracticasForms/Forms%20php/php/01.%20Tablita.php'>Ver registro</a></p>"; 
            } else {
                echo "<p class='result-message'>ERROR AL GUARDAR LA RESERVA EN LA BASE DE DATOS: " . $stmt->error . "</p>";
            }

            $stmt->close();
            $mysqli->close();

        } catch (Exception $e) {
            echo "<p class='result-message'>Error en el procesamiento de la reserva: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
</body>
</html>

<?php
session_start();

$mysqli = new mysqli("127.0.0.1", "root", "", "reservación", 3306);

if ($mysqli->connect_error) {
    header("Location: /PracticasForms/Forms%20php/php/01.Opciones%20(borrar,actualizar).php?mensaje=error_db_eliminar&error=" . urlencode($mysqli->connect_error));
    exit();
}

if (isset($_GET["id"])) {
    $id = intval($_GET["id"]); //variable entero
    
    //variable que contiene un objeto, relacionado con sentencias preparadas
    $stmt = $mysqli->prepare("DELETE FROM reservaciones WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Redirige de vuelta a la página de opciones con un mensaje de éxito
        header("Location: /PracticasForms/Forms%20php/php/01.Opciones%20(borrar,actualizar).php?mensaje=eliminado");
        exit();
    } else {
        // Redirige de vuelta a la página de opciones con un mensaje de error
        header("Location: /PracticasForms/Forms%20php/php/01.Opciones%20(borrar,actualizar).php?mensaje=error_eliminar&error=" . urlencode($stmt->error));
        exit();
    }
    $stmt->close();
} else {
    // Si no se proporcionó un ID, redirige a la página de opciones con un mensaje
    header("Location: /PracticasForms/Forms%20php/php/01.Opciones%20(borrar,actualizar).php?mensaje=no_id_eliminar");
    exit();
}



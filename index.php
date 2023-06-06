<?php

require 'flight/Flight.php';

Flight::register('db', 'PDO', array('mysql:host=localhost;dbname=caso_23','root',''));

Flight::route('GET|POST /login/@email/@password', function ($email, $password) {
    $sql = Flight::db() -> prepare("SELECT * FROM cliente WHERE CorreoElectronico = '$email' AND contrasenia = '$password'");
    $sql -> execute();
    $response = $sql -> fetchAll();

    Flight::json($response);
});

Flight::route('GET|POST /new_account/@name/@last_name/@email/@password', function ($name, $last_name, $email, $password) {
    $sql = Flight::db() -> prepare("INSERT INTO cliente (Nombre, Apellido, CorreoELectronico, contrasenia) VALUES ('$name', '$last_name', '$email', '$password')");
    $sql -> execute();
    $response = $sql -> fetchAll();

    Flight::json($response);
    Flight::redirect("/login/$email/$password");
});



Flight::start();

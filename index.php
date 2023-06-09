<?php

require 'flight/Flight.php';

Flight::register('db', 'PDO', array('mysql:host=localhost;dbname=caso_23', 'root', ''));

Flight::route('GET|POST /login', function () {
    $password = (Flight::request()->data->password);
    $email = (Flight::request()->data->email);

    $sql = Flight::db()->prepare("SELECT * FROM cliente WHERE CorreoElectronico = '$email' AND contrasenia = '$password'");
    $sql->execute();
    $response = $sql->fetchAll();

    Flight::set('userId', $response[0]["Id"]);
    // $id = Flight::get('userId');
    Flight::json($response);
});

Flight::route('GET|POST /new_account', function () {
    $name = (Flight::request()->data->name);
    $last_name = (Flight::request()->data->last_name);
    $email = (Flight::request()->data->email);
    $password = (Flight::request()->data->password);

    $sql = Flight::db()->prepare("INSERT INTO cliente (Nombre, Apellido, CorreoELectronico, contrasenia) VALUES ('$name', '$last_name', '$email', '$password')");
    $sql->execute();
    $response = $sql->fetchAll();

    Flight::json($response);
});



Flight::start();

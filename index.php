<?php
header("Access-Control-Allow-Origin: *");

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

header("Access-Control-Allow-Headers: Content-Type");

header("Content-Type: application/json");

require 'flight/Flight.php';

Flight::register('db', 'PDO', array('mysql:host=localhost;dbname=caso_23', 'root', ''));

# LOGIN

Flight::route('GET|POST /login/@email/@password', function ($email, $password) {

    // $password = (Flight::request()->data->password);
    // $email = (Flight::request()->data->email);

    $sql = Flight::db()->prepare("SELECT * FROM cliente WHERE CorreoElectronico = '$email' AND contrasenia = '$password'");
    $sql->execute();
    $response = $sql->fetchAll();

    if ($response) {
        // Flight::set('userId', $response[0]["Id"]);

        Flight::json($response);
    } else {
        echo "Error";
    }
});

# NEW_ACCOUNT || SIGN UP

Flight::route('GET|POST /new_account/@name/@last_name/@email/@password/@admin/', function ($name, $last_name, $email, $password, $admin ) {
    try {
        // $name = (Flight::request()->data->name);
        // $last_name = (Flight::request()->data->last_name);
        // $email = (Flight::request()->data->email);
        // $password = (Flight::request()->data->password);
        // $admin = (Flight::request()->data->admin);

        $sql = Flight::db()->prepare("INSERT INTO cliente (Nombre, Apellido, CorreoELectronico, contrasenia, Admin)
        VALUES ('$name', '$last_name', '$email', '$password', $admin)");

        $sql->execute();
        $response = $sql->fetchAll();
        $rowCount = $sql->rowCount();

        if ($rowCount > 0) {
            echo "Good";
        } else {
            echo "error";
        }
    } catch (Exception $e) {
        echo "Error";
    }
});

# PLANS

Flight::route('GET /plans', function () {
    try {
        $sql = Flight::db()->prepare("SELECT p.Id, p.Nombre, p.Duracion, c.Nombre, c.Horario
        FROM planmembresia p
        INNER JOIN membresia m ON m.PlanMembresiaId = p.Id
        INNER JOIN inscripcionclase ic ON ic.MembresiaId = m.Id
        INNER JOIN clase c ON ic.ClaseId = c.Id
        WHERE (p.Id, c.Id) IN (
            SELECT p1.Id, MIN(c1.Id)
            FROM planmembresia p1
            INNER JOIN membresia m1 ON m1.PlanMembresiaId = p1.Id
            INNER JOIN inscripcionclase ic1 ON ic1.MembresiaId = m1.Id
            INNER JOIN clase c1 ON ic1.ClaseId = c1.Id
            GROUP BY p1.Id
        )
        ORDER BY p.Id, c.Id;
        ");

        $sql->execute();
        $response = $sql->fetchAll();

        Flight::json($response);
    } catch (Exception $e) {
        echo "Error";
    }
});

# MEMBERSHIP || PLAN

Flight::route('GET /membership/@id', function ($id) {
    try {

        $sql = Flight::db()->prepare("SELECT planmembresia.Nombre, planmembresia.Duracion, clase.Nombre, clase.Horario
        FROM planmembresia, clase, membresia, inscripcionclase WHERE membresia.ClienteId = $id
        AND membresia.PlanMembresiaId = planmembresia.Id AND inscripcionclase.MembresiaId = membresia.Id
        AND inscripcionclase.ClaseId = clase.Id;");

        $sql->execute();
        $response = $sql->fetchAll();

        Flight::json($response);
    } catch (Exception $e) {
        echo "Error";
    }
});

# CLASSES BY USER

Flight::route('GET /class/@id', function ($id) {
    try {
        $sql = Flight::db()->prepare("SELECT clase.Nombre, clase.Descripcion
        FROM clase, membresia, inscripcionclase, planmembresia
        WHERE membresia.ClienteId = $id
        AND membresia.PlanMembresiaId = planmembresia.Id
        AND inscripcionclase.MembresiaId = membresia.Id
        AND inscripcionclase.ClaseId = clase.Id;");

        $sql->execute();
        $response = $sql->fetchAll();

        Flight::json($response);
    } catch (Exception $e) {
        echo "Error";
    }
});

# PROFILE

Flight::route('GET /profile/@id/@name/@last_name/@email/@password/@admin', function ($id, $name, $last_name, $email, $password, $admin) {
    try {
        // $name = Flight::request()->data->name;
        // $last_name = Flight::request()->data->last_name;
        // $email = Flight::request()->data->email;
        // $password = Flight::request()->data->password;
        // $admin = Flight::request()->data->admin;

        $sql = Flight::db()->prepare("UPDATE cliente SET Nombre='$name', Apellido='$last_name',
        CorreoElectronico='$email', contrasenia='$password', Admin=$admin WHERE id = $id");

        $sql->execute();
        $response = $sql->fetchAll();

        Flight::json("Good");
    } catch (Exception $e) {
        echo "Error";
    }
});

# SEARCH || FILTER

Flight::route('GET /filter', function () {
    try {
        // filter?name=Black
        $name = Flight::request()->query['name'];

        $name = ucwords($name);
        $name = trim($name);

        $sql = Flight::db()->prepare("SELECT planmembresia.Nombre, planmembresia.Duracion, clase.Nombre, clase.Horario
        FROM planmembresia, clase, membresia, inscripcionclase
        WHERE planmembresia.Nombre = '$name'
        AND membresia.PlanMembresiaId = planmembresia.Id
        AND inscripcionclase.MembresiaId = membresia.Id
        AND inscripcionclase.ClaseId = clase.Id;");

        $sql->execute();
        $response = $sql->fetchAll();

        Flight::json($response);
    } catch (Exception $e) {
        echo "Error";
    }
});

# MEMBERSHIP SELECTION

Flight::route('GET|POST /membership_selection/@id', function ($id) {
    try {

        $planId = Flight::request()->data->planId;
        $start_date = Flight::request()->data->start_date;

        $sql = Flight::db()->prepare("SELECT cliente.Id
        FROM cliente
        WHERE cliente.Id NOT IN (
          SELECT membresia.ClienteId
          FROM membresia
        )");


        $sql->execute();
        $response = $sql->fetchAll();

        $found = false;
        foreach ($response as $row) {
            if ($row['Id'] == $id) {
                $found = true;
                break;
            }
        }

        if ($found) {
            $sql = Flight::db()->prepare("INSERT INTO membresia (ClienteId, PlanMembresiaId, FechaInicio)
            VALUES ($id, $planId, '$start_date')");

            $sql->execute();
            $response2 = $sql->fetchAll();

        } else {
            $sql = Flight::db()->prepare("UPDATE membresia SET PlanMembresiaId = $planId, FechaInicio = '$start_date'
            WHERE ClienteId = $id");

            $sql->execute();
            $response2 = $sql->fetchAll();
        }


        Flight::json($response);
    } catch (Exception $e) {
        echo "Error";
    }
});


# ADMIN || DASHBOARD || RECORDS

Flight::route('GET /count_records', function () {
    try {
        $sql = Flight::db()->prepare("SELECT
        (SELECT COUNT(*) FROM cliente) AS total_clientes,
        (SELECT COUNT(*) FROM membresia) AS total_membresias,
        (SELECT COUNT(*) FROM clase) AS total_clases;
        ");

        $sql->execute();
        $response = $sql->fetchAll();

        Flight::json($response);
    } catch (Exception $e) {
        echo "Error";
    }
});

# -----------------

# CUSTOMERS

Flight::route('GET /customers', function () {
    try {
        $sql = Flight::db()->prepare("SELECT * FROM cliente WHERE 1");

        $sql->execute();
        $response = $sql->fetchAll();

        Flight::json($response);
    } catch (Exception $e) {
        echo "Error";
    }
});

# MEMBERSHIPS

Flight::route('GET /memberships', function () {
    try {
        $sql = Flight::db()->prepare("SELECT membresia.Id, cliente.Nombre, planmembresia.Nombre,
        planmembresia.Duracion, membresia.FechaInicio
        FROM cliente, planmembresia, membresia
        WHERE membresia.PlanMembresiaId = planmembresia.Id
        AND membresia.ClienteId = cliente.Id");

        $sql->execute();
        $response = $sql->fetchAll();

        Flight::json($response);
    } catch (Exception $e) {
        echo "Error";
    }
});

# CLASSES

Flight::route('GET /classes', function () {
    try {
        $sql = Flight::db()->prepare("SELECT * FROM clase WHERE 1;");

        $sql->execute();
        $response = $sql->fetchAll();

        Flight::json($response);
    } catch (Exception $e) {
        echo "Error";
    }
});

# -----------------

# SEARCH CUSTOMER BY ID

Flight::route('GET /customer/@customerId', function ($customerId) {
    try {

        $sql = Flight::db()->prepare("SELECT * FROM cliente WHERE Id = $customerId;");

        $sql->execute();
        $response = $sql->fetchAll();

        Flight::json($response);
    } catch (Exception $e) {
        echo "Error";
    }
});

# SEARCH MEMBERSHIP BY ID

Flight::route('GET /show_membership/@membershipId', function ($membershipId) {
    try {

        $sql = Flight::db()->prepare("SELECT membresia.Id, cliente.Nombre AS Cliente, planmembresia.Nombre,
        planmembresia.Duracion, membresia.FechaInicio
        FROM cliente, planmembresia, membresia
        WHERE membresia.PlanMembresiaId = planmembresia.Id
        AND membresia.ClienteId = cliente.Id AND membresia.Id = $membershipId");

        $sql->execute();
        $response = $sql->fetchAll();

        Flight::json($response);
    } catch (Exception $e) {
        echo "Error";
    }
});

# SEARCH CLASS BY ID

Flight::route('GET /show_class/@classId', function ($classId) {
    try {

        $sql = Flight::db()->prepare("SELECT * FROM clase WHERE Id = $classId");

        $sql->execute();
        $response = $sql->fetchAll();

        Flight::json($response);
    } catch (Exception $e) {
        echo "Error";
    }
});

# -----------------

# EDIT CUSTOMER

Flight::route('POST /update_membership/@membershipId', function ($membershipId) {
    try {

        $planId = Flight::request()->data->planId;
        $start_date = Flight::request()->data->start_date;

        $sql = Flight::db()->prepare("UPDATE membresia SET PlanMembresiaId = $planId, FechaInicio = '$start_date'
            WHERE ClienteId = $membershipId");

        $sql->execute();
        $response = $sql->fetchAll();

        Flight::json($response);
    } catch (Exception $e) {
        echo "Error";
    }
});

# EDIT CLASS

Flight::route('POST /update_class/@classId', function ($classId) {
    try {

        $name = Flight::request()->data->name;
        $horary = Flight::request()->data->horary;
        $description = Flight::request()->data->description;

        $sql = Flight::db()->prepare("UPDATE clase
        SET Nombre = '$name', Horario = '$horary', Descripcion = '$description'
        WHERE Id = $classId;");

        $sql->execute();
        $response = $sql->fetchAll();

        Flight::json($response);
    } catch (Exception $e) {
        echo "Error";
    }
});

# -----------------

# DELETE CUSTOMER

Flight::route('DELETE /delete_customer/@customerId', function ($customerId) {
    try {
        $sql = Flight::db()->prepare("DELETE FROM cliente WHERE Id = $customerId");

        $sql->execute();

        if ($sql->rowCount() > 0) {
            echo "Good";
        } else {
            echo "Error";
        }

    } catch (Exception $e) {
        echo "Error";
    }
});

# DELETE MEMBERSHIP

Flight::route('DELETE /delete_membership/@membershipId', function ($membershipId) {
    try {
        $sql = Flight::db()->prepare("DELETE FROM membresia WHERE Id = $membershipId");

        $sql->execute();

        if ($sql->rowCount() > 0) {
            echo "Good";
        } else {
            echo "Error";
        }

    } catch (Exception $e) {
        echo "Error";
    }
});

# DELETE CLASS

Flight::route('DELETE /delete_class/@classId', function ($classId) {
    try {
        $sql = Flight::db()->prepare("DELETE FROM clase WHERE Id = $classId");

        $sql->execute();

        if ($sql->rowCount() > 0) {
            echo "Good";
        } else {
            echo "Error";
        }

    } catch (Exception $e) {
        echo "Error";
    }
});

# -----------------

# ADMIN || FILTER || SEARCH

Flight::route('GET /admin/filter', function () {
    try {
        $name = Flight::request()->query['name'];

        $name = ucwords($name);
        $name = trim($name);

        $sql = Flight::db()->prepare("SELECT * FROM cliente WHERE cliente.Nombre LIKE '%$name%';");

        $sql->execute();
        $response = $sql->fetchAll();

        Flight::json($response);
    } catch (Exception $e) {
        echo "Error";
    }
});

Flight::before('json', function () {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE');
    header('Access-Control-Allow-Headers: Content-Type');
});

Flight::start();

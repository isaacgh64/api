<?php
      function connectionBD(){
        $server = "localhost";
        $user = "admin_kine";
        $pw = "Prueba-123!";
        $bd="kinestream";
        $conexion = new PDO('mysql:host='.$server.';dbname='.$bd.'', $user, $pw);
        return $conexion;
    }
?>
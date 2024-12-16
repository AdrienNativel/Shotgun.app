<?php

require 'headers/cors_header.php';
require 'database.php';

if (isset($_POST["code"])){

    $dbh = Database::connect();
    //On regarde si le shotgun existe
    if (Database::getIfSgExists($dbh, $_POST["code"])){
        //On regarde si le shotgun est ouvert: vaut 1 sur la shotgun est ouvert, 0 sinon
        $ouverture = (Database::dateEtHeure($dbh, $_POST["code"]))[0]["ouverture"];

        //Si le shotgun est ouvert
        if($ouverture==1){     
            $resultat = array('ready' => true, 'id' => Database::getId($dbh));
            echo json_encode($resultat);
        }
        //Sinon
        else{
            $resultat = array('ready' => false, 'id' => null);
            echo json_encode($resultat);
        }
    }
    else{
        $resultat = array_merge($_POST,array('resultat' => "Il y a eu une erreur"));
        echo json_encode($resultat);
        exit();
    }
}
else{
    $resultat = array_merge($_POST,array('resultat' => "Il y a eu une erreur"));
    echo json_encode($resultat);
    exit();
}
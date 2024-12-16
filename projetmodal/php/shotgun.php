<?php

require 'headers/cors_header.php';
require 'database.php';

if (isset($_POST["id"]) && isset($_POST["token"]) && isset($_POST["nomsg"])){

    $dbh = Database::connect();

    $result=Database::getRank($dbh, $_POST["id"], $_POST["token"], $_POST["nomsg"]);
    //On exclut le cas où il n'y a plus de places
    if ($result[1]!=0){
        //Cas où on est dans la file d'attente: on regarde le rang
        if ($result[1]==1){
            $rank = $result[0];
            echo json_encode(array("rang" => $rank, "finalisation" => "non", "place" => "oui"));
        }
        //Cas où on n'y est plus car on est dans finalisation
        else if($result[0]==true){
            echo json_encode(array("rang" => 0, "finalisation" => "oui", "place" => "oui"));
        }
        //Cas où on a été retiré de la table
        else{
            echo json_encode(array("rang" => -1, "finalisation" => "non", "place" => "oui"));
        }
    }
    //Cas où il n'y a plus de places
    else{
        echo json_encode(array("rang" => -1, "finalisation" => "non", "place" => "non"));
    }
}
else{
    $resultat = array_merge($_POST["id"],array('resultat' => "Il y a eu une erreur, shotgun.php"));
    echo json_encode($resultat);
    exit();
}


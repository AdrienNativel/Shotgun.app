<?php

require 'headers/cors_header.php';
require 'database.php';

if (isset($_POST["nomsg"])) {
    $dbh = Database::connect();
    $nomsg = $_POST["nomsg"];
    //On récupère l'image associée au shotgun
    $result = Database::afficheImage($dbh, $nomsg);
    $resultat = array();
    //Cas où il n'y a pas d'image
    if (empty($result)) {
        $resultat = array_merge(array('nomsg' => $_POST["nomsg"]), array('image' => "Vide"));
        echo json_encode($resultat);
    }
    //Cas où il y a une image
    else {
        $resultat['nomsg'] = $_POST["nomsg"];
        $resultat['image'] = $result[0];
        echo json_encode($resultat);
    }
} else {
    $resultat = array_merge(array('nomsg' => $_POST["nomsg"]), array('resultat' => "Il y a eu une erreur, image.php"));
    echo json_encode($resultat);
    exit();
}

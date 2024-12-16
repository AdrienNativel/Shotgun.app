<?php

require 'headers/cors_header.php';
require 'database.php';

if(!empty($_POST['nomBinet'])&& !empty($_POST['nomSg'])&& !empty( $_POST['nbPlaces'])&& !empty( $_POST['description'])&&!empty( $_POST['date'])&&!empty( $_POST['heure'])){
    $dbh = Database::connect();
    //On vérifie qu'il n'y a pas d'espace dans le nom du shotgun
    if(strpos($_POST['nomSg']," ")){
        $resultat = array_merge($_POST,array('resultat' => "Il y a eu une erreur lors de la création du shotgun: le nom du shotgun contient un espace"));
        echo json_encode($resultat);
        exit();
    }
    //On vérifie qu'aucun shotgun de ce nom n'existe pas déjà
    else if (Database::getIfSgExists($dbh, $_POST['nomSg'])){
        $resultat = array_merge($_POST,array('resultat' => "Un shotgun de ce nom existe déjà"));
        echo json_encode($resultat);
        exit();
    }
    //S'il y a une image
    else if(isset($_POST['image'])){
        //On insert l'élément dans la table catalogue
        if(Database::insertSg2($dbh, $_POST['nomSg'], $_POST['nomBinet'], $_POST['nbPlaces'], $_POST['description'], $_POST['date'], $_POST['heure'], $_POST['image'])){
            $resultat = array_merge($_POST,array('resultat' => "Le shotgun a bien été créé"));
            echo json_encode($resultat);
        }
        else{
            $resultat = array_merge($_POST,array('resultat' => "Il y a eu une erreur lors de la création du shotgun"));
            echo json_encode($resultat);
            exit();
        }
    }
    //S'il n'y a pas d'image
    else{
        if(Database::insertSg1($dbh, $_POST['nomSg'], $_POST['nomBinet'], $_POST['nbPlaces'], $_POST['description'], $_POST['date'], $_POST['heure'])){
            $resultat = array_merge($_POST,array('resultat' => "Le shotgun a bien été créé"));
            echo json_encode($resultat);
        }
        else{
            $resultat = array_merge($_POST,array('resultat' => "Il y a eu une erreur lors de la création du shotgun"));
            echo json_encode($resultat);
            exit();
        }
    }
}
else{
    $error = array_merge(array('resultat' => "L'un des champs n'est pas renseigné"));
    echo json_encode($error);
    exit();
}
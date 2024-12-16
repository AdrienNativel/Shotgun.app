<?php

require 'headers/cors_header.php';
require 'database.php';

if(isset($_POST['identifiant'], $_POST['mdp1'], $_POST['mdp2'], $_POST['nom'], $_POST['prenom'], $_POST['mail'], $_POST['binet'])){
    //On se connecte a la base apres un minimum de verifications
    $dbh = Database::connect();
    //On vérifie l'égalité des mots de passe
    if ($_POST['mdp1']!==$_POST['mdp2']){
        $resultat = array_merge($_POST,array('resultat' => "Echec: mots de passe différents"));
        echo json_encode($resultat);
        exit();
    }
    //On vérifie que l'utilisateur n'existe pas déjà
    else if (Database::getIfUserExists($dbh, $_POST['identifiant'])){
        $resultat = array_merge($_POST,array('resultat' => "Echec: utilisateur existe déjà"));
        echo json_encode($resultat);
        exit();
    }
    //Si la création du compte est un succès
    else if(Database::insertUser($dbh, $_POST['identifiant'], $_POST['mdp1'], $_POST['prenom'], $_POST['nom'], $_POST['mail'], $_POST['binet'])){
        $resultat = array_merge($_POST,array('resultat' => "Votre compte a bien été créé"));
        echo json_encode($resultat);
    }
    else{
        $error = array_merge($_POST,array('resultat' => "Il y a eu une erreur"));
        echo json_encode($resultat);
        exit();
    }
}
else{
    $error = array_merge($_POST,array('resultat' => "Il y a eu une erreur"));
    echo json_encode($error);
    exit();
}
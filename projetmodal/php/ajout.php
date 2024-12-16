<?php

require 'headers/cors_header.php';
require 'database.php';

if(!empty($_POST['nom'])&& !empty($_POST['prenom'])&& !empty( $_POST['trigramme'])&&!empty($_POST['id'])&&!empty($_POST['token'])&&!empty($_POST['token'])&&!empty($_POST['nomsg'])){
    
    $dbh = Database::connect();

    // On determine le nombre de places allouées pour ce shotgun
    $query = "SELECT nbplaces FROM catalogue WHERE nomsg=?";
    $sth = $dbh->prepare($query);
    $sth->execute(array($_POST['nomsg']));
    $result = $sth->fetchColumn();

    try {
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // On choisit ici le niveau de verrou le plus contraignant 

        $dbh->exec("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");

        $dbh->beginTransaction();

         // Insérer l'utilisateur dans la table inscrits quitte a faire un rollback plus tard 
        $query = "INSERT INTO inscrits (trigramme, prenom, nom) VALUES (?, ?, ?)";
        $sth = $dbh->prepare($query);
        $sth->execute(array($_POST['trigramme'], $_POST['prenom'], $_POST['nom']));
    
        // Compter les personnes déjà inscrites
        $query = "SELECT COUNT(*) FROM inscrits FOR UPDATE";
        $sth = $dbh->prepare($query);
        $sth->execute();
        $countInscrits = $sth->fetchColumn();
 
        // Vérifier s'il reste des places
        $placesDisponibles = $result - $countInscrits;
        if ($placesDisponibles < 0) {
            // Annuler la transaction et libérer les verrous avant de quitter
            if ($dbh->inTransaction()) {
                $dbh->rollBack();
            }
            $dbh->exec("UNLOCK TABLES");
            echo json_encode(array("resultat" => "L'inscription a échoué. Il n'y a plus de places."));
            return;
        }

        //on retarde ici volontairement la requete pour simuler des transations concurrentes 
        sleep(5);

        //On vérifie que la personne etait bien dans fincalisation pour des raisons de sécurité
        
        $query = "SELECT COUNT(*) FROM finalisation WHERE id = ? AND token = ?";
        $sth = $dbh->prepare($query);
        $sth->execute(array($_POST['id'], $_POST['token']));
        $count = $sth->fetchColumn();
        $err=3;


        if ($count != 1) {
            // Annuler la transaction si ce n'est pas le cas et libérer les verrous avant de quitter
            if ($dbh->inTransaction()) {
                $dbh->rollBack();
            }
            $dbh->exec("UNLOCK TABLES");
            echo json_encode(array("resultat" => "L'inscription a échoué. Identifiants invalides."));
            return;
        }

        // On retire alors la personne de la table finalisation 


        $query = "DELETE FROM finalisation WHERE id = ?";
        $sth = $dbh->prepare($query);
        $sth->execute(array($_POST['id']));
        $err=4;

        // Valider la transaction si tout s'est bien passé
        $dbh->commit();
        $resultat = "Vous êtes inscrit!";
    
        // Libérer les verrous
        $dbh->exec("UNLOCK TABLES");
        $err=6;
    
        echo json_encode(array("resultat" => $resultat));
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $dbh->exec("UNLOCK TABLES");
        if ($dbh->inTransaction()) {
            $dbh->rollBack();
        }
        //Afficher le status de innoDb pour voir d'ou vient l'erreur
        $query="SHOW ENGINE INNODB STATUS;";
        $sth = $dbh->prepare($query);
        $sth->execute();
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        //retourner cette erreur
        echo json_encode(array("resultat" => "Erreur : " . $e->getMessage() .$err .$result['Status'] ));
        return;
    }
    

}
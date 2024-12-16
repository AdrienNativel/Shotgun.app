<?php

class Database
{
    public static function connect()
    {
        $dbName   = 'adrien_nativel';
        $dbServer = '127.0.0.1';
        $dbUser   = 'root';
        $dbPass   = 'modal';

        $dsn = 'mysql:dbname='.$dbName.';host='.$dbServer;
        $dbh = null;
        try {
            $dbh = new PDO($dsn, $dbUser, $dbPass, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"));
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            return false;
        }

        return $dbh;
    }

    //Sélectionne les shotguns aujourd'hui et à venir
    public static function getCatalogue($dbh){
        $query = "SELECT * FROM catalogue WHERE DATEDIFF(date,CURRENT_DATE)>=0 ORDER BY date, heure";
        $sth = $dbh->prepare($query);
        $sth->execute();
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    //Regarde si l'utilisateur existe déjà
    public static function getIfUserExists($dbh, $login){
        $query = "SELECT COUNT(*) FROM oauth_users WHERE username=?";
        $sth = $dbh->prepare($query);
        $sth->execute(array($login));
        $count = $sth->fetchColumn();
        if($count==1){
            return true;
        }
        return false;
    }

    //Regarde si un shotgun du meme nom existe déjà
    public static function getIfSgExists($dbh, $nomsg){
        $query = "SELECT COUNT(*) FROM catalogue WHERE nomsg=?";
        $sth = $dbh->prepare($query);
        $sth->execute(array($nomsg));
        $count = $sth->fetchColumn();
        return ($count==1);
    }

    //Insert un utilisateur dans la table oauth_users
    public static function insertUser($dbh, $login, $password, $prenom, $nom, $mail, $binet){
        $query = "INSERT INTO oauth_users (username, password, first_name, last_name, email, nomBinet) VALUES (?, ?, ?, ?, ?, ?);";
        $sth = $dbh->prepare($query);
        $sth->execute(array($login, SHA1($password), $prenom, $nom, $mail, $binet));
        $count = $sth->rowCount();
        return ($count==1);
    }

    //Ajoute un shotgun où il n'y a pas d'image
    public static function insertSg1($dbh, $nomsg, $nombinet, $nbplaces, $description, $date, $heure){
        $query = "INSERT INTO catalogue (nomsg, nombinet, nbplaces, description, date, heure) VALUES (?, ?, ?, ?, ?, ?, NULL);";
        $sth = $dbh->prepare($query);
        $sth->execute(array($nomsg, $nombinet, $nbplaces, $description, $date, $heure));
        $count = $sth->rowCount();
        return ($count==1);
    }

    //Ajoute un shotgun avec une image
    public static function insertSg2($dbh, $nomsg, $nombinet, $nbplaces, $description, $date, $heure, $photo){
        $query = "INSERT INTO catalogue (nomsg, nombinet, nbplaces, description, date, heure, image) VALUES (?, ?, ?, ?, ?, ?, ?);";
        $sth = $dbh->prepare($query);
        $sth->execute(array($nomsg, $nombinet, $nbplaces, $description, $date, $heure, $photo));
        $count = $sth->rowCount();
        return ($count==1);
    }

    //Détermine si le shotgun est ouvert ou non (si on a dépassé la date et l'heure définis)
    public static function dateEtHeure($dbh, $nomsg){
        $query = "SELECT
            ((DATEDIFF((SELECT date FROM catalogue WHERE nomsg = ?), CURRENT_DATE) <= 0 AND TIMEDIFF((SELECT heure FROM catalogue WHERE nomsg = ?), CURRENT_TIME) <= 0) ) OR
            (DATEDIFF((SELECT date FROM catalogue WHERE nomsg =?), CURRENT_DATE) <= -1) AS ouverture
        FROM 
            catalogue 
        WHERE 
        nomsg = ?";
        $sth = $dbh->prepare($query);
        $sth->execute(array($nomsg, $nomsg,$nomsg,$nomsg ));
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    //Retourne l'image associée à un shotgun
    public static function afficheImage($dbh, $nomsg){
        $query = "SELECT image FROM catalogue WHERE nomsg=?";
        $sth = $dbh->prepare($query);
        $sth->execute(array($nomsg));
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    //Ajoute un utilisateur dans la file d'attente et retourne son id et son token
    public static function getId($dbh) {
        // Créer un objet DateTime pour récupérer l'heure actuelle
        $date = new DateTime();
        $heure = $date->format('H:i:s');
    
        // Générer un token unique
        $token = md5(uniqid(microtime(true), true));
    
        // Préparer et exécuter la requête d'insertion
        $query = "INSERT INTO filedattente (token, heure) VALUES (?, ?)";
        $sth = $dbh->prepare($query);
        $sth->execute(array($token, $heure));
    
        // Récupérer l'ID du dernier enregistrement inséré
        $id = $dbh->lastInsertId();
    
        // Retourner un tableau contenant l'ID et le token
        return array('id' => $id, 'token' => $token); // Correction ici
    }

    //Détermine s'il reste des places
    //Si l'utilisateur est passé dans finalisation
    //Son rang s'il est encore dans la file d'attente
    public static function getRank($dbh, $id, $token, $nomsg) {
        //Nombre de places total du shotgun
        $query = "SELECT nbplaces FROM catalogue WHERE nomsg=?";
        $sth = $dbh->prepare($query);
        $sth->execute(array($nomsg));
        $result = $sth->fetchColumn();
        //Compte les personnes déjà inscrites dans la table inscrits
        $query = "SELECT COUNT(*) FROM inscrits";
        $sth = $dbh->prepare($query);
        $sth->execute();
        $count1 = $sth->fetchColumn();
        //Si il reste des places
        if($result -$count1 > 0) {
            //On regarde si on est encore dans filedattente
            $query = "SELECT COUNT(*) FROM filedattente WHERE id= ? AND token=?";
            $sth = $dbh->prepare($query);
            $sth->execute(array($id, $token));
            $count = $sth->fetchColumn();
            //Si oui on update l'heure et on regarde le rang
            if($count==1){
                $query1 ="UPDATE filedattente SET heure = CURRENT_TIME WHERE id = ?;";
                $sth = $dbh->prepare($query1);
                $sth->execute(array($id));
                $query = "SELECT COUNT(*) + 1 AS position FROM filedattente WHERE id < ?; ";
                $sth = $dbh->prepare($query);
                $sth->execute(array($id));
                $count = $sth->fetchAll();
                //Le 1 correspond au cas: on est encore dans filedattente
                return array($count,1);
            }
            //Si on n'est plus dans filedattente, on regarde si on est passé dans la table finalisation
            else{
                $query2 = "SELECT COUNT(*) FROM finalisation WHERE id=? AND token=?";
                $sth = $dbh->prepare($query2);
                $sth->execute(array($id,$token));
                $count2 = $sth->fetchColumn();
                //Premier argument: true si on est dans finalisation / false si on a été redirigé vers l'accueil
                //Le 2 correspond au cas où on n'est plus dans filedattente
                return array($count2==1,2);
            }
        }
        //S'il ne reste plus de places
        //Le 2e zéro correspond au cas où il n'y a plus de places
        else{
            return array(0,0);
        }
    }
    
    //Fonction qui tourn en arrière plan
    public static function update($dbh) {

        //retire les gens inactifs de la table finalisation 
        $query4 = "DELETE FROM finalisation WHERE TIMESTAMPDIFF(SECOND, heure, CURRENT_TIME) >= 30";
        $sth4 = $dbh->prepare($query4);
        $sth4->execute();


        try {
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        
            // Verrouiller les tables 
            $dbh->exec("SET TRANSACTION ISOLATION LEVEL REPEATABLE READ");
            $dbh->beginTransaction();


          
            // Compter les enregistrements dans la table finalisation
            $query = "SELECT COUNT(*) FROM finalisation";
            $sth = $dbh->prepare($query);
            $sth->execute();
            $result = $sth->fetchColumn();
            sleep(5);
            if ($result < 10) {
                // Rechercher un enregistrement actif
                $query1 = "SELECT id, token, heure, TIMESTAMPDIFF(SECOND, heure, CURRENT_TIME) <= 30 AS actif FROM filedattente LIMIT 1";
                $sth1 = $dbh->prepare($query1);
                $sth1->execute();
                $result1 = $sth1->fetch(PDO::FETCH_ASSOC);
        
                if ($result1) {
                    // Supprimer l'enregistrement de filedattente
                    $query2 = "DELETE FROM filedattente WHERE id = :id LIMIT 1";
                    $sth2 = $dbh->prepare($query2);
                    $sth2->execute([':id' => $result1["id"]]);
        
                    if ($result1["actif"]) {
                        // Insérer l'enregistrement dans finalisation
                        $query3 = "INSERT INTO finalisation (id, token, heure) VALUES (?, ?, ?)";
                        $sth3 = $dbh->prepare($query3);
                        $sth3->execute([$result1["id"], $result1["token"], $result1["heure"]]);
                    }
                } else {
                    echo "Aucun enregistrement trouvé dans filedattente.";
                }
            }
        
            // Valider la transaction
            if ($dbh->inTransaction()) {
                $dbh->commit();
            }
        
            // Libérer les verrous
            $dbh->exec("UNLOCK TABLES");
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $dbh->exec("UNLOCK TABLES");
            if ($dbh->inTransaction()) {
                $dbh->rollBack();
            }

            //Afficher le status de innoDb si on a eu une erreur 
            $query="SHOW ENGINE INNODB STATUS;";
            $sth = $dbh->prepare($query);
            $sth->execute();
            $result = $sth->fetch(PDO::FETCH_ASSOC);

            //Renvoyer le message 
            echo json_encode(array("resultat" => "Erreur : " . $e->getMessage()  .$result['Status'] ));
            return;
        }
        
        



    }       
}
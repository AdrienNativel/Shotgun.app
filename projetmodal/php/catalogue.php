<?php

require 'headers/cors_header.php';
require 'database.php';

//On récupère la liste des shotguns et leurs informations
$liste=Database::getCatalogue(Database::connect());
echo json_encode($liste);
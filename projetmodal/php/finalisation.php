<?php

require 'headers/cors_header.php';
require 'database.php';

$dbh = Database::connect();

//Tourne en arrière-plan et fait des appels réguliers à la fonction update
Database::update($dbh);
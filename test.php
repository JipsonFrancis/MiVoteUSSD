<?php

require './Model.php';

$database = new Model();

$user = $database->get('+265884202666');

// echo json_encode( $user );

$cam = $database->getCampaign( $user['id']);

echo json_encode( $cam );
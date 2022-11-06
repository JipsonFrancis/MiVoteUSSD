<?php

require './Model.php';

$database = new Model();

$user = $database->get('+265884202666');

// echo json_encode( $user );

$cam = $database->getCampaign( $user['id']);

$data = $database->checkVote( 4,2,2 );

echo json_encode( count($data) );
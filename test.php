<?php

require './Model.php';

$dataModel = new Model();

$user = $dataModel->get(  '+265884202666');

$dataModel->deleteCampaign(2);

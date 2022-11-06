<?php

require './Model.php';

session_start();
// Read the variables sent via POST from our API
$sessionId = $_POST["sessionId"];
$serviceCode = $_POST["serviceCode"];
$networkCode = $_POST["networkCode"];
$phoneNumber = $_POST["phoneNumber"];
$text        = $_POST["text"];
$name = null;
$email = null;
$password = null;
$election = null;
$campaign = null;

$response_elements = explode('*', $text );

$size = sizeof( $response_elements );

if ( ( $size == 3 || $size > 3 ) )
{
    if ( $response_elements[0] == "1" && $response_elements[1] == "2" )
        $name = $response_elements[2]
    ;
    
    if ( $response_elements[0] == "1" && $response_elements[1] == "3" )
        $election = $response_elements[2]
    ;

    if ( $response_elements[0] == "1" && $response_elements[1] == "4" )
    $election = $response_elements[2]
    ;

    if ( $response_elements[0] == "1" && $response_elements[1] == "5" )
    $election = $response_elements[2]
    ;

    if ( $response_elements[0] == "2" && $response_elements[1] == "1" )
        $election = $response_elements[2]
    ;
    
    if ( $response_elements[0] == "2" && $response_elements[1] == "3" )
        $election = $response_elements[2]
    ;

    if ( $response_elements[0] == "2" && $response_elements[1] == "4" )
        $campaign = $response_elements[2]
    ;
    
    
}

if ( ( $size == 4 || $size > 4 ) )
{   
    if ( $response_elements[0] == '1' && $response_elements[1] == '2' )
        $email = $response_elements[3]
    ;

    if ( $response_elements[0] == "1" && $response_elements[1] == "4" ){
        $campaign = $response_elements[3];
    }
}

if ( ( $size == 5 || $size > 5 ) )
{
    if ( $response_elements[0] == "1" && $response_elements[1] == "2" )
        $password = $response_elements[4];
}


fwrite( $res = fopen( 'response.txt', 'a' ), json_encode(array( $name, $email, $password , $text, $election) ) );

fclose( $res );


if ($text == "") {
    // This is the first request. Note how we start the response with CON
    $response  = "CON MiVote \n \n";
    $response .= "1. Voter\n";
    $response .= "2. Candidate\n";

} else if ( $text == "1" ) {
    // Business logic for first level response
    $response = "CON MiVote Voters Menu \n \n";
    $response .= "1. Voting Status\n";
    $response .= "2. Register for Voting\n";
    $response .= "3. Select Elections\n";
    $response .= "4. Election Day\n";
    $response .= "5. See Election Campaigns\n";

} else if ( $text == "2" ) {
    // Business logic for first level response
    // This is a terminal request. Note how we start the response with END
    $response = "CON MiVote Candidate Menu \n\n";

    $response .= "1. Become A Candidate\n";
    $response .= "2. Current Campaigns\n";
    $response .= "3. Create Campaigns\n";
    $response .= "4. Withdraw for Elections\n";

} else if ( $text == "1*1" ) { 
    
    $dataModel = new Model();

    $user = $dataModel->get( $phoneNumber );

    if ( $user )
        $response = ( (bool) $user['voting_status'] )? "END Your Eligible To Vote" : "END Your not Eligible to Vote(Please Register)"
    ;
    else
        $response = "END Your not Registered";
    ;

} else if ( $text == "1*2" ){
    $response = "CON Enter Your Name\n";
} else if ( $text == "1*2*".$name  ){
    $response = "CON Enter Your email\n";
} else if ( $text == "1*2*".$name."*".$email ){
    $response = "CON Enter Your password\n";
} else if ( $text == "1*2*".$name."*".$email."*".$password ){
    $response = "END A account will be created\n";
    $response .= "Please go vote on our website.\n https://handsonnetworkprogramming.com";

    $database = new Model();
    $database->newUser( $name, $phoneNumber, $email, $password );

    fwrite( $log = fopen( 'session.txt', 'a'), json_encode(array($sessionId, $serviceCode, $networkCode, $phoneNumber, $text, $name, $email, password_hash( $password, PASSWORD_BCRYPT ) )) );

    fwrite( $log = fopen( 'session.txt', 'a'), "\n" );

    fclose( $log );

} else if ( $text == "1*3" ){
    $dataModel = new Model();

    $elections = $dataModel->getElections();

    $response = "CON Please Select Election Youd like to vote on \n \n";

    //please make sure that you  handles errors that will occur due to character lenght for ussd (limit the output and add next page)
    foreach ( $elections as $election )
    {
        $response .= $election['id'].'. '.$election['name']."\n";
    }
} else if ( $text == "1*3*$election" ){
    $database = new Model();

    //get a user
    $user = $database->get( $phoneNumber );

    if ( $user )
    {
        $electionUser = $database->createUserElection( $election, $user['id'] );

        $electioned = $database->getElection( $election );

        ( $electioned )? $response = "END You can now view Campaigns and Vote for ".$electioned['name']." \n" 
            : $response = "END please try again operation failed\n";
    }
    else
    {
        $response = "END please try again operation failed\n";
    }
    
} else if ( $text == "1*4" ){
    
    $database = new Model();

    //get user id
    $user = $database->get( $phoneNumber );

    if ( $user )
    {
        $userElections = $database->getUserElections( $user['id'] );

        if ( $userElections )
        {
            foreach ( $userElections as $userElection )
            {
                $election = $database->getElection( $userElection['election_id'] );

                $today = date_create( date( 'Y-m-d' ) );

                $voting_date = date_create( $election['voting_date'] );

                $date_diff = date_diff( $today, $voting_date );
                $date_diff = (int)$date_diff->format("%R%a");

                // fwrite( $dae = fopen( 'date.txt', 'a' ),  $date_diff );

                // fclose( $dae );

                if ( $election && $date_diff == 0 )
                {
                    $response = "CON Select Elections (to Start Vote) \n \n";
                    //limit the output and make a next page
                    $response .= $userElection['election_id'].". ".$election['name']." \n";
                }
                elseif( $date_diff < 0 )
                {
                    $response = "END Election is closed \n";
                }
                elseif( $date_diff > 0 )
                {
                    $response = "END Election will be available on ".$election['voting_date']."\n";
                }
            }
        }
        else
        {
            $response = "END Please Register For Election first (go back and select 3) \n \n";
        }
    }

} else if ( $text == '1*5' ){

    $database = new Model();

    //get user id
    $user = $database->get( $phoneNumber );

    if ( $user )
    {
        $userElections = $database->getUserElections( $user['id'] );

        if ( $userElections )
        {
            $response = "CON Select Elections (to View Campaigns) \n \n";

            foreach ( $userElections as $userElection )
            {
                $election = $database->getElection( $userElection['election_id'] );

                if ( $election )
                {
                    //limit the output and make a next page
                    $response .= $userElection['election_id'].". ".$election['name']." \n";
                }
            }
        }
    }

} else if ( $text == '2*1' ){
    
    $database = new Model();

    //get user id
    $user = $database->get( $phoneNumber );

    if ( $user )
    {
        $elections = $database->getElections();

        if ( $elections )
        {
            $response = "CON Select Election to Run for \n \n";

            foreach ( $elections as $election )
            {
                $response .= $election['id'].". ".$election['name']." \n";
            }
        }
    }

} else if ( $text == '2*2' ){

    $database = new Model();

    $user = $database->get( $phoneNumber );

    if ( $user )
    {
        $campaigns = $database->getCampaign( $user['id'] );

        if ( $campaigns )
        {
            $response = "END MiVote Candidate Current Campaigns \n \n";

            foreach( $campaigns as $campaign )
            {
                $election = $database->getElection( $campaign['election_id'] );

                if ( $election )
                {
                    $response .= $campaign['id'].". ".$campaign['name']." Campaign for ".$election['name']." Elections \n";
                }else
                {
                    $response .= "Elections does not exsit\n";
                }
            }
        }
        else
        {
            $response = "END You dont have any Campaigns Yet (go back and select oprion 3 to create) \n";
        }
    }
} else if ( $text == '2*3' ){

    $database = new Model();

    //get user id
    $user = $database->get( $phoneNumber );

    if ( $user )
    {
        $elections = $database->getCanElection( $user['id'] );

        if ( $elections )
        {
            $response = "CON Select Election to Campaign \n \n";

            foreach ( $elections as $election )
            {
                $response .= $election['election_id'].". ".$election['name']." \n";
            }
        }
    }

} else if ( $text == '2*4' ){

    $database = new Model();

    //get user id
    $user = $database->get( $phoneNumber );

    if ( $user )
    {
        $campaigns = $database->getCampaign( $user['id'] );

        if ( $campaigns )
        {
            $response = "CON Select Election to Run for \n \n";

            foreach ( $campaigns as $campaign )
            {
                $response .= $campaign['id'].". ".$campaign['name']." \n";
            }
        }
    }

} else if ( $text == "2*1*".$election ){

    $database = new Model();

    //get user id
    $user = $database->get( $phoneNumber );
    $electrol = $database->getElection( $election );

    if ( $user && $electrol )
    {
        $database->createCanElection( $election, $user['id'] );

        $database->updateUser( $user, [ 'running_office' => "1" ] );

        $response = "END Now Candidate for ".$electrol['name']."\n";
    }

} else if ( $text == "2*3*".$election ){

    $database = new Model();

    //get user id
    $user = $database->get( $phoneNumber );
    $electrol = $database->getElection( $election );

    if ( $user && $electrol)
    {
        $database->createCampaign( $election, $user['id'] );

        $response = "END Campaign Created for ".$electrol['name']."\n";
    }

} else if ( $text == "2*4*".$campaign ){

    $database = new Model();

    $database->deleteCampaign( $campaign );

    $response = "END MiVote Candidate Campaign Menu\n";

    $response .= "Campaign was Deleted Successfully";
} else if ( $text == "1*5*".$election ){

    $database = new Model();

    $campaigns = $database->getCampaignElection( (int) $election );

    $elections = $database->getElection( $election );

    if ( $campaigns && $elections )
    {
        $response = "END MiVote Running Campaigns for ".$elections['name']."\n";

        foreach( $campaigns as $campaign )
        {
            $response .= $campaign['id'].". ".$campaign['name']."\n";
        }
    }

} else if ( $text == "1*4*".$election ){

    // we have to order the campaigns according to posistions and then divide them in to submenus the user can just select one and vote
    $database = new Model();

    $campaigns = $database->getCampaignElection( (int) $election );

    $elections = $database->getElection( $election );

    if ( $campaigns && $elections )
    {
        $response = "CON MiVote Voting Menu \n";

        foreach( $campaigns as $campaign )
        {
            $response .= $campaign['id'].". ".$campaign['name']."\n";
        }
    }
} else if ( $text == "1*4*".$election."*".$campaign ){

    $dataModel = new Model();

    $user = $dataModel->get( $phoneNumber );

    $voted = $dataModel->checkVote( $user['id'], $election, $campaign );
    $votingStat = $dataModel->checkVoteStatus( $user['id'], $election );

    if ( count( $votingStat ) == 1 && count( $voted ) == 0 )
    {
        $dataModel->createVote( $user['id'], $election, $campaign );
        $response = "END Thanks for voting";
    }
    else
    {
        $response = "END You have already voted!";
    }
}

// Echo the response back to the API
header('Content-type: text/plain');
echo $response;
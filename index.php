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
$position = null;
$entity = null;

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

    if ( $response_elements[0] == "1" && $response_elements[1] == "4" )
        $entity = $response_elements[3]
    ;

    if ( $response_elements[0] == "2" && $response_elements[1] == "3" )
        $position = $response_elements[3]
    ;
    
}

if ( ( $size == 5 || $size > 5 ) )
{
    if ( $response_elements[0] == "1" && $response_elements[1] == "2" )
        $password = $response_elements[4]
    ;

    if ( $response_elements[0] == "1" && $response_elements[1] == "4" )
        $campaign = $response_elements[4]
    ;
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
        $database->createCanElection( $election, $user['id'], $electrol['name'] );

        $database->updateUser( $user, [ 'running_office' => "1" ] );

        $response = "END Now Candidate for ".$electrol['name']."\n";
    }

} else if ( $text == "2*3*".$election ){

    $database = new Model();

    $electrol = $database->getElection( $election );

    if ( $electrol )
    {
        $electrol_positions = $database->getPositions( $electrol['electrol_position_id'] );

        if ( $electrol_positions )
        {
            $response = "CON (Type) the positions you viaing for, e.g king \n \n";

            foreach( json_decode( $electrol_positions[0]['electrol_positions'] ) as $electrol_position )
            {
                $response .= $electrol_position." \n";

            }
        }
        else
        {
            $response = "END Election that was Selected does not Exist";
        }
    }
    else
    {
        $response = "END Election that was Selected does not Exist";
    }

} else if ( $text == "2*3*".$election."*".$position ){

    $database = new Model();

    //get user id
    $user = $database->get( $phoneNumber );
    $electrol = $database->getElection( $election );

    if ( $user && $electrol)
    {
        // JUST CHECKING TO SEE IF THE USER ENTER CORRECT DATA
        $election_positions = $database->getPositions( $electrol['electrol_position_id'] );
        $datum = json_decode($election_positions[0]['electrol_positions']) ;

        if ( $election_positions && in_array( $position, $datum ))
        {
            $strict = $database->checkCampaign( $user['id'], $electrol['id'], $electrol['electrol_position_id'], $electrol['strict'] );

            if ( (!$strict && $electrol['strict']) || (!$electrol['strict'] && $strict) || ( $electrol['strict'] == false && !$strict ))
            {
                $database->createCampaign( $election, $user['id'], $electrol['electrol_position_id'], $position );

                $response = "END Campaign Created for ".$electrol['name']."\n";
            }
            else
            {
                $response = "END Cannot Create two Campaigns for ".$electrol['name']." election \n";
            }
        }
        else
        {
            $response = "END THE Entity youve enter does not exit";
        }
    }
    else
    {
        $response = "END USER AMD THE ELECTION DONT EXIST";
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

    $election = $database->getElection( $election );

    if ( $election )
    {
        $loop = 1;
        // electrol positions id 
        $electrol_position_id = $election['electrol_position_id'];

        $election_positions = $database->getPositions( $electrol_position_id );

        $positions = json_decode($election_positions[0]['electrol_positions']);

        $response = "CON MiVote ".$election['name']." Voting Entity Menu (Type Entity e.g king)\n";

        foreach( $positions as $position )
        {
            $response .= $loop.". ".$position."\n";
            $loop++;
        }
    }

} else if ( $text == "1*4*".$election."*".$entity ){

    $database = new Model();

    $election = $database->getElection( $election );

    if ( $election )
    {
        $campaigns = $database->getCampaignElection( $election['id'] );

        if ( $campaigns )
        {
            $response = "CON Vote for the Campaign of Your Choice \n";
            foreach( $campaigns as $campaign )
            {
                if ( $campaign['name'] == $entity )
                    $response .= $campaign['id'].". ".$campaign['name']."\n"
                ;
            }
        }
        else
        {
            $response = "END NO CAMPAINGS AVAILABLE FOR THIS ENTITY.";
        }
    }

} else if ( $text == "1*4*".$election."*".$entity."*".$campaign ){

    $database = new Model();

    $flagged = false;

    $user = $database->get( $phoneNumber );

    $election = $database->getElection( $election );

    $campaign = $database->getCampaignID( $campaign );

    $today = date_create( date( 'Y-m-d' ) );

    $election_day = date_create( $election['voting_date'] );

    $date_diff = date_diff( $today, $election_day );

    if ( (int)$date_diff->format("%R%a") == 0 )
    {
        if ( $user && $election && $campaign)
        {
    
            $voted = $database->getUserVotes( $user['id'], $election['id'] );
    
            if ( $voted )
            {
                foreach( $voted as $vote )
                {
                    $votedCampaign = $database->getCampaignID( $vote['campaign_id'] );
    
                    if ( $campaign['name'] == $votedCampaign['name'] )
                    {
                        $flagged = true;
                        break;
                    }
                }
    
                if ( $flagged )
                {
                    $response = "END You cannot vote Twice for ".$campaign['name']." Position";
                }
                else
                {
                    $votedCampaign = $database->checkVote( $user['id'], $election['id'], $campaign['id'] );
    
                    $votingStat = $database->checkVoteStatus( $user['id'], $election['id'] );
        
                    if ( !$votedCampaign && $votingStat )
                    {
                        $Mivote = $database->createVote( $user['id'], $election['id'], $campaign['id'] );
        
                        $response = "END Thanks for voting";
                    }
                    else
                    {
                        $response = "END issues ocured";
                    }
                }
            }
            else 
            {
    
                $votedCampaign = $database->checkVote( $user['id'], $election['id'], $campaign['id'] );
    
                $votingStat = $database->checkVoteStatus( $user['id'], $election['id'] );
    
                if ( !$votedCampaign && $votingStat )
                {
                    $Mivote = $database->createVote( $user['id'], $election['id'], $campaign['id'] );
    
                    $response = "END Thanks for voting";
    
                }
                else
                {
                    $response = "END issues ocured";
                }
            }
        }
        else
        {
            $response = "END User || Election || Campaign issues";
        }
    }
    else if ( (int)$date_diff->format("%R%a") < 0 )
    {
        $response = "END Elections closed ".(int)$date_diff->format("%R%a")."days ago ";
    }
    else 
    {
        $response = "Elections day has not yet began!, Beginning in ".(int)$date_diff->format("%R%a days");
    }
}

// Echo the response back to the API
header('Content-type: text/plain');
echo $response;
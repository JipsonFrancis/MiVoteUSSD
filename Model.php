<?php
require './env.php';

class Model
{
    private $connection;

    public function __construct()
    {
        try
        {
            $this->connection = new PDO('mysql:host='.HOSTNAME.';dbname='.DATABASE, USERNAME, PASSWORD);

            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        }
        catch (PDOException $e)
        {
            echo 'Connection failed'. $e->getMessage();
        }
    }

    // users both candidate and voter table (users)
    public function newUser ( string $name, string $phone, ?string $email, ?string $password, ?bool $voting=true):string
    {
        $sql = "INSERT INTO `users` (name, phone, email, password, voting_status) VALUES (:name, :phone, :email, :password, :voting_status)";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':password', password_hash( $password, PASSWORD_BCRYPT ), PDO::PARAM_STR);
        $stmt->bindValue(':voting_status', $voting, PDO::PARAM_STR );

        $stmt->execute();

        return $this->connection->lastInsertId();
    }

        // get a user
    public function get( string $phone ): array | false
    {
        $sql = "SELECT * FROM `users` WHERE ( phone = :phone )";

        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue( 'phone', $phone, PDO::PARAM_STR );

        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data;

    }

    public function updateUser( array $current, array $data ): int
    {
        $sql = "UPDATE `users` SET 
        `name` = :name, phone = :phone, email = :email, password = :code, voting_status = :vs, running_office = :ro
        WHERE ( id = :id )";

        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue('name', $data['name'] ?? $current['name'],  PDO::PARAM_STR );
        $stmt->bindValue('phone', $data['phone'] ?? $current['phone'],  PDO::PARAM_STR );
        $stmt->bindValue('email', $data['email'] ?? $current['email'],  PDO::PARAM_STR );
        $stmt->bindValue('code', $data['password'] ?? $current['password'],  PDO::PARAM_STR );
        $stmt->bindValue('vs', $data['voting_status'] ?? $current['voting_status'],  PDO::PARAM_STR);
        $stmt->bindValue('ro', $data['running_office'] ?? $current['running_office'],  PDO::PARAM_STR);
        $stmt->bindValue('id', $current['id'] , PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->rowCount();

    }

    // election tables
    public function getElections () : array | false
    {
        $data = [];
        $sql = 'SELECT * FROM `elections`';

        $stmt = $this->connection->query( $sql );

        while( $row = $stmt->fetchAll( PDO::FETCH_ASSOC ) )
        {
            $data = $row;
        }
        
        return $data;

    }

    public function getElection ( int $id ) : array | false
    {
        $sql = "SELECT * FROM `elections` WHERE (id = :id)";

        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue( 'id', $id, PDO::PARAM_INT );

        $stmt->execute();

        $data = $stmt->fetch( PDO::FETCH_ASSOC );

        return $data;
    }

    // voter election tables
    public function createUserElection ( int $elections_id , int $users_id) : string
    {
        $sql = "INSERT INTO `election_users` ( `election_id`, `user_id` )
                VALUES ( :elections_id, :users_id )";

        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue( 'elections_id', $elections_id, PDO::PARAM_INT );
        $stmt->bindValue( 'users_id', $users_id, PDO::PARAM_INT );

        $stmt->execute();

        return $this->connection->lastInsertId();
    }

    public function getUserElections ( int $users_id ) : array | false
    {
        $data = [];

        $sql = "SELECT * FROM `election_users` WHERE ( `user_id` = :id )";

        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue( 'id', $users_id, PDO::PARAM_INT );

        $stmt->execute();

        while( $row = $stmt->fetchAll( PDO::FETCH_ASSOC ) )
        {
            $data = $row;
        }

        return $data;
    }

    // candidate elections tables 
    public function createCanElection ( int $elections_id , int $users_id, ?string $name='electrol' ) : string
    {
        $sql = "INSERT INTO `candidate_elections` ( `name`,`election_id`, `user_id` )
                VALUES ( :name, :elections_id, :users_id )";

        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue( 'elections_id', $elections_id, PDO::PARAM_INT );
        $stmt->bindValue( 'users_id', $users_id, PDO::PARAM_INT );
        $stmt->bindValue( 'name', $name, PDO::PARAM_STR );

        $stmt->execute();

        return $this->connection->lastInsertId();
    }

    public function getCanElection ( int $users_id ) : array | false
    {
        $data = [];

        $sql = "SELECT * FROM `candidate_elections` WHERE (`user_id` = :id)";

        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue( 'id', $users_id, PDO::PARAM_INT );

        $stmt->execute();

        while( $row = $stmt->fetchAll( PDO::FETCH_ASSOC ) )
        {
            $data = $row;
        }

        return $data;
    }

    // election user

    // public function createUserElection ( int $elections_id , int $users_id, ?string $name='electrol' ) : string
    // {
    //     $sql = "INSERT INTO `candidate_elections` ( `name`,`election_id`, `user_id` )
    //             VALUES ( :name, :elections_id, :users_id )";

    //     $stmt = $this->connection->prepare( $sql );

    //     $stmt->bindValue( 'elections_id', $elections_id, PDO::PARAM_INT );
    //     $stmt->bindValue( 'users_id', $users_id, PDO::PARAM_INT );
    //     $stmt->bindValue( 'name', $name, PDO::PARAM_STR );

    //     $stmt->execute();

    //     return $this->connection->lastInsertId();
    // }

    public function checkVoteStatus ( int $users_id, int $election_id ) : array | false
    {
        $data = [];

        $sql = "SELECT * FROM `election_users` WHERE (`user_id` = :id AND `election_id` = :eid )";

        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue( 'id', $users_id, PDO::PARAM_INT );
        $stmt->bindValue( 'eid', $election_id, PDO::PARAM_INT );

        $stmt->execute();

        $data = $stmt->fetch( PDO::FETCH_ASSOC );

        return $data;
    }

    // campaign tables

    public function createCampaign( int $elections_id, int $users_id, int $electrol_position_id, ?string $name='campaign' ): string
    {
        $sql = "INSERT INTO `campaigns` ( `name`,`election_id`, `user_id`, `electrol_position_id` )
        VALUES ( :name, :elections_id, :users_id, :epid)";

        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue( 'elections_id', $elections_id, PDO::PARAM_INT );
        $stmt->bindValue( 'users_id', $users_id, PDO::PARAM_INT );
        $stmt->bindValue( 'epid', $electrol_position_id, PDO::PARAM_INT );
        $stmt->bindValue( 'name', $name, PDO::PARAM_STR );

        $stmt->execute();

        return $this->connection->lastInsertId();
    }

    public function getCampaign( int $users_id ): array | false
    {
        $data = [];

        $sql = "SELECT * FROM `campaigns` WHERE (`user_id` = :id)";

        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue( 'id', $users_id, PDO::PARAM_INT );

        $stmt->execute();

        while( $row = $stmt->fetchAll( PDO::FETCH_ASSOC ) )
        {
            $data = $row;
        }

        return $data;
    }

    public function getCampaignID( int $id ): array | false
    {
        $data = [];

        $sql = "SELECT * FROM `campaigns` WHERE (`id` = :id)";

        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue( 'id', $id, PDO::PARAM_INT );

        $stmt->execute();

        $data = $stmt->fetch( PDO::FETCH_ASSOC );

        return $data;
    }

    public function getCampaignElection( int $elections_id ): array | false
    {
        $data = [];

        $sql = "SELECT * FROM `campaigns` WHERE (`election_id` = :id)";

        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue( 'id', $elections_id, PDO::PARAM_INT );

        $stmt->execute();

        while( $row = $stmt->fetchAll( PDO::FETCH_ASSOC ) )
        {
            $data = $row;
        }

        return $data;
    }

    public function deleteCampaign( int $campaign_id ): int
    {
        $sql = "DELETE FROM `campaigns` WHERE ( id = :id )";

        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue( 'id', $campaign_id, PDO::PARAM_INT );

        $stmt->execute();

        return $stmt->rowCount();
    }

    public function checkCampaign( int $user_id, int $election_id, ?int $electrol_position_id = null, ?bool $strict = false ): array | false
    {
        $data = [];

        if ( $electrol_position_id && $strict )
        {
            $sql = "SELECT * FROM `campaigns` WHERE (`user_id` = :uid AND `election_id` = :eid AND `electrol_position_id` = :epid)";

            $stmt = $this->connection->prepare( $sql );
    
            $stmt->bindValue( 'uid', $user_id , PDO::PARAM_INT );
            $stmt->bindValue( 'eid', $election_id , PDO::PARAM_INT );
            $stmt->bindValue( 'epid', $electrol_position_id , PDO::PARAM_INT );
        }
        else 
        {
            $sql = "SELECT * FROM `campaigns` WHERE (`user_id` = :uid AND `election_id` = :eid)";

            $stmt = $this->connection->prepare( $sql );
    
            $stmt->bindValue( 'uid', $user_id , PDO::PARAM_INT );
            $stmt->bindValue( 'eid', $election_id , PDO::PARAM_INT );
        }

        $stmt->execute();

        while ( $row = $stmt->fetchAll( PDO::FETCH_ASSOC ) )
        {
            $data = $row;
        }

        return $data;
    }

    // Vote Table

    public function createVote( int $users_id, int $election_id, int $campaign_id ): string
    {
        $sql = "INSERT INTO `votes` (`user_id`,`election_id`,`campaign_id`) VALUES (:user_id, :election_id, :campaign_id)";

        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue( 'user_id', $users_id, PDO::PARAM_INT);
        $stmt->bindValue( 'election_id', $election_id, PDO::PARAM_INT);
        $stmt->bindValue( 'campaign_id', $campaign_id, PDO::PARAM_INT);

        $stmt->execute();

        return $this->connection->lastInsertId();
    }

    public function checkVote( int $user_id, int $election_id, int $campaign_id ): array|false
    {
        $data = [];

        $sql = "SELECT * FROM `votes` WHERE ( `user_id` = :uid AND  `election_id` = :eid AND `campaign_id` = :cid)";

        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue( 'uid', $user_id ,PDO::PARAM_INT );
        $stmt->bindValue( 'eid', $election_id ,PDO::PARAM_INT );
        $stmt->bindValue( 'cid', $campaign_id ,PDO::PARAM_INT );

        $stmt->execute();

        while( $row = $stmt->fetchAll( PDO::FETCH_ASSOC ) )
            $data = $row
        ;

        return $data;
    }

    public function getUserVotes( int $user_id, int $election_id ): array|false
    {
        $data = [];

        $sql = "SELECT * FROM `votes` WHERE ( `user_id` = :uid AND  `election_id` = :eid )";

        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue( 'uid', $user_id ,PDO::PARAM_INT );
        $stmt->bindValue( 'eid', $election_id ,PDO::PARAM_INT );

        $stmt->execute();

        while( $row = $stmt->fetchAll( PDO::FETCH_ASSOC ) )
            $data = $row
        ;

        return $data;
    }

    //electrol Positions tables

    public function getPositions( int $id ): array | false
    {
        $data = [];

        $sql = "SELECT * FROM `electrol_positions` WHERE (`id` = :id)";


        $stmt = $this->connection->prepare( $sql );

        $stmt->bindValue( 'id', $id, PDO::PARAM_INT );

        $stmt->execute();

        while ( $row = $stmt->fetchAll( PDO::FETCH_ASSOC ) )
        {
            $data = $row;
        }

        return $data;
        
    }

}
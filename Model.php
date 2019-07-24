<?php
namespace anlprz;

use anlprz\Config;
use Google_Client;

// Replace acting $db Object to Database Connection

Class Model extends Config {

    private $client;
    private $userId;
    private $fitnessData = [];
    private $autoLoad = true;

    public function __construct()
    {
        $this->includeVendor( 
            ( $this->getAutoload() )
        );
    }

    public function includeVendor( Bool $autoload = true )
    {
        if( $autoload )
        {
            $file_path  = './vendor/autoload.php';
            if( !file_exists( $file_path ) )
            {
                throw new \Exception( "Google API PHP Client Library not found. Use composer to install." );
            }
            require_once $file_path;
            $this->setAutoLoad( false );
        }
    }

    public function getAutoload()
    {
        return $this->autoLoad;
    }

    public function setAutoLoad( Bool $autoLoad )
    {
        $this->autoLoad = $autoLoad;
        return $this;
    }

    public function setClient( Google_Client $client )
    {
        $this->client = $client;
        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId( Integer $userId )
    {
        $this->userId = $userId;
        return $this;
    }

    public function getFitnessData()
    {
        return $this->fitnessData;
    }

    public function setFitnessData( Array $fitnessData )
    {
        $this->fitnessData = $fitnessData;
        return $this;
    }

    public function init()
    {
        $client = new Google_Client([
            "redirect_uri"  => $this->getRedirectUrl(),
            "client_id"     => $this->getClientId(),
            "client_secret" => $this->getSecretKey(),
        ]);
        $client->setApplicationName( $this->getApplicationName() );
        $client->setAccessType("offline");        // offline access
        $client->setIncludeGrantedScopes(true);   // incremental auth
        $client->addScope( Google_Service_Oauth2::USERINFO_PROFILE );
        $client->addScope( Google_Service_Oauth2::USERINFO_EMAIL );
        $client->addScope( Google_Service_Fitness::FITNESS_ACTIVITY_READ );
        $this->setClient( $client );
        return $this;
    }

    public function getUrlAuth()
    {
        return $this->getClient()->createAuthUrl();
    }

    public function initUserFitnessData()
    {
        $db = new stdClass();
        $fitnessData = $db->query(
            "SELECT `field_fitness_id`,`field_user_stored_access_token_code`
            FROM `table_users`
            WHERE `field_user_id` = ". $this->getUserId() .
            "AND `field_user_active` = 1
            ORDER BY `field_fitness_id` desc"
        );
        if( !empty( $fitnessData ) && is_array( $fitnessData ) )
        {
            $this->setFitnessData( $fitnessData );
        }
        else {
            $this->setFitnessData( [] );
        }
        return $this;
    }

    public function updateUserAccessToken( Integer $field_fitness_id, String $field_user_stored_access_token_code )
    {
        $db = new \stdClass();
        return $db->query(
            "UPDATE `table_users`
            SET `field_user_stored_access_token_code` = \"". $field_user_stored_access_token_code ."\""
            ."WHERE `field_fitness_id` = ". $field_fitness_id
        );
    }

    public function initClientAssessed()
    {
        $client = $this->getClient();
        $fitnessData = $this->getFitnessData();

        if( empty( $fitnessData ) )
        {
            $this->initUserFitnessData();
            $fitnessData = $this->getFitnessData();
        }
        if ( empty( $fitnessData['access_token'] ) ) return $client;

        $accessToken = json_decode( $fitnessData['access_token'], true );

        if( !is_array( $accessToken ) || empty( $accessToken['access_token'] ) )
        {
            return $client;
        }
        $client->setAccessToken( $accessToken );

        if ( $client->isAccessTokenExpired() )
        {
            $v = $client->getRefreshToken();
            if ( !empty( $v ) )
            {
                try {
                    $client->fetchAccessTokenWithRefreshToken( $v );
                    $this->updateUserAccessToken( $fitnessData['id_fitness'], json_encode( $client->getAccessToken() ) );
                } catch ( \Exception $e ) {
                    throw new \Exception( $e->getMessage() );
                }
            }
        }
        $this->setClient( $client );
        return $this;
    }

    public function isAuthenticated()
    {
        $client = $this->getClient();
        $fitnessData = $this->getFitnessData();

        if( empty( $fitnessData ) )
        {
            $this->initUserFitnessData();
            $fitnessData = $this->getFitnessData();
        }

        if ( empty( $fitnessData['access_token'] ) ) return $client;

        $accessToken = json_decode( $fitnessData['access_token'], true );

        if( !is_array( $accessToken ) || empty( $accessToken['access_token'] ) )
        {
            return false;
        }
        return true;
    }

    public function setGoogleUserAuthentication( String $code )
    {
        $client = $this->getClient();
        $fitnessData = $this->getFitnessData();

        if( empty( $fitnessData ) )
        {
            $this->initUserFitnessData();
            $fitnessData = $this->getFitnessData();
        }

        $accessToken = $client->fetchAccessTokenWithAuthCode( $code );
        if( !empty( $accessToken ) )
        {
            $accessToken = json_encode( $accessToken );
        } else {
            $accessToken = '';
        }
        $this->updateUserAccessToken( $this->getUserId(), $accessToken );
        return $this;
    }

    public function getFitnessAcess()
    {
        if( $this->isAuthenticated() )
        {
            $this->initClientAssessed();
        } else {
            $this->getClient();
        }
        return $this;
    }
}
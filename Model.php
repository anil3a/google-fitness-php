<?php
namespace anlprz;

use anlprz\Config;
use Google_Client;
use anlprz\Helper;
use anlprz\Database;

Class Model extends Config {

    private $client;
    private $userId;
    private $fitnessData = [];
    private $autoLoad = true;
    private $helperClass;

    public function __construct()
    {
        $this->includeVendor( 
            ( $this->getAutoload() )
        );
        $this->helperClass = new Helper();
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
        $db = new Database();
        $fitnessData = $db->query(
            "SELECT `field_user_id`,`field_user_access_token_code`
            FROM `table_users`
            WHERE `field_user_id` = ". $this->getUserId() .
            " AND `field_user_active` = 1
            ORDER BY `field_user_id` DESC LIMIT 1"
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

    public function updateUserAccessToken( String $field_user_stored_access_token_code )
    {
        if( empty( $this->getUserId() ) ) throw new \Exception( "User ID must be set before storing Access token of user" );

        $db = new Database();
        return $db->query(
            "UPDATE `table_users`
            SET `field_user_access_token_code` = \"". $field_user_stored_access_token_code ."\""
            ."WHERE `field_user_id` = ". $this->getUserId()
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
        if ( empty( $fitnessData['field_user_access_token_code'] ) ) return $client;

        $accessToken = json_decode( $fitnessData['field_user_access_token_code'], true );

        if( empty( $accessToken['field_user_access_token_code'] ) || !is_array( $accessToken ) )
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
                    $this->updateUserAccessToken( $fitnessData['field_user_id'], $this->helperClass->safe_json_encode( $client->getAccessToken() ) );
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

        if ( empty( $fitnessData['field_user_access_token_code'] ) ) return $client;

        $accessToken = json_decode( $fitnessData['field_user_access_token_code'], true );

        if( empty( $accessToken['field_user_access_token_code'] ) || !is_array( $accessToken ) )
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
            $accessToken = $this->helperClass->safe_json_encode( $accessToken );
        } else {
            $accessToken = '';
        }
        $this->updateUserAccessToken( $accessToken );
        return $this;
    }

    public function getFitnessAccess()
    {
        if( $this->isAuthenticated() )
        {
            $this->initClientAssessed();
        } else {
            $this->getClient();
        }
        return $this;
    }

    public function saveUserAccessToken( String $accessToken = '' )
    {
        if( empty( $this->getUserId() ) ) throw new \Exception( "User ID must be set before storing Access token of user" );

        $user = [
            'field_user_id' => $this->getUserId(),
            'field_user_active' => 1,
        ];
        if( !empty( $accessToken ) )
        {
            $accessTokenArray = json_decode( $accessToken );
            if( empty( $accessTokenArray['access_token'] ) || empty( $accessTokenArray['expires_in'] ) )
            {
                throw new \Exception( "Invalid Access Token, Please verify this response: ". $accessToken );
            }
        } else {
            $accessToken = '';
        }

        try {
            $db = new Database();
            $helper = new Helper();

            $querySelect = 'SELECT * FROM `table_users` WHERE ';
            $where = implode( 
                " AND ",
                $helper->array_map_assoc( 
                    function( $k, $v ) {
                        return $k ." = ". $v;
                    },
                    $user
                 )
            );
            $querySelect .= $where;
            $querySelect .= ' ORDER BY `field_user_id` DESC LIMIT 1';

            $userResult = $db->query( $querySelect );

            if( !empty( $userResult['field_user_id'] ) )
            {
                $queryUpdate = 'UPDATE `table_users` SET `field_user_access_token_code`=\''. $accessToken .'\' WHERE '. $where;
                $db->query( $queryUpdate );
            } else {
                throw new \Exception( "User must been deactivated. Please activate the user to be able to update Access token" );
            }
            return $this;

        } catch ( \Exception $e ) {
            throw new \Exception( $e->getMessage() );
        }
        return false;
    }

}
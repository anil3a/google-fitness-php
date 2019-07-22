<?php
namespace anlprz;
use Exception;
use anlprz\Config as Config;
use Google_Client;

// Replace acting $db Object to Database Connection

Class Model extends Config {

    private $client;
    private $userId;
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
                throw new Exception( "Google API PHP Client Library not found. Use composer to install." );
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
}
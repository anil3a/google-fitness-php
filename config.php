<?php

namespace anlprz;

Class Config
{
    private $secretKey;
    private $clientId;
    private $redirectUrl;
    private $applicationName;

    public function getSecretKey()
    {
        return "GOOGLE_PROJECT_SECRET_KEY";
    }

    public function setSecretKey( String $secretKey )
    {
        $this->secretKey = $secretKey;
    }
    
    public function getClientId()
    {
        return "GOOGLE_PROJECT_CLIENT_ID";
    }
    
    public function setClientId( String $clientId )
    {
        $this->clientId = $clientId;
    }

    public function getRedirectUrl()
    {
        return "YOUR_DOMAIN_URL_FOR_CALLBACK_WHICH_IS_SET_IN_GOOGLE_PROJECT";
    }

    public function setRedirectUrl( String $redirectUrl )
    {
        $this->redirectUrl = $redirectUrl;
    }

    public function getApplicationName()
    {
        return "Application Name";
    }

    public function setApplicationName( String $name )
    {
        $this->applicationName = $name;
    }
}
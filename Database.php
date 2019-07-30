<?php
namespace anlprz;

Class Database
{
    private $databaseUser;
    private $databaseName;
    private $databasePassword;
    private $databaseHost;

    public function setDatabaseUser( String $databaseUser )
    {
        $this->databaseUser = $databaseUser;
        return $this;
    }

    public function getDatabaseUser()
    {
        return $this->databaseUser;
    }

    public function setDatabaseName( String $databaseName )
    {
        $this->databaseName = $databaseName;
        return $this;
    }

    public function getDatabaseName()
    {
        return $this->databaseName;
    }

    public function setDatabasePassword( String $databasePassword )
    {
        $this->databasePassword = $databasePassword;
        return $this;
    }

    public function getDatabasePassword()
    {
        return $this->databasePassword;
    }

    public function setDatabaseHost( String $databaseHost )
    {
        $this->databaseHost = $databaseHost;
        return $this;
    }

    public function getDatabaseHost()
    {
        return $this->databaseHost;
    }

    public function schema_user()
    {
        return 'CREATE TABLE IF NOT EXISTS `table_users` (
                `field_user_id` INT NOT NULL AUTO_INCREMENT,
                `field_user_name` VARCHAR(200) NULL DEFAULT NULL,
                `field_user_email` VARCHAR(200) NOT NULL,
                `field_user_image` VARCHAR(250) NULL DEFAULT NULL,
                `field_user_access_token_code` TEXT,
                `field_user_active` TINYINT(1) NOT NULL DEFAULT 0,
                `field_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `field_updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
        ';
    }

    public function query( String $q )
    {

    }
    
}
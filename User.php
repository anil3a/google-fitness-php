<?php
namespace anlprz;
require_once 'Database.php';

// use Model;
use anlprz\Database as Database;

use anlprz\Config;

Class User extends Config {

    private $id;
    private $name;
    private $email;
    private $image;
    private $accessToken;
    private $active;
    private $createdAt;
    private $updatedAt;
    
    public function __construct( String $email )
    {
        $db = new Database();
        $user = $db->from('table_users')->where( [ 'email' => $email ] )->order( 'field_user_id desc' )->limit( '1' )->getResult();
        if( !empty( $user ) )
        {
            if( count( $user ) === 1 )
            {
                $user = reset( $user );
            }
            if( empty( $user ) || empty( $user['field_user_id'] ) )
            {
                throw new \Exception( 'User not found' );
            }
            foreach( $user as $k => $v )
            {
                $this->set( $k, $v );
            }
        }
        throw new \Exception( 'User not found' );
    }

    public function __get( $property )
    {
        if( property_exists($this, $property ) )
        {
            return $this->$property;
        }
    }
    
    public function __set( $property, $value )
    {
        if( property_exists( $this, $property ) )
        {
            $this->$property = $value;
        }
        return $this;
    }


}


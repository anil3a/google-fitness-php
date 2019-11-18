<?php
namespace anlprz;
require_once 'Database.php';
require_once 'Helper.php';

// use Model;
use anlprz\Database;
use anlprz\Helper;
use anlprz\Config;

Class User extends Config {

    private $fieldUserId;
    private $fieldUserName;
    private $fieldUserEmail;
    private $fieldUserImage;
    private $fieldUserAccessTokenCode;
    private $fieldUserActive;
    private $fieldCreatedAt;
    private $fieldUpdatedAt;
    
    public function __construct( String $email )
    {
        $db = Database::getInstance();
        $user = $db->from('table_users')->where( [ 'field_user_email' => $email ] )
                    ->order( 'field_user_id desc' )->limit( '1' )->get()->resultRow();
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
                $functionName = 'set'. Helper::dashesToCamelCase( $k );
                if( method_exists( $this, $functionName ) && !empty( $v ) )
                {
                    $this->$functionName( $v );
                } elseif( empty( $v ) )
                {
                    continue;
                } else {
                    throw new \Exception( 'Getting and setting not working. Trace '. $functionName );
                }
            }
        } else {
            throw new \Exception( 'User not found' );
        }
    }

    /**
     * Get the value of fieldUserId
     */ 
    public function getId()
    {
        return $this->fieldUserId;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */ 
    public function setId( Int $fieldUserId )
    {
        $this->fieldUserId = (int) $fieldUserId;

        return $this;
    }

    /**
     * Get the value of email
     */ 
    public function getEmail()
    {
        return $this->fieldUserEmail;
    }

    /**
     * Set the value of email
     *
     * @return  self
     */ 
    public function setEmail( String $fieldUserEmail )
    {
        $this->fieldUserEmail = $fieldUserEmail;

        return $this;
    }

    /**
     * Get the value of fieldUserId
     */ 
    public function getFieldUserId()
    {
        return $this->fieldUserId;
    }

    /**
     * Set the value of fieldUserId
     *
     * @return  self
     */ 
    public function setFieldUserId( Int $fieldUserId )
    {
        $this->fieldUserId = (int) $fieldUserId;

        return $this;
    }

    /**
     * Get the value of fieldUserName
     */ 
    public function getFieldUserName()
    {
        return $this->fieldUserName;
    }

    /**
     * Set the value of fieldUserName
     *
     * @return  self
     */ 
    public function setFieldUserName( String $fieldUserName )
    {
        $this->fieldUserName = $fieldUserName;

        return $this;
    }

    /**
     * Get the value of fieldUserEmail
     */ 
    public function getFieldUserEmail()
    {
        return $this->fieldUserEmail;
    }

    /**
     * Set the value of fieldUserEmail
     *
     * @return  self
     */ 
    public function setFieldUserEmail( String $fieldUserEmail )
    {
        $this->fieldUserEmail = $fieldUserEmail;

        return $this;
    }

    /**
     * Get the value of fieldUserImage
     */ 
    public function getFieldUserImage()
    {
        return $this->fieldUserImage;
    }

    /**
     * Set the value of fieldUserImage
     *
     * @return  self
     */ 
    public function setFieldUserImage( String $fieldUserImage )
    {
        $this->fieldUserImage = $fieldUserImage;

        return $this;
    }

    /**
     * Get the value of fieldUserAccessTokenCode
     */ 
    public function getFieldUserAccessTokenCode()
    {
        return $this->fieldUserAccessTokenCode;
    }

    /**
     * Set the value of fieldUserAccessTokenCode
     *
     * @return  self
     */ 
    public function setFieldUserAccessTokenCode( String $fieldUserAccessTokenCode )
    {
        $this->fieldUserAccessTokenCode = $fieldUserAccessTokenCode;

        return $this;
    }

    /**
     * Get the value of fieldUserActive
     */ 
    public function getFieldUserActive()
    {
        return $this->fieldUserActive;
    }

    /**
     * Set the value of fieldUserActive
     *
     * @return  self
     */ 
    public function setFieldUserActive( Bool $fieldUserActive )
    {
        $this->fieldUserActive = (bool) $fieldUserActive;

        return $this;
    }

    /**
     * Get the value of fieldCreatedAt
     */ 
    public function getFieldCreatedAt()
    {
        return $this->fieldCreatedAt;
    }

    /**
     * Set the value of fieldCreatedAt
     *
     * @return  self
     */ 
    public function setFieldCreatedAt( String $fieldCreatedAt )
    {
        $this->fieldCreatedAt = $fieldCreatedAt;

        return $this;
    }

    /**
     * Get the value of fieldUpdatedAt
     */ 
    public function getFieldUpdatedAt()
    {
        return $this->fieldUpdatedAt;
    }

    /**
     * Set the value of fieldUpdatedAt
     *
     * @return  self
     */ 
    public function setFieldUpdatedAt( String $fieldUpdatedAt )
    {
        $this->fieldUpdatedAt = $fieldUpdatedAt;

        return $this;
    }
}


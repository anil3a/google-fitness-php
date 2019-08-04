<?php
namespace anlprz;
use anlprz\Helper;

Class Database
{
    private $databaseUser;
    private $databaseName;
    private $databasePassword;
    private $databaseHost;

    private $qselect;
    private $qfrom;
    private $qwhere;
    private $qorder = '';
    private $qlimit = '';
    private $qgroup = '';
    private $last_query = '';

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

    /**
     * Query to Database
     *
     * @param String $q
     * @return Array Table/s Result
     */
    public function query( String $q )
    {
        $q = $q;
    }

    public function select( String $select = "*" )
    {
        $this->qselect = $select;
        return $this;
    }

    public function from( String $from )
    {
        $this->qfrom = $from;
        return $this;
    }

    public function where( Array $where )
    {
        $this->qwhere = array_merge( $this->qwhere, $where );
        return $this;
    }

    public function order( String $order )
    {
        $this->qorder = $order;
        return $this;
    }

    public function group( String $group )
    {
        $this->qgroup = $group;
        return $this;
    }

    public function limit( String $limit )
    {
        $this->qlimit = $limit;
        return $this;
    }

    public function getResult()
    {
        $helper = new Helper;
        if( empty( $this->qselect ) ){ $this->select(); }

        $where = '';
        if( !empty( $this->qwhere ) )
        {
            $where = ' WHERE '.  implode( 
                " AND ",
                $helper->array_map_assoc( 
                    function( $k, $v ) {
                        return $k ." = ". $v;
                    },
                    $this->qwhere
                 )
            );
        }
        $limit = ( !empty( $this->qlimit ) ? ' LIMIT '. $this->qlimit : '' );
        $order = ( !empty( $this->qorder ) ? ' ORDER BY '. $this->qorder : '' );
        $group = ( !empty( $this->qgroup ) ? ' GROUP BY '. $this->qgroup : '' );

        $this->last_query = 
            '
                SELECT '. $this->qselect .' 
                FROM '. $this->from .' 
                '. $where .'
                '. $group .'
                '. $order .'
                '. $limit .'                
            '
        ;
        return $this->query( $this->last_query );
    }

    public function reset()
    {
        $this->qselect    = '';
        $this->qfrom      = '';
        $this->qwhere     = [];
        $this->qorder     = '';
        $this->qlimit     = '';
        $this->qgroup     = '';
        $this->last_query = '';
        return true;
    }

    public function getLastQuery()
    {
        return $this->last_query;
    }
    
}
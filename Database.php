<?php
namespace anlprz;
use anlprz\Helper;

Class Database
{
    private $databaseUser;
    private $databaseName;
    private $databasePassword;
    private $databaseHost;
    private $db;
    private $resultObject;

    private $qselect = '';
    private $qfrom = '';
    private $qwhere = [];
    private $qlike = [];
    private $qorder = '';
    private $qlimit = '';
    private $qgroup = '';
    private $last_query = '';

    public static $instance;

    public function __construct()
    {
        self::$instance = $this;
    }

    public static function getInstance()
    {
        if( self::$instance === null )
        {
            self::$instance = new self();
        }
        return self::$instance;
    }

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
                `field_updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY ( `field_user_id` )
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
        // TODO: Create custom query to Database
        // Also use escape(safe) queries 
        $this->mysql_connect();
        $this->resultObject = $this->db->query(
            $q
        );
        return $this;
    }

    private function mysql_connect()
    {
        $this->db = mysqli_connect($this->databaseHost,$this->databaseUser,$this->databasePassword,$this->databaseName);
        if( !$this->db )
        {
            throw new \Exception( 'Database connection failed' );
        }
        return $this->db;
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

    public function like( Array $like )
    {
        $this->qlike = array_merge( $this->qlike, $like );
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

    public function get( String $tableName = "" )
    {
        if( empty( $this->qselect ) ){ $this->select(); }

        $where = '';
        if( !empty( $this->qwhere ) )
        {
            $where = ' WHERE '.  implode( 
                " AND ",
                Helper::array_map_assoc(
                    function( $k, $v ) {
                        if( substr( $k, 0, 1 ) !== "`" )
                        {
                            $k = "`". $k . "`";
                        }
                        return $k ." = \"". $v ."\"";
                    },
                    $this->qwhere
                 )
            );
        }
        if( !empty( $this->qlike ) )
        {
            if( empty( $where ) )
            {
                $where = ' WHERE ';
            }

            $where .= implode(
                " AND ",
                Helper::array_map_assoc(
                    function( $k, $v ) {
                        if( substr( $k, 0, 1 ) !== "`" )
                        {
                            $k = "`". $k . "`";
                        }
                        return $k ." LIKE \"". $v ."\"";
                    },
                    $this->qlike
                 )
            );
        }
        $limit = ( !empty( $this->qlimit ) ? ' LIMIT '. $this->qlimit : '' );
        $group = ( !empty( $this->qgroup ) ? ' GROUP BY `'. $this->qgroup .'`' : '' );

        $order = '';
        if( !empty( $this->qorder ) )
        {
            $order = ' ORDER BY '. $this->qorder;
        }
        // TODO: Need to perform mysql safe field
        // if( !empty( $this->qorder ) )
        // {
        //     if( substr( $this->qorder, 0, 1 ) !== "`" )
        //     {
        //         $orderArray = explode( " ", $this->qorder, 1 );
        //         $orderArray[0] = "`". $orderArray[0] . "`";
        //         $order = ' ORDER BY '. implode( " ", $orderArray );
        //     }
        // }

        if( !empty( $tableName ) )
        {
            $this->qfrom = $tableName;
        }
        if( substr( $this->qfrom, 0, 1 ) !== "`" )
        {
            $fromArray = explode( " ", $this->qfrom, 1 );
            $fromArray[0] = "`". $fromArray[0] . "`";
            $this->qfrom = implode( " ", $fromArray );
        }

        $this->last_query = 
            '
                SELECT '. $this->qselect .'
                FROM '. $this->qfrom .'
                '. $where .'
                '. $group .'
                '. $order .'
                '. $limit .'                
            '
        ;
        return $this->query( $this->last_query );
    }

    public function resultRow()
    {
        if( !empty( $this->resultObject ) )
        {
            $result = $this->resultObject->fetch_assoc();
            $this->reset();
            return $result;
        }
        throw new \Exception( 'Result not found' );
    }

    public function resultArrays()
    {
        if( !empty( $this->resultObject ) )
        {
            $result = $this->resultObject->fetch_all( MYSQLI_ASSOC );
            $this->reset();
            return $result;
        }
        throw new \Exception( 'Result not found' );
    }

    public function count()
    {
        if( !empty( $this->resultObject ) )
        {
            return $this->resultObject->num_rows;
        }
        throw new \Exception( 'Result not found' );
    }

    public function reset()
    {
        $this->qselect    = '';
        $this->qfrom      = '';
        $this->qwhere     = [];
        $this->qlike     = [];
        $this->qorder     = '';
        $this->qlimit     = '';
        $this->qgroup     = '';
        $this->last_query = '';
        $this->resultObject = '';
        return true;
    }

    public function getLastQuery()
    {
        return $this->last_query;
    }
    
}
<?php
namespace anlprz;

Class Database
{
    private $schema = [
        'field_user_id'                 => 'INT NOT NULL AUTO_INCREMENT',
        'field_user_name'               => 'VARCHAR(200) NULL DEFAULT NULL',
        'field_user_email'              => 'VARCHAR(200) NOT NULL',
        'field_user_image'              => 'VARCHAR(250) NULL DEFAULT NULL',
        'field_user_access_token_code'  => 'TEXT',
        'field_user_active'             => 'TINYINT(1) NOT NULL DEFAULT 0',
        'field_created_at'              => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'field_updated_at'              => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];

    // CREATE TABLE IF NOT EXISTS `table_users` (
    // ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1

    public function query( String $q )
    {

    }
}
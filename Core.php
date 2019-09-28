<?php
namespace anlprz;

require_once 'Helper.php';
use anlprz\Helper as Helper;

Class Core
{
    public function return_result( Array $result, Bool $success = true )
    {
        if( !isset( $result['success'] ) )
        {
            $result['success'] = $success;
        }
        header('Content-Type: application/json');
        echo Helper::safe_json_encode(
            $result
        );die;
    }
}
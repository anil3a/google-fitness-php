<?php
namespace anlprz;

Class Core
{
    public function return_result( Array $result, Bool $success = true )
    {
        if( !isset( $result['success'] ) )
        {
            $result['success'] = $success;
        }
        header('Content-Type: application/json');
        echo json_encode(
            $result
        );die;
    }
}
<?php
namespace anlprz;

Class Helper
{
    public function array_map_assoc( $callback , Array $array )
    {
        $r = [];
        foreach( $array as $k => $v )
        {
            $r[$k] = $callback( $k,$v );
        }
        return $r;
    }
}
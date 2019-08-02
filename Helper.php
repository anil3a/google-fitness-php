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

    /**
     * Safe Json Encoder
     *
     * @param ANY $value
     * @param integer $options
     * @param integer $depth
     * @param boolean $utfErrorFlag
     * @return void
     */
    public function safe_json_encode( $value, INT $options = 0, INT $depth = 512, BOOL $utfErrorFlag = false )
    {
        $encoded = json_encode( $value, $options, $depth );
        switch ( json_last_error() )
        {
            case JSON_ERROR_NONE:
                return $encoded;
            case JSON_ERROR_DEPTH:
                throw new \Exception( 'Maximum stack depth exceeded' );
            case JSON_ERROR_STATE_MISMATCH:
                throw new \Exception( 'Underflow or the modes mismatch' );
            case JSON_ERROR_CTRL_CHAR:
                throw new \Exception( 'Unexpected control character found' );
            case JSON_ERROR_SYNTAX:
                throw new \Exception( 'Syntax error, malformed JSON' );
            case JSON_ERROR_UTF8:
                $clean = utf8ize( $value );
                if( $utfErrorFlag )
                {
                    throw new \Exception( 'UTF8 encoding error' );
                }
                return safe_json_encode( $clean, $options, $depth, true );
            default:
                throw new \Exception( 'Unknown error' );
        }
    }

    public function utf8ize( $mixed )
    {
        if( is_array( $mixed ) )
        {
            foreach( $mixed as $key => $value )
            {
                $mixed[$key] = utf8ize( $value );
            }
        } else if( is_string ( $mixed) )
        {
            return utf8_encode( $mixed );
        }
        return $mixed;
    }
}
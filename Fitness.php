<?php
namespace anlprz;

use anlprz\Model;
use anlprz\Anlprz_Date as AnlDate;
use anlprz\Helper;
use anlprz\Database;

Class Fitness extends Model
{
    /**
     * Get Fitness Data from Google REST API
     * 
     * @param integer $durationSeconds   Must be an Integer in seconds
     *                                   eg: 86400 24 hour duration in seconds for a day aggregate
     * @return Google_Service_Fitness_AggregateResponse
     */
    public function getGoogleFitness( AnlDate $date, Int $durationSeonds = 86400 )
    {
        if( empty( (int) $durationSeonds ) ) throw new \Exception( "Duration must a valid integer in seconds" );
        
        $startTimeMillis = $date->getStartTimeStampMillis();
        $endTimeMillis = $date->getEndTimeStampMillis();
        $durationMillis = (int) $durationSeonds;

        $this->initClientAssessed();

        if( empty( $this->getClient() ) ) throw new \Exception( "Google Client failed to initialize" );

        // set 24 hr
        $fitnessBucketByTime = new \Google_Service_Fitness_BucketByTime();
        $fitnessBucketByTime->setDurationMillis( $durationMillis );

        // set AggregateBy
        $aggregateBy = new \Google_Service_Fitness_AggregateBy();
        $aggregateBy->setDataTypeName( $this->getGoogleFitnessAggregateDataTypeName() );

        // fetch Aggregate
        $fitnessAggregate = new \Google_Service_Fitness_AggregateRequest();
        $fitnessAggregate->setAggregateBy( [ $aggregateBy ] );
        $fitnessAggregate->setBucketByTime( $fitnessBucketByTime );
        $fitnessAggregate->setEndTimeMillis( $endTimeMillis );
        $fitnessAggregate->setStartTimeMillis( $startTimeMillis );

        $this->service = new \Google_Service_Fitness( $this->getClient() );
        $steps = $this->service->users_dataset
                ->aggregate( 
                    $this->getGoogleUserId(), 
                    $fitnessAggregate
        );
        return $steps;
    }

    public function getPersonData()
    {
        $this->getFitnessAccess();
        $oauthService = new \Google_Service_Oauth2($this->client);
        return $oauthService->userinfo_v2_me->get();
    }
    
    // $this->save_to_users( $this->getPersonData(), $this->getContactId(), $accessToken );
    public function saveFitnessUser( \Google_Service_Oauth2_Userinfoplus $person, Int $userId, String $accessToken = '' )
    {
        if( !empty( $accessToken ) )
        {
            $accessToken = json_encode( $accessToken );
        }

        $userFolderPath = __DIR__ . DIRECTORY_SEPARATOR .'user-images/';
        $_ds = DIRECTORY_SEPARATOR;

        try {

            $path =  $userFolderPath . $userId;
            Helper::recursive_mkdir_folder( $path );
            $path .= $_ds;

            $parts = array_filter( explode( '/', $person->picture ) );
            $image_name = array_pop($parts);
            $image_name = 'gplus_'. time() .'_'. $image_name;
            file_put_contents( $path . $image_name, file_get_contents( $person->picture ) );

            $user = [
                'field_user_id' => $userId,
                'field_user_active' => 1,
            ];

            $db = new Database();

            $querySelect = 'SELECT * FROM `table_users` WHERE ';
            $where = implode(
                " AND ",
                Helper::array_map_assoc(
                    function( $k, $v ) {
                        return $k ." = ". $v;
                    },
                    $user
                    )
            );
            $querySelect .= $where;
            $querySelect .= ' ORDER BY `field_user_id` DESC LIMIT 1';

            $userResult = $db->query( $querySelect );

            if( !empty( $userResult['field_user_id'] ) )
            {
                $updateData = [];
                if( empty( $userResult['field_user_name'] ) )
                {
                    $updateData = [
                        'field_user_name' => trim( $person->name ),
                        'field_user_userdata' => json_encode( $person ),
                        'field_user_email' => trim( $person->email )
                    ];
                    if( !empty( $accessToken ) )
                    {
                        $updateData['field_user_access_token_code'] = $accessToken;
                    }
                }

                $set = implode(
                    ", ",
                    Helper::array_map_assoc(
                        function( $k, $v ) {
                            return $k ." = ". $v;
                        },
                        $updateData
                        )
                );

                $queryUpdate = 'UPDATE `table_users` SET '. $set .' WHERE '. $where;

                $db->query( $queryUpdate );

            } else {
                throw new \Exception( "User must exists and active." );
            }

        } catch ( \Exception $e ) {
            throw new \Exception( $e->getMessage() );
        }
        return $this;
    }

    /**
     * Save Fitness Daily data to user's fitness table
     *
     * @param Google_Service_Fitness_AggregateResponse $response
     * @param Int $user_field_user_id
     * @param String $date
     * @return void
     */
    // TODO: working here to remove codeingiter functions
    public function saveFitnessDaily( \Google_Service_Fitness_AggregateResponse $response, Int $user_field_user_id, String $date )
    {
        $result = [ 'success' => false, 'data' => [], 'message' => '' ];

        if( !empty( $response ) && !empty( $response->bucket ) && is_array( $response->bucket ) )
        {
            $r = $this->readableAggregateResponse( $response );

            if( empty( $r['result'] ) )
            {
                return false;
            }
            $result = $r['result'];

            $update = [
                "user_field_user_id"=> $user_field_user_id,
                "data_source_id"    => $result['data_source_id'],
                "start_time_millis" => $result['start_time_millis'],
                "end_time_millis"   => $result['end_time_millis'],
                "stepdate"          => $date
            ];
            $insert = [
                "steps"             => $result['steps'],
                "data"              => $result['data'],
            ];

            $db = new Database();

            $querySelect = 'SELECT `field_user_fitness_id` FROM `table_user_fitness` WHERE ';
            $where = implode(
                " AND ",
                Helper::array_map_assoc(
                    function( $k, $v ) {
                        return $k ." = ". $v;
                    },
                    $update
                )
            );
            $querySelect .= $where;
            $querySelect .= ' ORDER BY `field_user_fitness_id` DESC LIMIT 1';

            $existingSteps = $db->query( $querySelect );

            $insert['data'] = json_encode( $insert['data'] );
            if( empty( $existingSteps ) )
            {
                $result['data']['result'] = $db->insert( 'table_user_fitness', array_merge( $update, $insert ) );
                $result['data']['steps'] = $insert['steps'];
                $result['data']['query'] = "insert";
                $result['data']['id'] = $db->last_insert_id();
            }
            else {
                $result['data']['result'] = $db->update( 
                    "mdlgoogle_fitness_daily",
                    $insert,
                    [ "field_user_fitness_id" => $existingSteps['field_user_fitness_id'] ]
                );
                $result['data']['steps'] = $insert['steps'];
                $result['data']['query'] = "update";
                $result['data']['id'] = $existingSteps['field_user_fitness_id'];
            }
            $result['success'] = true;
        }
        return $result;
    }

    public function readableAggregateResponse( \Google_Service_Fitness_AggregateResponse $response )
    {
        $return = [ 'success' => false, 'result' => [], 'message' => '' ];

        $result = [
            "data_source_id" => null,
            "start_time_millis" => null,
            "end_time_millis" => null,
            "steps" => 0,
            "data" => []
        ];

        if( !empty( $response ) && !empty( $response->bucket ) && is_array( $response->bucket ) )
        {
            $response['message'] = 'if entered';
            $result['data']['bucket'] = [];
            try{
                foreach( $response->bucket as $k1 => $bucket )
                {
                    if( isset( $bucket->startTimeMillis ) )
                    {
                        $result['start_time_millis'] = $bucket->startTimeMillis;
                        $result['data']['bucket'][$k1]['startTimeMillis'] = $bucket->startTimeMillis;
                    }
                    if( isset( $bucket->endTimeMillis ) )
                    {
                        $result['end_time_millis'] = $bucket->endTimeMillis;
                        $result['data']['bucket'][$k1]['endTimeMillis'] = $bucket->endTimeMillis;
                    }
                    if( !empty( $bucket->dataset ) && is_array( $bucket->dataset ) )
                    {
                        $result['data']['bucket'][$k1]['dataset'] = [];
                        foreach( $bucket->dataset as $k2 => $dataset )
                        {
                            if( !empty( $dataset->dataSourceId ) )
                            {
                                $result['data']['bucket'][$k1]['dataset'][$k2]['dataSourceId'] = $dataset->dataSourceId;
                            }
                            if( !empty( $dataset->point ) && is_array( $dataset->point ) )
                            {
                                $result['data']['bucket'][$k1]['dataset'][$k2]['point'] = [];
                                foreach( $dataset->point as $k3 => $point )
                                {
                                    if( !empty( $point->dataTypeName ) )
                                    {
                                        $result['data']['bucket'][$k1]['dataset'][$k2]['point'][$k3]['dataTypeName'] = $point->dataTypeName;
                                    }
                                    if( !empty( $point->startTimeNanos ) )
                                    {
                                        $result['data']['bucket'][$k1]['dataset'][$k2]['point'][$k3]['startTimeNanos'] = $point->startTimeNanos;
                                    }
                                    if( !empty( $point->endTimeNanos ) )
                                    {
                                        $result['data']['bucket'][$k1]['dataset'][$k2]['point'][$k3]['endTimeNanos'] = $point->endTimeNanos;
                                    }
                                    if( !empty( $point->originDataSourceId ) )
                                    {
                                        $result['data_source_id'] = trim( $point->originDataSourceId );
                                        $result['data']['bucket'][$k1]['dataset'][$k2]['point'][$k3]['originDataSourceId'] = $point->originDataSourceId;
                                    }
                                    if( !empty( $point->value ) && is_array( $point->value ) )
                                    {
                                        $result['data']['bucket'][$k1]['dataset'][$k2]['point'][$k3]['value'] = [];
                                        foreach( $point->value as $value )
                                        {
                                            $result['data']['bucket'][$k1]['dataset'][$k2]['point'][$k3]['value'][] = $value;
                                            if( isset( $value->intVal ) )
                                            {
                                                $result['steps'] += (int) $value->intVal;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } // foreach $fitness bucket
                $return['success'] = true;
            } catch ( \Exception $e ) {
                $return['message'] = $e->getMessage();
            }
        }
        $return['result'] = $result;
        return $return;
    }

}
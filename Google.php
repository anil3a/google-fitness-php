<?php
namespace anlprz;
require_once 'Model.php';
require_once 'User.php';
require_once 'Fitness.php';
require_once 'Anlprz_Date.php';
require_once 'Core.php';

// use Model;
use anlprz\Model as Model;
use anlprz\User as User;
use anlprz\Fitness as Fitness;
use anlprz\Anlprz_Date as Anlprz_Date;
use anlprz\Core as Core;

Class Google extends Core
{
    private $apiCallInterval = 300; // 5 minutes

    public function initGoogleAuth( $email = '' )
    {
        $result = [ 'success' => false, 'data' => [], 'message' => '' ];

        $user = new User( $email );
        $model = new Model();

        $model->setUserId( $user->id );

        $result['data']['google_fitness_token'] = false;
        $result['data']['google_fitness_authurl'] = "";
        try {
            $googleClient = $model->getFitnessAccess();

            $result['data']['google_fitness_token'] = $googleClient->isAuthenticated();
            $result['data']['google_fitness_authurl'] = $googleClient->getUrlAuth();
            $result['data']['steps_today'] = 0;

            $todayDateTime = new \DateTime();
            //$today_date = "2019-07-07";

            if( $result['data']['google_fitness_token'] )
            {
                $googleClient->initUserFitnessData();
                $userFitnessData = $googleClient->getFitnessData();

                $fitnessModel = new Fitness();

                $fetch = true;
                if( !empty( $userFitnessData['field_user_id'] ) )
                {
                    $result['data']['query'] = $fitnessModel->getUserDailySteps(
                        $userFitnessData['field_user_id'],
                        '`field_user_daily_id`,`steps`,`data`,`created_at`',
                        [
                            'stepdate' => $todayDateTime->format( "Y-m-d" )
                        ],
                        'stepdate ASC'
                    );
                    if( !empty( $result['data']['query'][0]['steps'] ) )
                    {
                        $fetch = false;
                        if( !empty( $result['data']['query'][0]['created_at'] ) )
                        {
                            $created_at = '';
                            try {
                                $created_at = new \DateTime( $result['data']['query'][0]['created_at'] );
                                $now        = new \DateTime();
                                $last_ran   = $now->getTimestamp() - $created_at->getTimestamp();

                                // only every 5 mintues API is called to update your Today's Steps
                                if( $last_ran > $this->apiCallInterval )
                                {
                                    $fetch = true;
                                }

                            } catch ( Exception $e ) {
                               $fetch = true;
                            }
                        }
                        $result['data']['response'] = $result['data']['query'][0]['data'];
                        $result['data']['steps_today'] = (int) $result['data']['query'][0]['steps'];
                    }
                }
                if( $fetch )
                {
                    $anlDate = new Anlprz_Date( $todayDateTime );
                    $response = $fitnessModel->getGoogleFitness( $anlDate );
                    $result['data']['response'] = $response;
                    $result['data']['steps_result'] = $fitnessModel->readableAggregateResponse( $response );
                    if( !empty( $result['data']['steps_result']['result'] ) && isset( $result['data']['steps_result']['result']['steps'] ) )
                    {
                        $result['data']['steps_today'] = (int) $result['data']['steps_result']['result']['steps'];
                    }
                    // save it to database
                    $fitnessModel->saveFitnessDaily(
                        $response,
                        $userFitnessData['id_fitness'],
                        $todayDateTime->format("Y-m-d")
                    );
                }
            }
            $result['success'] = true;
        } catch ( Exception $e ) {
            $result['message'] = 'Please re-authenicate your access token again.';
        }
        return $this->return_result( $result );
    }

    public function callBack( String $email, Array $getGoogleRequest )
    {
        $result = [ 'success' => false, 'data' => [], 'message' => '' ];

        $user = new User( $email );
        $model = new Model();
        $todayDateTime = new \Datetime();
        $anlDate = new Anlprz_Date( $todayDateTime );
        $model->setUserId( $user->getId() );

        if( empty( $getGoogleRequest['code'] ) )
        {
            $result['message'] = 'Google Authentication Error. Please re-authenticate again.';
            return $this->return_result( $result );
        }
        try {
            $model->getFitnessAccess();
            $result['data']['at'] = $model->setGoogleUserAuthentication( $getGoogleRequest['code'] );
            $this->getFitnessStepsByDate( $user->getId(), $anlDate );
            $result['success'] = true;
        } catch ( Exception $e ) {
            $result['message'] = 'Please re-authenicate your access token again. Error Traced: '. $e->getMessage();
            $result['data']['post'] = $getGoogleRequest;
        }
        return $this->return_result( $result );
    }

    public function removeAccessToken( String $email )
    {
        $result = [ 'success' => false, 'data' => [], 'message' => '' ];

        $user = new User( $email );
        $model = new Model();
        $model->setUserId( $user->getId() );

        try {
            $model->initUserFitnessData();
            $fitnessData = $model->getFitnessData();
            if( !empty( $fitnessData['field_user_access_token_code'] ) )
            {
                $model->saveUserAccessToken( '' );
            }
            $result['success'] = true;
        } catch ( Exception $e ) {
            $result['message'] = 'Error Traced: '. $e->getMessage();
        }
        return $this->return_result( $result );
    }

}
<?php
namespace anlprz;

Class Anlprz_Date
{
    private $startTimestampMillis;
    private $endTimestampMillis;
    private $dateTime;
    private $googleDateTime;

    public function __construct( \DateTime $dateObj )
    {
        try {
            $this->dateTime = $dateObj;
            $this->reset();
            $dateObj->setTime( 0, 0, 0 );
            $dateObj->setTimezone( new \DateTimeZone('UTC') );

            $this->googleDateTime = $dateObj;

            $dateTimeEnd = clone $dateObj;

            // adjust from the next day to get end of this day
            $dateTimeEnd->modify('+1 day');
            $dateTimeEnd->modify('1 second ago');

            $start = strtotime( $dateObj->format('Y-m-d H:i:s e') ) * 1000;
            if( $start > 1000000000000 )
            {
                $this->setStartTimeStampMillis( $start );
            }
            $end = strtotime( $dateTimeEnd->format('Y-m-d H:i:sP') ) * 1000;
            if( $end > 1000000000000 )
            {
                $this->setEndTimeStampMillis( $end );
            }
            
        } catch ( \Exception $e ) {
            throw new \Exception( $e->getMessage() );
        }
    }

    public function getStartTimeStampMillis()
    {
        return $this->startTimestampMillis;
    }

    protected function setStartTimeStampMillis( Int $startTimestampMillis )
    {
        $this->startTimestampMillis = $startTimestampMillis;
    }

    public function getEndTimeStampMillis()
    {
        return $this->endTimestampMillis;
    }

    protected function setEndTimeStampMillis( Int $endTimestampMillis )
    {
        $this->endTimestampMillis = $endTimestampMillis;
    }

    public function getDateTime()
    {
        return $this->dateTime;
    }

    public function getGoogleDateTime()
    {
        return $this->googleDateTime;
    }

    public function reset()
    {
        $this->setStartTimeStampMillis( 0 );
        $this->setEndTimeStampMillis( 0 );
    }
}
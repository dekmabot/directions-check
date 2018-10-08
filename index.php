<?php

/**
 * Class Point
 */
class Point
{
    /** @var float */
    public $latitude;
    /** @var float */
    public $longitude;
    /** @var float */
    public $angle = 0;
    
    /**
     * Point constructor.
     *
     * @param float $latitude
     * @param float $longitude
     */
    public function __construct($latitude, $longitude)
    {
        $this->latitude = (float)$latitude;
        $this->longitude = (float)$longitude;
    }
}

/**
 * Class Command
 */
class Command
{
    /** @var string */
    public $command;
    /** @var float */
    public $value;
    
    const COMMANT_START = 'start';
    const COMMAND_WALK = 'walk';
    const COMMAND_TURN = 'turn';
    
    /**
     * Command constructor.
     *
     * @param string $command
     * @param float  $value
     */
    public function __construct($command, $value)
    {
        $this->command = $command;
        $this->value = (float)$value;
    }
    
    /**
     * @param Point $point
     */
    public function run(Point $point)
    {
        if (self::COMMANT_START === $this->command) {
            $point->angle += $this->value;
            
        }elseif (self::COMMAND_WALK === $this->command) {
            $point->latitude += $this->value * cos(deg2rad($point->angle));
            $point->longitude += $this->value * sin(deg2rad($point->angle));
            
        }elseif (self::COMMAND_TURN === $this->command) {
            $point->angle += $this->value;
        }
    }
}

class Direction
{
    /** @var Point */
    public $startCoords;
    
    /** @var Point */
    public $finalCoords;
    
    /** @var Command[] */
    public $commands = [];
    
    /**
     * Direction constructor.
     *
     * @param $string
     */
    public function __construct($string)
    {
        $regexCoords = '/(?<latitude>[\-\d\.]+)\s(?<longitude>[\-\d\.]+)/m';
        $regexCommands = '/\s(?<command>[\w]+)\s(?<value>[\-\d\.]+)/m';
        
        preg_match($regexCoords, $string, $coords);
        preg_match_all($regexCommands, $string, $commands, PREG_SET_ORDER, 0);
        
        $this->startCoords = new Point($coords['latitude'], $coords['longitude']);
        
        foreach ($commands as $command) {
            $this->commands[] = new Command($command['command'], $command['value']);
        }
    }
    
    public function run()
    {
        $point = clone($this->startCoords);
        foreach ($this->commands as $command) {
            $command->run($point);
        }
        
        $this->finalCoords = $point;
    }
}

class DirectionsPack
{
    /** @var integer */
    public $id;
    
    /** @var Direction[] */
    public $directions = [];
    
    /** @var Point */
    public $finalCoords = null;
    /** @var Point */
    public $averagePoint = null;
    
    /** @var float */
    public $farestDirectionDistance = 0;
    
    /**
     * @param integer $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }
    
    public function run()
    {
        foreach ($this->directions as $direction) {
            $direction->run();
        }
    }
    
    /**
     * @return Point
     */
    public function getAverageFinalPoint()
    {
        $averageLatitude = [];
        $averageLongitude = [];
        foreach ($this->directions as $direction) {
            if (null !== $direction->finalCoords) {
                $averageLatitude[] = $direction->finalCoords->latitude;
                $averageLongitude[] = $direction->finalCoords->longitude;
            }
        }
        
        $averageLatitude = array_sum($averageLatitude) / count($averageLatitude);
        $averageLongitude = array_sum($averageLongitude) / count($averageLongitude);
        
        return new Point($averageLatitude, $averageLongitude);
    }
    
    /**
     * @param Point $point
     *
     * @return float
     */
    public function getFarestDirectionDistance(Point $point)
    {
        $result = null;
        foreach ($this->directions as $direction) {
            $distance = sqrt(
                pow(abs($direction->finalCoords->latitude - $point->latitude), 2)
                + pow(abs($direction->finalCoords->longitude - $point->longitude), 2)
            );
            if (null === $result || $result < $distance) {
                $result = $distance;
            }
        }
        
        return $result;
    }
}

class PacksCollection
{
    /** @var DirectionsPack[] */
    private $packs = [];
    
    public function __construct($input)
    {
        $currentTestId = null;
        
        $strings = explode(PHP_EOL, $input);
        foreach ($strings as $i => $string) {
            $string = trim($string);
            
            if ($string === '0') {
                break;
                
            }elseif (is_numeric($string)) {
                $currentTestId = (int)$string;
                $this->packs[$currentTestId] = new DirectionsPack($currentTestId);
                
            }elseif (null !== $currentTestId) {
                $this->packs[$currentTestId]->directions[] = new Direction($string);
            }
        }
    }
    
    /**
     * @return array
     */
    public function run()
    {
        $reports = [];
        foreach ($this->packs as $pack) {
            $pack->run();
            
            $point = $pack->getAverageFinalPoint();
            
            $reports[] = round($point->latitude, 4) . ' '
                . round($point->longitude, 4) . ' '
                . round($pack->getFarestDirectionDistance($point), 5);
        }
        
        return $reports;
    }
}

$input = '3
87.342 34.30 start 0 walk 10.0
2.6762 75.2811 start -45.0 walk 40 turn 40.0 walk 60
58.518 93.508 start 270 walk 50 turn 90 walk 40 turn 13 walk 5
2
30 40 start 90 walk 5
40 50 start 180 walk 10 turn 90 walk 5
0';

$collection = new PacksCollection($input);
$report = $collection->run();

echo implode(PHP_EOL, $report) . PHP_EOL;




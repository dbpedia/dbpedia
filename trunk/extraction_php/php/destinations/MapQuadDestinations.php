<?php
namespace dbpedia\destinations
{

use dbpedia\util\PhpUtil;

class MapQuadDestinations implements QuadDestinations
{
    /** map from */
    private $destinations = array();
    
    public function getDestination( $destinationId )
    {
        PhpUtil::assertString($destinationId, 'destination id');
        if (! isset($this->destinations[$destinationId])) throw new \InvalidArgumentException('destination for id [' . $destinationId . '] not set');
        return $this->destinations[$destinationId];
    }
    
    public function setDestination( $destinationId, $destination )
    {
        PhpUtil::assertString($destinationId, 'destination id');
        PhpUtil::assertType($destination, 'dbpedia\destinations\QuadDestination', 'destination');
        if (isset($this->destinations[$destinationId])) throw new \InvalidArgumentException('destination for id [' . $destinationId . '] already set');
        $this->destinations[$destinationId] = $destination;
    }
}
}

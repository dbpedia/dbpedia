<?php

namespace dbpedia\destinations
{

use dbpedia\util\PhpUtil;

class SingletonQuadDestinations implements QuadDestinations
{
    private /* final */ $destination;
    
    public function __construct( $destination )
    {
        PhpUtil::assertType($destination, 'dbpedia\destinations\QuadDestination', 'destination');
        $this->destination = $destination;
    }
    
    public function getDestination( $destinationId )
    {
        PhpUtil::assertString($destinationId, 'destination id');
        return $this->destination;
    }
}

}

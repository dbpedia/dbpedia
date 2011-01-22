<?php
namespace dbpedia\destinations
{
interface QuadDestinations
{
    /**
     * @param $destinationId string
     * @return QuadDestination
     * @throws InvalidArgumentException if there is no destination with the given id
     */
    public function getDestination( $destinationId );
}
}

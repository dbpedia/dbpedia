<?php
namespace dbpedia\destinations
{
class EchoQuadDestination implements QuadDestination
{
    public function addQuad( $quad )
    {
        echo $quad . PHP_EOL;
    }
}
}

<?php
namespace dbpedia\destinations
{
class StringQuadDestination implements QuadDestination
{
    private $quads = '';
    
    public function addQuad( $quad )
    {
        $this->quads .= $quad;
        // Ontoprise Triple Store doesn't like line breaks - use spaces for now
        $this->quads .= ' ';
        // $this->quads .= "\n"; // don't use PHP_EOL here
    }
    
    public function reset()
    {
        $this->quads = '';
    }
    
    public function __toString()
    {
        return $this->quads;
    }
}
}

<?php

namespace dbpedia\util
{

class ConfigHolder
{
	public function __construct( $ontology, $extractor, $destinations )
	{
		$this->ontology = $ontology;
		$this->extractor = $extractor;
		$this->destinations = $destinations;
	}
	
    public $ontology;
    
    public $extractor;

	public $destinations;
}

}

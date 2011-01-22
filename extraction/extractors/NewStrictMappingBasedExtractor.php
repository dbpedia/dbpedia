<?php

/**
 * The NewStrictMappingBasedExtractor extracts infoboxes/templates from 
 * Wikipedia articles using the new property names and strict extraction.
 * It matches the template properties to the DBpedia ontology ones.
 */
class NewStrictMappingBasedExtractor extends MappingBasedExtractor {
	public function __construct() {
		$this->setFlagForNewSchemaExport(true);
		$this->setFlagForStrictExport(true);
	}
}

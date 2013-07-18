<?php

/**
 * The OldLenientMappingBasedExtractor extracts infoboxes/templates from 
 * Wikipedia articles using the old property names and lenient extraction.
 * It matches the template properties to the DBpedia ontology ones.
 */
class OldLenientMappingBasedExtractor extends MappingBasedExtractor {
	public function __construct() {
		$this->setFlagForNewSchemaExport(false);
		$this->setFlagForStrictExport(false);
	}
}

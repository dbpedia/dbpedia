<?php

/**
 * Defines the interface Extractor.
 * Extractors are used for the actual data extraction process
 * 
 */

interface ExtractorInterface {

	
    /** @return uri */
    public function getExtractorID();
    public function start($language);
    /**
	Extracts RDF triples from the markup source code of a wiki page.
	@param pageID the title of this page in the english Wikipedia; title may contain spaces or underscores, but is otherwise unencoded
	@param pageTitle the title of this page in the current language Wikipedia; title may contain spaces or underscores, but is otherwise unencoded
	@param pageSource the wiki markup of the page in the current language
	@return ExtractionResult */
    public function extractPage($pageID, $pageTitle, $pageSource);
    /** @return ExtractionResult */
    public function finish();
}


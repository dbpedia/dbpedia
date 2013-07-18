<?php

/**
 * this is a dummy extractor which should be included in LiveExtraction, but deactivated
 *  for all time
 * 
 */

class AlwaysFilterExtractor extends Extractor 
{

    public function extractPage($pageID, $pageTitle,  $pageSource) {
        return null;
    }
    
}



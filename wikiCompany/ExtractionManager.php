<?php

/**
 * The Extraction manager executes ExtractionJobs and triggers
 * the actual extraction process.
 * 
 * 
 */
class ExtractionManager {

	/**
	 * Triggers the ExtractionJob
	 * @param $job: An ExtractionJob
	 */
    public function execute($job) {
        $language = $job->getPageCollection()->getLanguage();
        
        // Initialize Extractors and Destination
        foreach (new ArrayObject($job->getExtractionGroups()) as $group) {
            $group->getDestination()->start();
            foreach (new ArrayObject($group->getExtractors()) as $extractor) {
                $extractor->start($language);
            }
        }
        
        // Extract content from WikiPedia Pages
        // The PageTitleIterator loops over all pages from a PageCollection
        foreach ($job->getPageTitleIterator() as $pageTitle) {
            $pageSource = $job->getPageCollection()->getSource($pageTitle);
            
            $pageID = $this->getPageID($pageTitle, $pageSource, $language);
            if($pageID === false)
            {
            	continue;
            	
            }
			if($pageID == NULL)
            {
            	continue;
            	
            }
            
            // Extract the content and pass it to the Destination
            foreach (new ArrayObject($job->getExtractionGroups()) as $group) {
                $destination = $group->getDestination();
                foreach (new ArrayObject($group->getExtractors()) as $extractor) {
                    $result = $extractor->extractPage($pageID, $pageTitle, $pageSource);
                    $destination->accept($result);
                }
            }
        }
        
        // Get Predicates (only used for InfoboxExtraction)
        // Optional MetaInformation is stroed in MetaDestination 
        foreach (new ArrayObject($job->getExtractionGroups()) as $group) {
		$destination = $group->getMetaDestination();
           if ( $destination == null ){ continue;}
		   $destination->start();
		   foreach (new ArrayObject($group->getExtractors()) as $extractor) {
         		$result = $extractor->finish();
          		if ( $result != null) $destination->accept($result);
             }            
        }
         
          
        // Close Destinations and Extractors
        foreach (new ArrayObject($job->getExtractionGroups()) as $group) {
            $group->getDestination()->finish();
			
			$MetaDestination = $group->getMetaDestination();
			if ( $MetaDestination != NULL){$MetaDestination -> finish();}
			
            foreach (new ArrayObject($group->getExtractors()) as $extractor) {
               $extractor->finish();
             }            
        }
    }
    
    private function getPageID($pageTitle, $PageSource, $Language)
    {
    	if($Language == "en")
    	{
    		return $pageTitle;
    		
    	}
    	else 
    	{
    		if (!preg_match("/\[\[en:(.*?)\]\]/", $PageSource, $match))
    		{
    			return false;	
    		}
    		else 
    		{
    			
    			return $match[1];
    		}
    	}
    	
    }
}


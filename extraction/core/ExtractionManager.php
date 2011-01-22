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
		$this->log(DEBUG, "extractors started");
		
		// Extract content from WikiPedia Pages
		// The PageTitleIterator loops over all pages from a PageCollection
		foreach ($job->getPageTitleIterator() as $pageTitle) {
			Timer::start(get_class($job->getPageCollection()).'::getSource');
			$pageSource = $job->getPageCollection()->getSource($pageTitle);
			Timer::stop(get_class($job->getPageCollection()).'::getSource');
			$pageID = $this->getPageID($pageTitle, $pageSource, $language);
			if($pageID === false || $pageID == NULL) {
				continue;
			}
			
			$this->log(DEBUG, "begin page extraction");
			// Extract the content and pass it to the Destination
			foreach (new ArrayObject($job->getExtractionGroups()) as $group) {
				$destination = $group->getDestination();
				foreach (new ArrayObject($group->getExtractors()) as $extractor) {
					 $this->log(DEBUG,"extractPage: ".$extractor->getExtractorID());	

						$result = $extractor->extractPage($pageID, $pageTitle, $pageSource);
												
						Timer::start('destination:accept');
						$destination->accept($result);
						Timer::stop('destination:accept');
					
				}//end foreach
				
			}//end outer foreach
		}
		
		// Close Destinations and Extractors
		foreach (new ArrayObject($job->getExtractionGroups()) as $group) {
			Timer::start('destination:finish');
			$group->getDestination()->finish();
			Timer::stop('destination:finish');
			
			// Optional MetaInformation is stored in MetaDestination 
			// Currently only used for InfoboxExtraction for predicates
			$metaDestination = $group->getMetaDestination();
			if ( $metaDestination != null) {
				
				$metaDestination -> start();
				foreach (new ArrayObject($group->getExtractors()) as $extractor) {
					$result = $extractor->finish();
					if ($result != null) {
						 $metaDestination->accept($result);
					}
				}
				$metaDestination -> finish();
			}
		}
	}
	
	private function getPageID($pageTitle, $PageSource, $Language) {
		
		if(($Language == "en") || (false == Options::getOption('dependsOnEnglishLangLink')) ) {
			return $pageTitle;
		} else if(Options::getOption('dependsOnEnglishLangLink')){
			if (!preg_match("/\[\[en:(.*?)\]\]/", $PageSource, $match)) {
				return false;	
			} else {
				return str_replace(" ", "_", $match[1]); /* underscores are allowed in links */
			}
		
		}else {
			$this->log(ERROR, 'bad tail in ExtractionManager, getPageID');
		}
	}
	
	private function log($lvl, $message){
		Logger::logComponent("core",get_class($this)."", $lvl ,$message);
	}
}


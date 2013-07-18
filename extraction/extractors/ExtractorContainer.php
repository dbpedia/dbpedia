<?php

class ExtractorContainer implements ExtractorInterface
{
	protected $extractor;
	
	public function getExtractorID() {
        return $this->extractor->getExtractorID();
    }
	public function __construct($extractor){
		$this->extractor = $extractor;
		
	}
    public function start($language) {
			$this->extractor->setChecksum();
			if(!$this->extractor->isActive()){
				return false;
			}
      		return $this->extractor->start($language);
	  
    }
    public function extractPage($pageID, $pageTitle, $pageSource) {
			$this->extractor->setPageURI($pageID);
			if(!$this->extractor->isActive()){
				return  $result = new ExtractionResult( $pageID, $this->extractor->getLanguage(), $this->getExtractorID());
			}
			Timer::start( $this->extractor->getExtractorID());
			$result =  $this->extractor->extractPage($pageID, $pageTitle, $pageSource);
			Timer::stop( $this->extractor->getExtractorID());
			Timer::start('validation');
			//$this->extractor->check();
			if(Options::getOption('validateExtractors')){
				ValidateExtractionResult::validate($result, $this->extractor);
			}
			Timer::stop('validation');
			
			Statistics::increaseCount( $this->extractor->getExtractorID(),'created_Triples', count($result->getTriples()));
			Statistics::increaseCount( 'Total','created_Triples', count($result->getTriples()));
			
			if($this->extractor->isGenerateOWLAxiomAnnotations()){
					$triples = $result->getTriples();
					if(count($triples)>0){
							foreach($triples as $triple){
									$triple->addDCModifiedAnnotation();
									$triple->addExtractedByAnnotation($this->extractor->getExtractorID());
							}
						}
				}
			
			return $result;
	}
	
	public function finish() { 
		if(!$this->extractor->isActive()){
				return false;
		}
		 return $this->extractor->finish();
	}
	
	public function inside(){
		return $this->extractor;
		}
	
/*
	public function isGenerateOWLAxiomAnnotations(){
		return $this->generateOWLAxiomAnnotations;
	}
*/
}

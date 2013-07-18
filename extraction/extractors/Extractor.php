<?php

/**
 * Defines the interface Extractor.
 * Extractors are used for the actual data extraction process
 * 
 */

abstract class Extractor implements ExtractorInterface{
 	
	protected $pageURI;
	protected $language;
	protected $metadata = array();
	protected $additionalInfo = array();
	var $validstates = array(ACTIVE, KEEP, PURGE);
	var $status = ACTIVE;
	public $vocabchecksum;
	protected $generateOWLAxiomAnnotations = true;
	
	/*
	 * this should be done in a much better way
	 * */
	public function __construct(){
		$this->metadata[EXTRACTORID] = $this->getExtractorID(); 
		$this->language = Options::getOption('language');
		
		$this->addMetaData(ExtractorConfiguration::getMetadata($this->getLanguage(), get_class($this)));
		
		}
	
	/*
	 * Default
	 * */
	public function start($language) {
		$this->language = $language;
	}
	 /*
	 * Default
	 * */
	 public function getExtractorID(){
		 	return DB_META_NS.get_class($this);
		 	//return $this->metadata[EXTRACTORID];
		 }
	
	 /*
	 * Default
	 * */
	 public function finish() { 
        return null;
    }
	
	/*
	 * SETTER AND GETTER
	 * */
	public function getMetadata(){
			$this->metadata[STATUS]=$this->status;
			$this->metadata[OWLAXIOMDEFAULT]=$this->isGenerateOWLAxiomAnnotations();
			return $this->metadata;
		}
	
	public function getLanguage(){
			return $this->language;
		}
	
	public function addAdditionalInfo($array){
			$this->additionalInfo = array_merge($this->additionalInfo, $array);
		}
	public function addMetaData( $metadata){
			 foreach ($metadata as $key=>$value){
				$this->metadata[$key] = $value;
				}
			//var_dump($this->metadata);die;
		}
	
	public function getStatus(){
		return $this->status;
		}
	
	protected function getPageURI(){
		return $this->pageURI;
		}
	
	public function setPageURI($pageID){
		$this->pageURI =  RDFtriple::page($pageID);
		}
	
	public function isActive(){
		return ($this->status===ACTIVE);
		}
		
	public function isGenerateOWLAxiomAnnotations(){
		return $this->generateOWLAxiomAnnotations;
	}
	
	public function setGenerateOWLAxiomAnnotations($bool){
		$this->generateOWLAxiomAnnotations = $bool;
	}
	
	public function setStatus($status){
			if(!in_array($status,$this->validstates)){
					die("Invalid Extractor State: ".$status. " ".get_class($this));
				}
			else {
				$this->status= $status;
				
				}
		}
		
	/*
	 * some process functions
	 * */
	
	public function setChecksum(){
			$this->vocabchecksum = count($this->getMetadataProduces());
		}
	
	
	public function check(){
			if ($this->vocabchecksum != count($this->getMetadataProduces())) {
				die("Error: you should NOT add 'produces' metadata dynamically. ".get_class($this));
			}
			return true;
		}

	public function getMetadataProduces(){
			if(isset($this->metadata[PRODUCES])){
				return $this->metadata[PRODUCES];
				}
		}

	public function getSPARQLFilter(){
			return Rule::getSPARQLFilter($this->metadata);
		
		}
		
	public function log ($lvl, $message){
			Logger::logComponent("extractor",get_class($this)."", $lvl ,$message);
		}
		
	
		
		
	
/*
		private function isResourceAvailable($type, $identifier){
			
			if(empty($this->resources)) {
				
				return false;
				}
			$retval = false;
			foreach ($this->resources as $resource) {
				if(isset($resource['type']) && $resource['type'] == $type){
					if(isset($resource['identifier']) && $resource['identifier'] == $identifier){
						$retval = true;
					}
				}
			}
			return $retval;
		}
	
	private function getResource($type, $identifier){
			
			if($this->isResourceAvailable($type, $identifier)===false){
					$message = "Extractor: ".get_class($this). 
						" is asking for a resource of type: ".$type." identifier: ".$identifier." which is not available. ".
						"Please check configuration. " ;
						Logger::info($message);
				 	return false;
				}
			foreach ($this->resources as $resource) {
					if(isset($resource['type']) && $resource['type'] == $type){
						if(isset($resource['identifier']) && $resource['identifier'] == $identifier){
							return $resource['object'];
						}
					}
				}
			Logger::error("tail in Extractor.php ".$type." ".$identifier." Extractor: ".get_class($this));
			return false;
		}
	
	public function announceResources($resources){
			$this->resources = $resources;
		}
*/
		
  
}


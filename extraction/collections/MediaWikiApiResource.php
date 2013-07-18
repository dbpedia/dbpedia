<?
class MediaWikiApiResource extends HTTPResource{
	
		protected $lastquery = "";
		protected $cachedResponse = "";
		protected $format = "php";
		protected $action = "parse";

		public function init(){
				//nothing
			}

		public function getMediaWikiOutput($wikimediaURL, $wikiSource){
				$wikiSource = trim($wikiSource);
			
				$log = ((strlen($wikiSource)>10)?substr($wikiSource,0,10):$wikiSource);
				if($this->lastquery == $wikiSource){
						$this->log('debug',"read from cache: ".$log );
						return $this->cachedResponse;
					}
				$prepareforGET = substr($wikiSource,0,3500);
				$prepareforGET = urlencode($prepareforGET);
				$url = $wikimediaURL."?action=".$this->action."&text=".$prepareforGET."&format=".$this->format;
				$result = $this->httpget($url);
				$this->log('debug',"requested via http: ".$log );
				if($result !== false){
						$this->lastquery = $wikiSource;
						$this->cachedResponse = $result;
					} 
				
				return $result;
				
			}
			
			public function close(){}
		
		
	
	
	}

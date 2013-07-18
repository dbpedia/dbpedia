<?php

class ValidateExtractionResult {
	
	public static function validate($result, $extractor){
			$triples = $result->getTriples();
			$md = $extractor->getMetadata();	
			if(isset($md[IGNOREVALIDATION]) && $md[IGNOREVALIDATION] == true){
				return;
				}
			if(!isset($md[PRODUCES]) || count($md[PRODUCES])==0){
				die('extractor '.$extractor->getExtractorID().' not set in Extractorconfiguration');
				}
			$produces = $md[PRODUCES];
			
			
			foreach ($triples as $triple){
					$checked = false;
					foreach ($produces as $rule){
						if($rule['type']==STARTSWITH && Rule::checkStartsWith($rule, $triple)){
							$checked = true;
							break;
							}
						if($rule['type']==EXACT && Rule::checkExactmatch($rule, $triple)){
							$checked = true;
							break;
							}
					}
					if($checked!= true){
						print_r($produces);
						die(
							"Extractor: ".get_class($extractor)." has wrong settings (see ExtractorConfiguration) in metadata['produces'] at \n".$triple->toString()
							
							);
						}
				}
		}
		
		private static function log($message){
				Logger::logComponent('core', "validateextractionresult", DEBUG ,$message);
			}
	
}

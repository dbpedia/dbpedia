<?php

class Rule{
	
	public static function checkStartsWith ($check, $triple){
				self::log("testing rule startsWith");
				$subject = $triple->getSubject()->getURI();
				$predicate = $triple->getPredicate()->getURI();
				//overrides checking of o, if not a uri
				$object = ($triple->getObject() instanceOf URI)? $triple->getObject()->getURI():$check['o'];
				
				$checked = true;
				if(!empty($check['s'])){
					self::log($subject. " startsWith ". $check['s']);
					$checked = ($checked && Util::startsWith($subject ,$check['s']));
					self::log("result: ".$checked);
					}
				if(!empty($check['p'])){
					self::log($predicate. " startsWith ". $check['p']);
					$checked = ($checked && Util::startsWith($predicate ,$check['p']));
					self::log("result: ".$checked);
					}
				if(!empty($check['o'])){
					self::log($object. " startsWith ". $check['o']);
					$checked = ($checked && Util::startsWith($object ,$check['o']));
					self::log("result: ".$checked);
					}
				return $checked;
			
			}
	public static function checkExactmatch ($check, $triple){
				self::log("testing rule exactmatch");
				$subject = $triple->getSubject()->getURI();
				$predicate = $triple->getPredicate()->getURI();
				//overrides checking of o, if not a uri
				$object = ($triple->getObject() instanceOf URI)? $triple->getObject()->getURI():$check['o'];
				if(empty($check['s']) && empty($check['p']) && empty($check['o']) ){
					Logger::error('do not use empty array in produces either:
					add IGNORE = true to metadata array in ExtractionConfiguration
					or validateExtractors =false in config/dbpedia.ini
					');
					die;
					}
				
				$checked = true;
				if(!empty($check['s'])){
					self::log($subject. " exactmatch ". $check['s']);
					$checked = ($checked && ($subject == $check['s']));
					self::log("result: ".$checked);
					}
				if(!empty($check['p'])){
					self::log($predicate. " exactmatch ". $check['p']);
					$checked = ($checked && ($predicate == $check['p']));
					self::log("result: ".$checked);
					}
				if(!empty($check['o'])){
					self::log($object. " exactmatch ". $check['o']);
					$checked = ($checked && ($object == $check['o']));
					self::log("result: ".$checked);
					}
				return $checked;
			
			}
	
		
	public static function depgetSPARQLFilter ($metadata) {
			$rules = $metadata[PRODUCES];
			$ret = array();
			foreach($rules as $rule){
					if($rule['type']==STARTSWITH){
							if(!empty($rule['p']) && !empty($rule['o'])) {
								$ret['pofilter'][] =array('p'=>$rule['p'],'o'=>$rule['o'] );
							}else if(!empty($rule['p'])){
								$ret['pfilter'][] = $rule['p'] ;
							}else if(!empty($rule['o'])){
								$ret['ofilter'][] = $rule['o'] ;
							}else {
								Logger::error("Uninterpretable filter in Rule.php from: ".$metadata[EXTRACTORID]);
								ob_start();
								// write content
								print_r($rules);
								$content = ob_get_contents();
								ob_end_clean();
								Logger::error("\n$content");
								}
							
						
						}
				
				}
			return $ret;
		}
		
		private static function log($message){
			
				Logger::logComponent('core', 'rule', TRACE ,$message);
		}
	
	
	}

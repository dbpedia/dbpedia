<?php

ini_set("memory_limit", "512M");

include('dbpedia.php');
//Options::configureOptions('config/live.config.ini');
Timer::start("main::Runtime");

//Get Variables for this script 

$sleepinterval = Options::getOption('sleepinterval');

//define("RDFAPI_INCLUDE_DIR", Options::getOption('rdfapi_include_dir')); 
//include(RDFAPI_INCLUDE_DIR . "RdfAPI.php");

@mkdir(Options::getOption('oaiRecords'));
/*
@mkdir(Options::getOption('updatelog'));
@mkdir(Options::getOption('logpath'));
*/
$currentArticleFile = Options::getFileOptionWithID('currentArticleFile');
$it = new LiveUpdateIterator( Options::getOption('oaiRecords'),$currentArticleFile, Options::getOption('debug_loop')  );

// configure which extractors have to be executed on which Wikipedia language editions
// for live it is currently only english
$extractors = array();
$extractors[ARTICLE] = Options::getArticleConfiguration();
$extractors[CATEGORY] = Options::getCategoryConfiguration();
$extractors[REDIRECT] = Options::getRedirectConfiguration();


/*
StringIDGenerator::test();
die;
$sparql = SPARQLEndpoint::getDefaultEndpoint();
$sparql->test();
die;
*/



$validstates = array(ACTIVE, KEEP, PURGE);

// message at beginning 
if(Options::getOption('showconfig')){
	printConfig($extractors);
}

if(Options::isOptionSet('language')){
	Logger::warn('option "language" has no effect for live extraction');
	}

$count = 0;
$lastarticles = array();
$lastarticlestmp = array();

Hash::initDB($clearhashtable);

while (true){
foreach($it as $key => $metainfo) {
	Timer::start("main::processing");
	//****PREPROCESSING*****
	//print_r($metainfo);
	$pageTitle = $metainfo['pageTitle'];
	$pageTitles = new ArrayObject(array($pageTitle));
	$pageURI = '';
	try{
		$pageURI = RDFtriple::page($pageTitle);
	}catch(Exception $e ){
		Logger::warn('main: invalid uri for '.$pageTitle);
		continue;
		}
	Logger::info( "Title:  $pageTitle ".mb_detect_encoding($pageTitle)."");	
	$language =  $metainfo['language'];
	Options::setLanguage($language);
	$lastarticlestmp[] = $pageURI->getURI();
	$metainfo['oaiidentifier'] = $metainfo['oaiId'];
	$metainfo['oaiId'] = Util::getOaiIDfromIdentifier($language, $metainfo['oaiidentifier']);
	Logger::info("oaiId ".$metainfo['oaiId']);
	
	//***MAINTAINANCE***
	$count++;
	
	//log statistics
	if($count%Options::getOption('printStatInterval') ==0){
		printAll($lastarticles, $language);
		}
	//50 last articles to statisticdir
	if($count <50){
		$lastarticles = $lastarticlestmp;
		}
	if($count%50 ==0){
		$lastarticles = array();
		$lastarticles = $lastarticlestmp;
		sort($lastarticles);
		$lastarticlestmp = array();
		}
	Timer::start("main::init");
		
	//*****THE WORK*****
	$manager = new ExtractionManager();
	$collection =  new LiveFromFileCollection($language, $currentArticleFile);
	//$collection =  new LiveWikipediaCollection($language);
	$job = new ExtractionJob( $collection, $pageTitles);
	$destination = new LiveUpdateDestination($metainfo);
	//$destination = new SimpleDumpDestination();
	$group = new ExtractionGroup($destination);
	
	//ESTIMATE TYPE
	$namespaceId = $metainfo['namespaceId'];
	$pageSource = $collection->getSource($pageTitle);
	if($namespaceId == 14 && strpos($pageTitle,  $metainfo['namespaceName'])===0 ){
			$type = CATEGORY;		
	}else if (Util::isRedirect($pageSource, $language)){ //#REDIRECT [[Blueprint (CSS framework)]]
			$type = REDIRECT;
	}else{
			$type = ARTICLE;
		}
	Statistics::increaseCount(STAT_TOTAL, $type);
	
	Logger::info( $type.": ".$pageURI->getURI()." (".$count.", ".mb_detect_encoding($pageURI->getURI()).")");

	//****EXTRACTORS ******	
	foreach($extractors[$type] as $extractor=>$status){
			$extractorClassName = $extractor.EXTRACTOR;
			Logger::debug(  $extractorClassName." Status: ".$status);
			$extractorClass = new ReflectionClass($extractorClassName);
			$extractorInstance = $extractorClass->newInstance();
			$extractorInstance->setStatus($status);
			$extractorInstance->addAdditionalInfo($metainfo);
			//$extractorInstance->addMetaData(ExtractorConfiguration::getMetadata($language, $extractorClassName));
			Statistics::addExtractorMetaArray($extractorInstance->getMetadata());
			//Statistics::addExtractorMeta($extractorInstance->getExtractorID(),'status',
			
			$group->addExtractor($extractorInstance);
		}
	$job->addExtractionGroup($group);
	Timer::stop("main::init");
	$manager->execute($job);
	
	
	if(Options::getOption('debug_die_after_one')){
		printAll($lastarticles, $language);
		die;
		}
/*
		printAll();
		die;
*/
	//var_dump($key, $article);
	//$live = new LiveExtraction();
	//$live->start($article,false);

/*
	Timer::printTime();
*/
/*
  	Statistics::printStats();
*/
Timer::stop("main::processing");	

}//end for each
Timer::start('sleeping');
sleep(Options::getOption('sleepinterval'));
Timer::stop('sleeping');
Logger::info('sleeping '.Options::getOption('sleepinterval').' seconds');
}//end while 

/**UNREACHABLE CODE BELOW**/

Timer::stop("main::Runtime");
Timer::printTime();
die;

function printConfig($extractors){
	Logger::info( "Displaying configuration ");
	$info = "";
	foreach (array_keys($extractors) as $key){
		$info .= "*****extractors for ".$key."*****\n";
		foreach($extractors[$key] as $extractor=>$status){
			$extractorClass = new ReflectionClass($extractor.EXTRACTOR);
			$extractorInstance = $extractorClass->newInstance();
			$extractorInstance->setStatus($status);
			$info .= "".$extractor.EXTRACTOR."\n";
			$info .= "\tStatus: ".$status."\n";
			$m = $extractorInstance->getMetadata();
			$info .= "\tID: ".$extractorInstance->getExtractorID()."\n";
			
				if(count(@$m[NOTICE])>0){
				$info .= "\tNotices: \n";
				foreach ($m[NOTICE] as $notice){
					$info .= "\t\t".strtoupper($notice)."\n";
					}}
			
				if(count($m[PRODUCES])>0){
				$info .= "\tRules: \n";
				foreach ($m[PRODUCES] as $rule){
						if(count($rule)>0){
							$info.= "\t\t";
						foreach ($rule as $key=>$value){
							$info.= $key.":".$value." | ";
							
						}$info.="\n";
						}
					//$info .= "\t\t".$produces."\n";
					}}
			}
		}
	Logger::info("\n".$info);
	Logger::info("Starting in 5 seconds");
	sleep(5);
	}//end function

function printAll($lastarticles, $language){
		$statisticdir = Options::getOption('statisticdir');
		Timer::start('main::glob');
		if(Options::getOption('noglob')){
			Statistics::setArticleQueue('deactivated for speed');
		}else{
			Statistics::setArticleQueue(count(glob(Options::getOption('oaiRecords')."/*.*")));
		}
		Timer::stop('main::glob');
/*
 * 		//too slow
		Timer::start('main::lswc');
		Statistics::setArticleQueue(exec ('ls -1 '.Options::getOption('oaiRecords').' | wc -l'));
		Timer::stop('main::lswc');
*/

		$data = array();
		$data['lastarticles'] = $lastarticles;
		Timer::stop("main::Runtime");
		$timeString = Timer::getTimeAsString();
		$data['timeString'] = $timeString;
		$data['time'] = Timer::$time;
		Timer::start("main::Runtime");
		
		
		$overall = array();
		$overall['startingtime'] = Timer::$startingTime;
		$overall['lasttime'] = microtime(true);
		$data['timeOverall'] = $overall;
		Timer::stop("main::processing");
		$data['processingTime'] = Timer::$time["main::processing"]['total'];
		Timer::start("main::processing");
		//toFile($time, 'timeString.txt', $statisticdir);
		//do statistics
		
		//Timer::timeToFile($statisticdir);
//		Statistics::statisticsToFile($statisticdir);
		
		$data['triples'] = Statistics::$countArr;
		$data['extractorMeta'] = Statistics::$extractorMeta;
		
		$s = Timer::getElapsedSeconds();
		$t = Statistics::getTotalTriples();
		
		$memory = "memory_get_usage  (true ): ".memory_get_usage  (true )."\n";
		$memory .= "memory_get_usage  (false ): ".memory_get_usage  (false )."\n";
		$memory .= "memory_get_peak_usage  (true ): ".memory_get_peak_usage  (true )."\n";
		$memory .= "memory_get_peak_usage  (false ): ".memory_get_peak_usage  (false )."\n";
		
		$data['memory'] = $memory;
		//toFile($memory, 'memory.txt', $statisticdir);
		
		//echo $s."\n";
		$general = "Seconds per article: ". ( $s/Statistics::getTotalArticles())."\n";
		$general .= "Articles per second: ". ( Statistics::getTotalArticles()/$s)."\n";
		$general .= "Articles per hour: ". (( Statistics::getTotalArticles()/$s)*3600)."\n";
		$general .= "Category per second: ". ( Statistics::getTotalCategories()/$s)."\n";
		$general .= "Category per hour: ". (( Statistics::getTotalCategories()/$s)*3600)."\n";
		$general .= "Redirect per second: ". ( Statistics::getTotalRedirects()/$s)."\n";
		$general .= "Redirect per hour: ". (( Statistics::getTotalRedirects()/$s)*3600)."\n";
		
		$general .= "Triples per second: ". ( $t/$s)."\n";
		$general .= "Triples per minute: ". (( $t/$s)*60)."\n";
		$general .= "Triples per hour: ". (( $t/$s)*3600)."\n";
		$general .= "Triples per day: ". (( $t/$s)*3600 * 24)."\n";
		
		$data['general'] = $general;
		
		$append = '';
		if(Options::isOptionSet('processID')){
			$append = Options::$config['processID'];
		}
		$indexfile = 'index'.$append.'.html';
		toFile( Statistics::generateStatisticHTML(Options::getOption('linkeddataresourceprefix'),
				$language,  $data), $indexfile, $statisticdir);
		Logger::info('wrote html file to '.$statisticdir.'/'.$indexfile);
		
	}
	
 function toFile($string, $filename, $statisticdir){
			if(is_writable($statisticdir)){
				$fp = fopen($statisticdir.'/'.$filename , 'w');
				//$ser = serialize($array);
				fwrite($fp, $string);
				fclose($fp);
			}else{
				Logger::warn('extract_live.php.php:  dir not writable: '. $statisticdir.'/'.$filename);
			}
		
		}


	


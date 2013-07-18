<?php

if($argc != 3) {
	echo "Usage: {$argv[0]} <article> <outfile>\n";
	die;
}

$pageName = $argv[1];
$outFileName = $argv[2];



echo "Command line version of the live extraction workflow.\n";
echo "Processing page: $pageName\n";
echo "Output file: $outFileName\n";


ini_set("memory_limit", "512M");

include('dbpedia.php');


//$currentArticleFile = Options::getFileOptionWithID('currentArticleFile');

$extractors = array();
$extractors[ARTICLE] = Options::getArticleConfiguration();
$extractors[CATEGORY] = Options::getCategoryConfiguration();
$extractors[REDIRECT] = Options::getRedirectConfiguration();

$validstates = array(ACTIVE, KEEP, PURGE);

// message at beginning 
if(Options::getOption('showconfig')){
	printConfig($extractors);
}

$language = Options::getOption('language');
if(!isset($language)) {
	$language = "en";
	Logger::warn("Language not set. Defaulting to '$language'.");
}
echo "Langauge = '$language'\n";

/*if(Options::isOptionSet('language')){
	Logger::warn('option "language" has no effect for live extraction');
}*/

$pageTitles = new ArrayObject(array($pageName));

$manager = new ExtractionManager();
$collection =  new LiveWikipediaCollection($language);
$job = new ExtractionJob($collection, $pageTitles);
$destination = new NTripleDumpDestination($outFileName);
$group = new ExtractionGroup($destination);

echo "Warning: Determining page type not implemented. Assuming 'ARTICLE'.\n";
$type = ARTICLE;

//****EXTRACTORS ******	
foreach($extractors[$type] as $extractor=>$status){
		$extractorClassName = $extractor.EXTRACTOR;
		Logger::debug(  $extractorClassName." Status: ".$status);
		$extractorClass = new ReflectionClass($extractorClassName);
		$extractorInstance = $extractorClass->newInstance();
		$extractorInstance->setStatus($status);
		//$extractorInstance->addAdditionalInfo($metainfo);
		//$extractorInstance->addMetaData(ExtractorConfiguration::getMetadata($language, $extractorClassName));
		//Statistics::addExtractorMetaArray($extractorInstance->getMetadata());
		//Statistics::addExtractorMeta($extractorInstance->getExtractorID(),'status',
		
		$group->addExtractor($extractorInstance);
}
$job->addExtractionGroup($group);
$manager->execute($job);


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
	}//end function


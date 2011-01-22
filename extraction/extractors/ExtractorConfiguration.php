<?php

/**
 *  used to set the metadata for extractors
 */

class ExtractorConfiguration
{

	private static $metadata = array(
	'en' => array(
		'AbstractExtractor' => array(
			PRODUCES => array(
						array('type'=>EXACT, 's' => '', 'p' => RDFS_COMMENT, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => DB_ABSTRACT, 'o'=>'')
						)),
		'ActiveAbstractExtractor' => array(
			PRODUCES => array(
						array('type'=>EXACT, 's' => '', 'p' => DBCOMM_ABSTRACT, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => DBCOMM_COMMENT, 'o'=>'')
						),
			NOTICE 	=> array(
						"There are still a lot TODOs in this extractor, multi-language support for example is completely missing"
						)),
		'AlwaysFilterExtractor' => array(
			PRODUCES => array(
						array('type'=>EXACT, 's' => '', 'p' => OWL_SAMEAS, 'o'=>''),
						//array('type'=>STARTSWITH, 's' => '', 'p' => DB_ONTOLOGY_NS, 'o'=>''),
						array('type'=>STARTSWITH, 's' => '', 'p' => RDF_TYPE, 'o'=>DB_YAGO_NS, 'pexact'=>true),
						array('type'=>STARTSWITH, 's' => '', 'p' => RDF_TYPE, 'o'=>UMBEL_NS, 'pexact'=>true),
						array('type'=>STARTSWITH, 's' => '', 'p' => RDF_TYPE, 'o'=>OPENCYC_NS, 'pexact'=>true),
						array('type'=>STARTSWITH, 's' => '', 'p' => DB_HASPHOTOCOLLECTION, 'o'=>'http://www4.wiwiss.fu-berlin.de/flickrwrappr/photos/', 'pexact'=>true),
						array('type'=>EXACT, 's' => '', 'p' => RDF_TYPE, 'o'=>DB_CLASS_BOOK)
						)),
		'ArticleCategoriesExtractor'=> array(
			PRODUCES => array(
						)),
		'CategoriesClassesExtractor'=> array(
			PRODUCES => array()),
		'CategoriesClassesToArticlesExtractor'	=> array(
			PRODUCES => array()),
		'CharacterCountExtractor'	=> array(
			PRODUCES => array(
						array('type'=>EXACT, 's' => '', 'p' => DB_CHARACTERCOUNT, 'o'=>'')
						)),
		'ChemboxExtractor'	=> array(
			PRODUCES => array(
						array('type'=>EXACT, 's' => '', 'p' => DB_MY_CHEM_PROPERTY, 'o'=>'')
						)),
		'DisambiguationExtractor'	=> array(
			PRODUCES => array(
						array('type'=>EXACT, 's' => '', 'p' => DB_DISAMBIGUATES, 'o'=>'')
						)),
		'ExternalLinksExtractor'	=> array(
			PRODUCES => array(
						array('type'=>EXACT, 's' => '', 'p' => DB_REFERENCE, 'o'=>'')
						)),
		'GeoExtractor'	=> array(
			PRODUCES => array(
						array('type'=>EXACT, 's' => '', 'p' => GEORSS_POINT, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => WGS_LAT, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => WGS_LONG, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => GEO_FEATURECLASS, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => GEO_FEATURECODE, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => RDF_TYPE, 'o'=>YAGO_LANDMARK),
						array('type'=>EXACT, 's' => '', 'p' => GEO_POPULATION, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => GEORSS_RADIUS, 'o'=>'')
						),
			POSTPROCESSING =>array(
						IFEXISTSDONOTDELETE=>'in dbpedia gecoordinates might exist
								in another language version, but is not produced by the english live extraction'
						)),
		'HomepageExtractor'	=> array(
			PRODUCES => array(
						array( 'type'=>EXACT,'s' => '', 'p' => FOAF_HOMEPAGE, 'o'=>'')
						)),
		'ImageExtractor'	=> array(
			PRODUCES => array(
						array('type'=>EXACT, 's' => '', 'p' => FOAF_DEPICTION, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => DBO_THUMBNAIL, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => FOAF_THUMBNAIL, 'o'=>''),
						array('type'=>STARTSWITH, 's' => '', 'p' => DC_RIGHTS, 'o'=>'http://en.wikipedia.org/wiki/Image:', 'pexact'=>true)
						),
			NOTICE => array(
						"In the third rule language is hardcoded, also image url"
						)),
		'InfoboxExtractor'	=> array(
			PRODUCES => array(
						array('type'=>STARTSWITH, 's' => '', 'p' => DB_PROPERTY_NS, 'o'=>'')
						//,array('type'=>STARTSWITH, 's' => '', 'p' => 'http://purl.org/dc/terms/rights', 'o'=>'')
						),
			NOTICE 	=> array(
						"I deactivated warning output for catchObjectDataTypeFunctions, this was for a test run only"
						)),
		'InstanceTypeExtractor'	=> array(
			PRODUCES => array(
						array('type'=>STARTSWITH, 's' => '', 'p' => RDF_TYPE, 'o'=> DB_ONTOLOGY_NS, 'pexact'=>true)
						)),
		'InterlanguageExtractor'	=> array(
			PRODUCES => array(
						)),
        'KoInfoboxExtractor' => array(
            PRODUCES =>  array('type'=>STARTSWITH, 's' => '', 'p' => DB_PROPERTY_NS, 'o'=>'')),
		'LabelExtractor'	=> array(
			PRODUCES => array(
						array('type'=>EXACT, 's' => '', 'p' => RDFS_LABEL, 'o'=>'')
						)),
		'LiveMappingBasedExtractor'	=> array(
			PRODUCES => array(
						array('type'=>STARTSWITH, 's' => '', 'p' => DB_ONTOLOGY_NS, 'o'=>''),
						array('type'=>STARTSWITH, 's' => '', 'p' => DB_PROPERTY_NS, 'o'=>''),
						array('type'=>STARTSWITH, 's' => '', 'p' => RDF_TYPE, 'o'=> DB_COMMUNITY_NS, 'pexact'=>true),
						array('type'=>EXACT, 's' => '', 'p' => RDF_TYPE, 'o'=> OWL_THING)
						)),
		'MappingBasedExtractor'	=> array(
			PRODUCES => array(
						array('type'=>STARTSWITH, 's' => '', 'p' => '', 'o'=>'')
						),
			NOTICE 	=> array(
						"no rules, unsure output"
						)),
		'MetaInformationExtractor'	=> array(
			PRODUCES => array(
						array('type'=>EXACT, 's' => '', 'p' => DC_MODIFIED, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => DBM_REVISION , 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => DBM_EDITLINK , 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => DBM_OAIIDENTIFIER, 'o'=>'')
						),
			NOTICE =>array('uses wikpedia uri in code')
						),
		'NewStrictMappingBasedExtractor'	=> array(
			IGNOREVALIDATION => true
						),
		'OldLenientMappingBasedExtractor'	=> array(
			IGNOREVALIDATION => true
						),
		'PageLinksExtractor'	=> array(
			PRODUCES => array(
						array('type'=>EXACT, 's' => '', 'p' => DB_WIKILINK, 'o'=>'')
						)),
		'PersondataExtractor'	=> array(
			PRODUCES => array(
						array('type'=>EXACT, 's' => '', 'p' => FOAF_NAME, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => FOAF_GIVENNAME, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => FOAF_SURNAME, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => DC_DESCRIPTION, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => RDF_TYPE, 'o'=>FOAF_PERSON),
						array('type'=>EXACT, 's' => '', 'p' => DB_BIRTH, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => DB_BIRTHPLACE, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => DB_DEATH, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => DB_DEATHPLACE, 'o'=>'')
						),
			NOTICE => array(
					 "turn uri validation off"
					)),
		'PNDExtractor'	=> array(
			PRODUCES => array(
						array('type'=>EXACT, 's' => '', 'p' => DBO_INDIVIDUALISED_PND, 'o'=>''),
						array('type'=>EXACT, 's' => '', 'p' => DBO_NON_INDIVIDUALISED_PND, 'o'=>'')
						//array('type'=>EXACT, 's' => '', 'p' => DBO_NAMEN_PND, 'o'=>''),
						//array('type'=>EXACT, 's' => '', 'p' => DBO_PERSONEN_PND, 'o'=>'')
						)),
		'PropertyExtractor'	=> array(
			PRODUCES => array(
						array('type'=>STARTSWITH, 's' => '', 'p' => '', 'o'=>'')
						)),
		'RedirectExtractor'	=> array(
			PRODUCES => array(
						array('type'=>EXACT, 's' => '', 'p' => DB_REDIRECT, 'o'=>'')
						),
			NOTICE 	=> array(
						"currently produces: Category, i.e. not language independent"
						)),
		'SampleExtractor'	=> array(
			PRODUCES => array(
						array('type'=>STARTSWITH, 's' => '', 'p' => RDFS_LABEL, 'o'=>'')
						)),
		'SkosCategoriesExtractor'	=> array(
			PRODUCES => array(
						array( 'type'=>EXACT,'s' => '', 'p' => SKOS_PREFLABEL, 'o'=>''),
						array( 'type'=>EXACT,'s' => '', 'p' => RDF_TYPE, 'o'=>SKOS_CONCEPT)
						),
			NOTICE 	=> array(
						"currently produces: Categ ory, i.e. not language independent"
						)),
		'TemplateExtractor'	=> array(
			IGNOREVALIDATION => true
						),
		'TemplateRedirectExtractor'	=> array(
			IGNOREVALIDATION => true
						),

		'WikipageExtractor'	=> array(
			NOTICE => array(
						"set in generics"
						)),
		'WordnetLinkExtractor'	=> array(
			PRODUCES => array(
						array('type'=>EXACT, 's' => '', 'p' => DB_WORDNET_TYPE, 'o'=>'')
						)),
		'YagoLinkExtractor'	=> array(
						)
	)

);

private static function _addGenerics($language, $extractorClassName , $arr){
		$cat = Util::getDBpediaCategoryPrefix($language);
		//echo $cat;

		if($extractorClassName == 'SkosCategoriesExtractor'){
				$arr[PRODUCES] [] =
						array( 'type'=>STARTSWITH,'s' => '', 'p' => SKOS_BROADER, 'o'=>$cat, 'pexact'=>true);
				//print_r(self::$metadata[$language][$extractorClassName]);die;
			}
		if($extractorClassName == 'WikipageExtractor'){
				$arr[PRODUCES] [] =
						array('type'=>STARTSWITH, 's' => '', 'p' => FOAF_PAGE, 'o'=>'http://'.$language.'.wikipedia.org/wiki/', 'pexact'=>true);
				$arr[PRODUCES] [] =
						array('type'=>STARTSWITH, 's' => '', 'p' => DB_WIKIPAGE_EN, 'o'=>'http://'.$language.'.wikipedia.org/wiki/', 'pexact'=>true);
			}
		if($extractorClassName == 'ArticleCategoriesExtractor'){
				$arr[PRODUCES] [] =
						array('type'=>STARTSWITH, 's' => '', 'p' => SKOS_SUBJECT, 'o'=>$cat, 'pexact'=>true);
			}

		return $arr;
	}

public static function getMetadata($originallanguage, $extractorClassName){

		if(!isset(self::$metadata[$originallanguage])){
			//default should be english
			Logger::warn('ExtractorConfigurator::no metadata for "'.$originallanguage. '" substituting with en');
			$language = 'en';
		}else{
			$language = $originallanguage;
			}
		if(!isset(self::$metadata[$language][$extractorClassName]))	{
			Logger::error('could not find extractor configuration in extraction/ExtractorConfiguration.php');
			die;
			}

		$arr = 	self::$metadata[$language][$extractorClassName];
		$arr =  self::_addGenerics($originallanguage, $extractorClassName, $arr);

		return $arr;
	}

}

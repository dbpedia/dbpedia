<?php
$GLOBALS['LIVE_EXTRACTORS'] = array(
    'en' =>array(
		ARTICLE => array(  		'ActiveAbstract'=>ACTIVE,
								'Label'=>ACTIVE,
								'ArticleCategories'=>ACTIVE,
								'ExternalLinks'=>ACTIVE,
								'Disambiguation'=>ACTIVE,
								'MetaInformation'=>ACTIVE,
								'Persondata'=>ACTIVE,	
								'Geo'=>ACTIVE,
								'Wikipage'=>ACTIVE,	
								'Homepage'=>ACTIVE,
                                'LiveMappingBased'=>ACTIVE,
																
								'Infobox'=>PURGE,
								'MappingBased'=>PURGE,
																
								'AlwaysFilter'=>KEEP,
								'PageLinks'=>KEEP,
								'Image'=>KEEP,
								'InstanceType'=>PURGE,
								'WordnetLink'=>KEEP
								),
		CATEGORY => array(
								'Label'=>ACTIVE,
								'SkosCategories'=>ACTIVE,
								'MetaInformation'=>ACTIVE
								),
		REDIRECT => array(
								'Redirect'=>ACTIVE,
								'MetaInformation'=>ACTIVE
								)
		),
	 'de' =>array(
		ARTICLE => array(	
								'ActiveAbstract'=>ACTIVE,
								'Label'=>ACTIVE,
								'ArticleCategories'=>ACTIVE,
								'ExternalLinks'=>ACTIVE,
								'Infobox'=>ACTIVE,
								'Disambiguation'=>ACTIVE,
								'MetaInformation'=>ACTIVE,
								'Persondata'=>ACTIVE,	
								'Geo'=>ACTIVE,
								'Wikipage'=>ACTIVE,	
								'Homepage'=>ACTIVE,
								
								'AlwaysFilter'=>KEEP,
								'PageLinks'=>ACTIVE,
								'Image'=>KEEP,
								'MappingBased'=>PURGE,
                                'LiveMappingBased'=>ACTIVE,
								'InstanceType'=>PURGE,
								'WordnetLink'=>KEEP
								),
		CATEGORY => array(
								'Label'=>ACTIVE,
								'SkosCategories'=>ACTIVE,
								'MetaInformation'=>ACTIVE
								),
		REDIRECT => array(
								'Redirect'=>ACTIVE,
								'MetaInformation'=>ACTIVE
								)
		)
  
);

<?php

/**
 * This file keeps the configuration settings for DBpedia data extraction:
 * - DBpedia base URI
 * - Templates names, which are excluded from extraction
 * - image extension (.jpg, ...)
 * - Units to parse (Units, currencies, dates)
 * - predicates which are known to be link lists (key people, products,...)
 */

// configure extraction
// $rdftypeProperty='http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
$rdftypeProperty = 'http://dbpedia.org/property/wikiPageUsesTemplate';
$skosSubject = "http://www.w3.org/2004/02/skos/core#subject";


$GLOBALS['W2RCFG']=array(
//Searched Template Types, leave empty for all types of Templates
'templates' => array(),
//Tags that will stay in Wikipedia-Text, eg: "'<sup>','<span>'"
// new lines are handled separately: in literals they are replaced by \n, in resources they are removed
'allowedtags' => "'<sup>','<br/>','<br>','<br />'" ,
//Ignored Template types
'ignoreTemplates' => array('redirect','seealso','main','citation', 'cquote', 'Chess diagram', 'IPA'),
//Ignored Template types matching wildcard pattern
'ignoreTemplatesPattern' => array('cite*','assessment*','zh-*','citation','cquote'),
// ignored properties (image is ignored because it is already extracted by the image extractor)
'ignoreProperties' => array('image'),
//Wikipedia Base URI
'wikipediaBase' => 'http://dbpedia.org/resource/',
//Base URI for Properties
'propertyBase' => 'http://dbpedia.org/property/',
//Base URI for newly generated instances
'instanceBase' => 'http://dbpedia.org/instances/',
//Base URI for non-specified Datatypes
'w2ruri' => 'http://dbpedia.org/ontology/',
// Property used to link pages to categories
'categoryProperty' => $skosSubject, #'http://dbpedia.org/category'
// Property used to link pages to templates
'templateProperty' => $rdftypeProperty, #'http://dbpedia.org/template'
//Object for Explicit Typing, Classes
'classBase' =>'http://www.w3.org/2002/07/owl#Class',
//Object for datatype Properties
'datatypePropertyBase' =>'http://www.w3.org/2002/07/owl#DatatypeProperty',
//Object for object Properties
'objectPropertyBase' =>'http://www.w3.org/2002/07/owl#ObjectProperty',
// Property used to link categories to categories
'subCategoryProperty' =>$rdftypeProperty, #'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'
// Property used to label pages
'labelProperty' =>'http://www.w3.org/2000/01/rdf-schema#label',
//Minimal Count of Pipes | in a found Wikipedia-Template
'minAttributeCount' => 2,
// Smaller outputfiles will be deleted
'minFileSize' => 1000,
//printed Categories, leave empty for all
'categories' => array(),
//printed Categories matching wildcard Pattern, leave empty for all
'categoriesPattern' => array(),
// maximum property length; if a property is (usually be problematic Wiki code) longer than this number
// of characters, it is automatically ignored
'maximumPropertyLength' => 250
);

//////////////////////////////////////////
//
//	Begin legacy code.
//	Should be excluded in the future.
//	Though it has to be tested first, if
//	these variables really are superfluous
//
//////////////////////////////////////////

// Output format: nt and csv supported
$outputFormat='nt';
// Powl database to load triples into if output format is csv
$powl_db='false';#powl; #'powl_wikipedia';
// id of the model to load the triples into
$modelID='3';
//directory where to generate the output
$outputDir='./extraction_results/';
//filename of complete extraction
$filename='wikipedia.'.$outputFormat;
//extraction will be saved to one file per extraction, or one file per Template
$onefile=true;
// keyword for categorizing
$categoryLabel='Category'; #'Kategorie';
// prefixed properties with template name
$prefixPropertiesWithTemplateName=false;
// collect statistics about templates and write as array to templateStatistics.inc.php
$templateStatistics=false;
//write Explicit Type Triples - Extraction will take up to 5 times longer
$addExplicitTypeTriples=false;
//correct Property Types
$correctPropertyType=false;
//File prefix for splitted type files eg.:types_wikipedia.csv -false for no rdf:type, true for rdf:type in main-output-file
$typefilename=false; #'types_'
//File prefix for splitted label files eg.:label_wikipedia.csv -false for no rdf:label, true for rdf:type in main-output-file
$labelfilename=false; #'labels_'

//////////////////////////////////////////
//
// END legacy code
//
//////////////////////////////////////////

// keyword for templates
$GLOBALS['templateLabel']='Template';
//Filename Prefixes to be recognized as picture
$GLOBALS['pictureFilenames']=array('png','jpeg','gif','bmp','svg','jpg');
//recognized Month for Date extraction
//month must be lowercase !!
$GLOBALS['month']=array('en' => array('january'=>'01','february'=>'02','march'=>'03','april'=>'04','may'=>'05','june'=>'06','july'=>'07','august'=>'08','september'=>'09','october'=>'10','november'=>'11','december'=>'12'),
'de' => array('januar'=>'01','februar'=>'02','m�rz'=>'03','maerz'=>'03','april'=>'04','mai'=>'05','juni'=>'06','juli'=>'07','august'=>'08','september'=>'09','oktober'=>'10','november'=>'11','dezember'=>'12'),
'fr' => array('janvier'=>'01','f�vrier'=>'02','mars'=>'03','avril'=>'04','mai'=>'05','juin'=>'06','juillet'=>'07','ao�t'=>'08','septembre'=>'09','octobre'=>'10','novembre'=>'11','d�cembre'=>'12'),
'it' => array('gennaio'=>'01','febbraio'=>'02','marzo'=>'03','aprile'=>'04','maggio'=>'05','giugno'=>'06','luglio'=>'07','agosto'=>'08','settembre'=>'09','ottobre'=>'10','novembre'=>'11','dicembre'=>'12'));

//recognized scales
$GLOBALS['scale']=array('thousand'=>'1000','million'=>'1000000','mio'=>'1000000','billion'=>'1000000000','mrd'=>'1000000000','trillion'=>'1000000000000',
'quadrillion'=>'1000000000000000');

//Predicates that contains Linklists
$GLOBALS['linklistpredicates']=array('starring','producer','director','writer', 'keyPeople', 'products', 'industry');

// If this is set true, the Templatename will be added in front of the propertyname
$GLOBALS['prefixPropertiesWithTemplateName'] = false;

//recognized Units
// AREA
$GLOBALS['Area']=array(
    'mm2'=>'square millimetre','mm²'=>'square millimetre','cm2'=>'square centimetre','cm²'=>'square centimetre','dm2'=>'square decimetre',
    'square metre'=>'square metre','m2'=>'square metre','m²'=>'square metre','dam2'=>'square decametre',
    'hm2'=>'square hectometre','km²'=>'square kilometre','km2'=>'square kilometre','square kilometre'=>'square kilometre','km\u00B2'=>'square kilometre',
    'ha'=>'hectare','sqin'=>'square inch','sqft'=>'square foot','ft2'=>'square foot','ft²'=>'square foot','sqyd'=>'square yard','acre'=>'acre','acres'=>'acre',
    'sqmi'=>'square mile','mi2'=>'square mile','mi²'=>'square mile',
    'sqnmi'=>'square nautical mile','nmi2'=>'square nautical mile');
$GLOBALS['Area']['STANDARD_UNIT'] = 'square metre';
// CURRENCY
$GLOBALS['Currency']=array(
    '$'=>'US dollar','US$'=>'US dollar','USD'=>'US dollar','Dollar'=>'US dollar','€'=>'Euro','EUR'=>'Euro',
    'GBP'=>'Pound sterling','British Pound'=>'Pound sterling','£'=>'Pound sterling','¥'=>'Japanese yen','yen'=>'Japanese yen',
    'RUR'=>'Russian rouble'/*RUR isnt the correct iso-code, but sometimes used */,
    'AED'=>'United Arab Emirates dirham','AFN'=>'Afghani','ALL'=>'Lek','AMD'=>'Armenian dram','ANG'=>'Netherlands Antillean guilder',
    'AOA'=>'Kwanza','ARS'=>'Argentine peso','AUD'=>'Australian dollar','AWG'=>'Aruban guilder','AZN'=>'Azerbaijanian manat','BAM'=>'Convertible marks',
    'BBD'=>'Barbados dollar','BDT'=>'Bangladeshi taka','BGN'=>'Bulgarian lev','BHD'=>'Bahraini dinar','BIF'=>'Burundian franc','BMD'=>'Bermudian dollar',
    'BND'=>'Brunei dollar','BOB'=>'Boliviano','BRL'=>'Brazilian real','BSD'=>'Bahamian dollar','BTN'=>'Ngultrum','BWP'=>'Pula',
    'BYR'=>'Belarussian ruble','BZD'=>'Belize dollar','CAD'=>'Canadian dollar','CDF'=>'Franc Congolais','CHF'=>'Swiss franc','CLP'=>'Chilean peso',
    'CNY'=>'Renminbi','COP'=>'Colombian peso','COU'=>'Unidad de Valor Real','CRC'=>'Costa Rican colon','CUP'=>'Cuban peso','CVE'=>'Cape Verde escudo',
    'CZK'=>'Czech koruna','DJF'=>'Djibouti franc','DKK'=>'Danish krone','DOP'=>'Dominican peso','DZD'=>'Algerian dinar','EEK'=>'Kroon',
    'EGP'=>'Egyptian pound','ERN'=>'Nakfa','ETB'=>'Ethiopian birr','FJD'=>'Fiji dollar','FKP'=>'Falkland Islands pound',
    'GEL'=>'Lari','GHS'=>'Cedi','GIP'=>'Gibraltar pound','GMD'=>'Dalasi','GNF'=>'Guinea franc',
    'GTQ'=>'Quetzal','GYD'=>'Guyana dollar','HKD'=>'Hong Kong dollar','HNL'=>'Lempira','HRK'=>'Croatian kuna','HTG'=>'Haiti gourde',
    'HUF'=>'Forint','IDR'=>'Rupiah','ILS'=>'Israeli new sheqel','INR'=>'Indian rupee','IQD'=>'Iraqi dinar','IRR'=>'Iranian rial',
    'ISK'=>'Iceland krona','JMD'=>'Jamaican dollar','JOD'=>'Jordanian dinar','JPY'=>'Japanese yen','KES'=>'Kenyan shilling','KGS'=>'Som',
    'KHR'=>'Riel','KMF'=>'Comoro franc','KPW'=>'North Korean won','KRW'=>'South Korean won','KWD'=>'Kuwaiti dinar','KYD'=>'Cayman Islands dollar',
    'KZT'=>'Tenge','LAK'=>'Kip','LBP'=>'Lebanese pound','LKR'=>'Sri Lanka rupee','LRD'=>'Liberian dollar','LSL'=>'Loti',
    'LTL'=>'Lithuanian litas','LVL'=>'Latvian lats','LYD'=>'Libyan dinar','MAD'=>'Moroccan dirham','MDL'=>'Moldovan leu','MGA'=>'Malagasy ariary',
    'MKD'=>'Denar','MMK'=>'Kyat','MNT'=>'Tugrik','MOP'=>'Pataca','MRO'=>'Ouguiya','MUR'=>'Mauritius rupee',
    'MVR'=>'Rufiyaa','MWK'=>'Kwacha','MXN'=>'Mexican peso','MYR'=>'Malaysian ringgit','MZN'=>'Metical','NAD'=>'Namibian dollar',
    'NGN'=>'Naira','NIO'=>'Cordoba oro','NOK'=>'Norwegian krone','NPR'=>'Nepalese rupee','NZD'=>'New Zealand dollar','OMR'=>'Rial Omani',
    'PAB'=>'Balboa','PEN'=>'Nuevo sol','PGK'=>'Kina','PHP'=>'Philippine peso','PKR'=>'Pakistan rupee','PLN'=>'Zloty',
    'PYG'=>'Guarani','QAR'=>'Qatari rial','RON'=>'Romanian new leu','RSD'=>'Serbian dinar','RUB'=>'Russian rouble','RWF'=>'Rwanda franc',
    'SAR'=>'Saudi riyal','SBD'=>'Solomon Islands dollar','SCR'=>'Seychelles rupee','SDG'=>'Sudanese pound','SEK'=>'Swedish krona','kr'=>'Swedish krona','SGD'=>'Singapore dollar',
    'SHP'=>'Saint Helena pound','SKK'=>'Slovak koruna','SLL'=>'Leone','SOS'=>'Somali shilling','SRD'=>'Surinam dollar','STD'=>'Dobra',
    'SYP'=>'Syrian pound','SZL'=>'Lilangeni','THB'=>'Baht','TJS'=>'Somoni','TMM'=>'Manat','TND'=>'Tunisian dinar',
    'TOP'=>'Paanga','TRY'=>'New Turkish lira','TTD'=>'Trinidad and Tobago dollar','TWD'=>'New Taiwan dollar','TZS'=>'Tanzanian shilling','UAH'=>'Hryvnia',
    'UGX'=>'Uganda shilling','UYU'=>'Peso Uruguayo','UZS'=>'Uzbekistan som','VEF'=>'Venezuelan bol�var fuerte','VUV'=>'Vatu',
    'WST'=>'Samoan tala','XAF'=>'CFA franc BEAC','XCD'=>'East Caribbean dollar','XOF'=>'CFA Franc BCEAO','XPF'=>'CFP franc','YER'=>'Yemeni rial',
    'ZAR'=>'South African rand','ZMK'=>'Kwacha','ZWD'=>'Zimbabwe dollar');
// DENSITY
$GLOBALS['Density']=array(
    'kg·m−3'=>'kilogram per cubic metre','kg/m³'=>'kilogram per cubic metre','kg/m3'=>'kilogram per cubic metre','kg·m'=>'kilogram per cubic metre','kg/l'=>'kilogram per litre','kg/L'=>'kilogram per litre',
    'g/cc'=>'gram per cubic centimetre','g/cm3'=>'gram per cubic centimetre','g/cm³'=>'gram per cubic centimetre','g/ml'=>'gram per millilitre','g/mL'=>'gram per millilitre');
$GLOBALS['Density']['STANDARD_UNIT'] = 'kilogram per cubic metre';
// ENERGY
$GLOBALS['Energy']=array(
    'J'=>'joule','kJ'=>'kilojoule','erg'=>'erg',
    'mWh'=>'milliwatt-hour','Wh'=>'watt-hour','kWh'=>'kilowatt-hour','MWh'=>'megawatt-hour','GWh'=>'gigawatt-hour','TWh'=>'terawatt-hour',
    'eV'=>'electron volt',
    'mcal'=>'millicalorie','cal'=>'calorie','kcal'=>'kilocalorie','Mcal'=>'megacalorie',
    'inlb'=>'inch-pound','ftlb'=>'foot-pound');
$GLOBALS['Energy']['STANDARD_UNIT'] = 'joule';
// FLOW RATE

$GLOBALS['FlowRate']=array(
    'm\u00B3/s'=>'cubic metre per second','m³/s'=>'cubic metre per second','ft\u00B3/s'=>'cubic feet per second','ft³/s'=>'cubic feet per second','cuft/s'=>'cubic feet per second',
    'm\u00B3/y'=>'cubic metre per year','m³/y'=>'cubic metre per year','ft\u00B3/y'=>'cubic feet per year','ft³/y'=>'cubic feet per year');
$GLOBALS['FlowRate']['STANDARD_UNIT'] = 'cubic metre per second';
// FORCE
$GLOBALS['Force']=array(
    'nN'=>'nanonewton','mN'=>'millinewton','N'=>'newton','kN'=>'kilonewton','MN'=>'meganewton','GN'=>'giganewton',
    'tf'=>'tonne-force','t-f'=>'tonne-force','Mp'=>'megapond','kgf'=>'kilogram-force','kg-f'=>'kilogram-force','kp'=>'kilopond','gf'=>'gram-force','g-f'=>'gram-force',
    'p'=>'pond','mgf'=>'milligram-force','mg-f'=>'milligram-force','mp'=>'millipond',
    'pdl'=>'poundal');
$GLOBALS['Force']['STANDARD_UNIT'] = 'newton';
// FREQUENCY
$GLOBALS['Frequency']=array(
    'mHz'=>'millihertz','Hz'=>'hertz','kHz'=>'kilohertz','MHz'=>'megahertz','GHz'=>'gigahertz');
$GLOBALS['Frequency']['STANDARD_UNIT'] = 'hertz';
// FUEL EFFICIENCY
$GLOBALS['FuelEfficiency']=array(
    'km/l'=>'kilometres per litre','km/L'=>'kilometres per litre','l/km'=>'litres per kilometre','L/km'=>'litres per kilometre',
    'mpgimp'=>'miles per imperial gallon','mpgus'=>'miles per US gallon',
    'impgal/mi'=>'imperial gallons per mile','usgal/mi'=>'US gallons per mile');
$GLOBALS['FuelEfficiency']['STANDARD_UNIT'] = 'kilometres per litre';
// INFORMATION UNIT
$GLOBALS['InformationUnit']=array(
    'bit'=>'bit','kbit'=>'kilobit','Mbit'=>'megabit','B'=>'byte','kB'=>'kilobyte','MB'=>'megabyte','GB'=>'gigabyte','TB'=>'terabyte');
$GLOBALS['InformationUnit']['STANDARD_UNIT'] = 'byte';
// LENGTH
$GLOBALS['Length']=array(
    'nm'=>'nanometre', 'µm'=>'micrometre', 'mm'=>'millimetre','cm'=>'centimetre','dm'=>'decimetre','m'=>'metre','meter'=>'metre','metres'=>'metre','metre'=>'metre',
    'dam'=>'decametre','hm'=>'hectometre','km'=>'kilometre','kilometre'=>'kilometre','Mm'=>'megametre','Gm'=>'gigametre',
    'in'=>'inch','hand'=>'hand','ft'=>'foot','feet'=>'foot','yd'=>'yard','fathom'=>'fathom','rd'=>'rod','perch'=>'rod','pole'=>'rod','chain'=>'chain','furlong'=>'furlong',
    'mi'=>'mile','miles'=>'mile','mile'=>'mile',
    'nmi'=>'nautial mile',
    'AU'=>'astronomical unit','ly'=>'light-year','kly'=>'kilolight-year');
$GLOBALS['Length']['STANDARD_UNIT'] = 'metre';
// MASS
$GLOBALS['Mass']=array(
    'mg'=>'milligram','g'=>'gram','kg'=>'kilogram',
    't'=>'tonne','MT'=>'metric ton',
    'st'=>'stone','lb'=>'pound','lbs'=>'pound','lbm'=>'pound','oz'=>'ounce','gr'=>'grain',
    'carat'=>'carat',
    'Da'=>'atomic mass unit', 'u'=>'atomic mass unit');
$GLOBALS['Mass']['STANDARD_UNIT'] = 'gram';
// TODO: MOLAR MASS
// POPULATION DENSITY
$GLOBALS['PopulationDensity']=array(
    'inhabitants per square kilometre'=>'inhabitants per square kilometre','PD/sqkm'=>'inhabitants per square kilometre',
    'PD/ha'=>'inhabitants per hectare','PD/sqmi'=>'inhabitants per square mile','PD/acre'=>'inhabitants per acre',
    '/sqkm'=>'per square kilometre','/ha'=>'per hectare','/sqmi'=>'per square mile','/acre'=>'per acre');
//$GLOBALS['PopulationDensity']['STANDARD_UNIT'] = 'inhabitants per square kilometre';
// POWER
$GLOBALS['Power']=array(
    'W'=>'watt','kW'=>'kilowatt','mW'=>'milliwatt','MW'=>'megawatt', 'GW'=>'gigawatt', 'hp'=>'horsepower','PS'=>'pferdestaerke');
$GLOBALS['Power']['STANDARD_UNIT'] = 'watt';
// PRESSURE
$GLOBALS['Pressure']=array(
    'mPa'=>'millipascal','Pa'=>'pascal','hPa'=>'hectopascal','kPa'=>'kilopascal','MPa'=>'megapascal',
    'mbar'=>'millibar','mb'=>'millibar','dbar'=>'decibar','bar'=>'bar',
    'atm'=>'standard atmosphere','psi'=>'pound per square inch');
$GLOBALS['Pressure']['STANDARD_UNIT'] = 'pascal';
//SPEED
$GLOBALS['Speed']=array(
    'm/s'=>'metre per second','km/s'=>'kilometre per second','ms'=>'metre per second','km/h'=>'kilometre per hour','kmh'=>'kilometre per hour','mph'=>'mile per hour',
    'ft/s'=>'foot per second','ft/min'=>'foot per minute','kn'=>'knot');
$GLOBALS['Speed']['STANDARD_UNIT'] = 'kilometre per hour';
// TEMPERATURE
$GLOBALS['Temperature']=array(
    'K'=>'kelvin','°C'=>'degree celsius','�C'=>'degree celsius','degree celsius'=>'degree celsius','C'=>'degree celsius','Celsius'=>'degree celsius',
    '°F'=>'degree fahrenheit','�F'=>'degree fahrenheit','F'=>'degree fahrenheit',
    'Fahrenheit'=>'degree fahrenheit','�R'=>'degree rankine','R'=>'degree rankine');
//$GLOBALS['Temperature']['STANDARD_UNIT'] = 'degree celsius';
// TIME
$GLOBALS['Time']=array(
    's'=>'second','sec'=>'second','second'=>'second','seconds'=>'second','m'=>'minute','min'=>'minute','min.'=>'minute','mins'=>'minute','minute'=>'minute','minutes'=>'minute',
    'h'=>'hour','hr'=>'hour','hr.'=>'hour','hour'=>'hour','hours'=>'hour',
    'std'=>'hour','d'=>'day','days'=>'day','day'=>'day');
$GLOBALS['Time']['STANDARD_UNIT'] = 'second';
// TORQUE
$GLOBALS['Torque']=array(
    'Nmm'=>'newton millimetre','Ncm'=>'newton centimetre','Nm'=>'newton metre','N.m'=>'newton metre');
$GLOBALS['Torque']['STANDARD_UNIT'] = 'newton metre';
// VOLUME
$GLOBALS['Volume']=array(
    'mm3'=>'cubic millimetre','mm³'=>'cubic millimetre','cm3'=>'cubic centimetre','cm³'=>'cubic centimetre','cc'=>'cubic centimetre',
    'dm3'=>'cubic decimetre','dm³'=>'cubic decimetre','m3'=>'cubic metre','m³'=>'cubic metre','dam3'=>'cubic decametre',
    'hm3'=>'cubic hectometre','hm³'=>'cubic hectometre','km3'=>'cubic kilometre','km³'=>'cubic kilometre',
    'ul'=>'microlitre','uL'=>'microlitre','ml'=>'millilitre','mL'=>'millilitre','cl'=>'centilitre','cL'=>'centilitre','dl'=>'decilitre','dL'=>'decilitre','l'=>'litre','L'=>'litre',
    'dal'=>'decalitre','daL'=>'decalitre','hl'=>'hectolitre','hL'=>'hectolitre','kl'=>'kilolitre','kL'=>'kilolitre','Ml'=>'megalitre','ML'=>'megalitre','Gl'=>'gigalitre','GL'=>'gigalitre',
    'cumi'=>'cubic mile','mi3'=>'cubic mile','mi³'=>'cubic mile','cuyd'=>'cubic yard','yd3'=>'cubic yard','cuft'=>'cubic foot','ft3'=>'cubic foot','ft³'=>'cubic foot',
    'cuin'=>'cubic inch','in3'=>'cubic inch','in³'=>'cubic inch',
    'impbl'=>'imperial barrel','usbl'=>'us barrel', 'impbbl'=>'imperial barrel oil','usbbl'=>'us barrel oil','impgal'=>'imperial gallon', 'usgal'=>'us gallon', 'USgal'=>'us gallon');
$GLOBALS['Volume']['STANDARD_UNIT'] = 'cubic metre';

// Note: it is IMPORTANT that Area and Volume are listed before Length in this array, otherwise the parser will return kilometer instead of squarekilometer
$GLOBALS['units']= array_merge($GLOBALS['Area'],$GLOBALS['Volume'],$GLOBALS['Length'],$GLOBALS['Speed'],$GLOBALS['Force'],$GLOBALS['Energy'],$GLOBALS['Temperature'],$GLOBALS['Mass'],$GLOBALS['Pressure'],$GLOBALS['Torque'],
                               $GLOBALS['FuelEfficiency'],$GLOBALS['Power'],$GLOBALS['PopulationDensity'],$GLOBALS['InformationUnit'],$GLOBALS['Frequency'],$GLOBALS['FlowRate'],$GLOBALS['Density']);
$GLOBALS['dimensions'] = array("Area" => $GLOBALS['Area'],"Volume" => $GLOBALS['Volume'], "Length" => $GLOBALS['Length'], "Speed" => $GLOBALS['Speed'], "Force" => $GLOBALS['Force'], "Energy" => $GLOBALS['Energy'], "Temperature" => $GLOBALS['Temperature'], "Mass" => $GLOBALS['Mass'], "Pressure" => $GLOBALS['Pressure'], "Torque" => $GLOBALS['Torque'], "FuelEfficiency" => $GLOBALS['FuelEfficiency'], "Power" => $GLOBALS['Power'], "PopulationDensity" => $GLOBALS['PopulationDensity'], "InformationUnit" => $GLOBALS['InformationUnit'], "Frequency" => $GLOBALS['Frequency'], "FlowRate" => $GLOBALS['FlowRate'], "Density" => $GLOBALS['Density']);

// CONVERSATION FACTORS
$GLOBALS['conversionFactor'] = array(
// AREA
    // square metre to ...
    'square metre'=>array('square millimetre'=>1000000,'square centimetre'=>10000,'square decimetre'=>100,'square metre'=>1,'square decametre'=>0.1,'square hectometre'=>0.001,'square kilometre'=>0.000001,
    'square inch'=>1550.0031,'square foot'=>10.76391,'square yard'=>1.19599,'acre'=>0.000247123,'hectare'=>0.0001,'square mile'=>0.000000386102),
    // ... to square metre
    'square millimetre'=>array('square metre'=>0.000001),'square centimetre'=>array('square metre'=>0.0001),'square decimetre'=>array('square metre'=>0.01),
    'square decametre'=>array('square metre'=>0.1),'square hectometre'=>array('square metre'=>10000),'square kilometre'=>array('square metre'=>1000000),
    'hectare'=>array('square metre'=>10000),'square inch'=>array('square metre'=>0.00064516),'square foot'=>array('square metre'=>0.09290304),'square yard'=>array('square metre'=>0.83612736),
    'acre'=>array('square metre'=>4046.564224),'square mile'=>array('square metre'=>2589988.110336),
// DENSITY
    // kilogram per cubic metre to ...
    'kilogram per cubic metre'=>array('kilogram per litre'=>0.001,'gram per cubic centimetre'=>0.001,'gram per millilitre'=>0.001),
    // ... to kilogram per cubic metre
    'kilogram per litre'=>array('kilogram per cubic metre'=>1000),
    'gram per cubic centimetre'=>array('kilogram per cubic metre'=>1000),
    'gram per millilitre'=>array('kilogram per cubic metre'=>1000),
// ENERGY
    // joule to ...
    'joule'=>array('kilojoule'=>0.001,
    'millicalorie'=>238.84589663,'calorie'=>0.23884589663,'kilocalorie'=>0.00023884589663,'megacalorie'=>0.00000023884589663,
    'milliwatt-hour'=>0.27777777778,'watt-hour'=>0.00027777777778,'kilowatt-hour'=>0.00000027777777778,
    'megawatt-hour'=>0.00000000027777777778,'gigawatt-hour'=>0.00000000000027777777778,'terawatt-hour'=>0.00000000000000027777777778,
    'inch-pound'=>8.8507457916,'foot-pound'=>0.7375621493,
    'erg'=>10000000,'electron volt'=>6241807627000000000),
    // ... to joule
    'kilojoule'=>array('joule'=>1000),'millicalorie'=>array('joule'=>0.0041868),'calorie'=>array('joule'=>4.1868),'kilocalorie'=>array('joule'=>4186.8),'megacalorie'=>array('joule'=>4186800),
    'milliwatt-hour'=>array('joule'=>3.6),'watt-hour'=>array('joule'=>3600),'kilowatt-hour'=>array('joule'=>3600000),
    'megawatt-hour'=>array('joule'=>3600000000),'gigawatt-hour'=>array('joule'=>3600000000000),'terawatt-hour'=>array('joule'=>3600000000000000),
    'inch-pound'=>array('joule'=>0.11298482902),'foot-pound'=>array('joule'=>1.3558179483),'erg'=>array('joule'=>0.0000001),'electronvolt'=>array('joule'=>0.00000000000000000016021),
// FLOW RATE
    // cubic metre per second to ...
    'cubic metre per second'=>array('cubic feet per second'=>35.31466672,'cubic metre per year'=>31536000,'cubic feet per year'=>1113683329.6644),
    // ... to cubic metre per second
    'cubic feet per second'=>array('cubic metre per second'=>0.028316846593),
    'cubic metre per year'=>array('cubic metre per second'=>0.0000000317097919837645865),
    'cubic feet per year'=>array('cubic metre per second'=>0.000000000897921315120468),
// FORCE
    // newton to ...
    'newton'=>array('nanonewton'=>1000000000,'millinewton'=>1000,'newton'=>1,'kilonewton'=>0.001,'meganewton'=>0.000001,'giganewton'=>0.000000001,
    'milligram-force'=>101971.621298,'gram-force'=>101.971621298,'kilogram-force'=>0.101971621,'tonne-force'=>0.000101971621298,
    'millipond'=>101971.621298,'pond'=>101.971621298,'kilopond'=>0.101971621,'megapond'=>0.000101971621298,'poundal'=>7.23066),
    // ... to newton
    'nanonewton'=>array('newton'=>0.000000001),'millinewton'=>array('newton'=>0.001),'kilonewton'=>array('newton'=>1000),'meganewton'=>array('newton'=>1000000),'giganewton'=>array('newton'=>1000000000),
    'milligram-force'=>array('newton'=>0.00980665),'gram-force'=>array('newton'=>0.00980665),'kilogram-force'=>array('newton'=>9.80665),'tonne-force'=>array('newton'=>9806.65),
    'millipond'=>array('newton'=>0.00980665),'pond'=>array('newton'=>0.00980665),'kilopond'=>array('newton'=>9.80665),'megapond'=>array('newton'=>9806.65),
    'poundal'=>array('newton'=>0.1383),
// FREQUENCY
    // hertz to ...
    'hertz'=>array('millihertz'=>1000,'kilohertz'=>0.001,'megahertz'=>0.000001,'gigahertz'=>0.000000001),
    // ... to hertz
    'millihertz'=>array('hertz'=>0.001),'kilohertz'=>array('hertz'=>1000),'megahertz'=>array('hertz'=>1000000),'gigahertz'=>array('hertz'=>1000000000),
// FUEL EFFINCIENCY
// INFORMATION UNIT
    // byte to ...
    'byte'=>array('bit'=>8,'kilobit'=>0.0078125,'megabit'=>0.0000076293945313,'kilobyte'=>0.001,'megabyte'=>0.000001,'gigabyte'=>0.000000001,'terabyte'=>0.000000000001),
    // ... to byte
    'bit'=>array('byte'=>0.125),'kilobit'=>array('byte'=>128),'megabit'=>array('byte'=>131072),
    'kilobyte'=>array('byte'=>1000),'megabyte'=>array('byte'=>1000000),'gigabyte'=>array('byte'=>1000000000),'terabyte'=>array('byte'=>1000000000000),
// LENGTH
    // metre to ...
    'metre'=>array('nanometre'=>1000000000, 'micrometre'=>1000000, 'millimetre'=>1000, 'centimetre'=>100, 'decimetre'=>10, 'metre'=>1,
    'decametre'=>0.1, 'hectometre'=>0.01, 'kilometre'=>0.001, 'megametre'=>0.000001, 'gigametre'=>0.000000001,
    'inch'=>39.3700787401574803, 'hand'=>9.84251968503937, 'foot'=>3.28083989501312335958, 'yard'=>1.0936132983377077865,
    'fathom'=>0.546807, 'rod'=>0.198839, 'chain'=>0.0497097, 'furlong'=>0.00497097, 'mile'=>0.000621371, 'nautial mile'=>0.000539954,
    'light-year'=>9460730472580800, 'kilolight-year'=>9460730472580800000),
    // ... to metre
    'nanometre'=>array('metre'=>0.000000001), 'micrometre'=>array('metre'=>0.000001), 'millimetre'=>array('metre'=>0.001), 'centimetre'=>array('metre'=>0.01), 'decimetre'=>array('metre'=>0.1),
    'decametre'=>array('metre'=>10), 'hectometre'=>array('metre'=>100), 'kilometre'=>array('metre'=>1000), 'megametre'=>array('metre'=>1000000), 'gigametre'=>array('metre'=>1000000000),
    'inch'=>array('metre'=>0.0254), 'hand'=>array('metre'=>0.1016), 'foot'=>array('metre'=>0.3048), 'yard'=>array('metre'=>0.9144),
    'fathom'=>array('metre'=>1.8288), 'rod'=>array('metre'=>5.0292), 'chain'=>array('metre'=>20.1168), 'furlong'=>array('metre'=>201.168),
    'mile'=>array('metre'=>1609.344), 'nautial mile'=>array('metre'=>1852.01),
    'astronomical unit'=>array('metre'=>149597870691), 'light-year'=>array('metre'=>9460730472580800), 'kilolight-year'=>array('metre'=>9460730472580800000),
// MASS
    // gram to ...
    'gram'=>array('milligram'=>1000,'gram'=>1,'kilogram'=>0.001,'tonne'=>0.000001,
    'stone'=>0.000157473,'pound'=>0.00220459,'ounce'=>0.0352734,'grain'=>15.4321,'carat'=>5),
    // ... to gram
    'milligram'=>array('gram'=>0.001),'kilogram'=>array('gram'=>1000),
    'tonne'=>array('gram'=>1000000),
    'stone'=>array('gram'=>6350.29318),'pound'=>array('gram'=>453.6),'ounce'=>array('gram'=>28.35),'grain'=>array('gram'=>0.0648),
    'carat'=>array('gram'=>0.2),
// MOLAR MASS
// POPULATION DENSITY
// POWER
    // watt to ...
    'watt'=>array('milliwatt'=>1000,'watt'=>1,'kilowatt'=>0.001,'megawatt'=>0.000001,'gigawatt'=>0.000000001,
    'horsepower'=>0.00134098,'pferdestaerke'=>0.00135962),
    // ... to watt
    'milliwatt'=>array('watt'=>0.001),'kilowatt'=>array('watt'=>1000),'megawatt'=>array('watt'=>1000000),'gigawatt'=>array('watt'=>1000000000),
    'horsepower'=>array('watt'=>745.72218),'pferdestaerke'=>array('watt'=>735.49875),
// PRESSURE
    // pascal to ...
    'pascal'=>array('millipascal'=>1000,'hectopascal'=>100,'kilopascal'=>0.001,'megapascal'=>0.000001,
    'millibar'=>0.01,'decibar'=>0.0001,'bar'=>0.00001,
    'standard atmosphere'=>0.0000098692326672,'pound per square inch'=>0.00014503773773),
    // ... to pascal
    'millipascal'=>array('pascal'=>0.001),'hectopascal'=>array('pascal'=>0.01),'kilopascal'=>array('pascal'=>1000),'megapascal'=>array('pascal'=>1000000),
    'millibar'=>array('pascal'=>100),'decibar'=>array('pascal'=>10000),'bar'=>array('pascal'=>100000),
    'standard atmosphere'=>array('pascal'=>101325),'pound per square inch'=>array('pascal'=>6894.7572932),
// SPEED
    // kilometre per hour to ...
    'kilometre per hour'=>array('metre per second'=>0.277778,'kilometre per hour'=>1,'mile per hour'=>0.621373,'foot per second'=>0.9112448333,'foot per minute'=>54.67469,'knot'=>0.539957),
    // ... to kilometre per hour
    'metre per second'=>array('kilometre per hour'=>3.6),'mile per hour'=>array('kilometre per hour'=>1.60934),'foot per second'=>array('kilometre per hour'=>0.0003048333333),
    'foot per minute'=>array('kilometre per hour'=>0.01829),'knot'=>array('kilometre per hour'=>1.852),
// TEMPERATURE
// TORQUE
    // newton metre to ...
    'newton metre'=>array('newton millimetre'=>1000, 'newton centimetre'=>100),
    // ... to newton metre
    'newton millimetre'=>array('newton metre'=>0.001),'newton centimetre'=>array('newton metre'=>0.001),
// VOLUME
    // cubic metre to ...
    'cubic metre'=>array('cubic millimetre'=>1000000000,'cubic centimetre'=>1000000,'cubic decimetre'=>1000,'cubic metre'=>1,'cubic decametre'=>0.001,'cubic hectometre'=>0.000001,'cubic kilometre'=>0.000000001,
    'microlitre'=>1000000000,'millilitre'=>1000000,'centilitre'=>100000,'decilitre'=>10000,'litre'=>1000,'hectolitre'=>10,'kilolitre'=>1,'megalitre'=>0.001,'gigalitre'=>0.000001,
    'cubic inch'=>61012.81269,'cubic foot'=>35.30834,'cubic yard'=>1.30772,'cubic mile'=>0.000000000239913,
    'imperial gallon'=>219.96923,'us gallon'=>264.17205,'imperial barrel'=>6.11026,'us barrel'=>8.38641,'imperial barrel oil'=>6.28484,'us barrel oil'=>6.28981),
    // ... to cubic metre
    'cubic millimetre'=>array('cubic metre'=>0.000000001),'cubic centimetre'=>array('cubic metre'=>0.000001),'cubic decimetre'=>array('cubic metre'=>0.001),'cubic decametre'=>array('cubic metre'=>1000),
    'cubic hectometre'=>array('cubic metre'=>1000000),'cubic kilometre'=>array('cubic metre'=>1000000000),
    'microlitre'=>array('cubic metre'=>0.000000001),'millilitre'=>array('cubic metre'=>0.000001),'centilitre'=>array('cubic metre'=>0.00001),'decilitre'=>array('cubic metre'=>0.0001),
    'litre'=>array('cubic metre'=>0.001),'hectolitre'=>array('cubic metre'=>0.1),'kilolitre'=>array('cubic metre'=>1),'megalitre'=>array('cubic metre'=>1000),'gigalitre'=>array('cubic metre'=>1000000),
    'cubic inch'=>array('cubic metre'=>0.00001639),'cubic foot'=>array('cubic metre'=>0.0283219),'cubic yard'=>array('cubic metre'=>0.764692),'cubic mile'=>array('cubic metre'=>4168181825.44058),
    'imperial gallon'=>array('cubic metre'=>0.00454609),'us gallon'=>array('cubic metre'=>0.00378541178),'imperial barrel'=>array('cubic metre'=>0.163659),'us barrel'=>array('cubic metre'=>0.11924),
    'imperial barrel oil'=>array('cubic metre'=>0.159113),'us barrel oil'=>array('cubic metre'=>0.158987)
);
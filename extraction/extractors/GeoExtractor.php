<?php

/**
 * GeoExtractor
 * Extracts geoinformation from known templates such as {{coord}} and common infobox predicates
 * Outputs latitude and longitude information using the Basic and the new GeoRSS-based W3C Geo Vocabularies
 * Feature details are expressed using the geonames ontology
 *
 * In batch extraction mode, uses a MySQL table to limit output of Basic Coordinates to one pair per resource across all languages and to avoid duplicate GeoRSS points
 *
 * See also http://en.wikipedia.org/wiki/Template:Coor_title_d#Usage and http://en.wikipedia.org/wiki/Wikipedia:WikiProject_Geographical_coordinates#Templates
 *
 * @author  Christian Becker <http://beckr.org>
 */

ini_set('display_errors', 'true');

class GeoExtractor extends Extractor {

    const enablePreview = false;

    const sqlDBSetup = 'CREATE DATABASE IF NOT EXISTS `##db##`';

    const sqlTableSetup = 'CREATE TABLE IF NOT EXISTS `GeoExtractorResults` (
		  `resource` varchar(255) NOT NULL,
		  `lang` varchar(2) NOT NULL,
		  `point` varchar(255) NOT NULL,
		  PRIMARY KEY  (`resource`,`lang`),
		  KEY `lang` (`lang`)
		  ) ENGINE=MyISAM DEFAULT CHARSET=UTF8;';

    const sqlLangSetup = 'DELETE FROM `GeoExtractorResults` WHERE `lang`= \'##lang##\'';

    const sqlInsertPoint = 'INSERT INTO `GeoExtractorResults` SET `resource`= \'##resource##\', `lang`= \'##lang##\', `point`= \'##point##\'';

    const sqlCheckAny = 'SELECT COUNT(*) AS `count` FROM `GeoExtractorResults` WHERE `resource`= \'##resource##\'';

    const sqlCheckExact = 'SELECT COUNT(*) AS `count` FROM `GeoExtractorResults` WHERE `resource`= \'##resource##\' AND `point`= \'##point##\'';

     /**
      * Coordinate templates with parameters understood by geo_param
      * These were selected from http://en.wikipedia.org/wiki/Category:Coordinates_templates
      * and http://de.wikipedia.org/wiki/Wikipedia:WikiProjekt_Georeferenzierung
      */
     static $knownTemplates = array(
            '/\{\{coor [^|}]*(?:d|dm|dms)\|([^}]+)\}\}/i',
            '/\{\{coord\|([^}]+)\}\}/i',
            '/\{\{Geolinks[^|}]*\|([^}]*)\}\}/i',
            '/\{\{Mapit[^|}]*\|([^}]+)\}\}/i', /* redirect to Geolinks, always create titles */
            '/\{\{Koordinate[^|}]*\|([^}]+)\}\}/i',
            '/\{\{Coordinate[^|}]*\|([^}]+)\}\}/i',
            '/\{\{좌표[^|}]*\|([^}]+)\}\}/i',
            );

     /**
      * Coordinate templates with parameters understood by geo_param, that set the article title
      * These were selected from http://en.wikipedia.org/wiki/Category:Coordinates_templates
      * and http://de.wikipedia.org/wiki/Wikipedia:WikiProjekt_Georeferenzierung
      *
      * Samples:
      *
      * {{coor title d|deg|NS|deg|EW[|parameters]}}
      * {{coor title dm|deg|min|NS|deg|min|EW[|parameters]}}
      * {{coor title dms|deg|min|sec|NS|deg|min|sec|EW[|parameters]}}
      *
      * {{coord|latitude|longitude[|parameters][|display=display]}}
      * {{coord|dd|N/S|dd|E/W[|parameters][|display=display]}}
      * {{coord|dd|mm|N/S|dd|mm|E/W[|parameters][|display=display]}}
      * {{coord|dd|mm|ss|N/S|dd|mm|ss|E/W[|parameters][|display=display]}}
      *
      * {{Geolinks-US-streetscale|37.429847|-122.169447}}
      *
      * {{Koordinate Artikel|49_45_34.85_N_6_38_38.47_E_type:landmark_region:DE-RP_dim:25|49° 45´ 35´´ n. Br., 6° 38´ 38´´ ö. L.}}
      */
     static $knownTemplatesTitle = array(
            '/\{\{coor (?:title|at) (?:d|dm|dms)\|([^}]+)\}\}/i',
            '/\{\{coord\|([^}]+display=[^|}]*title[^}]*)\}\}/i',
            '/\{\{Geolinks[^|}]*(?<!no-title)\|([^}]*)\}\}/i',
            '/\{\{Mapit[^|}]*\|([^}]+)\}\}/i', /* redirect to Geolinks, always create titles */
            '/\{\{Koordinate[^|}]+Artikel\|([^}]+)\}\}/i',

			/* NEW Coordinate format: http://de.wikipedia.org/wiki/Wikipedia:WikiProjekt_Georeferenzierung/Neue_Koordinatenvorlage */
            '/\{\{Coordinate[^|}]*\|(((?!text=)[^|}]+\|?)+)\}\}/i', /* 'text' key not present => coordinates belong to the article */
            '/\{\{Coordinate[^|}]*\|([^}]+article=[^}]*)\}\}/i', /* 'article' key present */
            '/\{\{좌표[^|}]*\|([^}]+)\}\}/i',
            );

    /**
     * DD MM SS N/S DD MM SS E/W [default NS] [default EW]
     * Urlencode UTF-8 if neccessary
     */
    static $knownInfoboxFormats =
        array(
            /* http://en.wikipedia.org/wiki/Wikipedia:Manual_of_Style_%28dates_and_numbers%29#Geographical_coordinates => Munich */
            array('lat_deg', 'lat_min', 'lat_sec', 'lat_NS', 'lon_deg', 'lon_min', 'lon_sec', 'long_EW'),

            /* http://de.wikipedia.org/wiki/Paris */
            array('lat-deg', 'lat-min', 'lat-sec', 'lat', 'lon-deg', 'lon-min', 'lon-sec', 'lon'),

            /*
             * http://en.wikipedia.org/wiki/Wikipedia:WikiProject_Geographical_coordinates#Templates
             * {{Geobox Town}} => Prague
             */
            array('lat_d', 'lat_m', 'lat_s', 'lat_NS', 'long_d', 'long_m', 'long_s', 'long_EW'),

            /*
             * {{Infobox Town}}
             * {{Infobox Country or territory}}
             */
            array('latd', 'latm', 'lats', 'latNS', 'longd', 'longm', 'longs', 'longEW'),

            /* {{Infobox Town DE}} */
            array('lat_d', 'lat_m', 'lat_s', 'lat_hem', 'lon_d', 'lon_m', 'lon_s', 'lon_hem'),

            /* {{Infobox nrhp}} => Yosemite_National_Park */
            array('lat_degrees', 'lat_minutes', 'lat_seconds', 'lat_direction', 'long_degrees', 'long_minutes', 'long_seconds', 'long_direction'),

            /* {{Infobox_Flughafen}}(de) => http://de.wikipedia.org/wiki/Flughafen_Berlin-Tegel */
            array('Koordinate_Breitengrad', 'Koordinate_Breitenminute', 'Koordinate_Breitensekunde', 'Koordinate_Breite', 'Koordinate_L%C3%A4ngengrad', 'Koordinate_L%C3%A4ngenminute', 'Koordinate_L%C3%A4ngensekunde', 'Koordinate_L%C3%A4nge'),

            /* {{Infobox Hungarian settlement}} => Pécs */
            array('N', null, null, null, 'E', null, null, null),

            /* {{Infobox Russian city}} => Moscow */
            array('LatDeg', 'LatMin', 'LatSec', null, 'LonDeg', 'LonMin', 'LonSec', null),

            /* {{Infobox Irish Place}} => Dublin */
            array('north coord', null, null, null, 'west coord', null, null, null, 'N' /* default NS */, 'W' /* default EW */),

            /* {{Infobox_UK_place}} => Glasgow */
            array('latitude', null, null, null, 'longitude', null, null, null),
        );

    /**
     * Infobox predicates for coordinate subtemplates
     * We could simply take all references, but the idea here is to filter out
     * cases where an infobox lists multiple locations (haven't seen one so far however)
     * Urlencode UTF-8 if neccessary
     */
    static $knownTemplatePredicates = array('coordinates', 'location', 'lat_long', 'coords', 'place', 'Coor dms', 'coordonn%C3%A9es');

    /**
     * Format: 'type' => array(featureClass[, featureCode])
     * Reference: http://en.wikipedia.org/wiki/Wikipedia:WikiProject_Geographical_coordinates#Parameters
     */
    static $typeToGeoNames = array(
        'country' => array('A', 'PCLI'),
        'state' => array('A', 'ADM1'),     /* Where applicable */
        'adm1st' => array('A', 'ADM2'),    /* Administrative unit of country, 1st level (province, county) */
        'adm2nd' => array('A', 'ADM3'),    /* Administrative unit of country, 2nd level (province, county) */
        'city' => array('P', 'PPL'),        /* City, town or village */
        'airport' => array('S', 'AIRP'),
        'mountain' => array('T', 'MT'),
        'isle' => array('T', 'ISL'),
        'waterbody' => array('H'),          /* Bays, fjords, lakes, glaciers, inland seas... */
        'forest' => array('V'),             /* Forests and woodlands */
    );


    private $allPredicates;
    private $batchExtraction;

	/**
	 * Constructs a new GeoExtractor
	 *
	 * @param	$batchExtraction	If set to <code>true</code>, the extraction result table is cleared for the respective language on
	 * 								<code>start()</code>, and entries are verified for duplicates.
	 *								This should be set to <code>false</code> for extraction previews.
	 */
    public function __construct() {
		parent::__construct();
    	$this->batchExtraction = Options::getOption('Geo.batchextraction');
    }



    public function start($language) {
        $this->language = $language;


        $this->allPredicates = new ExtractionResult("PredicateCollection", $this->language, $this->getExtractorID());
     	if(Options::getOption('Geo.usedb')) {
			include ('databaseconfig.php');
			$this->dbSharedConnection = mysql_connect($host, $user, $password, true /* force a new connection - mandatory as we use another database! */);
			mysql_query("SET NAMES utf8", $this->dbSharedConnection);

			/* Try to create shared DB if it does not exist; then select it */
			mysql_query($this->sqlParameterize(GeoExtractor::sqlDBSetup, array("db" => $dbprefix.'shared')), $this->dbSharedConnection) or die("Unable to initialize shared DB: " . mysql_error());

			if (! mysql_select_db($dbprefix.'shared' , $this->dbSharedConnection)) {
				echo mysql_error();
				return;
			}

			/* Create extraction result table if needed */
			mysql_query(GeoExtractor::sqlTableSetup, $this->dbSharedConnection) or die("Unable to initialize result table: " . mysql_error());
		}

        /* Remove previous extraction result for this language */
        if ($this->batchExtraction)
	        mysql_query($this->sqlParameterize(GeoExtractor::sqlLangSetup, array("lang" => $language)), $this->dbSharedConnection) or die("Unable to remove previous results: " . mysql_error());
	}

    public function extractPage($pageID, $pageTitle, $pageSource) {
        $result = new ExtractionResult(
            $pageID, $this->language, $this->getExtractorID());
        $foundCoordinates = array();

        /* Main title */
        if ($geoInfo = $this->extractGeoInfo($pageSource, true /* article titles only */)) {
            $pageG = new geo_param($geoInfo);
            array_push($foundCoordinates, $pageG);
           $this->log('debug', "Found title entry '" . implode("|", $geoInfo)."'");
        }

        /* Coordinates provided in infobox formats */
        $infoboxes = $this->getInfoboxes($pageSource);

        foreach ($infoboxes[1] as $box)  {
            $boxProperties = $this->getBoxProperties($box, true /* toLower */);

            foreach (GeoExtractor::$knownInfoboxFormats as $format)  {
                /* Initialize global defaults */
                $pieces = array(null, 0, 0, 'N', null, 0, 0, 'E');

                /* Apply template-specific NS/EW defaults */
                if (isset($format[8])) /* NS default */
                    $pieces[3] = $format[8];

                if (isset($format[9])) /* EW default */
                    $pieces[7] = $format[9];

                /* Copy from template */
                for ($i=0; $i<count($format); $i++)  {
                    $formatString = urldecode($format[$i]);

                    if ($formatString != "" && isset($boxProperties[strtolower($formatString)]))  {

                        /* German coordinates: Treat 'O' (Ost) as 'E' (East) */
                        if ($i == 7 /* EW */ && $boxProperties[strtolower($formatString)] == 'O')
                            $pieces[$i] = 'E';
                        else
                            $pieces[$i] = $boxProperties[strtolower($formatString)];
                    }
                }

                if (geo_param::is_lat($pieces[0])
                     && geo_param::is_long($pieces[4]))  {
                        $g = new geo_param($pieces);
                        array_push($foundCoordinates, $g);
                       $this->log('debug', "Found format '" . $format[0] . "'");
                }


             } /* $this->knownInfoboxFormats */

             /*
              * Look for coordinate tags inside the infobox
              * Used widely, e.g. airports (=> Los_Angeles_International_Airport)
              * These don't have to set the title, as they're in a first-level infobox
              * We could simply take all references, but the idea here is to filter out
              * cases where multiple locations are specified
              */
             foreach (GeoExtractor::$knownTemplatePredicates as $coord) {
               if (isset($boxProperties[strtolower(urldecode($coord))])
                    && ($geoInfo = $this->extractGeoInfo($boxProperties[strtolower(urldecode($coord))]))) {
                    $pageG = new geo_param($geoInfo);
                    array_push($foundCoordinates, $pageG);
                   $this->log('debug', "Found infobox entry '" . implode("|", $geoInfo). "'");
               }
            }
        } /* infoboxes */

        $numResults = 0;

        foreach ($foundCoordinates as $g)  {
             if ($g->is_valid()) {

                if (GeoExtractor::enablePreview) {
                     ?>
                     <iframe src="http://maps.google.com/?q=<?=$g->latdeg?>,<?=$g->londeg?>&z=5" style="width: 1000px; height: 600px; border: none;" scrolling="no">
                     </iframe>
                     <?php
                }

                /* Only process the first result */
                if (++$numResults == 1)  {

                	$sqlParams = array("resource" => $pageID, "lang" => $this->language, "point" => (string) $g->latdeg . " " . (string) $g->londeg);

                	/* Check whether this exact entry is present */
			        if ($this->batchExtraction) {
				        $results = mysql_query($this->sqlParameterize(GeoExtractor::sqlCheckExact, $sqlParams), $this->dbSharedConnection);
						$row = mysql_fetch_assoc($results);
						$hasExact = ($row['count'] != '0');

						if ($hasExact)
							$hasAny = true;
						else {
		                	/* Check whether any entry is present */
					        $results = mysql_query($this->sqlParameterize(GeoExtractor::sqlCheckAny, $sqlParams), $this->dbSharedConnection);
							$row = mysql_fetch_assoc($results);
							$hasAny = ($row['count'] != '0');
						}
			        } else {
			        	$hasExact = $hasAny = false;
			        }

					if ($hasAny /* was: $hasExact */) {
			           $this->log('debug', "Not generating geocoordinates because coordinates were previously generated for this resource");
					}
					else {
				        /* Store in results table for duplicate detection */
				        if ($this->batchExtraction)
					        mysql_query($this->sqlParameterize(GeoExtractor::sqlInsertPoint, $sqlParams), $this->dbSharedConnection);

	                	/* Triple generation */
	                	/* W3C Geospatial Vocabulary (GeoRSS) */
	                    $result->addTriple(
	                            $this->getPageURI(),
	                            RDFtriple::URI(GEORSS_POINT,false),
	                            RDFtriple::Literal((string) $g->latdeg . " " . (string) $g->londeg));

	                    /* Basic Geo Vocabulary - only add them if there are no points for the resource so far; it'd be ambigous otherwise! */
	                    if ($hasAny) {
				           $this->log('debug', "Not generating W3C Basic geocoordinates because basic coordinates were previously generated for this resource");
	                    } else {
		                    $result->addTriple(
		                            $this->getPageURI(),
		                            RDFtriple::URI(WGS_LAT,false),
		                            RDFtriple::Literal((string) $g->latdeg, XS_FLOAT,NULL));

		                    $result->addTriple(
		                            $this->getPageURI(),
		                            RDFtriple::URI(WGS_LONG,false),
		                            RDFtriple::Literal((string) $g->londeg, XS_FLOAT,NULL));
	                    }

	                     /*
	                      * Process additional attributes
	                      * See http://en.wikipedia.org/wiki/Wikipedia:WikiProject_Geographical_coordinates#Parameters
	                      * and http://de.wikipedia.org/wiki/Wikipedia:WikiProjekt_Georeferenzierung/Neue_Koordinatenvorlage#Parameter
	                      */
	                    if (isset($g->attr['type']))  {
	                        if (isset(GeoExtractor::$typeToGeoNames[$g->attr['type']]))  {
	                            $result->addTriple(
	                                    $this->getPageURI(),
	                                    RDFtriple::URI(GEO_FEATURECLASS,false),
	                                    RDFtriple::URI(GEONAMES_NS . GeoExtractor::$typeToGeoNames[$g->attr['type']][0]));

	                            if (isset(GeoExtractor::$typeToGeoNames[$g->attr['type']][1]))
	                                $result->addTriple(
	                                        $this->getPageURI(),
	                                        RDFtriple::URI(GEO_FEATURECODE,false),
	                                        RDFtriple::URI(GEONAMES_NS
	                                                        . GeoExtractor::$typeToGeoNames[$g->attr['type']][0]
	                                                        . '.' . GeoExtractor::$typeToGeoNames[$g->attr['type']][1]));
	                        }

	                        /* city(pop): City, town or village with specified population */
	                        if (strtolower($g->attr['type'] == 'city') && isset($g->attr['arg:type'])) {
	                            $result->addTriple(
	                                    $this->getPageURI(),
	                                    RDFtriple::URI(GEO_POPULATION,false),
	                                    RDFtriple::Literal((string)$g->attr['arg:type'], XS_INTEGER,NULL));
	                        }

	                        /*
	                         * landmark: Cultural landmark, building of special interest, tourist attration and other points of interest
	                         * Mapped to YAGO; could in theory use http://www.eionet.europa.eu/gemet/concept/8525 ("tourist facility"),
	                         * but it seems as if any POI not matching the above categories is tagged as a landmark
	                         * (=> "Google" etc.)
	                         */
	                        if (strtolower($g->attr['type'] == 'landmark')) {
	                            $result->addTriple(
	                                    $this->getPageURI(),
	                                    RDFtriple::URI(RDF_TYPE,false),
	                                    RDFtriple::URI(YAGO_LANDMARK,false));
	                        }
	                    } /* type */

	                    /* population */
	                    if (isset($g->attr['pop']))  {
                            $result->addTriple(
                                    $this->getPageURI(),
                                    RDFtriple::URI(GEO_POPULATION,false),
                                    RDFtriple::Literal((string) $g->attr['pop'],XS_INTEGER,NULL));
	                    }

	                    /* elevation in meters above sea level
	                     * TODO must be converted from sea level *to* wgs84 ellipsoid!
	                     */
	                    /*if (isset($g->attr['elevation']))  {
		                    $result->addTriple(
		                            $this->getPageURI(),
		                            RDFtriple::URI("http://www.georss.org/georss/elev"),
		                            RDFtriple::Literal((string) $g->attr['elevation']);
	                    }*/

	                    /* Diameter (m) to GeoRSS radius (m) */
	                    if (isset($g->attr['dim']))  {
                            $result->addTriple(
                                    $this->getPageURI(),
                                    RDFtriple::URI(GEORSS_RADIUS,false),
                                    RDFtriple::Literal((string) ($g->attr['dim'] / 2), XS_DECIMAL,NULL));
                        }

	                    /*
	                     * region: ISO 3166-1 alpha-2 country code or ISO 3166-2 region code to GeoNames inCountry
	                     * Very unreliable, as it just sets a preferred map view,
	                     * i.e. Germany's and The Czech Republic's regions are set to "EN"....
	                     */
	                    /*if (isset($g->attr['region']))
	                    {
	                            $result->addTriple(
	                                    $this->getPageURI(),
	                                    RDFtriple::URI("http://www.geonames.org/ontology#inCountry"),
	                                    RDFtriple::URI("http://www.geonames.org/countries/#".
	                                                    substr($g->attr['region'], 0, 2)));
	                    }  */
					} /* !$hasExact */
                } /* first entry */
             } /* is_valid */
      } /* $foundCoordinates */

      return $result;
    }

    public function finish() {
       	if(Options::getOption('geousedb')) {
		 	mysql_close($this->dbSharedConnection);
		}
        return $this->getPredicates();
    }


    private function getPredicates() {
        return $this->allPredicates->getPredicateTriples();
    }

    /**
     * Looks for templates that contain geo coordinates
     *
     * @param   $source         Page or infobox source
     * @param   $titlesOnly     Limit to templates that set the article title
     */
    public function extractGeoInfo($source, $titlesOnly = false)
    {
        foreach (($titlesOnly ? GeoExtractor::$knownTemplatesTitle : GeoExtractor::$knownTemplates) as $template) {
         if (preg_match($template, $source, $matches)) {
            $a = preg_split('/[|_]/', $matches[1]);

        	/* Convert new "Coordinate" template to the old format that is understood by geo_param :) */
        	if (stristr($matches[1], "NS=") !== FALSE && stristr($matches[1], "EW=") !== FALSE) {
        		/* Build key-value pairs */
        		$assoc = array();

        		foreach ($a as $pair) {
        			list($key, $value) = explode("=", $pair);
        			$assoc[$key] = $value;
        		}

      			/* Reconstruct array */
        		if (strpos($assoc['NS'], '/') !== FALSE) {
	        		/* Handle formats NS=49/45/34.85/N */
	        		$a = explode("/", $assoc['NS'] . "/" . $assoc['EW']);
        		} else {
        			/* Handle format NS=49.759681 */
        			$a = array($assoc['NS'], $assoc['EW']);
        		}

        		/* Provide further attributes using semicolon separator */
        		foreach ($assoc as $key => $value) {
        			if (strcasecmp("NS", $key) != 0 && strcasecmp("EW", $key) != 0) {
        				$a[] = $key . ":" . $value;
        			}
        		}
        	}

        	return $a;

         }
        }

        return null;
    }

    /**
     * Retrieves all infoboxes for a provided page source
     *
     * @param   $pageSource
     * @return  Array as returned by preg_match_all
     */
    private function getInfoboxes($pageSource)
    {
        preg_match_all('/\{((?>[^{}]+)|(?R))*\}/x', $pageSource, $infoboxes);
        return $infoboxes;
    }


    /**
     * Retrieves properties defined in an infobox as an associative array
     *
     * @param   $box    Infobox code
     * @param   $toLower    Whether to convert all predicate keys to lowercase
     * @return  Associative array with predicates as keys
     */
    private function getBoxProperties($box, $toLower = false) {

        /* Remove outside curly brackets */
        $box = substr($box, 1, strlen($box) - 2);

        /* Remove HTML comments */
        $box = preg_replace('/<\!--[^>]*->/mU', '', $box);

        /* Split triples; ignoring triples in subtemplates */
        $triples = preg_split('/\| (?! [^{]*\}\} | [^[]*\]\] )/x',$box);

        $a = array();

        foreach ($triples as $triple) {
                $predObj = explode('=',$triple,2);

                if (count($predObj) == 2 && ($pred = trim($predObj[0])) != "" && ($obj = trim($predObj[1])) != "")
                {
                    $key = ($toLower ? strtolower($pred) : $pred);
                    $a[$key] = $obj;
                }
        }

        return $a;
    }

	/**
	 * Substitutes parameters in a template using double hash sign delimiters and applies MySQL character escaping, for example:
	 *
     *    const sqlDBSetup = 'CREATE DATABASE IF NOT EXISTS `##db##`';
	 *
	 * A corresponding parameter "db" may then be used to provide a replacement that is
	 * transparently substituted.
	 *
	 * @param string $template	The template text
	 * @param array $params		A map with parameter names as keys and their replacements as values
	 * @return string	Parameterized text
	 */
	private function sqlParameterize($template, $params) {
		foreach ($params as $key => $value)
			$template = str_replace("##$key##", mysql_real_escape_string($value, $this->dbSharedConnection), $template);

		return $template;
	}
}

/*
 * Parses geo parameters
 * Adapted from http://tools.wikimedia.de/~magnus/common.php?common_source=geo/geo_param.php
 * (c) 2005, Egil Kvaleberg <egil@kvaleberg.no>, GPL
 *
 * - Made constructor array-based
 * - Added support for "coord" decimal specification (lat, long [,...])
 * - Added is_valid(), is_lat(), is_long()
 * - Added auto-loading of attributes
 * - Removed unused methods
 */
class geo_param {
    var $latdeg;
    var $londeg;

    var $pieces;
    var $error;
    var $coor;
    var $title;
    var $attr;

    /**
     *   Constructor:
     */
    public function geo_param( $pieces )
    {
        $this->pieces = $pieces;
        $this->get_coor();

        if ($this->is_valid())
            $this->get_attr();
    }

    public function is_valid()
    {
        return is_null($this->error);
    }

    public function is_coor( $ns,$ew )
    {
        $ns = strtoupper($ns);
        $ew = strtoupper($ew);
        return (($ns=="N" or $ns=="S") and
            ($ew=="E" or $ew=="W"));
    }

    public static function is_lat($lat)
    {
        return is_numeric($lat) && $lat >= -90 && $lat <= 90;
    }

    public static function is_long($long)
    {
        return is_numeric($long) && $long >= -180 && $long <= 180;
    }


    /**
     *  Get the additional attributes in an associative array
     *  Supports arguments such as "city(pop)" as well as "scale" without prefix
     */
    public function get_attr()
    {
        $a = array();
        while (($s = array_shift($this->pieces))) {
            if (($i = strpos($s,":")) >= 1) {
                $attr = substr($s,0,$i);
                $val = substr($s,$i+1);
                if (($j = strpos($val,"("))
                 && ($k = strpos($val,")"))
                 && ($k > $j)) {
                    $a["arg:".$attr] = substr($val,$j+1,$k-($j+1));
                    $val = substr($val,0,$j);
                }
                $a[$attr] = $val;
            } elseif (intval($s) > 0) {
                $a['scale'] = intval($s);
            }
        }
        $this->attr = $a;
    }

    /**
     *  Private:
     *  Get a set of coordinates from parameters
     */
    private function get_coor( ) {
        if ($i = strpos($this->pieces[0],';')) {
            /* two values seperated by a semicolon */
            $this->coor = array(
                $this->latdeg = substr($this->pieces[0],0,$i),
                $this->londeg = substr($this->pieces[0],$i+1));
            array_shift($this->pieces);
            $latNS = 'N';
            $lonEW = 'E';
            $latmin = $lonmin = $latsec = $lonsec = 0;
        } elseif (isset($this->pieces[3]) && $this->is_coor($this->pieces[1],$this->pieces[3])) {
            $this->coor = array(
                $this->latdeg = array_shift($this->pieces),
                $latNS        = array_shift($this->pieces),
                $this->londeg = array_shift($this->pieces),
                $lonEW        = array_shift($this->pieces));
            $latmin = $lonmin = $latsec = $lonsec = 0;
        } elseif (isset($this->pieces[2]) && isset($this->pieces[5]) && $this->is_coor($this->pieces[2],$this->pieces[5])) {
            $this->coor = array(
                $this->latdeg = array_shift($this->pieces),
                $latmin       = array_shift($this->pieces),
                $latNS        = array_shift($this->pieces),
                $this->londeg = array_shift($this->pieces),
                $lonmin       = array_shift($this->pieces),
                $lonEW        = array_shift($this->pieces));
            $latsec = $lonsec = 0;
        } elseif (isset($this->pieces[3]) && isset($this->pieces[7]) && $this->is_coor($this->pieces[3],$this->pieces[7])) {
            $this->coor = array(
                $this->latdeg = array_shift($this->pieces),
                $latmin       = array_shift($this->pieces),
                $latsec       = array_shift($this->pieces),
                $latNS        = array_shift($this->pieces),
                $this->londeg = array_shift($this->pieces),
                $lonmin       = array_shift($this->pieces),
                $lonsec       = array_shift($this->pieces),
                $lonEW        = array_shift($this->pieces));
        } elseif ($this->is_lat($this->pieces[0]) && $this->is_long($this->pieces[1])) {
            /* decimal specification */
            $this->coor = array(
                $this->latdeg = array_shift($this->pieces),
                $this->londeg = array_shift($this->pieces));
            $latNS = 'N';
            $lonEW = 'E';
            $latmin = $lonmin = $latsec = $lonsec = 0;
        } else {
            # support decimal, signed lat, lon
            $this->error = "Unrecognized format";
            #print $this->error ;
			return;
        }

        if ($this->latdeg >  90 or $this->latdeg <  -90
         or $this->londeg > 180 or $this->londeg < -180
         or $latmin       >  60 or $latmin       <    0
         or $lonmin       >  60 or $lonmin       <    0
         or $latsec       >  60 or $latsec       <    0
         or $lonsec       >  60 or $lonsec       <    0) {
            $this->error = "Out of range";
        }

        $latfactor = 1.0 ;
        $lonfactor = 1.0 ;
        if (isset($latNS) && strtoupper($latNS) == "S") {
            $latfactor = -1.0 ;
            #$this->latdeg = -$this->latdeg;
        }

        if (isset($lonEW) && strtoupper($lonEW) == "W") {
            $lonfactor = -1.0 ;
            #$this->londeg = -$this->londeg;
        }

        # Make decimal degree, if not already
        $latmin += $latsec/60.0;
        $lonmin += $lonsec/60.0;
        if ($this->latdeg < 0) {
            $this->latdeg -= $latmin/60.0;
        } else {
            $this->latdeg += $latmin/60.0;
        }
        if ($this->londeg < 0) {
            $this->londeg -= $lonmin/60.0;
        } else {
            $this->londeg += $lonmin/60.0;
        }
        $this->latdeg *= $latfactor ;
        $this->londeg *= $lonfactor ;
    }
} /* geo_param */

?>

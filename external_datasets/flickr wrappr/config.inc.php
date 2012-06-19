<?php

define('RDFAPI_INCLUDE_DIR','C:/!htdocs/flickrwrappr/rdfapi-php/api/');

define("DBPEDIA_URI_ROOT", "http://dbpedia.org/resource/");

define("FLICKRWRAPPR_HOMEPAGE", "http://www4.wiwiss.fu-berlin.de/flickrwrappr/");
define("FLICKRWRAPPR_PHOTOS_DOC_URI_ROOT", "http://www4.wiwiss.fu-berlin.de/flickrwrappr/photos/");

define("FLICKRWRAPPR_LOCATION_URI_ROOT", "http://www4.wiwiss.fu-berlin.de/flickrwrappr/location/");
define("FLICKRWRAPPR_LOCATION_DATA_URI_ROOT", "http://www4.wiwiss.fu-berlin.de/flickrwrappr/data/photosDepictingLocation/");
define("FLICKRWRAPPR_LOCATION_PAGE_URI_ROOT", "http://www4.wiwiss.fu-berlin.de/flickrwrappr/page/photosDepictingLocation/");

define("FLICKR_TOS_URL", "http://www.flickr.com/terms.gne");

define("SPARQL_ENDPOINT_URL", "http://dbpedia.org/sparql");
define("SPARQL_GRAPH_URI", "http://dbpedia.org");

define("FLICKR_API_KEY", "");

/**
 * Only return images that are licensed under CC-BY, CC-NC, CC-NC-SA or CC-SA and may thus be used for derivative works
 */
define("ONLY_CC_DERIV", true);

/**
 * Maximum number of results to return per language
 * This is technically the number of results per page, but we don't support pagination, so there's just one page
 * Maximum is 30 according to 1.b.iii of flickr TOS, see http://www.flickr.com/services/api/tos/
 */
define("NUM_RESULTS_PER_QUERY", 30);

/**
 * The search will be performed in multiple languages until this minimum count has been satisfied
 */
define("MINIMUM_RESULT_COUNT", 15);

define("SEARCH_RADIUS_KM", 1);

define("MAX_RADIUS_KM", 100);

/* Link generator */
define("MYSQL_HOST", "localhost");
define("MYSQL_DB", "dbpedia_en");
define("MYSQL_USER", "root");
define("MYSQL_PASS", "softwiki");


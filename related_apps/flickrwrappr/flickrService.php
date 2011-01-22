<?php

include ("GeoCalc.class.php");

/**
 * @author   Christian Becker
 */

class flickrService {

    var $apiKey;
    var $numResultsPerQuery;
    var $geoCalc;
	var $errCode;
	var $errMsg;
	var $onlyCCDeriv;

    /**
     * Creates a new flickrService
     *
     * @param   $apiKey
     */
    function __construct($apiKey, $numResultsPerQuery, $onlyCCDeriv) {
        $this->apiKey = $apiKey;
        $this->numResultsPerQuery = $numResultsPerQuery;
		$this->onlyCCDeriv = $onlyCCDeriv;
        $this->geoCalc = new GeoCalc();
    }

   /**
    * @param    $topic      UTF-8
    * @param    $lat        WGS-84 latitude (decimal)
    * @param    $long       WGS-84 longitude (decimal)
    * @param    $radiusKm   Search radius in kilometers
    * @return	Array of photos as returned by the Flickr REST service; enhanced with 'flickrpage' and 'imgsmall' URLs
    */
    function getFlickrPhotos($topic, $lat = null, $long = null, $radiusKm = null) {

        $params = array(
            'api_key'    => $this->apiKey,
            'method'    => 'flickr.photos.search',
            'format'    => 'php_serial',
            'per_page'    => $this->numResultsPerQuery,
		//      'tags'        => str_replace(' ', ',', $label)
		//      'text'        => $topic,
            'sort'        => 'relevance',

			/**
			 * Geo queries require some sort of limiting agent in order to prevent the database from crying. This is basically like the check against "parameterless searches" for queries without a geo component.
			 * A tag, for instance, is considered a limiting agent as are user defined min_date_taken and min_date_upload parameters � If no limiting factor is passed we return only photos added in the last 12 hours (though we may extend the limit in the future).
			 */
			'min_taken_date' => '1800-01-01 00:00:00'
        );
		
		if ($this->onlyCCDeriv)
			$params['license'] = '4,2,1,5'; /* CC-BY, CC-NC, CC-NC-SA, CC-SA */

		if ($topic != '')
			$params['text'] = '"' . $topic . '"';

        if (!is_null($lat) && !is_null($long) && !is_null($radiusKm)) {
            $bbox_dst_lat = $this->geoCalc->getLatPerKm() * $radiusKm;
            $bbox_dst_long = $this->geoCalc->getLonPerKmAtLat($lat) * $radiusKm;

            /* minimum_longitude, minimum_latitude, maximum_longitude, maximum_latitude */
            $params['bbox'] = (($long - $bbox_dst_long) . "," . ($lat - $bbox_dst_lat) .
                        "," .($long + $bbox_dst_long) . "," . ($lat + $bbox_dst_lat));
        }

        $encoded_params = array();

        foreach ($params as $k => $v)
            $encoded_params[] = urlencode($k).'='.urlencode($v);

        $url = "http://api.flickr.com/services/rest/?".implode('&', $encoded_params);
        $rsp = file_get_contents($url);
        $rsp_obj = unserialize($rsp);

        $photos = array();

        if ($rsp_obj && is_array($rsp_obj['photos']['photo']))
            foreach ($rsp_obj['photos']['photo'] as $photo) {
				/* Enhance with URLs */
                $photo['imgsmall'] = 'http://farm' . $photo['farm'] . '.static.flickr.com/' . $photo['server'] . '/' . $photo['id'] . '_' . $photo['secret'] . '_m.jpg';
				$photo['flickrpage'] = 'http://www.flickr.com/photos/' . $photo['owner'] . '/' . $photo['id'];
                array_push($photos, $photo);
            }

		if (empty($photos) && $rsp_obj['stat'] == 'fail') {
			$this->errCode = $rsp_obj['code'];
			$this->errMsg = $rsp_obj['message'];
		}

        return $photos;
    }
}

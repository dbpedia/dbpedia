<?php

include ("config.inc.php");
include ("common.php");

/**
 * @author   Christian Becker
 */

$fhandle = fopen("photoCollections.nt", "w");

$conn = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, true);
mysql_select_db(MYSQL_DB, $conn);
mysql_query("SET NAMES utf8", $conn);
$res = mysql_query("select page_title from page where (page_namespace = 0) and page_is_redirect = 0", $conn);
$ctr = 0;

while ($row = mysql_fetch_assoc($res)) {
    /* Output as N-Triples */
    if ($resource = wikipediaEncode($row['page_title'])) {
    	fwrite($fhandle, "<" . DBPEDIA_URI_ROOT . $resource . "> ");
    	fwrite($fhandle, "<http://dbpedia.org/property/hasPhotoCollection> ");
    	fwrite($fhandle, "<" . FLICKRWRAPPR_PHOTOS_DOC_URI_ROOT . $resource ."> .\n");
    }

   if (++$ctr % 1000 == 0)
   	echo ($ctr . "\n");
}

fclose($fhandle);


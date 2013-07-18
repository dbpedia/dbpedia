<?php

function dump($v, $title = '')
{
    if ($title != '')
        echo ("<h1>$title</h1>");
    echo("<pre>" . htmlspecialchars($v) . "</pre>");
}

function dumpArray($a, $title = '')
{
    dump(print_r($a, true), $title);
}

function parameterize($template, $params)
{
    foreach ($params as $key => $value)
        $template = str_replace("##$key##", $value, $template);
        
    return $template;
}
    
function loadRdf($url)
{
    $context=array('http' => array ('method'=>"GET", 'header'=> 'Accept: application/rdf+xml', ),);
    $xcontext = stream_context_create($context);
    return file_get_contents($url, FALSE, $xcontext);
}

function clientAcceptsRDF()
{
    return (isset($_REQUEST['format']) ? $_REQUEST['format'] == 'rdf' 
				: strstr($_SERVER['HTTP_ACCEPT'], 'application/rdf+xml') !== false);
}

function outputRdf($rdf, $title = 'Serialized RDF')
{
    if (!clientAcceptsRDF())
    {
        dump($rdf, $title);
    }
    else
    {
        ob_clean();
        header("Content-Type: application/rdf+xml;charset=utf-8");
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: " . gmdate("D, d M Y H:i:s", time()));
        echo($rdf);
    }    
}

function outputXml($xml, $title = 'Serialized XML')
{
    if (/*strstr($_SERVER['HTTP_ACCEPT'], 'application/xml') === false && */!(isset($_REQUEST['format']) && $_REQUEST['format'] == 'xml'))
    {
        dump($xml, $title);
    }
    else
    {
        ob_clean();
        header("Content-Type: application/xml;charset=utf-8");
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: " . gmdate("D, d M Y H:i:s", time()));
        echo($xml);
    }    
}

function wikipediaEncode($page_title) {
	$string = urlencode(str_replace(" ","_",trim($page_title)));

	// Decode slash "/", colon ":", as wikimedia does not encode these
	$string = str_replace("%2F","/",$string);
	$string = str_replace("%3A",":",$string);
	return $string;
}


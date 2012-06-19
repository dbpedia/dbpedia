<?php

$file = "opencyc-latest.owl";
$result_file = "opencyc_dbpedia_ontology.rdf";
$dbpedia_url = "dbpedia.org/ontology/";
$opencyc_url = "sw.cyc.com";

if (!$file_handle_a = fopen($result_file, 'a')) {
	die("Cannot open file ($result_file)");
}

$RDF = file_get_contents($file);
$RDF = str_replace('<RDF:', '<RDF_', $RDF);
$RDF = str_replace('<em:', '<em_', $RDF);
$RDF = str_replace('owl:sameAs', 'owl_sameAs', $RDF);   
$RDF = str_replace('rdf:resource', 'rdf_resource', $RDF);
$RDF = str_replace('<owl:', '<owl_', $RDF);
$RDF = str_replace('</owl:', '</owl_', $RDF);



$XML = simplexml_load_string($RDF);
foreach($XML->children() as $child)
{

$dbpedia = "";
$opencyc = "";
$count = $child->owl_sameAs->count();

for ($i = 0; $i <= $count -1; $i++)
{
$element =  (string)$child->owl_sameAs[$i]->attributes()->rdf_resource ;
$check_dbpedia = strpos($element, $dbpedia_url);
$check_opencyc = strpos($element, $opencyc_url);

if($check_dbpedia != FALSE)
{
    $dbpedia = $element;
}

if($check_opencyc != FALSE)
{
    $opencyc = $element;
}

if ($dbpedia != "" AND $opencyc !="")
{
if (fwrite($file_handle_a, "<".$opencyc.">"."\t<http://www.w3.org/2002/07/owl#sameAs>\t"."<".$dbpedia.">\n") === FALSE) {
echo "Cannot write to file ($result_file)";
exit;
}
}

}


}
?>
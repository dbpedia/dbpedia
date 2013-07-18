<?php

// =============== WARNING ===============
// This file uses the global mysql connection.
// Do not use this code with other code that uses mysql.
// =============== WARNING ===============

include ("cluster_config.inc.php");	
include ("sharedFunctions.php");
ob_start();
echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>DBpedia Relationship Finder</title>
<META NAME="Author" CONTENT="Joerg Schueppel">
<META NAME="Revisit" CONTENT="After 20 days">
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">
<meta name="description" content="Connection between two Objects of the Wikipedia Template Extraction">
<meta name="keywords" content="">
<META NAME="Robots" CONTENT="INDEX,FOLLOW">
<META NAME="Language" CONTENT="en">
<link rel="stylesheet" type="text/css" href="cluster_style.css">
<script src="scriptaculous-js/lib/prototype.js" type="text/javascript"></script>
<script src="scriptaculous-js/src/scriptaculous.js" type="text/javascript"></script>
<script src="scripts.js" type="text/javascript"></script>
<script language="JavaScript" type="text/javascript">

function toggle(htmlid,alwaysOn){
  if(document.getElementById(htmlid).style.display=="none") {
   	document.getElementById(htmlid).style.display="block";
   	if (htmlid==\'ignoreOptions\')
   		document.getElementById("moreopt").innerHTML="[-]";
   } 
  else {
  	if (alwaysOn==false) {
	  	document.getElementById(htmlid).style.display="none";
	  	if (htmlid==\'ignoreOptions\')
	  		document.getElementById("moreopt").innerHTML="[+]";
  	}
  }
}

var newObjectList= new Array();
var newPredicateList=new Array();';
//ignorierte Objekte/Pr�dikate in JavaScript Array (fuer Anzeige in Advanced Options)
$ignoredPredicates=array();
$ignoredObjects=array();
for ($t=0;$t<5;$t++) {
	if (isset($_REQUEST['ignoreObject_'.$t])) {
		echo 'newObjectList.push("'.$_REQUEST['ignoreObject_'.$t].'");';
		$ignoredObjects[]=$_REQUEST['ignoreObject_'.$t];
	}
}
for ($t=0;$t<5;$t++) {
	if (isset($_REQUEST['ignorePredicate_'.$t])) {
		echo 'newPredicateList.push("'.$_REQUEST['ignorePredicate_'.$t].'");';
		$ignoredPredicates[]=$_REQUEST['ignorePredicate_'.$t];
	}
}
echo '</script>
</head>
<body style="margin:0px;" onload="ladeQueries(\'ajax.php?f=2&amp;sort=popular\',\'q\');printIgnored();loadProgress(\'ajax.php?f=5\');">
<!--<div id ="progress_OLD" style="position:absolute; top:90px; left:220px; width:740px; height:125px; background-color:#fff; visibility:hidden; text-align:center;vertical-align:middle"> </div>-->
<div id="helpbox" style="display:none;position:absolute;top:3px;right:20%;width:200px;height:400px;">
<table cellpadding="0" cellspacing="0">
	<tr>
	<td>
	<table class="closeheader" style="background-color:white;">
		<tr>
		<td width="150">Help Section</td><td align="right"><a href="javascript:toggle(\'helpbox\',false);" title="close" style="color:white;text-decoration:none;background-color:orange">x</a></td>
		</tr>
	</table>
	</td>
	</tr>
	<tr>
	<td>
	<table style="border: 1px solid #888;background-color:white;width:400px">
		<tr>
		<td>
			<span style="font-weight:bold;color:#E46F29;">Help</span>

			<p>The DBpedia Relationship Finder tries to find connections between two things. The underlying knowledge base is the DBpedia data set (<a href="http://dbpedia.org">DBpedia website</a>). To use the relationship finder you just have to type in two objects in the form. If Javascript is activated in your browser, you will automatically get autocompletion suggestions (usually the entered objects have to correspond to names of articles in the <a href="http://en.wikipedia.org">English Wikipedia</a>). The form also allows you to restrict the number of results and the depth of search - if the connections between the two entered objects are long the search can be very time-consuming. Enjoy using the DBpedia relationship finder!</p>

			<span style="font-weight:bold;color:#E46F29;">Features</span><br>
			<ul>
				<li>usage of the DBpedia Infobox data set, i.e. a lot of information which is stored in Wikipedia in (semi-)structured form is available</li>
				<li>a cluster analysis of the underlying RDF data allows to quickly determine whether a connection between the two given objects exist - furthermore, the relationship finder can even give an upper limit on the length of connection and provide a preview result (which is not guarenteed to be the shortest connection)</li>
				<li>number of results and depth of search can configured</li>
				<li>the advanced settings allow you to ignore objects and properties as you like</li>
				<li>use of AJAX technology - information is loaded in the background without having to reload the entire page</li>
				<li>for each object you can view the information available about it (if the server is under heavy load this can take some time)</li>
				<li>clicking on an object directly takes you to the related DBpedia article</li>
				<li>the queries you made can be stored, which allows you to make interesting connections available for others</li>
				<li>stored queries are cached and thus cause no significant server load</li>
				<li>stored queries can be ordered by popularity and date</li>
			</ul>
		</td>
		</tr>
	</table>
</table>
</div>
<table border="0">
<tr>
    <td width="185" valign="top" rowspan="8">
    	<table style="border:1px solid #FF8040;">
    		<tr>
    		<td style="padding-top:6px;padding-left:5px;" valign="top">	
	    	<span style="background-color:#e4e4e4;border:1px solid #CFCFCF;font-weight:bold;">Previously saved Queries:</span>
	    	<br>
	    	<table width="100%" cellpadding="0" cellspacing="0">
	    		<tr>    	
	    		<td style="background-color:#e4e4e4;border: 1px solid #CFCFCF;"  width="80%">
	    		<a href="javascript:ladeQueries(\'ajax.php?f=2&amp;sort=popular\',\'q\')" title="sort by: most ropular">most popular</a>|<a href="javascript:ladeQueries(\'ajax.php?f=2&amp;sort=time\',\'q\')" title="Sort by: most recent">most recent</a>
	    		</td>
	    		<td width="20%"> </td>
	    		</tr>
	    	</table>
	    	<br>   
    		<div class="queries" id="queries">Loading...</div>
    		</td></tr>
    		<tr><td valign="bottom">	
			<span style="background-color:#e4e4e4;border:1px solid #CFCFCF;font-weight:bold;">More Information:</span> at <a href="http://dbpedia.org"><b>DB</b>pedia.org</a> and in the paper
			<a href="http://www.informatik.uni-leipzig.de/~auer/publication/ExtractingSemantics.pdf">What have Innsbruck and Leipzig in common? Extracting Semantics from Wiki Content.</a>
			<br><br>
			<span style="background-color:#e4e4e4;border:1px solid #CFCFCF;font-weight:bold;">Contact:</span>
			<a href="http://aksw.org">AKSW Workgroup</a> @ BIS / Universit&auml;t Leipzig<br><br>Concept: <a href="http://jens-lehmann.org">Jens Lehmann</a><br>
			Implementation: <a href="mailto:schorsus@gmx.de">J&ouml;rg Sch&uuml;ppel</a>		
   			</td>
			</tr>
		</table>
	</td>
</tr>
<tr>
	<td style="padding-left:5px;" valign="top" height="130"><img src="images/logo.jpg" alt="DBpedia Relationship Finder" width="656" height="84"><br>The Relationship Finder 
	allows to explore the DBpedia dataset to find relationships between two 
	things.<br> Enter two things in the following form to find out how they are 
	related (read more in our <a href="javascript:toggle(\'helpbox\',false);" title="HelpBox">Help and Feature Infobox</a>):<br><br> 
	</td>
</tr>
<tr>
    <td valign="top" height="70">
	    <form action="index.php" method="post" name="formular">
		    <table border="0">
	    	    <tr>
            	    <td style="padding-right:100px;" valign="top">First Object:</td>
                    <td><input type="text" name="firstObject" size="28" class="input" id="inputA" value="'.$_REQUEST['firstObject'].'">
                    <div id="Autocompleter1" class="autocomplete"></div>
                    </td>
                    <td style="padding-left:10px;" align="right">max. Results:</td>
                    <td><select class="input" name="limit" style="width:50px" id="limit">
                    		<option '.(($_REQUEST['limit']==1)?'selected="selected"':'').'>1</option>
							<option '.(($_REQUEST['limit']==10)?'selected="selected"':!(isset($_REQUEST['limit']))?'selected="selected"':'').'>10</option>
							<option '.(($_REQUEST['limit']==20)?'selected="selected"':'').'>20</option>
							<option '.(($_REQUEST['limit']==50)?'selected="selected"':'').'>50</option>
		                    <option '.(($_REQUEST['limit']==100)?'selected="selected"':'').'>100</option>
                    </select>
                
                    </td>
 
	            </tr>
                <tr>
                   	<td valign="top">Second Object:</td>
					<td><input type="text" name="secondObject" size="28" class="input" id="inputB" value="'.$_REQUEST['secondObject'].'">
					<div id="Autocompleter2" class="autocomplete"></div>

					</td>
					<!--<td><input type="radio" name="auto" value="maxd" '.(($_REQUEST['auto']=='maxd')?'checked="checked"':!(isset($_REQUEST['auto']))?'checked="checked"':'').'></td>-->
					<td align="right">max. Distance:</td>
                    <td>
                    
                    <select class="input" name="maxdistance" style="width:50px" id="maxdistance">
                    		<option '.(($_REQUEST['maxdistance']==1)?'selected="selected"':'').'>1</option>
							<option '.(($_REQUEST['maxdistance']==3)?'selected="selected"':'').'>3</option>
							<option '.((($_REQUEST['maxdistance']==5))?'selected="selected"':!(isset($_REQUEST['limit']))?'selected="selected"':'').'>5</option>
							<option '.(($_REQUEST['maxdistance']==10)?'selected="selected"':'').'>10</option>
                    </select>
                    
                    </td>
 <!--<td align="center"><input type="radio" name="auto" value="inc" '.(($_REQUEST['auto']=='inc')?'checked="checked"':'').'></td>
 <td>incremental</td>-->
                </tr>               
                <tr>
                	<td></td>
                	<td></td>
                	<td align="right">Advanced Options:</td>
                	<td align="center"><a id ="moreopt" href="javascript:toggle(\'ignoreOptions\',false);" title="Advanced Options" onclick="new Effect.Highlight(\'moreopt\');">[+]</a></td>
                </tr>
                <tr>
                  	<td></td>
                  	<td>
                  	<input type="submit" value="find relation" class="submitbutton">
                    </td><!--<td colspan="2"><div id="progress" class="queries"> </div></td> onmousedown="ladeQueries(\'ajax.php?f=5&amp;dist=1\',\'p\');"-->
                </tr>
                <tr><td>
                	<div id="ignoreOptions" style="display:none;">
						<table cellpadding="0" cellspacing="0">
							<tr>
							<td>
							<table class="closeheader" style="background-color:white;">
								<tr>
								<td width="150">Advanced Options</td><td align="right"><a href="javascript:toggle(\'ignoreOptions\',false);" title="close" style="color:white;text-decoration:none;background-color:orange">x</a></td>
								</tr>
							</table>
							</td>
							</tr>
							<tr>
							<td>
							<table style="border: 1px solid #888;background-color:white;width:100%">
								<tr>
								<td colspan="2">Ignore Objects:</td>
								<td colspan="2">Ignore Properties:</td>
								<tr>
								<td><input type="text" name="ignoreObject" id="inputC" class="input" size="28"></td>
								<td><input type="button" class="submitbutton" value="add" onclick="addNewObject(document.getElementById(\'inputC\').value,newObjectList)"></td>
								<td><input type="text" name="ignorePredicate" id="inputD" class="input" size="28"></td>
								<td><input type="button" class="submitbutton" value="add" onclick="addNewObject(document.getElementById(\'inputD\').value,newPredicateList)"></td>
								</tr>
								<tr>               			
								<td colspan="2"><div id="Autocompleter3" class="autocomplete"></div></td>
								<td colspan="2"><div id="Autocompleter4" class="autocomplete"></div></td>
								</tr>
								<tr>
								<td colspan="2"><ul id="ignoreObjectList" style="list-style-type:none;margin:0px;padding:0px;"><li></li></ul></td>
								<td colspan="2"><ul id="ignorePredicateList" style="list-style-type:none;margin:0px;padding:0px;"><li></li></ul></td>
								</tr>
							</table>
							</td>
							</tr>
						</table>
					</div>	
                </td></tr>
                <tr><td height="40">					
                	<script type="text/javascript">
					new Ajax.Autocompleter("inputA", "Autocompleter1", "ajax.php?f=1");
					new Ajax.Autocompleter("inputB", "Autocompleter2", "ajax.php?f=1");
					new Ajax.Autocompleter("inputC", "Autocompleter3", "ajax.php?f=1");
					new Ajax.Autocompleter("inputD", "Autocompleter4", "ajax.php?f=1");			
					</script></td></tr>
	        </table>
		</form>
	</td>
</tr>';

if (!isset($_REQUEST['firstObject'])||!isset($_REQUEST['secondObject'])&&(strlen($_REQUEST['firstObject'])==0||strlen($_REQUEST['secondObject'])==0)) {
	echo '<tr><td valign="top"><div id ="resultsbox" style="visibility:visible"><span style="color:gray">Please insert two  objects.</span></div></tr>';
}
if ((strlen($_REQUEST['firstObject'])>0&&strlen($_REQUEST['secondObject'])>0)) {
	echo '<tr><td valign="top" height="30"><div id="progress"><img src="images/kreis.gif" alt="progress..."></div></td></tr>';
	echo '<tr><td valign="top"><div id ="resultsbox" style="visibility:visible"><span style="color:gray;font-size:20px">Results:</span><br>';
	calcConnectionDirectConnection((isset($_REQUEST['saved']))?urlencode($_REQUEST['firstObject']):$_REQUEST['firstObject'],(isset($_REQUEST['saved']))?urlencode($_REQUEST['secondObject']):$_REQUEST['secondObject'],$_REQUEST['limit'],$_REQUEST['maxdistance'],0,$ignoredObjects,$ignoredPredicates,(isset($_REQUEST['fullc']))?true:false);
	echo '</div></td></tr>';
}

echo '<tr><td height="1000"> </td></tr>';
echo '</table>
</body></html>';
/****
 * Hauptfunktion:
 * Bekommt s�mtliche Eingabewerte �bergeben: Ressourcen, Limit, max. Tiefe, Ignorierte Objekte/Praedikate
 * Zuerst �berpr�fung, ob gerade gestellte Anfrage bereits abgespeichert, falls ja, gespeichertes Ausgeben
 * dann Berechnung und Ausgabe der Cluster Tabellen Tiefen
 * Falls Verbindung mit eingegebener Tiefe m�glich: Erstellung des SQL Queries und Ausgabe der Ergebnisse
 * 
 */
function calcConnectionDirectConnection($first,$second,$startlimit,$maxdepth,$depth,$ignoredObjects,$ignoredPredicates,$fullconnection) {
	$time=microtime(true);
	mysql_connect($GLOBALS['host'],$GLOBALS['user'],$GLOBALS['password']);
	mysql_select_db($GLOBALS['db']);
	//fuer alte Links
	if (isset($_GET['maxdepth'])) {
		$maxdepth=$_GET['maxdepth']+1;		
	}
	$foundconnection=false;
	$limit=$startlimit;
	$idcounter=0;
	$htmlcounter=0;
	$saveRow=array();
	//ignorierte Objekte/Praedikate kommen als Array an => Umrechnung in String fuer URL
	for ($i=0;$i<count($ignoredObjects);$i++) {
		$permalinkIgnoreObjects.='&amp;ignoreObject_'.$i.'='.$ignoredObjects[$i];	
	}
	for ($i=0;$i<count($ignoredPredicates);$i++) {
		$permalinkIgnorePredicates.='&amp;ignorePredicate_'.$i.'='.$ignoredPredicates[$i];	
	}
	//Ueberpruefung, ob gegebene Anfrage schon gespeichert ist
	include("queries.inc.php");
	$savedIndex=isSaved($first,$second,$limit,$maxdepth,$depth,$ignoredObjects,$ignoredPredicates);
	//Falls gegebene Anfrage schon gespeichert ist=> Ausgeben
	if (is_int($savedIndex)) {
		$lastdepth=-1;
		for ($i=0;$i<count($queries[$savedIndex]['savedResult']['row']);$i++) {
			echo ($lastdepth!=$queries[$savedIndex]['savedResult']['depth'][$i])?'<table style="border:solid 1px #FF8040;margin-left:2px;"><tr><td style="background-color:#e4e4e4;border:1px solid #CFCFCF;">Distance: '.($queries[$savedIndex]['savedResult']['depth'][$i]+1).'</td></tr>':'';			
			printResults($queries[$savedIndex]['savedResult']['row'][$i],$htmlcounter,$idcounter,$first,$second);
			echo (($queries[$savedIndex]['savedResult']['depth'][$i]!=$queries[$savedIndex]['savedResult']['depth'][$i+1])||!isset($queries[$savedIndex]['savedResult']['depth'][$i+1]))?'</table><br>':'';
			$lastdepth=$queries[$savedIndex]['savedResult']['depth'][$i];
		}
		echo 'This is a cached result. It was saved on '.date('r',$queries[$savedIndex]['saveTime']).'.<br>';
		$queries[$savedIndex]['clickCount']++;
		file_put_contents('queries.inc.php',"<?\n\$queries=".var_export($queries,true).";\n?>");
	}	
	//Falls Anfrage noch nicht gespeichert => Ueberpruefen, ob Verbindung moeglich ist
	//(nur, wenn $usingClusterTable in der Config Datei auf true gesetzt ist)
	else {
		if ($GLOBALS['usingClusterTable']==true && $fullconnection==false) {
			$clusterConSwitch=calcConnectionCluster($first,$second,$maxdepth);
			if (is_Int($clusterConSwitch)) {
				$depth=$clusterConSwitch;
				echo 'We are now searching the complete data set for connections. Meanwhile, you may have a look at a preview result <a href="#" onclick="loadClusterConnection(\'ajax.php?f=6&amp;first='.str_replace("%","__perc__",$first).'&amp;second='.str_replace("%","__perc__",$second).$permalinkIgnoreObjects.$permalinkIgnorePredicates.'\')" title="Load Cluster Connection">here</a>.<br><br>';
				echo '<div id="clusterCon" style="display:none;"></div>';
				echo '<div id="ib_1000" style="position:absolute;top:500px;left:20%;width:200px;height:100px;"></div>';
				#echo ', or maybe you want to <a href="'.substr($_SERVER['PHP_SELF'],0,-strlen($_SERVER['SCRIPT_NAME'])).'index.php?firstObject='.$first.'&amp;secondObject='.$second.'&amp;limit='.$startlimit.'&amp;maxdistance='.$maxdepth.$permalinkIgnoreObjects.$permalinkIgnorePredicates.'&amp;fullc=true&amp;saved=saved">load the full Results</a>?<br><br>';
				
				$fullconnection=true;
			} else
				if ($clusterConSwitch=='notenoughdistance') {
					echo 'For a Preview Result click <a href="#" onclick="loadClusterConnection(\'ajax.php?f=6&amp;first='.str_replace("%","__perc__",$first).'&amp;second='.str_replace("%","__perc__",$second).$permalinkIgnoreObjects.$permalinkIgnorePredicates.'\')" title="Load Cluster Connection">here</a>.<br>';
					echo '<div id="clusterCon" style="display:none;"></div>';
					echo '<div id="ib_0" style="position:absolute;top:500px;left:20%;width:200px;height:100px;"></div>';
				}
		}
	    if ($fullconnection==true || $GLOBALS['usingClusterTable']==false) {
			ob_flush();
			flush();
			do {//Berechnung der Verbindung, falls dieses moeglich ist
				$res=mysql_query(getQuery($depth,$first,$second,$limit,$ignoredObjects,$ignoredPredicates)) or die (mysql_error());
				if (mysql_num_rows($res)>0) {
					$limit=$limit-mysql_num_rows($res);
					$foundconnection=true;
					echo '<table style="border:solid 1px #FF8040;margin-left:2px;"><tr><td style="background-color:#e4e4e4;border:1px solid #CFCFCF;">Distance: '.($depth+1).'</td></tr>';
					while ($row=mysql_fetch_row($res)) {
						printResults($row,$htmlcounter,$idcounter,$first,$second);
						$saveRow['row'][]=$row;
						$saveRow['depth'][]=$depth;
					}
					echo '</table><br>';
				}
				else
					if ($depth==($maxdepth-1)) {
						echo "No Connection Found at max. Distance $maxdepth !<br><br>";	//f�r maximale Tiefe Fehlschlag ausgeben
						#if ($GLOBALS['usingClusterTable']==true)
							#calcConnectionCluster($first,$second,$maxdepth,true);
					}
				$depth++;
			} while ($depth<$maxdepth && $limit>0);
		
			if ($foundconnection==true) {
				//Queries koennen abgespeichert werden, wenn eine Verbindung gefunden wurde	
				echo '<span style="padding-left:2px;">Would you like to <a href="#" title="save Query" onmousedown="saveQuery(\'ajax.php?f=3&amp;first='.str_replace("%","__perc__",$first).'&amp;second='.str_replace("%","__perc__",$second).'&amp;limit='.$startlimit.'&amp;maxdepth='.$maxdepth.$permalinkIgnoreObjects.$permalinkIgnorePredicates.'&amp;depth='.$depth.'\',\''.str_replace('%','__perc__',str_replace('"','__quot__',serialize($saveRow))).'\');">save</a> your query?</span><br>';
				echo '<span style="padding-left:2px;"><div id="save">&nbsp;</div></span><br>';
			}
		}
	}
	echo 'Result obtained in '.(round(microtime(true)-$time,3)).' seconds.<br>';	
}
/****
 * Ausgabefunktion:
 * Bekommt ein Ergebnisarray einer mysql Anfrage (von mysql_fetch_array oder mysql_fetch_row generiert)
 * Au�erdem werden die aktuellen Z�hler f�r die IDs der "Infobox Aufklapp Buttons" und die IDs, welche Ergebnistabelle gerade geschrieben werden soll (Tiefe) uebergeben
 */
function printResults($row,&$htmlcounter,&$idcounter,$first,$second) {
	$k=0;
	$htmlcounter++;
	$save=array();
	
	echo '<tr><td style="padding-left:10px;"><span style="background-color:#e4e4e4;border:1px solid #CFCFCF;"> Result '.$htmlcounter.': </span></td></tr>';
	echo '<tr><td style="padding-left:20px;"><table><tr>';
	for($k=0;$k<count($row);$k++) {
		//Objekte
		if (($k%3==0) || ($k%3==2) ) {
			if (!in_array(array(strtolower(cutBaseUri($row[$k],'wikiBase')),($idcounter-1)),$save)) {
				echo '<td><table>
                                <tr>
                                   <td>';
                                      echo (($row[$k]==$GLOBALS['wikipediaBase'].$first)||($row[$k]==$GLOBALS['wikipediaBase'].$second))?'<span style="font-weight:bold">':'<span>';
                                      echo'<a href="'.$GLOBALS['objectLinkingURL'].cutBaseUri($row[$k],'wikiBase').'">'.getLabel($row[$k]).'</a></span>
                                   </td>
				   				   <td>
                                      <a href="#" title="more Information" onclick="ladeInfobox(\'ajax.php?f=4&amp;actObject='.$row[$k].'&amp;actPredicate='.(($k%3==0)?$row[($k+1)]:$row[($k-1)]).'&amp;k='.$k.'\','.$idcounter.',\''.str_replace('"','',urldecode(cutBaseUri($row[$k],'wikiBase'))).'\');">
                                      <img border="0" src="images/moreKnopf.png" alt="show Details about this Object">
                                      </a><br>
                                   	  <a href="javascript:toggle(\'ignoreOptions\',true);" title="add to Ignore List" onclick="addNewObject(\''.cutBaseUri($row[$k],'wikiBase').'\',newObjectList)">
                                   	  <img border="0" src="images/ignoreKreuzProp.png" alt="ignore this Object" style="padding-left:2px;padding-top:2px;">
                                   	  </a>
                                   </td>
                                </tr>
                                <tr>
                                   <td colspan="2">
                                      <div id="ib_'.$idcounter.'" class="infobox"></div>
                                   </td>
                                </tr>
                            </table>';
				echo '</td>';
				//Zeilenumbruch nach dem 4. Tripel
				//Nach Zeilenumbruch nach links verschieben
				if ((($k%11==0 || $k%23==0) && $k!=0)&& isset($row[($k+1)])) 
					echo '</tr><tr><td colspan="10" align="center" style="padding-left:'.(($k*10)+10).'px;padding-right:30px"><img src="images/conPfeil.png" width="100%" height="26" alt="connects"></td></tr></table><table><tr><td style="padding-left:'.(8*$k).'px;"></td>';							
				else
					$save[]=array(strtolower(cutBaseUri($row[$k],'wikiBase')),$idcounter); //abspeichern, damit ein Objekt nicht direkt 2 mal nacheinander auftaucht 
				$idcounter++;
			}					
		}
		//Praedikate
		if ($k%3==1) {
			$pred=getPredicate($row[($k-1)],$row[$k]);
			echo '<td><table><tr><td align="center">'.str_replace("_"," ",cutBaseUri($pred[0],'predBase'));
			echo ' <a href="javascript:toggle(\'ignoreOptions\',true);" title="add to Ignore List" onclick="addNewObject(\''.cutBaseUri($pred[0],'predBase').'\',newPredicateList)">';
			echo '<img border="0" src="images/ignoreKreuzProp.png" alt="ignore this Property">';
			echo '</a>';
			echo '</td></tr><tr><td>';
			echo ($pred[1]=='leftarrow')?'<img src="images/nachlinksPfeil.png" width="90" height="10"  alt="leftcon">':'<img src="images/nachrechtsPfeil.png" width="90" height="10" alt="rightcon">';
			echo '</td></tr></table></td>';
		}
	}
	echo "</table></td></tr>";	
}
/***
 * Sucht aus der Statementscopy Tabelle das passende Pr�dikat zur �bergebenen ID der Statementscopy Tabelle
 */
function getPredicate($subject,$triple_id) {
	$result=mysql_query("SELECT subject,predicate FROM ".$GLOBALS['copyTableName']." WHERE id=".$triple_id);
	$rows=mysql_fetch_array($result);
	if ($rows[0]==$subject)
		return array($rows[1],'rightarrow');
	else
		return array($rows[1],'leftarrow');
}
/***
 * Sucht uebergebene Ressourcen in der Clustertabelle
 * Berechnet die minimale Tiefe, die benoetigt wird fuer Verbindung
 * Berechnet die maximale Verbindungstiefe (welche auch bei der Clustervorschau dann zu sehen ist)
 */
function calcConnectionCluster($first,$second,$maxdepth) {
	$res=mysql_query("SELECT t1.object, t1.depth, t1.referenced_by_property, t1.referenced_by_object, t1.cluster_id, t2.object, t2.depth, t2.referenced_by_property, t2.referenced_by_object,t2.cluster_id FROM ".$GLOBALS['clusterTableName']." AS t1, ".$GLOBALS['clusterTableName']." AS t2 WHERE t1.object='".$GLOBALS['wikipediaBase'].$first."' AND t2.object='".$GLOBALS['wikipediaBase'].$second."' AND t1.cluster_id=t2.cluster_id");
	$row=mysql_fetch_array($res);
	if (mysql_num_rows($res)>0) {	
		$resCount=mysql_query("SELECT object_count, triple_count FROM ".$GLOBALS['countTableName']." WHERE id='".$row[4]."'");
		$resRow=mysql_fetch_array($resCount);		
		$mindepthcalc=abs(($row[1]-$row[6]));
		#$maxdepthcalc=$row[1]+$row[6];
		$connectedfirst=searchClusterStart($GLOBALS['wikipediaBase'].$first);
		$connectedsecond=searchClusterStart($GLOBALS['wikipediaBase'].$second);
		$shortestPathFound=false;
		do {
			$popA=array_pop($connectedfirst);
			$popB=array_pop($connectedsecond);
			if ($popA==$popB)
				continue;
			else {
				$shortestPathFound=true;
				array_push($connectedfirst,(strlen($popA)==0)?$GLOBALS['wikipediaBase'].$first:$popA);
				array_push($connectedsecond,(strlen($popB)==0)?$GLOBALS['wikipediaBase'].$second:$popB);
			}
		}while($shortestPathFound==false);
		$maxdepthcalc=(count($connectedfirst)+count($connectedsecond))-1;
		if ($mindepthcalc<=$maxdepth) {
			$return=($mindepthcalc>0)?$mindepthcalc-1:$mindepthcalc;
			$isInMainCluster = ($row[4]==0);
			echo '"'.$first.'" and "'.$second.'" can be found in cluster '.$row[4].' '.($isInMainCluster?'(the main cluster)':'(a minor cluster)').' containing '.$resRow[0].' objects and '.$resRow[1].' triples.<br>';
			echo 'Using the paths stored in the cluster table, we determined that the shortest connection between them has a length between '.$mindepthcalc.' and '.$maxdepthcalc.'. (You choosed '.$maxdepth.' as max. Distance.)<br><br>';
		}
		else {
			$return='notenoughdistance';
			echo 'There is no connection between these objects within the chosen distance. Using the paths stored in the cluster table, we determined that the shortest connection between them has a length between '.$mindepthcalc.' and '.$maxdepthcalc.'';
		}
	}
	else {
		echo '<a href="'.$GLOBALS['objectLinkingURL'].$first.'">'.getLabel($GLOBALS['wikipediaBase'].$first).'</a> and <a href="'.$GLOBALS['objectLinkingURL'].$second.'">'.getLabel($GLOBALS['wikipediaBase'].$second).'</a> are not in the same Cluster.<br> There exists no connection between these two objects in the DBpedia infobox data set.<br> The reason is that infoboxes in the corresponding Wikipedia articles do not exist or do not contain information<br>allowing to connect these two objects (note that this may have changed since the data was extracted). <br>';
		$return=false;
	}
	return $return;
}
/***
 * Erstellt SQL Anfrage abhaengig von der Suchtiefe
 * beruecksichtigt max Anzahl der Ergebnisse und ignorierte Objekte/Praedikate
 */
function getQuery($depth,$first,$second,$limit,$ignoredObjects,$ignoredPredicates) {
	// fuer ConnectionTable
	$select="SELECT ";
	for ($i=0;$i<=$depth;$i++) {
		$select.="t".$i.".resource1, t".$i.".triple_id, t".$i.".resource2";
		$select.=($i<$depth)?", ":" ";
	}
	$select.="FROM ";
	for ($i=0;$i<=$depth;$i++) {
		$select.=$GLOBALS['directionConnectionTableName']." AS t".$i;
		$select.=($i<$depth)?", ":" ";
	}
	$select.="WHERE ";
	$select.="(t0.resource1='".$GLOBALS['wikipediaBase'].$first."' ";
	for ($i=1;$i<=$depth;$i++) {
		$select.="AND t".($i-1).".resource2=t".$i.".resource1 ";
	}
	$select.="AND t".$depth.".resource2='".$GLOBALS['wikipediaBase'].$second."') ";
	if ($GLOBALS['excludeDuplicateObjects']==true) {
		for ($i=0;$i<$depth;$i++) {
			for ($j=1;$j<=$depth;$j++) {
				$select.=(($i<$j))?"AND t".$i.".resource2!=t".$j.".resource2 ":"";
			}			
		}	
		for ($i=0;$i<$depth;$i++) {
			for ($j=1;$j<=$depth;$j++) {
				$select.=(($i<$j))?"AND t".$i.".resource1!=t".$j.".resource1 ":"";
			}			
		}
	}
	if (count($ignoredObjects)>0) {
		for ($i=0;$i<$depth;$i++) {
			foreach ($ignoredObjects as $ignored) {
				$select.="AND t".($i).".resource2!='".$GLOBALS['wikipediaBase'].$ignored."' ";
				$select.="AND t".($i+1).".resource1!='".$GLOBALS['wikipediaBase'].$ignored."' ";
			}
		}
	}
	if (count($ignoredPredicates)>0) {
		for ($i=0;$i<=$depth;$i++) {
			foreach ($ignoredPredicates as $ignored) {
				$select.="AND t".$i.".predicate!='".$GLOBALS['propertyBase'].$ignored."' ";
			}
		}
	}
	$select.="LIMIT ".$limit;	
	return $select;	
}


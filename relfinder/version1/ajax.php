<?

// =============== WARNING ===============
// This file uses the global mysql connection.
// Do not use this code with other code that uses mysql.
// =============== WARNING ===============

/****
 * Diese Funktionen werden fuer die AJAX Aufrufe in der index.php ausgefuehrt
 */
include ("cluster_config.inc.php");
include ("sharedFunctions.php");
error_reporting(E_ALL & ~E_NOTICE);
/****
 * Funktion zum Sortieren eines Arrays bei bestimmtem Array Schluessel
 */
function msort($array, $id="clickCount") {
    $temp_array = array();
    while(count($array)>0) {
        $highest_id = 0;
        $index=0;
        foreach ($array as $item) {
            if ($item[$id]>$array[$highest_id][$id]) {
                $highest_id = $index;
            }
            $index++;
        }
        $temp_array[] = $array[$highest_id];
        $array = array_merge(array_slice($array, 0,$highest_id), array_slice($array, $highest_id+1));
    }
    return $temp_array;
}
/****
 * Aus Tripeln fuer Infoboxen, durch das Extraktionsskript nicht geparste Werte (Links) umwandeln in Links
 * Bilder direkt Laden
 * Blanknodes, falls vorhanden werden direkt in einer Untertabelle behandelt ausgegeben
 */
function catchUriandPict($x) {
	//wikipedia Links
	if(preg_match('~^('.$GLOBALS['wikipediaBase'].'.*)~',$x))
		return '<a href="'.$GLOBALS['objectLinkingURL'].cutBaseUri($x,'wikiBase').'" target="_blank">'.urldecode(cutBaseUri($x,'wikiBase')).'</a>';
	//Bilder
	if (preg_match('~^(http://.+(\.'.implode('|',$GLOBALS['pictureFilenames']).')+)$~',$x))
		return (@fopen($x,'r'))? '<a href="'.$x.'" target="_blank"><img src="'.$x.'" width="100"></a>':'';
	
	//Links
	if(preg_match('~^(http://.*)$~U',$x)) 
		return '<a href="'.$x.'" target="_blank">'.$x.'</a>';
	//Wikipedia-interne Links
	$x=preg_replace('~\[\[([^\]]+)\|([^\]]*)\]\]~','<a href="'.$GLOBALS['objectLinkingURL'].'\1" target="_blank">\2</a>',$x);
	$x=preg_replace('~\[\[([^\]]+)\]\]~','<a href="'.$GLOBALS['objectLinkingURL'].'\1" target="_blank">\1</a>',$x);	
	//Blanknodes
	if (preg_match('~^[;]?bn[0-9]*[;]?$~',$x)) {
		$resD=mysql_query("SELECT predicate,object FROM ".$GLOBALS['statementsTableName']." WHERE subject_is='b' AND subject='".$x."'");
		if (mysql_num_rows($resD)>0) {
			$x='<table>';
			while ($rowD=mysql_fetch_array($resD)) {
				$x.='<tr><td>'.cutBaseUri($rowD[0],'predBase').'</td><td>'.catchUriandPict($rowD[1]).'</td></tr>';
			}
			$x.='</table>';
		}
		else
			$x='';
	}	
	//Text
	return $x;
}
function loadProgress() {
		echo ' ';
}
/*****
 * Funktion erzeugt die Autocomplete Listen fuer alle Eingabefelder
 */
function autocomplete() { 
	mysql_connect($GLOBALS['host'],$GLOBALS['user'],$GLOBALS['password']);
	mysql_select_db($GLOBALS['db']);
	$labelsave=array();
	if (isset($_POST['ignorePredicate'])) {	//Praedikate
		$res=mysql_query("SELECT DISTINCT predicate FROM ".$GLOBALS['directionConnectionTableName']." WHERE predicate LIKE '".$GLOBALS['propertyBase'].$_POST['ignorePredicate']."%' LIMIT 20");
		echo '<ul>';
		while ($row=mysql_fetch_array($res)) {
			echo '<li>'.cutBaseUri($row[0],'predBase').'</li>';
		}
		echo '</ul>';
	}	
	else {	
		$auto=(isset($_POST['firstObject']))?$_POST['firstObject']:$_POST['secondObject'];
		$auto=(isset($_POST['ignoreObject']))?$_POST['ignoreObject']:$auto;
		$res=mysql_query("SELECT DISTINCT object,subject FROM ".$GLOBALS['statementsTableName']." WHERE (predicate='rdfs:label' or predicate='http://www.w3.org/2000/01/rdf-schema#label') AND object LIKE '".$auto."%' AND subject NOT LIKE '".$GLOBALS['wikipediaBase']."Category:%' LIMIT 20");
		echo '<ul>';
		while($row=mysql_fetch_array($res)) {
			echo '<li><nobr><span class="informal">'.$row[0].'</span><span style="font-size:1pt;visibility:hidden">'.cutBaseUri($row[1],'wikiBase').'</nobr></span></li>';
			$labelsave[]=$row[1];
		}		
		if (mysql_num_rows($res)<20) {
			$res2=mysql_query("SELECT DISTINCT resource1 FROM ".$GLOBALS['directionConnectionTableName']." WHERE resource1 LIKE '".$GLOBALS['wikipediaBase'].$auto."%' LIMIT ".(20-mysql_num_rows($res)));
			while ($row=mysql_fetch_array($res2)) {
				if (!in_array($row[0],$labelsave)) {
					echo '<li><nobr><span class="informal">'.getLabel($row[0]).'</span><span style="font-size:1pt;visibility:hidden">'.cutBaseUri($row[0],'wikiBase').'</nobr></span></li>';
				}				
			}
		}
		echo '</ul>';		
	}
}
/*****
 * Funktion l�dt die Liste der abgespeicherten Queries
 */
function loadQueries($sortstyle) {
	include('queries.inc.php');
	
	$c=0;
	if (isset($_GET['site'])) {
		$from=$_GET['site'];
		$to=$_GET['site']+9;
	}
	else {
		$from=0;
		$to=9;
	}
	echo '<table width="100%"><tr><td height="200" valign="top">';
	echo '<ul>';
	for ($i=$from;$i<=$to;$i++) {
		$c++;
		if (isset($queries[$i])) {			
			if ($sortstyle=='time')
				$queries=msort($queries,'saveTime');
			else
				$queries=msort($queries);
			for ($j=0;$j<count($queries[$i]['ignoredObjects']);$j++) {
			$ignoredObjects.='&amp;ignoreObject_'.$j.'='.$queries[$i]['ignoredObjects'][$j];	
			}
			for ($j=0;$j<count($queries[$i]['ignoredPredicates']);$j++) {
			$ignoredPredicates.='&amp;ignorePredicate_'.$j.'='.$queries[$i]['ignoredPredicates'][$j];	
			}
			echo '<li><a id="l_'.$c.'" title="saved Connection" href="'.substr($_SERVER['PHP_SELF'],0,-strlen($_SERVER['SCRIPT_NAME'])).'index.php?firstObject='.$queries[$i]['firstObject'].'&amp;secondObject='.$queries[$i]['secondObject'].'&amp;limit='.$queries[$i]['limit'].'&amp;maxdistance='.$queries[$i]['maxdepth'].'&amp;saved=saved';
			#echo ($queries[$i]['auto']=='inc')?'&amp;deep='.$queries[$i]['savedResult']['depth'][0]:'';
			echo $ignoredObjects.$ignoredPredicates.'">';
			echo getLabel($GLOBALS['wikipediaBase'].$queries[$i]['firstObject']).' and '.getLabel($GLOBALS['wikipediaBase'].$queries[$i]['secondObject']);
			echo '<br />(lim:'.$queries[$i]['limit'];
			echo ' maxd:'.$queries[$i]['maxdepth'];
			echo ((count($queries[$i]['ignoredObjects'])||count($queries[$i]['ignoredPredicates']))>0)?' with ignored)':'';
			echo ' visited '.$queries[$i]['clickCount'].'x)';
			
			echo '</a></li>';
			$ignoredObjects='';
			$ignoredPredicates='';
			#onclick="new Effect.Highlight(\'l_'.$c.'\');new Effect.Highlight(\'cachedContent\');new Effect.Highlight(\'cachedHeader\');formular.inputA.value = \''.urlencode($queries[$i]['firstObject']).'\';formular.inputB.value = \''.urlencode($queries[$i]['secondObject']).'\';formular.limit.selectedIndex= \''.$queries[$i]['limitkey'].'\';formular.maxdistance.selectedIndex= \''.$queries[$i]['maxdepthkey'].'\';document.getElementById(\'cachedHeader\').style.visibility= \'visible\';document.getElementById(\'cachedContent\').style.visibility= \'visible\';document.getElementById(\'resultsbox\').style.visibility= \'hidden\';ladeQueries(\'ajax.php?f=7&cached='.$i.'\',\'load\');"
		}
		if ($c==10) {
			echo '</ul></td></tr><tr><td align="right" style="padding-right:10px;">';
			if (isset($queries[($from-10)])) {
			echo '<a href="#e" onclick="ladeQueries(\'ajax.php?f=2&amp;site='.($from-10).'\',\'q\')">&lt;&lt;</a>';
			}
			else echo '&lt;&lt;';
			if (isset($queries[($to+1)])) {
				echo '<a href="#e" onclick="ladeQueries(\'ajax.php?f=2&amp;site='.($to+1).'\',\'q\')">&gt;&gt;</a><br />';
			}
			else echo '&gt;&gt;<br />';
			echo '</td></tr></table>';
			exit;	
		}
	}
}
/****
 * Funktion schreibt die Infobox Inhalte fuer per GET (AJAX) uebergebene Parameter von Ressource und Praedikat, welches zur Verbindung gefuehrt hatte
 */
function loadInfobox() {
	mysql_connect($GLOBALS['host'],$GLOBALS['user'],$GLOBALS['password']);
	mysql_select_db($GLOBALS['db']);
	$site=urlencode(str_replace('\\"','"',cutBaseUri($_GET['actObject'],'wikiBase')));
	if (isset($_GET['actPredicate'])) {
		$resA=mysql_query("SELECT resource1 FROM ".$GLOBALS['directionConnectionTableName']." WHERE triple_id=".$_GET['actPredicate']);
		$rowA=mysql_fetch_array($resA);
	}
	$colormark=($rowA[0]==$GLOBALS['wikipediaBase'].$site)?true:false;
	$resB=mysql_query("SELECT predicate,object FROM ".$GLOBALS['statementsTableName']." WHERE subject='".$GLOBALS['wikipediaBase'].$site."'");
	if (isset($_GET['actPredicate'])) {
		$resC=mysql_query("SELECT predicate FROM ".$GLOBALS['copyTableName']." WHERE id=".$_GET['actPredicate']);
		$rowC=mysql_fetch_array($resC);
	}
	$written=array();
	if (mysql_num_rows($resB)==0) echo '<table style="border: 1px solid #888;"><tr><td>No Infobox Information found for '.$_GET['actObject'].'</td></tr></table>';
	else {
		echo '<table style="border: 1px solid #888;background-color:white;width:300px">';
		
		while ($rowB=mysql_fetch_array($resB)) {
		echo '<tr><td style="padding-left:5px;">';
		$object=catchUriandPict($rowB[1]);
		
		//Pr�dikate		
		if (!in_array(cutBaseUri($rowB[0],'predBase'),$written)&&strlen($object)>0) {
			echo ($colormark==true && $rowB[0]==$rowC[0])?'<span style="color:red">'.cutBaseUri($rowB[0],'predBase').'</span>':cutBaseUri($rowB[0],'predBase');			
			$written[]=cutBaseUri($rowB[0],'predBase');
		}
		//Objekte
		echo '</td><td>'.$object.'</td></tr>';
		}
		echo '</table>';
	}
}
/*****
 * Schreibt die Ergebnisse und Parameter eines Queries, welches gespeichert werden soll in die Datei queries.inc.php
 * abgespeicherte Queries in einem Array ($queries) in dieser Datei
 */
function saveQuery($arrayserialized,$first,$second,$limit,$maxdepth,$depth) {
	include('queries.inc.php');
	$save=unserialize(str_replace('__perc__','%',str_replace('__quot__','"',$arrayserialized)));
	$first=str_replace('__perc__','%',$first);
	$second=str_replace('__perc__','%',$second);
	$ignoredPredicates=array();
	$ignoredObjects=array();
	for ($t=0;$t<5;$t++) {
		if (isset($_REQUEST['ignoreObject_'.$t])) {
			$ignoredObjects[]=$_REQUEST['ignoreObject_'.$t];
		}
	}
	for ($t=0;$t<5;$t++) {
		if (isset($_REQUEST['ignorePredicate_'.$t])) {
			$ignoredPredicates[]=$_REQUEST['ignorePredicate_'.$t];
		}
	}
	if (!isSaved($first,$second,$limit,$maxdepth,$depth,$ignoredObjects,$ignoredPredicates)) {
		$queries[]=array('firstObject'=>$first,'secondObject'=>$second,'limit'=>$limit,'maxdepth'=>$maxdepth,'savedResult'=>$save,'ignoredObjects'=>$ignoredObjects,'ignoredPredicates'=>$ignoredPredicates,'clickCount'=>1,'saveTime'=>microtime(true));		
		file_put_contents('queries.inc.php',"<?\n\$queries=".var_export($queries,true).";\n?>");
		echo '<span style="color:green">Query saved!</span>';
	}
	else echo '<span style="color:red">Query already saved!</span>';
	
}
/****
 * Sucht referenced_by_property aus der Clustertabelle fuer uebergebene Ressource
 */
function getClusterProperty($resource) {
	mysql_connect($GLOBALS['host'],$GLOBALS['user'],$GLOBALS['password']);
	mysql_select_db($GLOBALS['db']);
	$res=mysql_query("SELECT referenced_by_property FROM ".$GLOBALS['clusterTableName']." WHERE object='".$resource."'");
	$row=mysql_fetch_array($res);
	return $row[0];
}
/****
 * Sucht Richtung der Verbindung fuer ubergebene Ressource mit uebergebener Property
 */
function getClusterPropertyDirection($resource,$property) {
	$result=mysql_query("SELECT * FROM ".$GLOBALS['copyTableName']." WHERE subject='".$resource."' AND predicate='".$property."'");
	if (mysql_num_rows($result)>0)
		return 'left';
	else
		return 'right';
}
/***
 * Berechnet und gibt Schnellverbindung mittels Clustertabelle aus 
 * Uebergeben werden nur die Ressourcen
 */
function clusterConnection($first,$second) {
	$first=str_replace("__perc__","%",$first);
	$second=str_replace("__perc__","%",$second);
	$ignoredPredicates=array();
	$ignoredObjects=array();
	for ($t=0;$t<5;$t++) {
		if (isset($_REQUEST['ignoreObject_'.$t])) {
			$ignoredObjects[]=urlencode($_REQUEST['ignoreObject_'.$t]);
		}
	}
	for ($t=0;$t<5;$t++) {
		if (isset($_REQUEST['ignorePredicate_'.$t])) {
			$ignoredPredicates[]=$_REQUEST['ignorePredicate_'.$t];
		}
	}
	echo '<table cellpadding="0" cellspacing="0">
			<tr>
			<td>
			<table class="closeheader" style="background-color:white;">
				<tr>
				<td>Quick Connection with Cluster Table</td><td align="right"><a href="javascript:toggle(\'clusterCon\',false);" alt="close" style="color:white;text-decoration:none;background-color:orange">x</a></td>
				</tr>
			</table>
			</td>
			</tr>
			<tr>
			<td>
			<table style="border: 1px solid #888;background-color:white;width:100%">
				<tr>
				<td>';
	
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
	
	echo '<table><tr>';
	#print_r($ignoredObjects);
	#print_r($connectedsecond);
	for ($i=0;$i<count($connectedfirst);$i++) {
		if ($i==0) {
			echo '<td><a href="'.$GLOBALS['objectLinkingURL'].cutBaseUri($connectedfirst[$i],'wikiBase').'">';
			echo (cutBaseUri($connectedfirst[$i],'wikiBase')==$first)?'<span style="font-weight:bold">'.getLabel($connectedfirst[$i]).'</span>':getLabel($connectedfirst[$i]);
			echo '</a>';
			echo '<a href="#e" onclick="ladeInfobox(\'ajax.php?f=4&amp;actObject='.$connectedfirst[$i].'\',1000,\''.str_replace('"','',urldecode(cutBaseUri($connectedfirst[$i],'wikiBase'))).'\');"><img border="0" src="images/moreKnopf.png" title="show Details about this Object"></a>';			
			echo '</td>';
		}
		else {
			$ignoretest[]=cutBaseUri(getClusterProperty($connectedfirst[$i]),'predBase');
			$ignoretest[]=cutBaseUri($connectedfirst[$i],'wikiBase');
			echo '<td><table><tr><td align="center">'.cutBaseUri(getClusterProperty($connectedfirst[$i]),'predBase').'</td></tr><tr><td>';
			echo (getClusterPropertyDirection($connectedfirst[$i],getClusterProperty($connectedfirst[$i]))=='left')?'<img src="images/nachlinksPfeil.png" width="90" height="10" />':'<img src="images/nachrechtsPfeil.png" width="90" height="10" />';
			echo '</td></tr></table></td>';
			echo '<td><a href="'.$GLOBALS['objectLinkingURL'].cutBaseUri($connectedfirst[$i],'wikiBase').'">'.getLabel($connectedfirst[$i]).'</a>';
			echo '<a href="#e" onclick="ladeInfobox(\'ajax.php?f=4&amp;actObject='.$connectedfirst[$i].'\',1000,\''.str_replace('"','',urldecode(cutBaseUri($connectedfirst[$i],'wikiBase'))).'\');"><img border="0" src="images/moreKnopf.png" title="show Details about this Object"></a>';
			echo '</td>';
		}
	}
	for ($i=(count($connectedsecond)-1);$i>=0;$i--) {
		$ignoretest[]=cutBaseUri(getClusterProperty($connectedsecond[$i]),'predBase');
		$ignoretest[]=cutBaseUri($connectedsecond[$i],'wikiBase');
		echo '<td><table><tr><td align="center">'.cutBaseUri(getClusterProperty($connectedsecond[$i]),'predBase').'</td></tr><tr><td>';
		echo (getClusterPropertyDirection($connectedsecond[$i],getClusterProperty($connectedsecond[$i]))=='left')?'<img src="images/nachlinksPfeil.png" width="90" height="10" />':'<img src="images/nachrechtsPfeil.png" width="90" height="10" />';
		echo '</td></tr></table></td><td><a href="'.$GLOBALS['objectLinkingURL'].cutBaseUri($connectedsecond[$i],'wikiBase').'">';
		echo (cutBaseUri($connectedsecond[$i],'wikiBase')==$second)?'<span style="font-weight:bold">'.getLabel($connectedsecond[$i]).'</span>':getLabel($connectedsecond[$i]);
		echo '</a>';
		echo '<a href="#e" onclick="ladeInfobox(\'ajax.php?f=4&amp;actObject='.$connectedsecond[$i].'\',1000,\''.str_replace('"','',urldecode(cutBaseUri($connectedsecond[$i],'wikiBase'))).'\');"><img border="0" src="images/moreKnopf.png" title="show Details about this Object"></a>';
		echo '</td>';
	}
	echo '</tr></table>';
	echo '		</td>
			</tr>';
			foreach ($ignoretest as $ignore) {
		if (in_array($ignore,$ignoredPredicates)||in_array($ignore,$ignoredObjects))
			echo '<tr><td><span style="color:red">Warning: The preview connection contains elements in your ignore list.</span></td></tr>';
	}
	echo '</table>
			</td>
			</tr>';	
	echo '</table>';
}
//AJAX Funktionsaufrufe
//Autocomplete fuer die 4 Eingabefelder
if ($_GET['f']==1) autocomplete();
//Laden der Queryliste
if ($_GET['f']==2) loadQueries($_GET['sort']);
//Uebergebenes Query abspeichern
if ($_GET['f']==3) saveQuery($_POST['arrayserialized'],$_GET['first'],$_GET['second'],$_GET['limit'],$_GET['maxdepth'],$_GET['depth']);
//Laden der Infobox zu einer Resource
if ($_GET['f']==4) loadInfobox();
//Progressanzeige
if ($_GET['f']==5) loadProgress();
//Cluster Kurzverbindung
if ($_GET['f']==6) clusterConnection($_GET['first'],$_GET['second']);

<?php
//for debugging pipe stderr to stdout 2>&1
echo "<xmp>Starting...\n";

exout("./svnstatus.sh");

exout("./svnupdate.sh  2>&1");

exout("./svnstatus.sh");
//exec('svn status', $arr);
//echo exec('pwd', $arr)."\n";
//exec("#!/bin/bash\n./svnupdatehelper.sh", $arr,  $retval);



function exout($cmd){
	$arr = array();
	$retval = 'not set';
	echo "\n******\ncommand: $cmd\n";
	exec($cmd, $arr,  $retval);
	echo "finished, return value: ".$retval.", output was:\n";
	//print_r($arr);
	foreach($arr as $one){
		echo "".$one."\n";
	}
	
}
/*
$arr = array();
echo "<xmp>Starting svn update\n";
echo exec("ls\n", $arr);
echo "finished, output was:\n";
print_r($arr);
foreach($arr as $one){
	echo "1".$one."\n";
	}
*/

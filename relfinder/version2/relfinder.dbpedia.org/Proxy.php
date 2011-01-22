<?php

$timeout = 30;
$userAgent = $_SERVER['HTTP_USER_AGENT'];

$session = curl_init();

if ($_POST['query']) {
	$postvars = '';
	while ($key = key($_POST)) {
		if (get_magic_quotes_gpc() == 1){
			$postvars .= '&'.$key.'='.urlencode(stripslashes(current($_POST)));
		}else{
			$postvars .= '&'.$key.'='.urlencode(current($_POST));
		}
		next($_POST);
	}
	curl_setopt ($session, CURLOPT_POST, true);
	curl_setopt ($session, CURLOPT_POSTFIELDS, $postvars);
}

curl_setopt($session, CURLOPT_URL, $HTTP_SERVER_VARS['QUERY_STRING']);

curl_setopt($session, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
curl_setopt($session, CURLOPT_CONNECTTIMEOUT, $timeout);
curl_setopt($session, CURLOPT_USERAGENT, $userAgent);

$response = curl_exec($session);

echo $response;

curl_close($session);

?>
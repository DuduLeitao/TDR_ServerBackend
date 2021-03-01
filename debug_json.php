<?php
//$url = 'https://www.google.es';
$url = 'http://192.168.2.250/postcommand/';

function check_URL_alive($url){
	// URL variable must be a string. Example: $url = 'http://www.google.com'

	// Edit all http funtions timeout.
	stream_context_set_default(array('http' => array('timeout' => 3)));

	// Use get_headers() function
	$headers = @get_headers($url);

	// Set all http funtions timeout to default.
	stream_context_set_default(array('http' => array('timeout' => 60)));

	// Use condition to check the existence of URL
	return ($headers && (strpos( $headers[0], '200') or (strpos( $headers[0], '405'))));
}


function send_post($url, $post){
	// $url variable must be a string. Example: $url = 'http://www.google.com'
	// $post variable  must be a json. Example: $post = ['lightbox' => 'switchlight']

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$response = curl_exec($ch);
	curl_close($ch);
	var_dump($response);
}


if (check_URL_alive($url)){
	/*
	$data = array ('user'=>'Dudu');
	$data += ['type' => 'command'];
	$data += ['value' => 'updateGateState'];
	$post = json_encode([$data]);
	*/
	$post = ['lightbox' => 'switchlight'];
	send_post($url, $post);
}else{
	echo 'ERROR: URL not reachable.';
}


?>

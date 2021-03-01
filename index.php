<?php

// Authorized users declaration
$authorizedUsers = array('Dudu', 'Rafa', 'Júúúju', 'Carminha');

$url_gardenLight_module = 'http://192.168.2.250/postcommand/';

date_default_timezone_set('Europe/Madrid');
/* Echoclears the date
     h : 12 hr format
     H : 24 hr format
     i : Minutes
     s : Seconds
     u : Microseconds
     a : Lowercase am or pm
     l : Full text for the day
     F : Full text for the month
     j : Day of the month
     M : Month in letters
     m : month in numbers
     S : Sufix of the day st, nd, rd, etc
     Y : 4 digit year
*/

function write2db($sql){
	$servername = "localhost";
	$username = "dbuser";
	$password = "dbpass";
	$dbname = "TDR";

	// Open a connection
	$conn = new mysqli($servername, $username, $password, $dbname);

	// Check connection
	if ($conn->connect_error) {die("Connection failed: " . $conn->connect_error);}
	if ($conn->query($sql) === TRUE){}
	else{echo "Error: " . $sql . "<br>" . $conn->error;}
	$conn->close();
}

function readDB($sql,$rowName){
	// Create connection
	$servername = "localhost";
	$username = "dbuser";
	$password = "dbpass";
	$dbname = "TDR";
	$conn = new mysqli($servername, $username, $password, $dbname);

	// Check connection
	if ($conn->connect_error) {die("Connection failed: " . $conn->connect_error);}
		$result = $conn->query($sql);
		if($result->num_rows > 0){while($row = $result->fetch_assoc()) {return $row[$rowName];}
	}else{return null;}
	$conn->close();
}


function sendNotification($tokens, $message){
	// Set POST variables
	$url = 'https://fcm.googleapis.com/fcm/send';

	$fields = array(
		'to' => $tokens,
		'data' => $message
		);

	$headers = array(
		'Authorization: key=AAAAAlsfh58:APA91bGMkfZEShK8a0qHiJhSh1dqCPA8HLLMvdRR_iD6_3ogA7N2SEUO53_CGph6uxDiB1FET4M80Nec6zqWDKEY_BdeEta4Sm0MfzcG6gS2Pu-DUWJRRLCM_lcX8-mpVqRbVQn4dZZ0',
		'Content-Type: application/json'
	);

	// Open connection
	$ch = curl_init();

	// Set the url, number of POST vars, POST data
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	// Disabling SSL Certificate support temporarily
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

	// Execute post
	$result = curl_exec($ch);
	if($result === FALSE){
		die('Curl failed: ' . curl_error($ch));
	}

	// Close connection
	curl_close($ch);

	return $result;
}

function checkAction($user, $type, $value){
	global $authorizedUsers;
	if($user == 'Gate'){
		if($value == 'open'){return 'gateOpened';}
		elseif($value == 'close'){return 'gateClosed';}
		else{return 'error';}

	}elseif(in_array($user, $authorizedUsers)){
		if($type == 'command'){
			switch($value){
				case 'open': return 'openGate';
				case 'close': return 'closeGate';
				case 'updateGateState': return 'updateGateState';
				case 'picture': return 'takePicture';
				case 'gardenLightSwitch': return 'switchGardenLight';
				default: return 'error';
			}
		}elseif($type == 'token'){return 'token';}
		else{return 'error';}
	}
	else{return 'error';}
}


function check_URL_alive($url){
        // URL variable must be a string. Example: $url = 'http://www.google.com'

        // Edit all http funtions timeout.
        stream_context_set_default(array('http' => array('timeout' => 1)));

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
	return $response;
        //var_dump($response);
}

// Takes raw data from the request
$json = file_get_contents('php://input');

// Converts the data into a PHP object
$dataj = json_decode($json);

// error_log("DUDU_ERROR: "."String here",0);
//var_dump($dataj);

$user = $dataj->user;
$type = $dataj->type;
$value = $dataj->value;

switch(checkAction($user, $type, $value)){
	case 'gateOpened':
		$data = array ('result'=>'OKgateOpened');
		write2db("UPDATE gate_state SET state='1'");
                //$gateTokens = readDB("SELECT token FROM users","token");
		//$message = array("message"=>"Consejiche mandar notificacións, pájaro! ;)");
		//$messageStatus = sendNotification($gateTokens,$message);
		//error_log("Message status: ".$messageStatus, 0);
		break;
	case 'gateClosed':
		$data = array ('result'=>'OKgateClosed');
		write2db("UPDATE gate_state SET state='0'");
		break;
	case 'openGate':
		$data = array ('result'=>'OK');
		$data += ['gateAction' => 'opening'];
		// "INSERT INTO history (id_history, time, action) SELECT (users.id_user, '".date('Y-m-j H:i:s')."', 'open') FROM users WHERE history.id_history=users.id_user"
		// 'SELECT username, time, action FROM history, users WHERE history.id_history=users.id_user'
		// write2db("INSERT INTO history (user, time, action) VALUES ('".$user."', '".date('Y-m-j H:i:s')."', 'open')");
		// write2db("INSERT INTO history (id_history, time, action) SELECT (users.id_user, '".date('Y-m-j H:i:s')."', 'open') FROM users WHERE users.username='".$user."'");
		//write2db("INSERT INTO history (id_history, time, action) VALUES ((SELECT id_user FROM users WHERE users.username='".$user."'), '".date('Y-m-j H:i:s')."', 'open')");
		//exec("/usr/bin/touch /var/www/tdr/backend/timerFlag.txt");
		exec("/usr/bin/python3 /home/pi/tdrDrivers/gateAction.py");
		break;
	case 'closeGate':
		$data = array ('result'=>'OK');
		$data += ['gateAction' => 'closing'];
		//write2db("INSERT INTO history (id_history, time, action) VALUES ((SELECT id_user FROM users WHERE users.username='".$user."'), '".date('Y-m-j H:i:s')."', 'close')");
		//exec("rm /var/www/tdr/backend/timerFlag.txt");
		exec("/usr/bin/python3 /home/pi/tdrDrivers/gateAction.py");
		break;
	case 'updateGateState':
		$data = array ('result'=>'OK');
		$gateState = readDB("SELECT state FROM gate_state","state");
		if($gateState == 1){
			$data += ['gateState' => 'opend'];
		}
		elseif($gateState == 0){
			$data += ['gateState' => 'closed'];
		}
		else{
			$data += ['gateState' => 'ERROR'];
		}

		if (check_URL_alive($url_gardenLight_module)){
			$post = ['lightbox' => 'lightstate'];

			$response = send_post($url_gardenLight_module, $post);
			if ($response == "ON"){
				$data += ['gardenLightState' => 'on'];
			} elseif ($response == "OFF"){
				$data += ['gardenLightState' => 'off'];
			}

		} else {
			$data += ['gardenLightState' => 'ERROR'];
		}
		break;
	case 'switchGardenLight':
		$data = array ('result'=>'OK');
		if (check_URL_alive($url_gardenLight_module)){
			$post = ['lightbox' => 'switchlight'];
			$response = send_post($url_gardenLight_module, $post);
			$data += ['gardenLightAction' => 'OK'];
		} else {
			$data += ['gardenLightAction' => 'ERROR'];
		}
		break;
	case 'token':
		$data = array ('result'=>'OK');
		write2db("UPDATE users SET token='".$value."' WHERE username='".$user."'");
		break;
	case 'takePicture':
		$data = array ('result'=>'OK');
		$data += ['cameraAction' => 'OK'];
		exec("/usr/bin/python3 /home/pi/tdrDrivers/takePicture.py");
		break;
	case 'error':
		$data = array ('result'=>'ERROR: Problem with the command');
		break;
	default:
		$data = array ('result'=>'ERROR: Action not found');
}

// Send the response data
header('Content-type: text/javascript');
//$array_to_string = implode(", ", $data);
//error_log("DUDU_ERROR: ".$array_to_string,0);
echo json_encode([$data]);
?>

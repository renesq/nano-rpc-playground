<?php
// this middleware mainly pre-validates json requests before they are sent to the NANO node. 

// configuration
$node = 'http://[::1]';  
$nodeport = 7076;
$nodeconnection = CURL_VERSION_IPV6;

// restricting some malicious RPC actions
$actions_forbidden = array(
"stop",
"bootstrap",
"work_peers_clear",
"work_peer_add",
);

// begin CURL abstraction for node communication
function node_request($query)
{
    global $node;
    global $nodeport;
    global $nodeconnection;
    $data_string = json_encode($query);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $node);
    curl_setopt($ch, CURLOPT_PORT, $nodeport);
    curl_setopt($ch, CURLOPT_IPRESOLVE, $nodeconnection);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string)
    ));
    $curlresult = curl_exec($ch);
    if (curl_errno($ch)) {
        // error handling when node is offline
		die('{ "error": "Internal server error. Could not reach node." }');
    }
    else {
        return $curlresult;
    }
}
// end of abstraction

// style output with some css
echo '<html><body style="color:white; background-color:black;white-space:pre;">';

// throw exception if there's no input
if (empty($_POST['jsoninput'])) {
    die('{ "error": "Malformed request. Missing input." }');
}

// parsing json input to an array
$jsonrequest_array = json_decode($_POST['jsoninput'], true);

// throw exception if parsing failed. Possible errors are listed here: https://www.php.net/manual/en/function.json-last-error.php
if (json_last_error_msg () != 'No error') {
    die('{ "error": "Malformed request. ' . json_last_error_msg () . '" }');
}

// throw exception if JSON did not contain a RPC action
if (!array_key_exists('action', $jsonrequest_array)) {
    die('{ "error": "Malformed request. No action recognized." }');
}

// limit draining requests to 25 result elements
if (!array_key_exists('count', $jsonrequest_array)) {
    $jsonrequest_array['count'] = "25";
} elseif (intval($jsonrequest_array['count']) == -1) {
    $jsonrequest_array['count'] = "25";
} elseif (intval($jsonrequest_array['count']) > 25) {
    $jsonrequest_array['count'] = "25";
}

// check if requested action is allowed
if (in_array($jsonrequest_array['action'], $actions_forbidden)) {
    die('{ "error": "Requested RPC action is forbidden on this node." }');
} else {
	// redirect the requested JSON to the node and grab the response
	$response = node_request($jsonrequest_array);
	echo $response;
}

echo '</html>';
?>
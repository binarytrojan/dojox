<?php
// enter your Amazon S3 secret key and access key here:
$accessKey = "access key";
$secretAccessKey = "secret access key";



$TARGET_WS = "http://s3.amazonaws.com";

ob_start();

require_once 'Crypt/HMAC.php';
require_once 'HTTP/Request.php';

$method = $_SERVER["REQUEST_METHOD"];
if ($method == "PUT") {
	$contentType = $_SERVER['CONTENT_TYPE'];
}
else {
	$contentType ='';
}
$resource = str_replace($TARGET_WS, '', $_REQUEST['url']);
$queryIndex = strpos($resource,'?'); // remove the query string
if ($queryIndex) {
	$resource = substr($resource,0,$queryIndex);
}

if (substr($resource,strlen($resource)-1,strlen($resource)) == '/') {
	// remove the last slash
	$resource = substr($resource,0,strlen($resource)-1);
}
$content = file_get_contents('php://input');

$httpDate = gmdate("D, d M Y H:i:s T");
$acl = "private";
$stringToSign = "$method\n\n$contentType\n$httpDate\nx-amz-acl:$acl\n$resource";
$hashObj =& new Crypt_HMAC($secretAccessKey, "sha1");
$signature = hexTob64($hashObj->hash($stringToSign));

$req =& new HTTP_Request($TARGET_WS . $resource);
$req->setMethod($method);
$req->addHeader("content-type", $contentType);
$req->addHeader("Date", $httpDate);
$req->addHeader("x-amz-acl", $acl);
$req->addHeader("Authorization", "AWS " . $accessKey . ":" . $signature);
if ($content != "") {
	$req->setBody($content);
}

$req->sendRequest();

$contentType = $req->getResponseHeader("content-type");
header("content-type: $contentType");
header('HTTP/1.1 ' . $req->getResponseCode() . ' Ok');

ob_end_flush();

$content = $req->getResponseBody();
if ($content) {
	print($content);
}
else {
	print("\"success\"");
}

function hexTob64($str) {
    $raw = '';
    for ($i=0; $i < strlen($str); $i+=2) {
        $raw .= chr(hexdec(substr($str, $i, 2)));
    }
    return base64_encode($raw);
}

?>
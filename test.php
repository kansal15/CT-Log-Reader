<?php
include_once("asn1/ASN1X509.class.php");
require 'tldextractphp/tldextract.php';
include_once("ctreader.class.php");
error_reporting(E_ERROR | E_PARSE);
//quick example

$known_logs = "https://www.gstatic.com/ct/log_list/v2/log_list.json";
$json = file_get_contents($known_logs);
$json_data = json_decode($json, true);
#var_dump($json_data);

class CTParser extends CTReader
{
	var $current = array() ;

	public function parseCert($cert_pem)
	{
	#	$parsed = openssl_x509_parse($cert_pem);
	#	echo json_encode($parsed['subject']['CN'])."\n";
		$file = new ASN1X509();
		$file->loadPEM($cert_pem);
		$details = array();
		//$details['version'] = $file->getVersion();
		//$details['serial'] = $file->getSerialNumber();
		//$details['signatureType'] = $file->getSignatureType();
		//$details['issuer'] = $file->getIssuer();
		//$details['validDates'] = $file->getValidDates();
		$details['subject'] = $file->getSubject();
		//$details['publicKey'] = $file->getPublicKeyInfo();
		//$details['extensionInfo'] = $file->getExtensionInfo();
		//$details['signatureInfo'] = $file->getSignatureInfo();
#		print_r($details);
	}
}
$log_server = array();
$url_array = array();
$filename = "testjson" ;

#$file = test.json ;
#$string = '';
#file_put_contents($file, $string);   # emptying the file 


#foreach($array as $last_status) {
#	$status = explode(":",$last_status);
#	$logid_status[$status[0]] = $status[1];
#
#}

foreach($json_data['operators'] as $operators) {
#	if (!file_exists($operators['name'])) {
 #	 mkdir($operators['name'], 0777, true);
#}
	if($operators['name'] != 'Google') { continue ; }
$log_server[$operators['name']] = array() ;
$status = array();
	foreach($operators['logs'] as $server) {
		$pid = pcntl_fork();
      if (!$pid) {
###		if($server['url'] == 'https://ct.googleapis.com/logs/argon2020/') { ###1
#		echo $server['url'];
		$file = $server['log_id'].".status" ;
                $file = str_replace("=","_",$file);
		$fp = @fopen($file, 'r');
if ($fp) {
   $array = explode(":", fread($fp, filesize($file))); #getting previously parsed locatiion
}
#	var_dump($array);	
	$start = $array[1];
		$ct = new CTReader($server['url'],$start,$server['log_id']);
		#$status_new = $ct->downloadNextRange($operators['name'],$i);//grab first 2000		#$ct->parseFileList();//parse first 2000
		$status_new = $ct->downloadAll($operators['name']);//grab first 2000		#$ct->parseFileList();//parse first 2000
           exit($server);
	}

###}  ### 1
	}
}


<?php
include_once("ctreader.class.php");
error_reporting(E_ERROR | E_PARSE);
//quick example

$known_logs = "https://www.gstatic.com/ct/log_list/v2/log_list.json";
$json = file_get_contents($known_logs);
$json_data = json_decode($json, true);

class CTParser extends CTReader
{
	public function parseCert($cert_pem)
	{
		$parsed = openssl_x509_parse($cert_pem);
		echo json_encode($parsed['subject']['CN'])."\n";
		//$file = new ASN1X509();
		//$file->loadPEM($pem);
		//$details = array();
		//$details['version'] = $file->getVersion();
		//$details['serial'] = $file->getSerialNumber();
		//$details['signatureType'] = $file->getSignatureType();
		//$details['issuer'] = $file->getIssuer();
		//$details['validDates'] = $file->getValidDates();
		//$details['subject'] = $file->getSubject();
		//$details['publicKey'] = $file->getPublicKeyInfo();
		//$details['extensionInfo'] = $file->getExtensionInfo();
		//$details['signatureInfo'] = $file->getSignatureInfo();
		//print_r($details);
	}
}
$i=0;
foreach($json_data['operators'] as $operators) {
	echo "Downloading data from".$operators['name']."logs\n";
	echo "Number if Know CTL of ".$operators['name']." are ".sizeof($operators['logs'])."\n" ;
	echo "\n";
	foreach($operators['logs'] as $server) {

		$ct = new CTReader($server['url']);
		$ct->downloadAll($operators['name']);//loop and fetch 2000 at a time
#		$ct->downloadNextRange($operators['name'],$i);//grab first 2000
#		$ct->parseFileList();//parse first 2000

	}}
		$ct = new CTParser($server['url']);
		$ct->parseFileList();

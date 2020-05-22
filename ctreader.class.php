<?php
/**
 * Certificate Transparenct CTLog Reader, 
 * for parsing SSL X509 certificates from a ctlog:
 * http://www.certificate-transparency.org/
 *
 * @author     mk-j
 * @license    http://opensource.org/licenses/MIT
 * @link       https://github.com/mk-j/PHP_CT_Reader
*/
class CTReader
{
	private $ct_url='';//see: http://www.certificate-transparency.org/known-logs
	private $download_step=1000;
	var $start = 0 ;
	var $tlds = array();
	private $log_id = ''; 
	public function __construct($ct_url,$start,$id)
	{
		$this->start = $start ;
		$this->log_id = $id ;
        	$this->download_step = 1000;
        	//different ct logs have different batch sizes
        	if (strpos($ct_url, 'digicert')!==false ) { $this->download_step=64;   }

		$this->ct_url = rtrim($ct_url, "/");
	}

	public function getMax()
	{
		$url = $this->ct_url.'/ct/v1/get-sth';
		$contents = file_get_contents($url);
		if (($parsed = json_decode($contents,true))!==false)
		{
#			file_put_contents("php://stderr", "log_size:{$parsed['tree_size']}\n");
			return $parsed['tree_size'];//$parsed = array('tree_size'=>, 'timestamp'=>, 'sha256_root_hash'=>, 'tree_head_signature'=>,);
		}
		return 0;
	}
	
	public function downloadNextRange($name)
{
	 	                $total = $this->getMax();
		$from=$this->start;
		$until=$from+$this->download_step-1;
            $from = $from;
	    $until=$from+$this->download_step-1;
#		for($i=$start; $i<=4*$until; $i+=$this->download_step)

	    $url = $this->ct_url.'/ct/v1/get-entries?start='.urlencode($from).'&end='.urlencode($until);
	    $json = file_get_contents($url);
	    if (($r = json_decode($json,true))!==false)
                                {       
                                        foreach($r['entries'] as $entry)
                                        {       
                                                $this->parseEntry($entry);
                                        }
                                }
#     	echo "Child $from $until completed\n";
    

	}

	public function downloadAll($name)
	{
		$max = $this->getMax();
		$from=$this->start;
		for($i=$from; $i<$max; $i+=$this->download_step)
		{
			$this->start=$i ;
			$until = $i + $this->download_step ;
#     	echo $this->log_id." \t start =".$this->start." End=".$until." Total=".$max."\n";
			$this->downloadNextRange($name);
                $string = $this->log_id.":".$until."\n" ;
                $file = $this->log_id.".status" ;
		$file = str_replace("=","_",$file);
                $f=fopen($file,'w+');
                fwrite($f,$string);
                fclose($f);


		}
	}	

	public function parseFileList($i)
	{
		$files = glob($i."/*.gz");
		foreach($files as $filename)
		{
		var_dump($filename);		
	file_put_contents("php://stderr", "reading file: $filename\n");
			if (($fd = fopen("compress.zlib://$filename","r"))!==false)
			{
				$f = "";
				while (!feof($fd)) { $f.=fread($fd, 1024); }
				if (($r = json_decode($f,true))!==false)
				{
					foreach($r['entries'] as $entry)
					{
						$this->parseEntry($entry);
					}
				}
			}
		}
	}

	public function parseEntry($entry)
	{    $tldslocal = $this->tlds ;
             $matches = array();
             $matches2 = array();
	     $merkleTreeLeaf = base64_decode( substr($entry['leaf_input'], 0, 16) );
             $entryType = ord(substr($merkleTreeLeaf, 10, 1)) *256 +ord(substr($merkleTreeLeaf, 11, 1));
            if ($entryType==0) //x509_entry
            {
                $length_bytes = base64_decode( substr($entry['leaf_input'], 16, 4) );
                $cert_length = current(unpack("N", "\x00".$length_bytes));
                $bin = base64_decode( substr($entry['leaf_input'], 20) );
                $leaf_cert = base64_encode( substr($bin, 0, $cert_length) );
                $cert_pem = "-----BEGIN CERTIFICATE-----"."\r\n".chunk_split($leaf_cert)."-----END CERTIFICATE-----"."\r\n";
		
		$ext = openssl_x509_parse($cert_pem);
#		echo $ext['serialNumberHex'];
		$word  = '.gov.in';
		$word2  = '.nic.in';
		$matches  = str_replace("DNS:","",$ext['extensions']['subjectAltName']);
		$domains_array = explode(",",$matches);
		foreach ($domains_array as $domain) { 
			$components = tldextract($domain);
			#echo $components->subdomain; 
			#echo $components->domain;
			 
			if (array_key_exists($components->tld,$tldslocal)) {
                             $tldslocal[$components->tld] = $tldslocal[$components->tld]+1 ;
                                }
                        else {
                            $tldslocal[$components->tld] = 1 ;
                                }
			$this->tlds = $tldslocal ;
			echo "\033[2J\033[;H";
                        print_r($this->tlds);   
		}
#		var_dump($domains_array);
#		if (preg_match("/\.gov\.in/i", $matches)) {
#		$this->parseCert($cert_pem);	
#		}
            }
            else if ($entryType==1) //precertEntry
            {
             	
		   $xtra = base64_decode($entry['extra_data']);//extract full leaf cert from extra_data
                $length_bytes = substr($xtra, 0, 3);
                $cert_length = current(unpack("N", "\x00".$length_bytes));
                $leaf_cert = base64_encode( substr($xtra, 3, $cert_length) );
                $cert_pem = "-----BEGIN CERTIFICATE-----"."\r\n".chunk_split($leaf_cert)."-----END CERTIFICATE-----"."\r\n";
		$ext = openssl_x509_parse($cert_pem);
#		$matches = $ext['extensions']['subjectAltName'] ;
#		echo $ext['serialNumberHex'];
		$word  = '.gov.in';
		$word2  = '.nic.in';
                #$matches  = $ext['extensions']['subjectAltName'];
		$matches  = str_replace("DNS:","",$ext['extensions']['subjectAltName']);
		$domains_array = explode(",",$matches);
               foreach ($domains_array as $domain) {
                        $components = tldextract($domain);
                        #echo $components->subdomain;
                        #echo $components->domain;
                        if (array_key_exists($components->tld,$tldslocal)) {
			     $tldslocal[$components->tld] = $tldslocal[$components->tld] + 1 ;
				}
			else {
			    $tldslocal[$components->tld] = 1 ;
				}
			                        $this->tlds = $tldslocal ;

			echo "\033[2J\033[;H";
			print_r($tlds);
                }

#		 var_dump($domains_array);

		// The "i" after the pattern delimiter indicates a case-insensitive search
#		if (preg_match("/\.gov\.in/i", $matches)) {
 #               $this->parseCert($cert_pem);
  #          }
	}
            else
            {
                file_put_contents("php://stderr", "unable to parse ctlog entry\n");
            }
        }

	public function parseCert($cert_pem)
	{
		$file = new ASN1X509();
                $file->loadPEM($cert_pem);
                $details = array();
                //$details['version'] = $file->getVersion();
                $details['serial'] = $file->getSerialNumber();
                //$details['signatureType'] = $file->getSignatureType();
                //$details['issuer'] = $file->getIssuer();
                //$details['validDates'] = $file->getValidDates();
                $details['subject'] = $file->getSubject();
                //$details['publicKey'] = $file->getPublicKeyInfo();
                //$details['extensionInfo'] = $file->getExtensionInfo();
                //$details['signatureInfo'] = $file->getSignatureInfo();
		file_put_contents("certs/".$details['serial'].".pem",$cert_pem) ;
	}
}

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

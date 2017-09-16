<?php
/* 
 * XCoin API-call related functions
 *
 * @author	btckorea
 * @date	2014-12-30
 */

class XCoinAPI {
	protected $api_url = "https://api.bithumb.com";

	protected $api_key;
	protected $api_secret;

	public function __construct($api_key, $api_secret) {
		$this->api_key = $api_key;
		$this->api_secret = $api_secret;

	}
	
	private function usecTime() 
	{
		list($usec, $sec) = explode(' ', microtime());
		$usec = substr($usec, 2, 3); 
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			return $sec.$usec;
		}
		return intval($sec.$usec);
	}

	private function request($strHost, $strMemod='GET', $rgParams=array(), $httpHeaders=array())
	{
		$ch = curl_init();

		// SSL: 여부
		if(stripos($strHost, 'https://') !== FALSE) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		}

		if(strtoupper($strMemod) == 'HEAD') {
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'HEAD' );
			curl_setopt( $ch, CURLOPT_HEADER, 1 );
			curl_setopt( $ch, CURLOPT_NOBODY, true );
			curl_setopt( $ch, CURLOPT_URL, $strHost );
		}
		else {
			// POST/GET 설정
			if(strtoupper($strMemod) == 'POST') {
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_URL, $strHost);
				curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($rgParams));
			}
			else {
				curl_setopt($ch, CURLOPT_URL, $strHost . ((strpos($strHost, '?') === FALSE) ? '?' : '&') . http_build_query($rgParams));
			}
			curl_setopt($ch, CURLOPT_HEADER, 0);
		}
		if(isset($httpHeaders) && is_array($httpHeaders) && !empty($httpHeaders)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);

		if( !$response = curl_exec($ch) ) {
			$response = curl_error($ch);
		}
		curl_close($ch);

		return $response;
	}

	private function _getHttpHeaders($endpoint, $rgData, $apiKey, $apiSecret)
	{

		$strData	= http_build_query($rgData);
		$nNonce		= $this->usecTime();
		return array(
			'Api-Key: ' . $apiKey,
			'Api-Sign:' . base64_encode(hash_hmac('sha512', $endpoint . chr(0) . $strData . chr(0) . $nNonce, $apiSecret)),
			'Api-Nonce:' . $nNonce
		);
	}


	public function xcoinApiCall($endpoint, $params=null) {
		
		$rgParams = array(
				'endpoint'	=> $endpoint
		);

		if($params) {
			$rgParams = array_merge($rgParams, $params);
		}

		$api_host		= $this->api_url . $endpoint;
		$httpHeaders	= $this->_getHttpHeaders($endpoint, $rgParams, $this->api_key, $this->api_secret);
		
		$rgResult = $this->request($api_host, 'POST', $rgParams, $httpHeaders);
		$rgResultDecode = json_decode($rgResult);


		return $rgResultDecode;

	}

}

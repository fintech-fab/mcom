<?php
/**
 * Created by PhpStorm.
 * User: popov
 * Date: 08.12.14
 * Time: 15:20
 */

namespace FintechFab\Payments\RuRuPayment;


abstract class RuRuRequest extends RuRu
{
	protected $resultHttpCode;

	abstract public function doRequest($transactionId, $params = null);

	// получение описания ошибки
	abstract public function getResponseErrorInfo();

	public function __construct($apiUrl, $spId, $secretKey)
	{
		$this->secretKey = $secretKey;
		$this->spId = $spId;
		$this->apiUrl = $apiUrl;
	}

	protected function request($parameters, $soapAction)
	{
		$curl = curl_init($this->apiUrl);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 20);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_ENCODING, 'utf-8');
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
		//curl_setopt($curl, CURLOPT_VERBOSE, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'SOAPAction:"http://ruru.ru/serviceprovider/ITransactionService/' . $soapAction . '"',
			'Content-Type: text/xml;charset=utf-8',
			'Expect:'
		));

		$response = curl_exec($curl);

		$this->resultHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);

		if ($response && $this->resultHttpCode == '200') {
			return $response;
		}

		$this->setError(self::ERROR_HTTP);


		return false;
	}

	public function getHttpCode()
	{
		return $this->resultHttpCode;
	}


	protected function clearXml($responseText)
	{
		// удалим из XML информацию о SOAP для его успешного парсинга как обычного XML
		if (preg_match_all('/xmlns:([a-z]+)=/ui', $responseText, $matches)) {
			foreach ($matches as $key=>$submatch) {
				if ($key == 0) {
					continue;
				}
				foreach ($submatch as $match) {
					$responseText = preg_replace('/<'.$match.':/ui', '<', $responseText);
					$responseText = preg_replace('/<\/'.$match.':/ui', '</', $responseText);
				}
			}
		}


		return $responseText;
	}
}
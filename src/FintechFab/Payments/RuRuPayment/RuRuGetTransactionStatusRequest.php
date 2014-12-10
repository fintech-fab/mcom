<?php
/**
 * Created by PhpStorm.
 * User: popov
 * Date: 08.12.14
 * Time: 10:49
 */

namespace FintechFab\Payments\RuRuPayment;


use Exception;

class RuRuGetTransactionStatusRequest extends RuRuRequest
{
	public $errorCode = 0;

	public $transactionId = null;

	public $signature;

	public $reason;

	public $description;

	public function __construct($apiUrl, $spId, $secretKey)
	{
		$this->secretKey = $secretKey;
		$this->spId = $spId;
		$this->apiUrl = $apiUrl;
	}

	/**
	 * @param int  $transactionId ID транзакции в RuRu
	 * @param null $params
	 */
	public function doRequest($transactionId, $params = null)
	{
		// собираем подпись из данных
		$signature = $transactionId . $this->spId;
		$signature = base64_encode(hash_hmac('sha1', $signature, base64_decode($this->secretKey), true));

		$requestParameters =
			'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://ruru.ru/serviceprovider/">' .
			'<soapenv:Header/>' .
			'<soapenv:Body>' .
			'<ser:GetTransactionStatus>' .
			'<ser:trnId>' . $transactionId . '</ser:trnId>' .
			'<ser:spId>' . $this->spId . '</ser:spId>' .
			'<ser:signature>' . $signature . '</ser:signature>' .
			'</ser:GetTransactionStatus>' .
			'</soapenv:Body>' .
			'</soapenv:Envelope>';

		$response = $this->request($requestParameters, 'GetTransactionStatus');

		if ($this->isHttpError()) {
			return;
		}

		$this->parseResponse($response);
		$this->checkSignature();
	}

	public function parseResponse($responseText = null)
	{
		if ($responseText === null) {
			return;
		}
		// удалим из XML информацию о SOAP для его успешного парсинга
		$responseText = str_replace('s:', '', $responseText);
		$responseXml = @simplexml_load_string($responseText);

		try {
			$initResult = $responseXml->Body->GetTransactionStatusResponse->GetTransactionStatusResult;

			$this->errorCode = (string)$initResult->ErrorCode;
			$this->transactionId = (string)$initResult->TransactionId;
			$this->description = (string)$initResult->Description;
			$this->reason = (string)$initResult->Reason;
			$this->signature = (string)$initResult->Signature;
		} catch (Exception $e) {
			$this->setError(self::ERROR_UNKNOWN);

			return;
		}

		if ($this->errorCode != self::SUCCESS) {
			$this->error = $this->errorCode;
		}
	}

	public function checkSignature()
	{
		$string = $this->transactionId . $this->errorCode . $this->reason . $this->description;
		$signature = $this->generateSignature($string);

		$result = ($signature === $this->signature);

		if (!$result) {
			$this->setError(self::ERROR_SIGNATURE);
		}

		return $result;
	}

	public function getResponseErrorInfo()
	{
		if ($this->errorCode > 0) {
			return $this->description;
		}

		return false;
	}
}
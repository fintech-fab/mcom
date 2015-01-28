<?php
/**
 * Created by PhpStorm.
 * User: popov
 * Date: 08.12.14
 * Time: 10:49
 */

namespace FintechFab\Payments\RuRuPayment;


use Exception;

class RuRuNoticeRequest extends RuRuRequest
{
	const OPERATION_COMPLETE = 0; // Операция на стороне ТСП успешно завершена
	const OPERATION_ERROR = 1; // Операция на стороне ТСП не может быть завершена
	const OPERATION_TEMP_BLOCKED = 2; // Операция на стороне ТСП временно заблокирована

	/**
	 * Код ошибки RuRu
	 *
	 * @var int
	 */
	public $errorCode = 0;

	/**
	 * Описание ошибки
	 *
	 * @var null
	 */
	public $info = null;

	/**
	 * ID транзакции в RuRu
	 *
	 * @var null
	 */
	public $id = null;

	/**
	 * ЭЦП
	 *
	 * @var
	 */
	public $signature;

	public function __construct($apiUrl, $spId, $secretKey)
	{
		$this->secretKey = $secretKey;
		$this->spId = $spId;
		$this->apiUrl = $apiUrl;
	}

	/**
	 * @param int  $transactionId    номер транзакции в ТСП (не в RuRu!)
	 * @param null $params           array(
	 *                               ruRuTransactionId - номер транзакции в RuRu
	 *                               type - тип уведомления Notice
	 *                               0    Операция на стороне ТСП успешно завершена
	 *                               1    Операция на стороне ТСП не может быть завершена
	 *                               2    Операция на стороне ТСП временно заблокирована
	 *                               )
	 *
	 * @return null
	 */
	public function doRequest($transactionId, $params = null)
	{
		if (empty($params) || !is_array($params)) {
			return null;
		}

		$trnId = $params['ruRuTransactionId'];
		$type = $params['type'];
		$dt = $params['dt'];
		$receiver = $params['receiver'];
		$account = $params['account'];
		$rate = $params['rate'];

		$parameters =
			(!empty($dt) ? '<ser:Parameter><ser:Name>dt</ser:Name><ser:Value>' . $dt . '</ser:Value></ser:Parameter>' : '') .
			(!empty($receiver) ? '<ser:Parameter><ser:Name>receiver</ser:Name><ser:Value>' . $receiver . '</ser:Value></ser:Parameter>' : '') .
			(!empty($account) ? '<ser:Parameter><ser:Name>account</ser:Name><ser:Value>' . $account . '</ser:Value></ser:Parameter>' : '') .
			(!empty($rate) ? '<ser:Parameter><ser:Name>rate</ser:Name><ser:Value>' . $rate . '</ser:Value></ser:Parameter>' : '');

		// собираем подпись из данных
		$signature = $trnId . $transactionId;
		$signature = base64_encode(hash_hmac('sha1', $signature, base64_decode($this->secretKey), true));

		$requestParameters =
			'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://ruru.ru/serviceprovider/">' .
			'<soapenv:Header/>' .
			'<soapenv:Body>' .
			'<ser:Notice>' .
			'<ser:trnId>' . $trnId . '</ser:trnId>' .
			'<ser:spTrnId>' . $transactionId . '</ser:spTrnId>' .
			'<ser:spId>' . $this->spId . '</ser:spId>' .
			'<ser:type>' . $type . '</ser:type>' .
			(!empty($parameters) ?
				'<ser:parameters>' . $parameters .
				'</ser:parameters>' : '') .
			'<ser:signature>' . $signature . '</ser:signature>' .
			'</ser:Notice>' .
			'</soapenv:Body>' .
			'</soapenv:Envelope>';


		$response = $this->request($requestParameters, 'Notice');

		if ($this->isHttpError()) {
			return;
		}

		$this->parseResponse($response);
		$this->checkSignature();

		return;
	}

	public function parseResponse($responseText = null)
	{
		if ($responseText === null) {
			return;
		}
		// удалим из XML информацию о SOAP для его успешного парсинга как обычного XML
		$responseText = $this->clearXml($responseText);
		$responseXml = @simplexml_load_string($responseText);

		try {
			$initResult = $responseXml->Body->NoticeResponse->NoticeResult;

			// преобразуем элементы XML к строкам
			$this->errorCode = (string)$initResult->ErrorCode;
			$this->id = (string)$initResult->Id;
			$this->info = (string)$initResult->Info;
			$this->signature = (string)$initResult->Signature;

		} catch (Exception $e) {
			$this->setError(self::ERROR_UNKNOWN);

			return;
		}

		if ($this->errorCode != self::SUCCESS) {
			$this->error = $this->errorCode;
		}

		return;
	}

	public function checkSignature()
	{

		$string = $this->id . $this->errorCode . $this->info;
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
			return $this->info;
		}

		return false;
	}
}
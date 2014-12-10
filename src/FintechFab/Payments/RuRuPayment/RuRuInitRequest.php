<?php
/**
 * Created by PhpStorm.
 * User: popov
 * Date: 08.12.14
 * Time: 10:49
 */

namespace FintechFab\Payments\RuRuPayment;


use Exception;

class RuRuInitRequest extends RuRuRequest
{
	/**
	 * Код ошибки RuRu
	 *
	 * @var int
	 */
	public $errorCode = 0;

	/**
	 * Дополнительная информация
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

	/**
	 * Таймаут резервирования (минуты)
	 *
	 * @var
	 */
	public $timeOut1;

	/**
	 * Таймаут покупки (минуты)
	 *
	 * @var
	 */
	public $timeOut2;

	public function __construct($apiUrl, $spId, $secretKey)
	{
		$this->secretKey = $secretKey;
		$this->spId = $spId;
		$this->apiUrl = $apiUrl;
	}

	/**
	 * @param int  $transactionId номер транзакции в ТСП (не в RuRu!)
	 * @param null $params        array(
	 *                            phone - телефон плательщика
	 *                            account - номер счета плательщика в ТСП (например номер телефона)
	 *                            info - информация, отправляемая клиенту в момент подтверждения списания
	 *                            productId - идентификатор услуги или товара в RuRu (как она зарегистрирована)
	 *                            amount - сумма покупки В КОПЕЙКАХ
	 *                            )
	 *
	 * @return null
	 */
	public function doRequest($transactionId, $params = null)
	{
		if (empty($params) || !is_array($params)) {
			return null;
		}

		$phone = $params['phone'];
		$productId = $params['productId'];
		$info = $params['info'];
		$account = $params['account'];
		$amount = $params['amount'];

		$transactionDate = date('Y-m-d H:i:s');

		// собираем подпись из данных
		$signature = $transactionId . $transactionDate . $phone . $info . $account . $productId . $amount . $this->spId;
		$signature = base64_encode(hash_hmac('sha1', $signature, base64_decode($this->secretKey), true));

		$requestParameters =
			'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://ruru.ru/serviceprovider/">' .
			'<soapenv:Header/>' .
			'<soapenv:Body>' .
			'<ser:Init>' .
			'<ser:spTrnId>' . $transactionId . '</ser:spTrnId>' .
			'<ser:spTrnDate>' . $transactionDate . '</ser:spTrnDate>' .
			'<ser:phone>' . $phone . '</ser:phone>' .
			'<ser:info>' . $info . '</ser:info>' .
			'<ser:spAccount>' . $account . '</ser:spAccount>' .
			'<ser:productId>' . $productId . '</ser:productId>' .
			'<ser:amount>' . $amount . '</ser:amount>' .
			'<ser:spId>' . $this->spId . '</ser:spId>' .
			'<ser:signature>' . $signature . '</ser:signature>' .
			'</ser:Init>' .
			'</soapenv:Body>' .
			'</soapenv:Envelope>';


		$response = $this->request($requestParameters, 'Init');

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
		// удалим из XML информацию о SOAP для его успешного парсинга
		$responseText = str_replace('s:', '', $responseText);
		$responseXml = @simplexml_load_string($responseText);

		try {
			$initResult = $responseXml->Body->InitResponse->InitResult;

			// преобразуем элементы XML к строкам
			$this->errorCode = (string)$initResult->ErrorCode;
			$this->id = (string)$initResult->Id;
			$this->info = (string)$initResult->Info;
			$this->signature = (string)$initResult->Signature;
			$this->timeOut1 = (string)$initResult->TimeOut1;
			$this->timeOut2 = (string)$initResult->TimeOut2;

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

		$string = $this->id . $this->errorCode . $this->info . $this->timeOut1 . $this->timeOut2;
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
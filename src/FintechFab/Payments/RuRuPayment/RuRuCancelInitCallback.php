<?php
/**
 * Created by PhpStorm.
 * User: popov
 * Date: 09.12.14
 * Time: 13:50
 */

namespace FintechFab\Payments\RuRuPayment;


class RuRuCancelInitCallback extends RuRuCallback
{
	public $reason;

	protected function getParameters()
	{
		$this->action = self::getParam('action');
		$this->id = self::getParam('id');
		$this->externalId = self::getParam('externalId');
		$this->reason = self::getParam('reason');
		$this->signature = self::getParam('signature');
	}

	/**
	 * @param $info
	 *
	 * @return string
	 */
	public function doCallbackResponseSuccess($info)
	{
		$date = date('Y-m-d H:i:s');

		$errorCode = self::SUCCESS;
		$errorDescription = 'Success';

		$signature = $this->generateSignature($errorCode . $errorDescription . $date . $this->externalId . $this->id);

		return '<ServiceResponse xmlns="http://ruru.service.provider" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">' .
		'<ErrorCode>' . $errorCode . '</ErrorCode>' .
		'<ErrorDescription>' . $errorDescription . '</ErrorDescription>' .
		'<Signature>' . $signature . '</Signature>' .
		'<ResponseBody>' .
		'<Date>' . $date . '</Date>' .
		'<ExternalId>' . $this->externalId . '</ExternalId>' .
		'<Id>' . $this->id . '</Id>' .
		'</ResponseBody>' .
		'</ServiceResponse>';
	}

	public function checkSignature()
	{
		$string = $this->action . $this->id . $this->externalId . $this->reason;
		$signature = $this->generateSignature($string);

		$result = ($signature === $this->signature);

		if (!$result) {
			$this->setError(self::ERROR_SIGNATURE);
		}

		return $result;
	}

	public function getReason()
	{
		return $this->reason;
	}

	/**
	 * @return string|null
	 */
	public function getReasonMessage()
	{
		return isset(self::$errorCodes[$this->reason]) ? self::$errorCodes[$this->reason] : null;
	}

	/**
	 * @return string|null
	 */
	public function getErrorMessage()
	{
		return $this->getReasonMessage();
	}
}
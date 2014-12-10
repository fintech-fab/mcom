<?php
/**
 * Created by PhpStorm.
 * User: popov
 * Date: 09.12.14
 * Time: 14:27
 */

namespace FintechFab\Payments\RuRuPayment;


class RuRuCallback extends RuRu
{
	/**
	 * Имя запроса
	 *
	 * @var
	 */
	public $action;

	/**
	 * ID транзакции в системе RuRu
	 */
	public $id;

	/**
	 * ID транзакции ТСП
	 * @var
	 */
	public $externalId;

	/**
	 * Код продукта/товара
	 *
	 * @var
	 */
	public $code;

	/**
	 * Информация об аккаунте клиента (номер телефона)
	 *
	 * @var
	 */
	public $account;

	/**
	 * Сумма
	 *
	 * @var
	 */
	public $amount;

	/**
	 * Дата
	 *
	 * @var
	 */
	public $date;

	/**
	 * Дополнительные параметры, не используется
	 *
	 * @var
	 */
	public $parameters;

	/**
	 * ЭЦП
	 *
	 * @var
	 */
	public $signature;

	/**
	 * Флаг "коллбэк обработан", ставится после успешной загрузки данных из коллбэка
	 *
	 * @var bool
	 */
	protected $processed = false;

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

		$signature = $this->generateSignature($errorCode.$errorDescription.$this->amount.$date.$this->externalId.$info.$this->id);

		return '<ServiceResponse xmlns="http://ruru.service.provider" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">' .
		'<ErrorCode>'.$errorCode.'</ErrorCode>' .
		'<ErrorDescription>'.$errorDescription.'</ErrorDescription>' .
		'<WillCallback>false</WillCallback>' .
		'<Signature>'.$signature.'</Signature>' .
		'<ResponseBody>' .
		'<Amount>' . $this->amount . '</Amount>' .
		'<Date>' . $date . '</Date>' .
		'<ExternalId>' . $this->externalId . '</ExternalId>' .
		'<Info>' . $info . '</Info>' .
		'<Id>' . $this->id . '</Id>' .
		'</ResponseBody>' .
		'</ServiceResponse>';
	}

	/**
	 * @param int    $error            ошибка (код из константы)
	 * @param string $errorDescription описание ошибки
	 *
	 * @return string
	 */
	public static function doCallbackResponseError($error, $errorDescription)
	{
		return '<ServiceResponse xmlns="http://ruru.service.provider" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">' .
			'<ErrorCode>'.$error.'</ErrorCode>' .
			'<ErrorDescription>'.$errorDescription.'</ErrorDescription>' .
			'<WillCallback></WillCallback>' .
			'<Signature></Signature>' .
			'</ServiceResponse>';
	}

	public static function doCallbackActionError()
	{
		return self::doCallbackResponseError(self::CALLBACK_ERROR_WRONG_ACTION, self::$errorCodes[self::CALLBACK_ERROR_WRONG_ACTION]);
	}

	public static function getParam($name, $defaultValue = null)
	{
		return isset($_GET[$name]) ? $_GET[$name] : $defaultValue;
	}

	protected function getParameters()
	{
		$this->action = self::getParam('action');
		$this->id = self::getParam('id');
		$this->externalId = self::getParam('externalId');
		$this->code = self::getParam('code');
		$this->account = self::getParam('account');
		$this->amount = self::getParam('amount');
		$this->date = self::getParam('date');
		$this->parameters = self::getParam('parameters');
		$this->signature = self::getParam('signature');
	}

	public function isProcessed()
	{
		return $this->processed;
	}

	public function doProcessCallback()
	{
		$this->error = null;
		// получим параметры коллбэка, они сразу загружаются в свойства объекта
		$this->getParameters();
		// запустим проверку подписи, если ошибка - она автоматически установится
		$this->checkSignature();

		// установим флаг, что коллбэк обработан
		$this->processed = true;
	}

	public function checkSignature()
	{
		$string = $this->action . $this->id . $this->externalId . $this->code. $this->account . $this->amount . $this->date.$this->parameters;
		$signature = $this->generateSignature($string);

		$result =  ($signature === $this->signature);

		if(!$result){
			$this->setError(self::ERROR_SIGNATURE);
		}

		return $result;
	}

	public function setActionError()
	{
		$this->error = self::CALLBACK_ERROR_WRONG_ACTION;
	}

	public function isActionError()
	{
		return ($this->error == self::CALLBACK_ERROR_WRONG_ACTION);
	}
}
<?php
namespace FintechFab\Payments\RuRuPayment;


abstract class RuRu
{
	// общие ошибки (для трансферов, статусов, коллбэков)
	const SUCCESS = 0;
	const ERROR_SIGNATURE = 600;
	const ERROR_UNKNOWN = 999;
	const ERROR_HTTP = 2002;
	const ERROR_TIMEOUT = 30;

	// ошибки, что можем получить в ответ на запрос транзакции или статуса
	const REQUEST_ERROR_INTERNAL = 1;
	const REQUEST_ERROR_PHONE_OPERATOR_NOT_AVAILABLE = 2;
	const REQUEST_ERROR_DATE_FORMAT = 3;
	const REQUEST_ERROR_WRONG_CHANNEL = 17;
	const REQUEST_ERROR_SERVICE_NOT_AVAILABLE = 18;
	const REQUEST_ERROR_SERVICE_ROUTE_ERROR = 20;
	const REQUEST_ERROR_SERVICE_ROUTE_NOT_FOUND = 21;
	const REQUEST_ERROR_PHONE_EMPTY = 22;
	const REQUEST_ERROR_AMOUNT_FORMAT = 24;
	const REQUEST_ERROR_ACCESS = 27;
	const REQUEST_ERROR_RIGHTS = 28;
	const REQUEST_ERROR_CUSTOMER_NOT_FOUND = 29;
	const REQUEST_ERROR_TRANSACTION_OF_OTHER_CLIENT = 31;
	const REQUEST_ERROR_TRANSACTION_NOT_FOUND = 32;
	const REQUEST_ERROR_TRANSACTION_STATUS = 35;
	const REQUEST_ERROR_PAYER = 36;
	const REQUEST_ERROR_PAYER_NOT_FOUND = 37;
	const REQUEST_ERROR_AMOUNT_TOO_LOW = 38;
	const REQUEST_ERROR_AMOUNT_TOO_HIGH = 39;
	const REQUEST_ERROR_TRANSACTION_NOT_FOUND_2 = 60;
	const REQUEST_ERROR_TRANSACTION_ERROR = 61;

	// ошибки, которыми можно ответить на коллбэк
	const CALLBACK_ERROR_CLIENT_CANT_PAY = 100;
	const CALLBACK_ERROR_WRONG_ACCOUNT = 101;
	const CALLBACK_ERROR_WRONG_ID = 102;
	const CALLBACK_ERROR_WRONG_TRANSACTION_ID = 103;
	const CALLBACK_ERROR_WRONG_DATE = 104;
	const CALLBACK_ERROR_FRAUD = 200;
	const CALLBACK_ERROR_LOW_AMOUNT = 201;
	const CALLBACK_ERROR_HIGH_AMOUNT = 202;
	const CALLBACK_ERROR_WRONG_AMOUNT = 203;
	const CALLBACK_ERROR_PRODUCT_NOT_AVAILABLE = 204;
	const CALLBACK_ERROR_WRONG_PARAMS = 300;
	const CALLBACK_ERROR_BAD_REQUEST_SEQUENCE = 400;
	const CALLBACK_ERROR_WRONG_ACTION = 500;

	public static $errorCodes = array(
		self::SUCCESS                                    => 'Успех',
		self::ERROR_SIGNATURE                            => 'Ошибка ЭЦП',
		self::ERROR_UNKNOWN                              => 'Внутренняя ошибка',
		self::ERROR_HTTP                                 => 'Ошибка HTTP-запроса',
		self::ERROR_TIMEOUT                              => 'Истек таймаут на резервирование/покупку (услуга не оказана)',
		self::ERROR_SIGNATURE                            => 'Ошибка ЭЦП',
		self::ERROR_UNKNOWN                              => 'Внутренняя ошибка',

		self::REQUEST_ERROR_INTERNAL                     => 'Внутренняя ошибка',
		self::REQUEST_ERROR_PHONE_OPERATOR_NOT_AVAILABLE => 'Невозможно определить оператора по номеру телефона плательщика',
		self::REQUEST_ERROR_DATE_FORMAT                  => 'Некорректный формат даты',
		self::REQUEST_ERROR_WRONG_CHANNEL                => 'Неверный канал инициирования покупки',
		self::REQUEST_ERROR_SERVICE_NOT_AVAILABLE        => 'Услуга не найдена или не активна',
		self::REQUEST_ERROR_SERVICE_ROUTE_ERROR          => 'Ошибка определения маршрута оплаты услуги или комиссии с нее по источнику средств',
		self::REQUEST_ERROR_SERVICE_ROUTE_NOT_FOUND      => 'Доступный маршрут оплаты услуги не найден',
		self::REQUEST_ERROR_PHONE_EMPTY                  => 'Не указан номер телефона плательщика',
		self::REQUEST_ERROR_AMOUNT_FORMAT                => 'Ошибочный формат цены',
		self::REQUEST_ERROR_ACCESS                       => 'Ошибка доступа ТСП (проверьте клиентский сертификат)',
		self::REQUEST_ERROR_RIGHTS                       => 'Нет прав на инициацию покупки',
		self::REQUEST_ERROR_CUSTOMER_NOT_FOUND           => 'ТСП не найден или не активен',
		self::REQUEST_ERROR_TRANSACTION_OF_OTHER_CLIENT  => 'Транзакция принадлежит другому пользователю',
		self::REQUEST_ERROR_TRANSACTION_NOT_FOUND        => 'Транзакция не найдена',
		self::REQUEST_ERROR_TRANSACTION_STATUS           => 'Текущий статус транзакции не позволяет выполнить операцию',
		self::REQUEST_ERROR_PAYER                        => 'У плательщика отсутствует номер счета в источнике средств',
		self::REQUEST_ERROR_PAYER_NOT_FOUND              => 'Пользователь (плательщик) не найден',
		self::REQUEST_ERROR_AMOUNT_TOO_LOW               => 'Сумма покупки меньше минимально допустимой',
		self::REQUEST_ERROR_AMOUNT_TOO_HIGH              => 'Сумма покупки больше максимально допустимой',
		self::REQUEST_ERROR_TRANSACTION_NOT_FOUND_2      => 'Транзакция не найдена',
		self::REQUEST_ERROR_TRANSACTION_ERROR            => 'Транзакция завершена с ошибкой',

		self::CALLBACK_ERROR_CLIENT_CANT_PAY             => 'Клиент существует, но не является допустимым для платежа',
		self::CALLBACK_ERROR_WRONG_ACCOUNT               => 'Информация в поле запроса account не соответствует идентификатору клиента или отсутствует',
		self::CALLBACK_ERROR_WRONG_ID                    => 'Отсутствует или неправильный идентификатор запроса ПС (поле запроса id)',
		self::CALLBACK_ERROR_WRONG_TRANSACTION_ID        => 'Отсутствует или неправильный идентификатор запроса, полученный от ТСП (поле запроса externalId)',
		self::CALLBACK_ERROR_WRONG_DATE                  => 'Поле даты отсутствует или имеет неправильный формат',
		self::CALLBACK_ERROR_FRAUD                       => 'Сработали фрод ограничения на стороне ТСП',
		self::CALLBACK_ERROR_LOW_AMOUNT                  => 'Сумма платежа ниже минимально допустимой',
		self::CALLBACK_ERROR_HIGH_AMOUNT                 => 'Сумма платежа выше максимально допустимой',
		self::CALLBACK_ERROR_WRONG_AMOUNT                => 'Неверная сумма платежа',
		self::CALLBACK_ERROR_PRODUCT_NOT_AVAILABLE       => 'Товар отсутствует в наличии',
		self::CALLBACK_ERROR_WRONG_PARAMS                => 'Ошибка в одном из дополнительных параметров. В поле ErrorDescription содержится описание, в каком именно.',
		self::CALLBACK_ERROR_BAD_REQUEST_SEQUENCE        => 'Нарушение ожидаемой логики вызовов. Например, вызов payment без init, если иное не предусмотрено в данной услуге',
		self::CALLBACK_ERROR_WRONG_ACTION                => 'Запрос в параметре action не распознан',
	);


	protected $apiUrl;

	protected $secretKey;

	protected $spId;

	protected $error;

	abstract public function checkSignature();

	public function __construct($apiUrl, $spId, $secretKey)
	{
		$this->secretKey = $secretKey;
		$this->spId = $spId;
		$this->apiUrl = $apiUrl;
	}

	public function generateSignature($string)
	{
		$signature = $string;
		$signature = base64_encode(hash_hmac('sha1', $signature, base64_decode($this->secretKey), true));

		return $signature;
	}

	public function getError()
	{
		return $this->error;
	}

	public function getErrorMessage()
	{
		return isset(self::$errorCodes[$this->error]) ? self::$errorCodes[$this->error] : null;
	}

	protected function setError($error)
	{
		$this->error = $error;
	}

	public function isHttpError()
	{
		return ($this->error == self::ERROR_HTTP);
	}

	public function isError()
	{
		return !!$this->error;
	}
}
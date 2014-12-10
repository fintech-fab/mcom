<?php
use FintechFab\Payments\RuRuPayment\RuRuPayment;
use FintechFab\Payments\RuRuPayment\RuRuInitCallback;
use FintechFab\Payments\RuRuPayment\RuRuCancelInitCallback;
use FintechFab\Payments\RuRuPayment\RuRuPaymentCallback;
use FintechFab\Payments\Test\TestCase;

/**
 * Class RuRuTest
 */
class RuRuTest extends TestCase
{
	/**
	 * @var RuRuPayment
	 */
	private $ruRuPayment;

	public function setUp()
	{
		parent::setUp();

		if (!isset($this->ruRuPayment)) {
			// тут требуется указывать секретный ключ
			$secretKey = '';

			$spId = 1234;

			// создаем объект с параметрами секретного ключа и ID пользователя

			$this->ruRuPayment = new RuRuPayment($spId, $secretKey);
		}
	}

	/**
	 * Тест трансфера на мобильный телефон
	 */
	public function testOne()
	{
		// установим уникальный номер транзакции по времени
		$transactionId = time();
		$phone = '9031111111';

		// проверим формат номера телефона - ровно 10 цифр
		$this->assertEquals(10, strlen($phone));

		$info = 'тестовый товар';
		$account = $phone;
		$amount = 1000;
		$productId = 3922;

		$ruRuPayment = $this->ruRuPayment;

		// инициируем запрос на оплату
		$ruRu = $ruRuPayment->doPaymentRequest($transactionId, $phone, $productId, $info, $account, $amount);

		// убедимся, что
		$this->assertNotEmpty($ruRu->id);

		// проверим, что нет ошибки
		$this->assertFalse($ruRu->isError(), $ruRu->getError());

		// убедимся, что ЭЦП прошла проверку (если не прошла - должна была вылететь ошибка строкой выше)
		$this->assertTrue($ruRu->checkSignature());

		/**
		 * Дальше симулируем коллбэки и проверяем реакцию системы на них
		 */

		// тут ловится коллбэк, запускается метод обработки коллбэка
		// отвечаем "все ОК"
		$action = 'init';
		$date = date('Y-m-d H:i:s');

		$_GET = array(
			'action'     => $action,
			'id'         => $ruRu->id,
			'externalId' => $transactionId,
			'code'       => $productId,
			'account'    => $phone,
			'amount'     => $amount,
			'date'       => $date,
			'signature'  => $ruRu->generateSignature($action . $ruRu->id . $transactionId . $productId . $phone . $amount . $date),
		);

		$result = $ruRuPayment->doProcessCallback();

		$this->assertEquals(RuRuInitCallback::class, get_class($result));

		$this->assertNotEmpty($result->id);
		$this->assertTrue($result->isProcessed());

		// проверим, что нет ошибки
		$this->assertFalse($result->isError(), $result->getError());

		// убедимся, что ЭЦП прошла проверку (если не прошла - должна была вылететь ошибка строкой выше)
		$this->assertTrue($result->checkSignature());

		$result->doCallbackResponseSuccess('тестовая оплата');

		// тут ловится второй коллбэк с отчетом об успешности оплаты
		$action = 'payment';
		$date = date('Y-m-d H:i:s');

		$_GET = array(
			'action'     => $action,
			'id'         => $ruRu->id,
			'externalId' => $transactionId,
			'code'       => $productId,
			'account'    => $phone,
			'amount'     => $amount,
			'date'       => $date,
			'signature'  => $ruRu->generateSignature($action . $ruRu->id . $transactionId . $productId . $phone . $amount . $date),
		);

		$result = $ruRuPayment->doProcessCallback();

		$this->assertEquals(RuRuPaymentCallback::class, get_class($result));

		$this->assertNotEmpty($result->id);
		$this->assertTrue($result->isProcessed());

		// проверим, что нет ошибки
		$this->assertFalse($result->isError(), $result->getError());

		// убедимся, что ЭЦП прошла проверку (если не прошла - должна была вылететь ошибка строкой выше)
		$this->assertTrue($result->checkSignature());

		// ждем пока пройдут коллбэки (настоящие на тестовый шлюз, а не симулированные в тесте), чтобы проверить статус
		sleep(5);
		$result = $ruRuPayment->getTransactionStatus($ruRu->id);

		$this->assertEquals(0, (int)$result->errorCode);

		// проверим, что нет ошибки
		$this->assertFalse($result->isError(), $result->getError());

		// убедимся, что ЭЦП прошла проверку (если не прошла - должна была вылететь ошибка строкой выше)
		$this->assertTrue($result->checkSignature());
	}

	public function testTwo()
	{
		$ruRuPayment = $this->ruRuPayment;

		// данный тест требует конкретный секретный ключ, при необходимости - переделать
		$_GET = array(
			'action'     => 'cancelinit',
			'id'         => '359166351',
			'externalId' => '1418211046',
			'reason'     => '1',
			'signature'  => 'QRsSXEyrl5VCNO0hCmVWdFS+Vgg=',
		);

		$result = $ruRuPayment->doProcessCallback();

		$this->assertEquals(RuRuCancelInitCallback::class, get_class($result));

		$this->assertNotEmpty($result->id);
		$this->assertTrue($result->isProcessed());

		// проверим, что нет ошибки
		$this->assertFalse($result->isError(), $result->getError());

		// убедимся, что ЭЦП прошла проверку (если не прошла - должна была вылететь ошибка строкой выше)
		$this->assertTrue($result->checkSignature());
	}
}

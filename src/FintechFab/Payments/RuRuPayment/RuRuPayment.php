<?php

namespace FintechFab\Payments\RuRuPayment;

class RuRuPayment
{

	protected $apiUrl = 'https://178.20.234.188/RuRu.FrontEnd.ServiceProvider2/TransactionService.svc';

	public $secretKey = '';

	protected $spId = null;

	public function __construct($spId, $secretKey, $apiUrl = null)
	{
		$this->secretKey = $secretKey;
		$this->spId = $spId;

		if ($apiUrl) {
			$this->apiUrl = $apiUrl;
		}
	}

	/**
	 * @param int    $transactionId номер транзакции в ТСП (не в RuRu!)
	 * @param string $phone         телефон плательщика
	 * @param string $productId     идентификатор услуги или товара в RuRu (как она зарегистрирована)
	 * @param string $info          информация, отправляемая клиенту в момент подтверждения списания
	 * @param string $account       номер счета плательщика в ТСП (например номер телефона)
	 * @param int    $amount        сумма покупки В КОПЕЙКАХ
	 *
	 * @return RuRuInitRequest
	 */
	public function doPaymentRequest($transactionId, $phone, $productId, $info, $account, $amount)
	{
		$ruRu = new RuRuInitRequest($this->apiUrl, $this->spId, $this->secretKey);

		$params = array(
			'phone'     => $phone,
			'productId' => $productId,
			'info'      => $info,
			'account'   => $account,
			'amount'    => $amount,
		);

		$ruRu->doRequest($transactionId, $params);


		return $ruRu;
	}

	/**
	 * @param int  $transactionId     ID транзакции в ТСП
	 * @param int  $ruRuTransactionId ID транзакции в RuRu
	 * @param int  $type              тип уведомления Notice
	 *                                0    Операция на стороне ТСП успешно завершена
	 *                                1    Операция на стороне ТСП не может быть завершена
	 *                                2    Операция на стороне ТСП временно заблокирована
	 *
	 * @param null $dt
	 * @param null $receiver
	 * @param null $account
	 * @param null $rate
	 *
	 * @return RuRuNoticeRequest
	 */
	public function doNoticeRequest($transactionId, $ruRuTransactionId, $type, $dt = null, $receiver = null, $account = null, $rate = null)
	{
		$ruRu = new RuRuNoticeRequest($this->apiUrl, $this->spId, $this->secretKey);

		$params = array(
			'ruRuTransactionId' => $ruRuTransactionId,
			'type'              => $type,
			'dt'                => $dt,
			'receiver'          => $receiver,
			'account'           => $account,
			'rate'              => $rate,
		);

		$ruRu->doRequest($transactionId, $params);


		return $ruRu;
	}

	/**
	 * @param int $transactionId ID транзакции в RuRu
	 *
	 * @return RuRuGetTransactionStatusRequest
	 */
	public function getTransactionStatus($transactionId)
	{
		$ruRu = new RuRuGetTransactionStatusRequest($this->apiUrl, $this->spId, $this->secretKey);

		$ruRu->doRequest($transactionId);

		return $ruRu;
	}

	/**
	 * @return RuRuInitCallback|RuRuPaymentCallback|RuRuCancelInitCallback|null
	 */
	public function doProcessCallback()
	{
		$action = mb_strtolower(RuRuCallback::getParam('action'));

		switch ($action) {
			case 'init':
				$ruRu = new RuRuInitCallback($this->apiUrl, $this->spId, $this->secretKey);
				$ruRu->doProcessCallback();
				break;
			case 'cancelinit':
				$ruRu = new RuRuCancelInitCallback($this->apiUrl, $this->spId, $this->secretKey);
				$ruRu->doProcessCallback();
				break;
			case 'payment':
				$ruRu = new RuRuPaymentCallback($this->apiUrl, $this->spId, $this->secretKey);
				$ruRu->doProcessCallback();
				break;
			default:
				// по-умолчанию установим ошибку экшна и вернем объект
				$ruRu = new RuRuCallback($this->apiUrl, $this->spId, $this->secretKey);
				$ruRu->setActionError();
		}

		return $ruRu;
	}

}
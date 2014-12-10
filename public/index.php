<?php
/**
 * Тестовый обработчик коллбэков
 *
 */
require __DIR__ . '/../vendor/autoload.php';

$secretKey = '';

$spId = 1234;

$ruRuPayment = new FintechFab\Payments\RuRuPayment\RuRuPayment($spId, $secretKey);


// обрабатываем пришедший коллбэк
$result = $ruRuPayment->doProcessCallback();

if ($result->isProcessed() && !$result->isError()) {
	$response =  $result->doCallbackResponseSuccess('тестовая оплата');
} elseif(!$result->checkSignature()){
	$response = $result->doCallbackResponseError(FintechFab\Payments\RuRuPayment\RuRu::ERROR_SIGNATURE, 'signature error');
} else {
	$response = $result->doCallbackResponseError(FintechFab\Payments\RuRuPayment\RuRu::ERROR_UNKNOWN, 'error unknown');
}

echo $response;

mail('v.yuldashev@fintech-fab.ru', 'RuRu callback', print_r($_GET, true) . "\n" . print_r($response, true)."\nError code: ".$result->getError()."\nError message:".$result->getErrorMessage());
mail('i.popov@fintech-fab.ru', 'RuRu callback', print_r($_GET, true) . "\n" . print_r($response, true)."\nError code: ".$result->getError()."\nError message:".$result->getErrorMessage());

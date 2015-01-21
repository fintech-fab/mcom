RuRu Payment
=========

# Требования

- php >=5.5.0
- MySQL Database
- composer


# Установка

### Composer

	"repositories": [
      {
       "url": "git remote add origin https://github.com/fintech-fab/mcom.git",
       "type": "git"
      }
     ],
     "require": {
        "fintech-fab/mcom": "dev-master"
     }

	composer update

### Использование

Инициализируем запрос на оплату покупки:

```PHP
use FintechFab\Payments\RuRuPayment\RuRuPayment;

$secretKey = 'секретный ключ';

// ID продавца
$spId = 1234;

// задаем URL сервиса, для тестового запроса это не обязательно, в классе прописан тестовый шлюз
$apiUrl = 'http://serviceurl';

// создаем объект с параметрами секретного ключа, ID пользователя и URL сервиса
$ruRuPayment = new RuRuPayment($spId, $secretKey, $apiUrl);

// установим уникальный номер транзакции по времени
$transactionId = time();
$phone = '9031111111'; //10 цифр

$info = 'тестовый товар';
$account = $phone; // номер счета клиента, может быть например номером телефона
$amount = 1000; // 10 рублей, сумма в копейках

// ID продукта, зарегистрированного для данного продавца в RuRu
$productId = 3922;

// инициируем запрос на оплату
$ruRu = $ruRuPayment->doPaymentRequest($transactionId, $phone, $productId, $info, $account, $amount);

```

В ответ получаем объект RuRuInitRequest, у него есть методы isError(), getError(), getErrorMessage()
Следует проверить, не произошло ли каких-то ошибок, и адекватно на ошибки реагировать.

Если все прошло успешно, то придет коллбэк на настроенный у RuRu URL, на нем должен быть следующий обработчик:
```PHP
use FintechFab\Payments\RuRuPayment\RuRuPayment;
use FintechFab\Payments\RuRuPayment\RuRu;

$secretKey = 'секретный ключ';

$spId = 1234;

$ruRuPayment = new RuRuPayment($spId, $secretKey);

// обрабатываем пришедший коллбэк
$result = $ruRuPayment->doProcessCallback();

// убедимся, что коллбэк обработан, и что не возникло ошибок (например, ошибка ЭЦП)
// также следует проверять данные (ID транзации и прочее) согласно требованиям ТСП
if ($result->isProcessed() && !$result->isError()) {
	// отвечаем на коллбэк что все ОК
	$response =  $result->doCallbackResponseSuccess('тестовая оплата успешно');//текст от ТСП, который в случае если запрос успешный будет (по желанию ТСП) передан клиенту
} else {
	// отвечаем на коллбэк ошибкой, следует определять конкретную ошибку и указывать ее код (из констант класса RuRu) в ответе
	// код ошибки следует определять для каждого случая отдельно
	$response = $result->doCallbackResponseError(FintechFab\Payments\RuRuPayment\RuRu::ERROR_UNKNOWN, 'error unknown');
}

// всегда следует отправить в ответ $response, т.к. методы doCallback*() возвращают текст ответа, но не отвечают сами.
echo $response;

// если ошибка при обработке, получим данные об ошибке для логирования
if($result->isError){

    // код ошибки
	$error =  $result->getError();

	// сообщение об ошибке
    $errorMessage = $result->getErrorMessage();

	// информация о curl запросе
    $curlInfo = $result->getCurlInfo();

    // массив с параметрами запроса
    $curlParameters = $result->getCurlParameters();

    // текст ответа на запрос
    $curlResponse = $result->getCurlResponse();
}


```

В нормальном сценарии приходит 2 коллбэка: init и payment, а также, если на payment был ответ "ошибка", приходит коллбэк cancelinit.
Все коллбэки следует обрабатывать по их имени или классу:
```PHP
$result = $ruRuPayment->doProcessCallback();

// проверим, нет ли ошибки action запроса
if($result->isActionError()){
	// ответим ошибкой action запроса
	echo $result->doCallbackActionError();
	return;
}

switch($result->action)
{
	case 'init':
		// обработаем init, т.е. проверим что мы отправляли такой запрос и подтвердим его
		// тут проверяем ошибки isError(), проверяем данные и так далее, отвечаем успехом или ошибкой
		break;
	case 'payment':
		// обработаем payment, т.е. обработаем сообщение, что клиент упсешно оплатил товар/услугу
		// тут проверяем ошибки isError(), проверяем данные и так далее, отвечаем успехом или ошибкой
       	break;
    case 'cancelinit':
		// обработаем cancelinit, т.е. пометим запрос как не удавшийся (например, позже потребуется его переслать заново)
		// тут проверяем ошибки isError(), проверяем данные и так далее, отвечаем успехом или ошибкой
		break;
	default:
		// выше показан пример, когда еще до switch проверяется action запроса, тогда default не потребуется
		echo $result->doCallbackActionError();
	}

```

Если необходимо отправить уведомление Notice о статусе операции, все операции доступны в константах RuRuNoticeRequest::OPERATION_
 ```
	$result = $ruRuPayment->doNoticeRequest($transactionId, $ruRuTransactionId, RuRuNoticeRequest::OPERATION_COMPLETE);
 ```
 В ответ получаем объект RuRuNoticeRequest, у него есть методы isError(), getError(), getErrorMessage()
 Следует проверить, не произошло ли каких-то ошибок, и адекватно на ошибки реагировать.
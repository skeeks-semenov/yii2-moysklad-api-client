<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\yii2\moyskladApiClient;

use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\httpclient\Client;

/**
 * @property array  $accessCredentials
 * @property string $accessToken
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class MoyskladApiClient extends Component
{
    /**
     * @var string
     */
    public $base_api_url = "https://online.moysklad.ru/api/remap/1.2/";

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $password;

    /**
     * @var int
     */
    public $request_timeout = 10000;

    /**
     * @var int
     */
    public $request_maxRedirects = 2;

    /**
     * @var string
     */
    public $cache_key = 'moysklad_access_v4';

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->email || !$this->password) {
            //throw new InvalidConfigException("Need email or password");
        }

        return parent::init();
    }

    /**
     * Получение данных авторизации из апи
     *
     * @return array
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    protected function _getAccessCredentialsFromApi()
    {
        $client = new Client([
            'requestConfig' => [
                'format' => Client::FORMAT_JSON,
            ],
        ]);

        $request = $client->createRequest()
            ->setMethod("POST")
            ->setUrl($this->base_api_url."security/token")
            ->addHeaders(['Authorization' => 'Basic '.base64_encode($this->email.":".$this->password)])
            ->setOptions([
                'timeout'      => $this->request_timeout,
                'maxRedirects' => $this->request_maxRedirects,
            ]);

        $response = $request->send();

        if (!$response->isOk) {
            throw new Exception("Error request:".$response->content);
        }

        return (array)$response->data;
    }


    /**
     * @return array
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getAccessCredentials()
    {
        $data = \Yii::$app->cache->get($this->cache_key);

        if ($data === false) {
            $data = $this->_getAccessCredentialsFromApi();

            if (!$data || ArrayHelper::getValue($data, 'error')) {
                throw new Exception("Ошибка получения ключа доступа к апи: ".print_r($data, true)." Пользователь: ".$this->email);
            }

            \Yii::$app->cache->set($this->cache_key, $data, 10);
        }

        return (array)$data;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        $data = $this->accessCredentials;
        return (string)ArrayHelper::getValue($data, 'access_token');
    }


    /**
     * @param string $api_method
     * @param string $request_method
     * @return \yii\httpclient\Request
     * @throws InvalidConfigException
     */
    public function createApiRequest(string $api_method, string $request_method = "GET")
    {
        $client = new Client([
            'requestConfig' => [
                'format' => Client::FORMAT_JSON,
            ],
        ]);

        $request = $client->createRequest()
            ->setMethod($request_method)
            ->setUrl($this->base_api_url.$api_method)
            ->addHeaders(['Authorization' => 'Bearer '.$this->accessToken])
            ->setOptions([
                'timeout'      => $this->request_timeout,
                'maxRedirects' => $this->request_maxRedirects,
            ]);

        return $request;
    }

    /**
     * Получить список Модификаций
     * Запрос на получение списка всех Модификаций на данной учетной записи. Результат успешного запроса - JSON представление списка Модификаций с перечисленными полями:
     * @see https://dev.moysklad.ru/doc/api/remap/1.2/dictionaries/#suschnosti-modifikaciq-poluchit-spisok-modifikacij
     *
     * @return array
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getEntityVariantApiMethod($offset = 0)
    {
        $response = $this->createApiRequest("entity/variant" . ($offset ? "?offset={$offset}" : ""))->send();
        return (array)$response->data;
    }

    /**
     * Получить список Товаров
     * Запрос на получение всех Товаров для данной учетной записи. Результат: Объект JSON, включающий в себя поля:
     * @see https://dev.moysklad.ru/doc/api/remap/1.2/dictionaries/#suschnosti-towar-towary
     *
     * @return array
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getEntityProductApiMethod($id = null)
    {
        $response = $this->createApiRequest("entity/product" . ($id ? "/{$id}" : ""))->send();
        return (array)$response->data;
    }

    /**
     * Получить список комплектов
     * Запрос на получение всех комплектов для данной учетной записи.
     * @see https://dev.moysklad.ru/doc/api/remap/1.2/dictionaries/#suschnosti-komplekt-komplekty
     *
     * @return array
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getEntityBundleApiMethod()
    {
        $response = $this->createApiRequest("entity/bundle")->send();
        return (array)$response->data;
    }

    /**
     * Получить список Заказов покупателей
     * @see https://dev.moysklad.ru/doc/api/remap/1.2/documents/#dokumenty-zakaz-pokupatelq-poluchit-spisok-zakazow-pokupatelej
     *
     * @return array
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getEntityCustomerorderApiMethod($id = null)
    {
        $response = $this->createApiRequest("entity/customerorder" . ($id ? "/{$id}" : ""))->send();
        return (array)$response->data;
    }

    /**
     * @return array
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getEntityPaymentinApiMethod($id = null)
    {
        $response = $this->createApiRequest("entity/paymentin" . ($id ? "/{$id}" : ""))->send();
        return (array)$response->data;
    }

    /**
     * Получить список Заказов покупателей
     * @see https://dev.moysklad.ru/doc/api/remap/1.2/documents/#dokumenty-zakaz-pokupatelq-poluchit-spisok-zakazow-pokupatelej
     *
     * @return array
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getEntityCustomerorderAttributeApiMethod($id)
    {
        $response = $this->createApiRequest("entity/customerorder/metadata/attributes/" . $id)->send();
        return (array)$response->data;
    }

    /**
     * Создать Заказ покупателя
     * @see https://dev.moysklad.ru/doc/api/remap/1.2/documents/#dokumenty-zakaz-pokupatelq-sozdat-zakaz-pokupatelq
     *
     * @return array
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function postEntityCustomerorderApiMethod($data = [])
    {
        $response = $this
            ->createApiRequest("entity/customerorder", "POST")
            ->setData($data)
            ->send();
        return (array)$response->data;
    }

    /**
     * Создать Заказ покупателя
     * @see https://dev.moysklad.ru/doc/api/remap/1.2/documents/#dokumenty-vhodqschij-platezh-sozdat-vhodqschij-platezh
     *
     * @return array
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function postEntityPaymentinApiMethod($data = [])
    {
        $response = $this
            ->createApiRequest("entity/paymentin", "POST")
            ->setData($data)
            ->send();
        return (array)$response->data;
    }

    /**
     * Получить список Контрагентов
     * @see https://dev.moysklad.ru/doc/api/remap/1.2/dictionaries/#suschnosti-kontragent-kontragenty
     *
     * @return array
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getEntityCounterpartyApiMethod($id = null)
    {
        $response = $this->createApiRequest("entity/counterparty" . ($id ? "/{$id}" : ""))->send();
        return (array)$response->data;
    }

    /**
     * Создать Контрагента
     * @see https://dev.moysklad.ru/doc/api/remap/1.2/dictionaries/#suschnosti-kontragent-sozdat-kontragenta
     *
     * @return array
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function postEntityCounterpartyApiMethod($data)
    {
        $response = $this
            ->createApiRequest("entity/counterparty", "POST")
            ->setData($data)
            ->send()
        ;
        return (array)$response->data;
    }

    /**
     * Получить список юрлиц
     * @see https://dev.moysklad.ru/doc/api/remap/1.2/dictionaries/#suschnosti-jurlico-jurlica
     *
     * @return array
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getEntityOrganizationApiMethod($id = null)
    {
        $response = $this->createApiRequest("entity/organization" . ($id ? "/{$id}" : ""))->send();
        return (array)$response->data;
    }

}
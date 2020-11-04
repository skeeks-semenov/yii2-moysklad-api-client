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
    public $request_timeout = 5;

    /**
     * @var int
     */
    public $request_maxRedirects = 2;

    /**
     * @var string
     */
    public $cache_key = 'moysklad_access';

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

            if (!$data || $data['error']) {
                throw new Exception("Ошибка получения ключа доступа к апи: ".print_r($data, true)." Пользователь: ".$this->email);
            }

            \Yii::$app->cache->set($this->cache_key, $data, 3600 * 24);
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
    protected function _createApiRequest(string $api_method, string $request_method = "GET")
    {
        $client = new Client([
            'requestConfig' => [
                'format' => Client::FORMAT_JSON,
            ],
        ]);

        $request = $client->createRequest()
            ->setMethod("GET")
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
    public function getEntityVariantApiMethod()
    {
        $response = $this->_createApiRequest("entity/variant")->send();
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
    public function getEntityProductApiMethod()
    {
        $response = $this->_createApiRequest("entity/product")->send();
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
        $response = $this->_createApiRequest("entity/bundle")->send();
        return (array)$response->data;
    }

}
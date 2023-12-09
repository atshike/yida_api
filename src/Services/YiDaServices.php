<?php

namespace Atshike\YidaApi\Services;

use AlibabaCloud\SDK\Dingtalk\Voauth2_1_0\Dingtalk as Dingtalk2;
use AlibabaCloud\SDK\Dingtalk\Voauth2_1_0\Models\GetAccessTokenRequest;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Dingtalk;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\CreateOrUpdateFormDataHeaders;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\CreateOrUpdateFormDataRequest;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\CreateOrUpdateFormDataResponse;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\DeleteFormDataHeaders;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\DeleteFormDataRequest;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\DeleteFormDataResponse;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\GetFormDataByIDHeaders;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\GetFormDataByIDRequest;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\GetFormDataByIDResponse;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\GetOpenUrlHeaders;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\GetOpenUrlRequest;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\GetOpenUrlResponse;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\ListTableDataByFormInstanceIdTableIdHeaders;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\ListTableDataByFormInstanceIdTableIdRequest;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\ListTableDataByFormInstanceIdTableIdResponse;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\SaveFormDataHeaders;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\SaveFormDataRequest;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\SaveFormDataResponse;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\SearchFormDatasHeaders;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\SearchFormDatasRequest;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\SearchFormDatasResponse;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\UpdateFormDataHeaders;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\UpdateFormDataRequest;
use AlibabaCloud\SDK\Dingtalk\Vyida_1_0\Models\UpdateFormDataResponse;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;
use Darabonba\OpenApi\Models\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Psr\Http\Message\ResponseInterface;

class YiDaServices
{
    private string $access_token;

    private string $cache_yida_access_token_key = 'dingtalk_access_token';

    private string $appKey;

    private string $appSecret;

    private string $appType;

    private string $systemToken;

    public function __construct()
    {
        $this->appType = config('services.yida.app_type');
        $this->systemToken = config('services.yida.system_token');
        $this->appKey = config('services.yida.appkey');
        $this->appSecret = config('services.yida.appsecret');

        $access_token = Cache::get($this->cache_yida_access_token_key);

        if (! empty($access_token)) {
            $this->access_token = $access_token;
        } else {
            $this->get_access_token();
        }
    }

    /**
     * 获取access_token.
     */
    private function get_access_token(): void
    {
        $config = new Config([]);
        $config->protocol = 'https';
        $config->regionId = 'central';
        $client = new Dingtalk2($config);

        $getAccessTokenRequest = new GetAccessTokenRequest([
            'appKey' => $this->appKey,
            'appSecret' => $this->appSecret,
        ]);
        $rs = $client->getAccessToken($getAccessTokenRequest);

        $this->access_token = $rs->body?->accessToken;
        Cache::put($this->cache_yida_access_token_key, $rs->body->accessToken, bcsub($rs->body->expireIn, 20));
    }

    /**
     * 统一HTTP请求.
     *
     * @throws GuzzleException
     */
    public function httpSend(string $url, array $params = [], string $method = 'POST'): ResponseInterface
    {
        $client = new Client();
        if (! empty($params) && $method == 'POST') {
            $params = [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'form_params' => $params,
            ];
        }
        $url_params = [
            'access_token' => $this->access_token,
        ];
        $url = $url.'?'.http_build_query($url_params);
        if ($method == 'GET') {
            $url = $url.'?'.http_build_query($params);
        }

        return $client->request($method, $url, $params);
    }

    /**
     * 使用 Token 初始化账号Client
     *
     * @return Dingtalk Client
     */
    public static function createClient(): Dingtalk
    {
        $config = new Config([]);
        $config->protocol = 'https';
        $config->regionId = 'central';

        return new Dingtalk($config);
    }

    /**
     * 查询表单实例数据列表.
     * https://open.dingtalk.com/document/isvapp/querying-form-instance-data
     */
    public function getFormList($formUuid, $userId, $searchFieldJson = [], $currentPage = 1, $pageSize = 100): ?SearchFormDatasResponse
    {
        $client = self::createClient();
        $searchFormDatasHeaders = new SearchFormDatasHeaders([]);
        $searchFormDatasHeaders->xAcsDingtalkAccessToken = $this->access_token;
        $params = [
            'appType' => $this->appType,
            'systemToken' => $this->systemToken,
            'userId' => $userId,
            'formUuid' => $formUuid,
            'currentPage' => $currentPage,
            'pageSize' => $pageSize,
        ];
        if (! empty($searchFieldJson)) {
            $params['searchFieldJson'] = json_encode($searchFieldJson, JSON_UNESCAPED_UNICODE);
        }
        $searchFormDatasRequest = new SearchFormDatasRequest($params);

        try {
            $rs = $client->searchFormDatasWithOptions($searchFormDatasRequest, $searchFormDatasHeaders, new RuntimeOptions([]));
        } catch (\Exception $err) {
            info($err->getMessage().':'.__CLASS__.':'.json_encode($searchFieldJson, JSON_UNESCAPED_UNICODE));
            if ($err->getCode() == 'InvalidAuthentication') {
                $this->get_access_token();
                $rs = $this->getFormList($formUuid, $searchFieldJson, $userId, $currentPage, $pageSize);
            }
            DingNoticeService::sendNotify($userId, json_encode($err->getMessage(), JSON_UNESCAPED_UNICODE));
        }

        return $rs ?? null;
    }

    /**
     * 获取子表组件数据
     * https://open.dingtalk.com/document/isvapp/obtain-child-table-component-data
     */
    public function obtainChildTable($formUuid, $tableFieldId, $formInstanceId, $userId, $pageNumber = 1, $pageSize = 10): ListTableDataByFormInstanceIdTableIdResponse
    {
        $client = self::createClient();
        $listTableDataByFormInstanceIdTableIdHeaders = new ListTableDataByFormInstanceIdTableIdHeaders([]);
        $listTableDataByFormInstanceIdTableIdHeaders->xAcsDingtalkAccessToken = $this->access_token;
        $params = [
            'appType' => $this->appType,
            'systemToken' => $this->systemToken,
            'formUuid' => $formUuid,
            'tableFieldId' => $tableFieldId,
            'pageNumber' => $pageNumber,
            'pageSize' => $pageSize,
            'userId' => $userId,
        ];
        $listTableDataByFormInstanceIdTableIdRequest = new ListTableDataByFormInstanceIdTableIdRequest($params);

        return $client->listTableDataByFormInstanceIdTableIdWithOptions($formInstanceId, $listTableDataByFormInstanceIdTableIdRequest, $listTableDataByFormInstanceIdTableIdHeaders, new RuntimeOptions([]));
    }

    /**
     * 实例ID查询表单实例数据.
     * https://open.dingtalk.com/document/isvapp/query-form-data
     */
    public function getFormInstId($instId, $userId): GetFormDataByIDResponse
    {
        $client = self::createClient();
        $getFormDataByIDHeaders = new GetFormDataByIDHeaders([]);
        $getFormDataByIDHeaders->xAcsDingtalkAccessToken = $this->access_token;
        $getFormDataByIDRequest = new GetFormDataByIDRequest([
            'appType' => $this->appType,
            'systemToken' => $this->systemToken,
            'userId' => $userId,
        ]);

        return $client->getFormDataByIDWithOptions($instId, $getFormDataByIDRequest, $getFormDataByIDHeaders, new RuntimeOptions([]));
    }

    /**
     * 新增或更新表单实例.
     * https://open.dingtalk.com/document/isvapp/add-or-update-form-instances.
     */
    public function addOrUpdateFormInstances($searchCondition, $formData, $formUuid, $userId, $appType = null, $systemToken = null): ?CreateOrUpdateFormDataResponse
    {
        $client = self::createClient();
        $createOrUpdateFormDataHeaders = new CreateOrUpdateFormDataHeaders([]);
        $createOrUpdateFormDataHeaders->xAcsDingtalkAccessToken = $this->access_token;
        $createOrUpdateFormDataRequest = new CreateOrUpdateFormDataRequest([
            'appType' => $appType ?? $this->appType,
            'systemToken' => $systemToken ?? $this->systemToken,
            'userId' => $userId,
            'formUuid' => $formUuid,
            'searchCondition' => json_encode($searchCondition, JSON_UNESCAPED_UNICODE),
            'formDataJson' => json_encode($formData, JSON_UNESCAPED_UNICODE),
        ]);

        try {
            $rs = $client->createOrUpdateFormDataWithOptions($createOrUpdateFormDataRequest, $createOrUpdateFormDataHeaders, new RuntimeOptions([]));
        } catch (\Exception $err) {
            info($err->getMessage().':'.__CLASS__.':'.json_encode($formData, JSON_UNESCAPED_UNICODE));
            DingNoticeService::sendNotify($userId, json_encode($err->getMessage(), JSON_UNESCAPED_UNICODE));
        }

        return $rs ?? null;
    }

    /**
     * 新增表单实例.
     * https://open.dingtalk.com/document/isvapp/save-form-data.
     */
    public function saveFormData($formData, $formUuid, $userId, $appType = null, $systemToken = null): ?SaveFormDataResponse
    {
        $client = self::createClient();
        $saveFormDataHeaders = new SaveFormDataHeaders([]);
        $saveFormDataHeaders->xAcsDingtalkAccessToken = $this->access_token;
        $saveFormDataRequest = new SaveFormDataRequest([
            'appType' => $appType ?? $this->appType,
            'systemToken' => $systemToken ?? $this->systemToken,
            'userId' => $userId,
            'formUuid' => $formUuid,
            'language' => 'zh_CN',
            'formDataJson' => json_encode($formData, JSON_UNESCAPED_UNICODE),
        ]);
        try {
            $rs = $client->saveFormDataWithOptions($saveFormDataRequest, $saveFormDataHeaders, new RuntimeOptions([]));
        } catch (\Exception $err) {
            info($err->getMessage().':'.__CLASS__.':'.json_encode($formData, JSON_UNESCAPED_UNICODE));
            DingNoticeService::sendNotify($userId, json_encode($err->getMessage(), JSON_UNESCAPED_UNICODE));
        }

        return $rs ?? null;
    }

    /**
     * 更新表单数据
     * https://open.dingtalk.com/document/isvapp/update-form-data.
     */
    public function updateFormData($formInstanceId, $formData, $userId, $appType = null, $systemToken = null): ?UpdateFormDataResponse
    {
        $client = self::createClient();
        $updateFormDataHeaders = new UpdateFormDataHeaders([]);
        $updateFormDataHeaders->xAcsDingtalkAccessToken = $this->access_token;
        $updateFormDataRequest = new UpdateFormDataRequest([
            'appType' => $appType ?? $this->appType,
            'systemToken' => $systemToken ?? $this->systemToken,
            'userId' => $userId,
            'language' => 'zh_CN',
            'formInstanceId' => $formInstanceId,
            'updateFormDataJson' => json_encode($formData, JSON_UNESCAPED_UNICODE),
        ]);

        try {
            $rs = $client->updateFormDataWithOptions($updateFormDataRequest, $updateFormDataHeaders, new RuntimeOptions([]));
        } catch (\Exception $err) {
            info($err->getMessage().':'.__CLASS__.':'.json_encode($formData, JSON_UNESCAPED_UNICODE));
            DingNoticeService::sendNotify($userId, json_encode($err->getMessage(), JSON_UNESCAPED_UNICODE));
        }

        return $rs ?? null;
    }

    /**
     * 获取免登陆下载地址
     */
    public function downloadAttUrl($fileUrl, $userId, $appType = null, $systemToken = null): GetOpenUrlResponse
    {
        $client = self::createClient();
        $getOpenUrlHeaders = new GetOpenUrlHeaders([]);
        $getOpenUrlHeaders->xAcsDingtalkAccessToken = $this->access_token;
        $getOpenUrlRequest = new GetOpenUrlRequest([
            'systemToken' => $systemToken ?? $this->systemToken,
            'userId' => $userId,
            'language' => 'zh_CN',
            'fileUrl' => $fileUrl,
            'timeout' => 60000,
        ]);

        return $client->getOpenUrlWithOptions($appType ?? $this->appType, $getOpenUrlRequest, $getOpenUrlHeaders, new RuntimeOptions([]));
    }

    /**
     * 删除表单数据
     * https://open.dingtalk.com/document/isvapp/delete-form-data.
     */
    public function deleteFormData($formInstanceId, $userId): ?DeleteFormDataResponse
    {
        $client = self::createClient();
        $deleteFormDataHeaders = new DeleteFormDataHeaders([]);
        $deleteFormDataHeaders->xAcsDingtalkAccessToken = $this->access_token;
        $deleteFormDataRequest = new DeleteFormDataRequest([
            'appType' => $this->appType,
            'systemToken' => $this->systemToken,
            'userId' => $userId,
            'formInstanceId' => $formInstanceId,
        ]);

        try {
            $rs = $client->deleteFormDataWithOptions($deleteFormDataRequest, $deleteFormDataHeaders, new RuntimeOptions([]));
        } catch (\Exception $err) {
            info($err->getMessage());
        }

        return $rs ?? null;
    }
}

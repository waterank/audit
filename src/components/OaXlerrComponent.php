<?php

namespace waterank\audit\components;

use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use xlerr\httpca\ComponentTrait;
use xlerr\httpca\RequestClient;
use yii\base\UserException;


class OaXlerrComponent extends RequestClient implements OaComponentInterface
{
    use ComponentTrait;

    public $oaUrl;
    public $clientId;
    public $redirectUrl;
    public $responseType;
    public $scope;
    public $clientSecret;
    public $oaConfig;

    public function createOa($params, $auditType, $accessToken)
    {
        $oaTemplate = $this->oaConfig[$auditType]['flow_key'] ?? '';
        if (!$oaTemplate) {
            throw new UserException("找不到flow_key");
        }

        $this->post('openapi/approval/create', [
            RequestOptions::JSON    => [
                'flow_key'   => $oaTemplate,
                'entry_data' => $params,
            ],
            RequestOptions::HEADERS => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ],

        ]);

        $response = $this->getResponse();

        return (array)json_decode($response['data'] ?? '', true);
    }

    public function createBulkOa($params, $auditType, $accessToken, $setTotalConfig = [])
    {
        $oaTemplate = $this->oaConfig[$auditType]['flow_key'] ?? '';
        if (!$oaTemplate) {
            throw new UserException("找不到flow_key");
        }

        $this->post('openapi/approval/create', [
            RequestOptions::JSON    => [
                'flow_key'   => $oaTemplate,
                'entry_data' => $params,
            ],
            RequestOptions::HEADERS => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ],

        ]);

        $response = $this->getResponse();

        return (array)json_decode($response['data'] ?? '', true);
    }

    public function getAccessToken($userId, $oaRefreshToken)
    {
        $this->post('oauth/token', [
            RequestOptions::JSON => [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $oaRefreshToken,
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope'         => $this->scope,
            ],
        ]);

        return $this->getResponse();
    }

    public function getOaRedirectUrl($cacheKey)
    {
        $query = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUrl,
            'response_type' => $this->responseType,
            'scope'         => $this->scope,
            'state'         => $cacheKey,
        ]);

        return $this->oaUrl . 'oauth/authorize?' . $query;
    }

    public function getRefreshToken($code)
    {
        $this->post('oauth/token', [
            RequestOptions::JSON => [
                'grant_type'    => 'authorization_code',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri'  => $this->redirectUrl,
                'code'          => $code,
            ],
        ]);

        return $this->getResponse();
    }

    /**
     * @param ResponseInterface $response
     *
     * @return array
     */
    protected function handleResponse(ResponseInterface $response)
    {
        $content = (string)$response->getBody();

        return (array)json_decode($content, true);
    }

    public function getClientToken()
    {
        $this->post('oauth/token', [
            RequestOptions::JSON => [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope'         => $this->scope,
            ],
        ]);

        return $this->getResponse();
    }

    public function getOaNodeInfo($accessToken, $entry_ids)
    {
        $this->get('openapi/approval/queryDetail?entry_ids=' . $entry_ids, [
            RequestOptions::HEADERS => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        return $this->getResponse();
    }

    /**
     * 获取OA审核单在配置中对应的业务类路径
     *
     * @param $auditType
     *
     * @return mixed|string
     */
    public function getBusinessLogicClass($auditType)
    {
        return $this->oaConfig[$auditType]['business_logic_class'] ?? '';
    }

    public function getAuditTypeList()
    {
        $config = $this->oaConfig;
        $result = [];
        foreach ($config as $item => $value) {
            if ($item == 'oauth') {
                continue;
            }
            $result[$item] = $value['note'] ?? '未知';
        }

        return $result;
    }
}
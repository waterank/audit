<?php

namespace waterank\audit\components;

use common\helpers\RestHelper;
use common\models\KeyValue;
use yii\base\UserException;

class OaCapitalComponent implements OaComponentInterface
{

    public $oaUrl;

    public $clientId;
    public $redirectUrl;
    public $responseType;
    public $scope;
    public $clientSecret;
    public $oaConfig;


    public function __construct()
    {
        $oaConfig           = KeyValue::getValueAsArray('oa_oauth_config');
        $this->oaConfig     = $oaConfig;
        $this->oaUrl        = $oaConfig['oauth']['oa_url'] ?? '';
        $this->clientId     = $oaConfig['oauth']['client_id'] ?? '';
        $this->redirectUrl  = $oaConfig['oauth']['redirect_url'] ?? '';
        $this->responseType = $oaConfig['oauth']['response_type'] ?? '';
        $this->scope        = $oaConfig['oauth']['scope'] ?? '';
        $this->clientSecret = $oaConfig['oauth']['client_secret'] ?? '';
    }

    /**
     * 创建OA审核单
     *
     * @param $params
     * @param $auditType
     * @param $accessToken
     *
     * @return array
     * @throws \Exception
     */
    public function createOa($params, $auditType, $accessToken)
    {
        $oaTemplate = $this->oaConfig[$auditType]['flow_key'] ?? '';
        if (!$oaTemplate) {
            throw new UserException("找不到flow_key");
        }
        $responseRaw = RestHelper::postWithJson($this->oaUrl . '/openapi/approval/create',
            json_encode(
                [
                    'flow_key'   => $oaTemplate,
                    'entry_data' => $params,
                ]
            ),
            [
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization:Bearer ' . $accessToken],
                CURLOPT_HEADER     => 0,
            ]
        );
        $response    = (array)json_decode($responseRaw, true);
        if (!empty($response['code'])) {
            throw new UserException($responseRaw);
        }

        return (array)json_decode($response['data'] ?? '', true);
    }

    /**
     * 创建OA审核单(内包含多条数据的表格)
     *
     * @param $params
     * @param $auditType
     * @param $accessToken
     *
     * @return array
     * @throws \Exception
     */
    public function createBulkOa($params, $auditType, $accessToken,$setTotalConfig = [])
    {
        $oaTemplate = $this->oaConfig[$auditType]['flow_key'] ?? '';
        if (!$oaTemplate) {
            throw new UserException("找不到flow_key");
        }
        $responseRaw = RestHelper::postWithJson($this->oaUrl . '/openapi/approval/create',
            json_encode(
                [
                    'flow_key'   => $oaTemplate,
                    'entry_data' => $params,
                ]
            ),
            [
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization:Bearer ' . $accessToken],
                CURLOPT_HEADER     => 0,
            ]
        );
        $response    = (array)json_decode($responseRaw, true);
        if (!empty($response['code'])) {
            throw new UserException($responseRaw);
        }

        return (array)json_decode($response['data'] ?? '', true);
    }

    /**
     * 获取accessToken
     *
     * @param $userId
     * @param $oaRefreshToken
     *
     * @return array
     * @throws \Exception
     */
    public function getAccessToken($userId, $oaRefreshToken)
    {
        $responseRaw = RestHelper::postWithJson($this->oaUrl . '/oauth/token',
            json_encode(
                [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $oaRefreshToken,
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope'         => $this->scope,
                ]
            )
        );

        return $response = (array)json_decode($responseRaw, true);;
    }

    /**
     * 获取refreToken
     *
     * @param $codecreateOa
     *
     * @return array
     * @throws \Exception
     */
    public function getRefreshToken($code)
    {
        $responseRaw = RestHelper::postWithJson($this->oaUrl . '/oauth/token',
            json_encode(
                [
                    'grant_type'    => 'authorization_code',
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'redirect_uri'  => $this->redirectUrl,
                    'code'          => $code,
                ]
            )
        );

        return $response = (array)json_decode($responseRaw, true);
    }


    /**
     * 获取跳转到OA授权的URL
     *
     * @param $cacheKey
     *
     * @return string
     */
    public function getOaRedirectUrl($cacheKey)
    {
        $query = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUrl,
            'response_type' => $this->responseType,
            'scope'         => $this->scope,
            'state'         => $cacheKey,
        ]);

        return $this->oaUrl . '/oauth/authorize?' . $query;
    }

    /**
     * 获取OA客户端模式的认证TOKEN
     *
     * @return array
     * @throws \Exception
     */
    public function getClientToken()
    {
        $responseRaw = RestHelper::postWithJson($this->oaUrl . '/oauth/token',
            json_encode(
                [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope'         => $this->scope,
                ]
            )
        );

        return $response = (array)json_decode($responseRaw, true);
    }

    /**
     * 获取OA审核节点信息
     *
     * @param $accessToken
     * @param $entry_ids
     *
     * @return array
     */
    public function getOaNodeInfo($accessToken, $entry_ids)
    {
        $responseRaw = RestHelper::getWithForm(
            $this->oaUrl . '/openapi/approval/queryDetail?entry_ids=' . $entry_ids,
            [],
            ['Content-Type: application/json', 'Authorization:Bearer ' . $accessToken]
        );

        return (array)json_decode($responseRaw ?? [], true);
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

    /**
     * 获取审核类型列表
     */
    public function getAuditTypeList()
    {
        
        $config = $this->oaConfig;
        $result = [];
        foreach ($config as $item => $value) {
            if($item == 'oauth'){
                continue;
            }
            $result[$item] = $value['note'] ?? '未知';
        }
        return $result;
    }

}
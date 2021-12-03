<?php

namespace waterank\audit\components;

use waterank\audit\service\AuditService;
use yii;

class OaHttpComponent implements OaComponentInterface
{
    public $oaHttpClient;

    public function __construct()
    {
        if (class_exists('\\common\\helpers\\RestHelper')) {
            $this->oaHttpClient = new OaCapitalComponent();
        } elseif (class_exists('\\xlerr\\httpca\\RequestClient')) {
            $this->oaHttpClient = OaXlerrComponent::instance();
        }
    }

    /**
     * 创建OA审核单
     * @param $params
     * @param $auditType
     * @param $accessToken
     *
     * @return array
     * @throws \Exception
     */
    public function createOa($params, $auditType, $accesToken)
    {
        return $this->oaHttpClient->createOa($params, $auditType, $accesToken);
    }

    /**
     * 获取accessToken
     * @param $userId
     * @param $oaRefreshToken
     *
     * @return array
     * @throws \Exception
     */
    public function getAccessToken($userId, $oaRefreshToken)
    {
        $accesInfo = $this->oaHttpClient->getAccessToken($userId,$oaRefreshToken);
        Yii::$app->redis->setex($userId . AuditService::$oaRefreshTokenKey, 60 * 60 * 24 * 14,
            $accesInfo['refresh_token'] ?? '');
        return $accesInfo['access_token']?? '';
    }

    /**
     * 获取跳转到OA授权URL
     * @param $redisKey
     *
     * @return string
     */
    public function getOaRedirectUrl($redisKey)
    {
        return $this->oaHttpClient->getOaRedirectUrl($redisKey);
    }

    /**
     * 获取refreToken
     * @param $code
     *
     * @return array
     * @throws \Exception
     */
    public function getRefreshToken($code)
    {
        return $this->oaHttpClient->getRefreshToken($code);
    }

    /**
     * 获取OA客户端模式的认证TOKEN
     * @return array
     * @throws \Exception
     */
    public function getClientToken()
    {
        $accesInfo =  $this->oaHttpClient->getClientToken();
        return $accesInfo['access_token']?? '';
    }


    /**
     * 获取OA审核节点信息
     * @param $accessToken
     * @param $entry_ids
     *
     * @return array
     */
    public function getOaNodeInfo($accessToken,$entry_ids)
    {
        return $this->oaHttpClient->getOaNodeInfo($accessToken,$entry_ids);
    }

    /**
     * 获取OA审核单在配置中对应的业务类路径
     * @param $auditType
     *
     * @return mixed|string
     */
    public function getBusinessLogicClass($auditType)
    {
        return $this->oaHttpClient->getBusinessLogicClass($auditType);
    }

    /**
     * 获取审核类型列表
     * @return mixed
     */
    public function getAuditTypeList()
    {
        return $this->oaHttpClient->getAuditTypeList();
    }

}
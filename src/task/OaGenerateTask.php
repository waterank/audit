<?php

namespace waterank\audit\task;

use waterank\audit\components\OaHttpComponent;
use waterank\audit\models\Audit;
use waterank\audit\service\AuditService;
use xlerr\proxy\task\ProxyTaskHandler;
use yii\base\UserException;
use yii\db\Exception;
use yii;

class OaGenerateTask extends ProxyTaskHandler
{

    public static function process($data): array
    {
        $oaComponent    = new OaHttpComponent();
        $audit          = self::findByAuditId($data['dataId'] ?? 0);
        $auditType      = $audit->audit_type;
        $userId         = $audit->audit_creator_id;
        $params = json_decode($audit->audit_oa_params, true);
        if(isset($params['params'])){
            $oaParams =  $params['params']['WorkflowApply'] ?? [];
        }else{
            $oaParams = $params;
        }
        $oaRefreshToken = Yii::$app->getCache()->get($userId . AuditService::$oaRefreshTokenKey);
        $accesInfo    = $oaComponent->getAccessToken($userId, $oaRefreshToken);
        if (empty($accesInfo['access_token'])) {
            throw new UserException("accesToken 获取失败:".json_encode($accesInfo, JSON_UNESCAPED_UNICODE));
        }
        $accessToken = $accesInfo['access_token'];
        $oaResponse         = $oaComponent->createOa($oaParams, $auditType, $accessToken);
        $oaId               = $oaResponse['entry_id'] ?? 0;
        $audit->audit_oa_id = $oaId;
        $audit->audit_status = Audit::STATUS_WAIT_OA_AUDIT;
        if (!$audit->save()) {
            throw new UserException(json_encode($audit->getErrors(), JSON_UNESCAPED_UNICODE));
        }

        return [
            'code'    => 0,
            'message' => 'ok',
            'data'    => null,
        ];
    }

    public static function findByAuditId($id): Audit
    {
        $audit = Audit::findOne(['audit_id' => $id]);
        if (!$audit) {
            throw new Exception("数据不存在!");
        }

        return $audit;
    }
}
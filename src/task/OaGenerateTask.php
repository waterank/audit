<?php

namespace waterank\audit\task;

use waterank\audit\components\OaHttpComponent;
use waterank\audit\models\Audit;
use waterank\audit\service\AuditService;
use xlerr\proxy\task\ProxyTaskHandler;
use yii\base\UserException;
use yii\db\Exception;
use Yii;

class OaGenerateTask extends ProxyTaskHandler
{
    public static function process($data): array
    {
        $oaComponent = new OaHttpComponent();
        $audit       = self::findByAuditId($data['dataId'] ?? 0);
        $auditType   = $audit->audit_type;
        $userId      = $audit->audit_creator_id;
        $params      = json_decode($audit->audit_oa_params, true);
        if (isset($params['params'])) {
            $oaParams = $params['params']['WorkflowApply'] ?? [];
        } else {
            $oaParams = $params;
        }

        $accessToken = Yii::$app->getCache()->get($userId . AuditService::$oaAccessTokenKey);
        if (!$accessToken) {
            $oaRefreshToken = Yii::$app->getCache()->get($userId . AuditService::$oaRefreshTokenKey);
            if (!$oaRefreshToken) {
                throw new UserException("refreshToken已经过期，请重新提交审核单");
            }
            $accessInfo = $oaComponent->getAccessToken($userId, $oaRefreshToken);
            if (empty($accessInfo['access_token'])) {
                throw new UserException("accessToken 获取失败:" . json_encode($accessInfo, JSON_UNESCAPED_UNICODE)
                    . 'refreshToken:' . $oaRefreshToken);
            }
            $accessToken = $accessInfo['access_token'];
        }
        if(stristr($auditType,'bulk')){
            if(in_array($auditType,['bulk_transfer','bulk_withdraw'])){
                $setTotalConfig = [
                    'attribute'=>'clearing_manual_amount'
                ];
                $oaResponse          = $oaComponent->createBulkOa($oaParams, $auditType, $accessToken,$setTotalConfig);
            }else{
                $oaResponse          = $oaComponent->createBulkOa($oaParams, $auditType, $accessToken);
            }
        }else{
            $oaResponse          = $oaComponent->createOa($oaParams, $auditType, $accessToken);
        }
        if(empty($oaResponse['entry_id'])){
            throw new UserException('请求创建OA审核单接口失败'.json_encode($oaResponse, JSON_UNESCAPED_UNICODE));
        }
        $oaId                = $oaResponse['entry_id'];
        $audit->audit_oa_id  = $oaId;
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
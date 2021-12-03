<?php

namespace waterank\audit\task;

use waterank\audit\business\BusinessInterface;
use waterank\audit\components\OaHttpComponent;
use waterank\audit\models\Audit;
use waterank\audit\service\AuditService;
use xlerr\proxy\task\ProxyTaskHandler;
use yii\base\UserException;
use yii\db\Exception;
use yii;

class OaCallbackTask extends ProxyTaskHandler
{

    public static function process($data): array
    {
        $oaComponent = new OaHttpComponent();
        $status      = $data['status'] ?? '';
        $audit       = self::findByOaId($data['dataId'] ?? 0);
        $oaAgree     = false;
        switch ($status) {
            case Audit::OA_AGREE_STATUS:
                $audit->audit_status = Audit::STATUS_SUCCESS;
                $oaAgree             = true;
                break;
            case Audit::OA_REFUSE_STATUS:
                $audit->audit_status = Audit::STATUS_FAILURE;
                break;
            default:
                $audit->audit_status = Audit::STATUS_FAILURE;
        }
        $accessToken = $oaComponent->getClientToken();
        if (!$accessToken) {
            throw new UserException("accesToken 获取失败");
        }
        //获取审核节点信息
        $oaNodeInfo                  =
            json_encode($oaComponent->getOaNodeInfo($accessToken, $audit->audit_oa_id), JSON_UNESCAPED_UNICODE);
        $audit->audit_oa_response    = $oaNodeInfo;
        $audit->audit_oa_finished_at = date("Y-m-d H:i:s");
        $transaction                 = Audit::getDb()->beginTransaction();
        try {
            if (!$audit->save()) {
                throw new UserException(json_encode($audit->getErrors(), JSON_UNESCAPED_UNICODE));
            }
            //若OA审核同意，实例化配置中对应审核类型的业务类，执行相应的业务逻辑
            if ($oaAgree) {
                $businessClassName = $oaComponent->getBusinessLogicClass($audit->audit_type);
                $businessClass     = Yii::createObject($businessClassName);
                if ($businessClass instanceof BusinessInterface) {
                    $businessClass->process($audit); 
                } else {
                    throw new UserException('无法加载业务类：' . $businessClassName);
                }
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw new UserException(json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE));
        }

        return [
            'code'    => 0,
            'message' => 'ok',
            'data'    => null,
        ];
    }

    public static function findByOaId($id): Audit
    {
        $audit = Audit::findOne(['audit_oa_id' => $id]);
        if (!$audit) {
            throw new Exception("审核数据不存在!");
        }

        return $audit;
    }
}
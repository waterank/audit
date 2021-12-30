<?php

namespace waterank\audit\task;

use waterank\audit\business\BusinessInterface;
use waterank\audit\components\OaHttpComponent;
use waterank\audit\models\Audit;
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
        $oaId        = $data['dataId'] ?? 0;
        $audit       = self::findByOaId($oaId);
        switch ($status) {
            case Audit::OA_AGREE_STATUS:
                $makeStatus = Audit::STATUS_SUCCESS;
                break;
            case Audit::OA_REFUSE_STATUS:
                $makeStatus = Audit::STATUS_FAILURE;
                break;
            default:
                $makeStatus = Audit::STATUS_FAILURE;
        }
        $accessToken = $oaComponent->getClientToken();
        if (!$accessToken) {
            throw new UserException("accesToken 获取失败");
        }
        //获取审核节点信息 并验证OA审核单的状态  必须跟传过来的状态吻合
        $oaNodeInfo = $oaComponent->getOaNodeInfo($accessToken, $audit->audit_oa_id);
        $nodeData   = json_decode((string)$oaNodeInfo['data'] ?? '', true);

        if (!isset($nodeData['status_code']) || $nodeData['status_code'] != $status) {
            throw new UserException("无法获取oa单状态或oa单状态与提交的状态不符");
        }
        $oaNodeInfo = json_encode($oaNodeInfo, JSON_UNESCAPED_UNICODE);

        $transaction = Audit::getDb()->beginTransaction();
        try {
            $processed = Audit::updateAll([
                'audit_status'         => $makeStatus,
                'audit_oa_response'    => $oaNodeInfo,
                'audit_oa_finished_at' => date("Y-m-d H:i:s"),
            ], [
                'audit_status' => Audit::STATUS_WAIT_OA_AUDIT,
                'audit_oa_id'  => $oaId,
            ]);
            if ($processed !== 1) {
                throw new UserException("更新audit失败，请检查是否存在audit_status=" .
                    Audit::STATUS_WAIT_OA_AUDIT . ",audit_oa_id=$oaId");
            }
            //实例化配置中对应审核类型的业务类，执行相应的业务逻辑
            $businessClassName = $oaComponent->getBusinessLogicClass($audit->audit_type);
            $businessClass     = Yii::createObject($businessClassName);
            if ($businessClass instanceof BusinessInterface) {
                $businessClass->process($audit);
            } else {
                throw new UserException('无法加载业务类：' . $businessClassName);
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
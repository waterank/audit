<?php

namespace waterank\audit\task;

use waterank\audit\models\Audit;
use xlerr\proxy\task\ProxyTaskHandler;
use yii\base\UserException;
use yii\db\Exception;

class BusinessCallbackTask extends ProxyTaskHandler
{

    /**
     * @throws \yii\db\Exception
     * @throws \yii\base\UserException
     */
    public static function process($data): array
    {
        $status       = $data['status'] ?? '';
        $finishTime   = $data['finish_time'] ?? '';
        $memo         = $data['memo'] ?? '';
        $statusDetail = $data['status_detail'] ?? '';
        $auditSource  = $data['audit_source'] ?? 'business_key';
        $audit        = '';
        switch ($auditSource) {
            case 'business_key':
                $audit = self::findByBusinessKey($data['key'] ?? 0);
                break;
            case 'audit_id':
                $audit = self::findById($data['key'] ?? 0);
                break;
        }
        if (!$audit) {
            throw new Exception("audit_source无法验证:" . $auditSource);
        }
        switch ($status) {
            case Audit::BUSINESS_FAILURE:
                $audit->business_status = Audit::BUSINESS_FAILURE;
                break;
            case Audit::BUSINESS_END:
                $audit->business_status = Audit::BUSINESS_END;
                break;
            default:
                $audit->business_status = Audit::BUSINESS_FAILURE;
        }
        if ($finishTime) {
            $audit->business_finished_at = $finishTime;
        }
        if ($memo) {
            $audit->business_note = $memo;
        }
        if ($statusDetail) {
            $audit->business_status_detail = $statusDetail;
        }
        {
            if (!$audit->save()) {
                throw new UserException(json_encode($audit->getErrors(), JSON_UNESCAPED_UNICODE));
            }
        }

        return [
            'code'    => 0,
            'message' => 'ok',
            'data'    => null,
        ];
    }

    public static function findByBusinessKey($key): Audit
    {
        $audit = Audit::findOne(['business_key' => $key]);
        if (!$audit) {
            throw new Exception("审核数据不存在!");
        }

        return $audit;
    }

    public static function findById($key): Audit
    {
        $audit = Audit::findOne(['audit_id' => $key]);
        if (!$audit) {
            throw new Exception("审核数据不存在!");
        }

        return $audit;
    }
}
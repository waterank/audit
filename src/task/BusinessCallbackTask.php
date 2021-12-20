<?php

namespace waterank\audit\task;

use waterank\audit\business\BusinessInterface;
use waterank\audit\components\OaHttpComponent;
use waterank\audit\models\Audit;
use xlerr\proxy\task\ProxyTaskHandler;
use yii\base\UserException;
use yii\db\Exception;
use yii;

class BusinessCallbackTask extends ProxyTaskHandler
{

    /**
     * @throws \yii\db\Exception
     * @throws \yii\base\UserException
     */
    public static function process($data): array
    {
        $status     = $data['status'] ?? '';
        $finishTime = $data['finish_time'] ?? '';
        $audit      = self::findByBusinessKey($data['key'] ?? 0);
        switch ($status) {
            case Audit::BUSINESS_FAILURE:
                $audit->business_status = Audit::BUSINESS_FAILURE;
                break;
            case Audit::BUSINESS_SUCCESS:
                $audit->business_status = Audit::BUSINESS_SUCCESS;
                break;
            default:
                $audit->business_status = Audit::BUSINESS_FAILURE;
        }
        if ($finishTime) {
            $audit->business_finished_at = $finishTime;
        }
        if (!$audit->save()) {
            throw new UserException(json_encode($audit->getErrors(), JSON_UNESCAPED_UNICODE));
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
}
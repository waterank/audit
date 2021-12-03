<?php

namespace waterank\audit\service;

use common\helpers\RestHelper;
use waterank\audit\components\OaHttpComponent;
use waterank\audit\models\Audit;
use waterank\audit\models\AuditSearch;
use waterank\audit\task\OaGenerateTask;
use yii\base\Controller;
use yii\base\Object;
use yii\helpers\Url;
use yii;
use yii\base\UserException;

class AuditService extends Controller
{

    public static $oaRefreshTokenKey = '_oa_refresh_token';

    /**
     * OA审核流程入口
     *
     * @param $paramsKey
     * @param $auditType
     * @param $params
     *
     * @return \yii\console\Response|\yii\web\Response
     * @throws \yii\base\UserException
     */
    public static function oaAudit($paramsKey, $auditType, $params)
    {
        $oaComponent = new OaHttpComponent();

        $userId    = Yii::$app->user->id;
        $userName  = Yii::$app->user->identity->fullname;
        $userEmail = Yii::$app->user->identity->email;
        $request   = Yii::$app->request;
        $userInfo  = [
            'user_id'    => $userId,
            'user_name'  => $userName,
            'user_email' => $userEmail,
        ];
        $redisKey  = self::saveOaCache($userInfo, $paramsKey, $auditType, $request, $params);
        // 如果没有token 或者token存留时间小于1天（留一天用作异步TASK余量） 发起OA授权跳转
        $oaRefreshToken = self::checkInvalid($userId);
        if (!$oaRefreshToken) {
            $url = $oaComponent->getOaRedirectUrl($redisKey);

            Yii::$app->getResponse()->redirect(Url::to($url), 302);
        }
        //生成AUDIT数据 开启OA task
        $auditModelParams = [
            'audit_oa_params' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'user_id'         => $userId,
            'user_name'       => $userName,
            'user_email'      => $userEmail,
            'audit_type'      => $auditType,
        ];
        self::saveAuditGenerateOa($auditModelParams);
    }

    /**
     * 将OA审核信息保存到缓存 供OA授权完成后跳转地址使用  \waterank\\audit\\controllers\ApiController actionOaRedirect
     *
     * @param array $userInfo
     * @param       $paramsKey
     * @param       $auditType
     * @param       $request
     * @param       $params
     *
     * @return string
     */
    public static function saveOaCache(array $userInfo, $paramsKey, $auditType, $request, $params)
    {
        $redisKey  = $userInfo['user_id'] . '_' . time();
        $redisInfo = [
            'params'     => [
                'key'   => $paramsKey,
                'value' => $params,
                'route' => '/' . $request->getPathInfo(),
            ],
            'user_info'  => $userInfo,
            'audit_type' => $auditType,
        ];
        Yii::$app->redis->setex($redisKey, 60 * 60, json_encode($redisInfo, JSON_UNESCAPED_UNICODE));

        return $redisKey;
    }


    /**
     * 判断OA的refreshToken是否过期
     *
     * @param $userId
     *
     * @return string
     */
    public static function checkInvalid($userId)
    {
        $oaRefreshToken = Yii::$app->redis->get($userId . self::$oaRefreshTokenKey);
        $tokenTtl       = Yii::$app->redis->ttl($userId . self::$oaRefreshTokenKey);

        return (!$oaRefreshToken || $tokenTtl <= 60 * 60 * 24) ? '' : $oaRefreshToken;
    }

    /**
     * 生成AUDIT审核表数据 并开启创建OA审核单异步TASK
     *
     * @param $auditModelParams
     *
     * @throws \yii\base\UserException
     * @throws \yii\db\Exception
     */
    public static function saveAuditGenerateOa($auditModelParams)
    {
        //生成审核表数据
        $audit                   = new Audit();
        $oaComponent             = new OaHttpComponent();
        $audit->audit_status     = Audit::STATUS_PROCESSING;
        $audit->audit_oa_params  = $auditModelParams['audit_oa_params'];
        $audit->audit_user_id    = $auditModelParams['user_id'];
        $audit->audit_user_name  = $auditModelParams['user_name'];
        $audit->audit_user_email = $auditModelParams['user_email'];
        $audit->audit_type       = $auditModelParams['audit_type'];
        $transaction             = Audit::getDb()->beginTransaction();
        try {
            if ($audit->save()) {
                OaGenerateTask::make([
                    'dataId' => $audit->audit_id,
                ]);
            } else {
                throw new UserException(json_encode($audit->getErrors(), JSON_UNESCAPED_UNICODE));
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw new UserException(json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE));
        }
        Yii::$app->getResponse()->redirect([
            '/audit/audit/index',
            (new AuditSearch())->formName() => [
                'audit_type' => $audit->audit_type,
            ],
        ], 302);
    }
}

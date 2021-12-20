<?php

namespace waterank\audit\service;

use waterank\audit\components\OaHttpComponent;
use waterank\audit\models\Audit;
use waterank\audit\task\OaGenerateTask;
use yii\base\Controller;
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
        $cacheKey  = self::saveOaCache($userInfo, $paramsKey, $auditType, $request, $params);
        // 如果没有token 或者token存留时间小于1天（留一天用作异步TASK余量） 发起OA授权跳转
//        $oaRefreshToken = self::checkInvalid($userId);
//        if (!$oaRefreshToken) {
            $url = $oaComponent->getOaRedirectUrl($cacheKey);

            return Yii::$app->getResponse()->redirect($url);
//        }
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
        $cacheKey  = $userInfo['user_id'] . '_' . time();
        $cacheInfo = [
            'params'     => [
                'key'   => $paramsKey,
                'value' => $params,
                'route' => '/' . $request->getPathInfo(),
            ],
            'user_info'  => $userInfo,
            'audit_type' => $auditType,
        ];
        Yii::$app->getCache()->set($cacheKey, $cacheInfo, 60 * 60);

        return $cacheKey;
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
        return Yii::$app->getCache()->get($userId . self::$oaRefreshTokenKey);
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
        $audit                      = new Audit();
        $audit->audit_status        = Audit::STATUS_PROCESSING;
        $audit->audit_oa_params     = $auditModelParams['audit_oa_params'];
        $audit->audit_creator_id    = $auditModelParams['user_id'];
        $audit->audit_creator_name  = $auditModelParams['user_name'];
        $audit->audit_creator_email = $auditModelParams['user_email'];
        $audit->audit_type          = $auditModelParams['audit_type'];
        $transaction                = Audit::getDb()->beginTransaction();
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
        Yii::$app->session->addFlash("success", "你已成功提交，需要先经过OA审核，请在此审核列表</a>中查看进度");

//        return  Yii::$app->getResponse()->redirect($auditModelParams['referrer']);
        return Yii::$app->getResponse()->redirect('/audit/audit/index');
    }

}

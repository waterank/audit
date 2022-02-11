<?php

namespace waterank\audit\service;

use Throwable;
use waterank\audit\components\OaHttpComponent;
use waterank\audit\models\Audit;
use waterank\audit\task\OaGenerateTask;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\UserException;
use yii\web\Application;
use yii\web\Response;

class AuditService
{
    public static $oaRefreshTokenKey = '_oa_refresh_token';
    public static $oaAccessTokenKey = '_oa_access_token';

    /**
     * OA审核流程入口
     *
     * @param string $paramsKey
     * @param string $auditType
     * @param mixed  $params
     * @param array  $custom
     * @param bool   $returnUrl
     *
     * @return Response|string
     * @throws InvalidConfigException
     * @throws UserException
     */
    public static function oaAudit($paramsKey, $auditType, $params, $custom = [], $returnUrl = false)
    {
        $app = Yii::$app;
        if (!$app instanceof Application) {
            throw new UserException('只能在页面中创建审核单');
        }

        $cacheInfo = [
            'params'     => [
                'key'   => $paramsKey,
                'value' => $params,
                'route' => '/' . $app->request->getPathInfo(),
            ],
            'user_info'  => [
                'user_id'    => $app->user->id,
                'user_name'  => $app->user->identity->fullname,
                'user_email' => $app->user->identity->email,
            ],
            'audit_type' => $auditType,
        ];
        if ($custom) {
            $cacheInfo['custom'] = $custom;
        }

        $cacheKey = $app->user->id . '_' . uuid_create();

        if (!$app->getCache()->set($cacheKey, $cacheInfo, 60 * 60)) {
            throw new UserException('缓存审核数据失败');
        }

        $url = (new OaHttpComponent())->getOaRedirectUrl($cacheKey);

        return $returnUrl ? $url : $app->response->redirect($url);
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
     * @param array $auditModelParams
     *
     * @return Response
     * @throws UserException
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
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw new UserException(json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE));
        }
        Yii::$app->session->addFlash("success", "你已成功提交，需要先经过OA审核，请在此审核列表</a>中查看进度");

        //        return  Yii::$app->getResponse()->redirect($auditModelParams['referrer']);
        return Yii::$app->getResponse()->redirect('/audit/audit/index');
    }
}

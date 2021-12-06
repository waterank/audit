<?php

namespace waterank\audit\controllers;

use waterank\audit\components\OaHttpComponent;
use waterank\audit\service\AuditService;
use waterank\audit\task\OaCallbackTask;
use yii;
use yii\rest\Controller;
use yii\web\Response;

class ApiController extends Controller
{
    /**
     * OA授权回跳地址，并保存audit信息，并开启创建OA审核TASK
     *
     * @return \yii\web\Response
     * @throws \yii\base\UserException
     */
    public function actionOaRedirect()
    {
        $apiParams = Yii::$app->request->get();
        $state     = $apiParams['state'] ?? '';
        $code      = $apiParams['code'] ?? '';
        $error     = $apiParams['error'] ?? '';
        $data      = Yii::$app->getCache()->get($state);
        if (!$data) {
            return $this->redirect('index');
        }
        $params    = $data['params'] ?? [];
        $userInfo  = $data['user_info'] ?? [];
        $userId    = $userInfo['user_id'] ?? 0;
        $userName  = $userInfo['user_name'] ?? '';
        $userEmail = $userInfo['user_email'] ?? '';
        $auditType = $data['audit_type'] ?? '';

        if (!$code && $error) { //拒绝的话原路跳回页面并渲染
            return $this->redirect([
                $params['route'] ?? '/index',
                $params['key'] ?? 'oaRedirece' => $params['value'] ?? [],
                'refuse'                       => 1,
            ]);
        }
        $oaComponent = new OaHttpComponent();

        $response = $oaComponent->getRefreshToken($code);
        if (!empty($response['error'])) {
            return $this->redirect([
                $params['route'] ?? '/index',
                $params['key'] ?? 'oaRedirect' => $params['value'] ?? [],
                'refuse'                       => 2,
            ]);
        }
        if (!empty($response['refresh_token'])) {
            Yii::$app->getCache()->set($userId . AuditService::$oaRefreshTokenKey, $response['refresh_token'],
                60 * 60 * 24 * 13);
        }
        //生成AUDIT数据 开启OA task
        $auditModelParams = [
            'audit_oa_params' => json_encode($params['value'] ?? [], JSON_UNESCAPED_UNICODE),
            'user_id'         => $userId,
            'user_name'       => $userName,
            'user_email'      => $userEmail,
            'audit_type'      => $auditType,
        ];
        AuditService::saveAuditGenerateOa($auditModelParams);
    }

    /**
     * OA审核通过或拒绝，回调API
     *
     * @throws \Throwable
     */
    public function actionOaCallback()
    {
        $eventData                  = Yii::$app->request->post('event_data');
        $eventDataArray             = json_decode($eventData ?? '', true);
        $id                         = $eventDataArray['entry']['entry_id'] ?? 0;
        $statusCode                 = $eventDataArray['entry']['status_code'] ?? 0;
        Yii::$app->response->format = Response::FORMAT_RAW;
        if (!empty($data['debug'])) {
            $id         = $data['id'];
            $statusCode = $data['status'];
        }
        OaCallbackTask::make([
            'dataId' => $id,
            'status' => $statusCode,
        ]);

        return 'success';
    }
}
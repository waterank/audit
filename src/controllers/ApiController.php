<?php

namespace waterank\audit\controllers;

use common\consts\Consts;
use common\helpers\RestHelper;
use waterank\audit\components\OaHttpComponent;
use waterank\audit\models\Audit;
use waterank\audit\service\AuditService;
use waterank\audit\task\OaCallbackTask;
use waterank\audit\task\OaGenerateTask;
use yii;
use yii\base\Object;
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
        $data      = Yii::$app->redis->get($state);
        if (!$data) {
            return $this->redirect('index');
        }
        $data      = json_decode($data, true);
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
            Yii::$app->redis->setex($userId . AuditService::$oaRefreshTokenKey, 60 * 60 * 24 * 14,
                $response['refresh_token']);
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
     * @throws \Throwable
     */
    public function actionOaCallback()
    {
        $data = Yii::$app->request->post();
        $eventData                  = $data['event_data'] ?? '';
        $eventDataArray             = json_decode($eventData, true);
        $info                       = $eventDataArray['entry'] ?? [];
        $id                         = $eventDataArray['entry']['entry_id'] ?? 0;
        $statusCode                 = $eventDataArray['entry']['status_code'] ?? 0;
        Yii::$app->response->format = Response::FORMAT_JSON;
        if(!empty($data['debug'])){
            $id         = $data['id'];
            $statusCode = $data['status']; 
        }
        OaCallbackTask::make([
            'dataId' => $id,
            'status' => $statusCode,
        ]);
        echo 'success';
        exit;
    }
}
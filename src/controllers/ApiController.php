<?php

namespace waterank\audit\controllers;

use Throwable;
use waterank\audit\components\OaHttpComponent;
use waterank\audit\models\Audit;
use waterank\audit\service\AuditService;
use waterank\audit\task\BusinessCallbackTask;
use waterank\audit\task\OaCallbackTask;
use Yii;
use yii\base\UserException;
use yii\db\Exception;
use yii\rest\Controller;
use yii\web\Response;

class ApiController extends Controller
{
    /**
     * OA授权回跳地址，并保存audit信息，并开启创建OA审核TASK
     *
     * @return Response
     * @throws UserException
     * @throws Exception
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
        $params       = $data['params'] ?? [];
        $userInfo     = $data['user_info'] ?? [];
        $userId       = $userInfo['user_id'] ?? 0;
        $userName     = $userInfo['user_name'] ?? '';
        $userEmail    = $userInfo['user_email'] ?? '';
        $auditType    = $data['audit_type'] ?? '';
        $custom       = $data['custom'] ?? [];
        $redirectInfo = [
            $params['route'] ?? '/index',
            $params['key'] ?? 'oaRedirece' => $params['value'] ?? [],
            'refuse'                       => 1,
        ];
        foreach ($custom as $k => $value) {
            $redirectInfo[$k] = $value;
        }
        if (!$code && $error) { //拒绝的话原路跳回页面并渲染
            return $this->redirect($redirectInfo);
        }

        $oaComponent = new OaHttpComponent();

        $response = $oaComponent->getRefreshToken($code);
        if (!empty($response['error']) || empty($response['refresh_token'])) {
            $redirectInfo['refuse'] = 2;

            return $this->redirect($redirectInfo);
        }

        Yii::$app->getCache()->set($userId . AuditService::$oaRefreshTokenKey, $response['refresh_token'],
            60 * 60 * 24 * 13);
        Yii::$app->getCache()->set($userId . AuditService::$oaAccessTokenKey, $response['access_token'] ?? '',
            $response['expires_in'] ?? 0);

        //生成AUDIT数据 开启OA task
        $auditModelParams = [
            'audit_oa_params' => json_encode($params['value'] ?? [], JSON_UNESCAPED_UNICODE),
            'user_id'         => $userId,
            'user_name'       => $userName,
            'user_email'      => $userEmail,
            'audit_type'      => $auditType,
        ];

        return AuditService::saveAuditGenerateOa($auditModelParams);
    }

    /**
     * OA审核通过或拒绝，回调API
     *
     * @throws Throwable
     */
    public function actionOaCallback()
    {
        $rawBody    = Yii::$app->getRequest()->getRawBody();
        $dataArray  = (array)json_decode($rawBody, true);
        $eventData  = (array)json_decode($dataArray['event_data'] ?? '', true);
        $id         = $eventData['entry']['entry_id'] ?? 0;
        $statusCode = $eventData['entry']['status_code'] ?? 0;
        if (in_array($statusCode, [Audit::OA_AGREE_STATUS, Audit::OA_REFUSE_STATUS])) {
            OaCallbackTask::make([
                'dataId' => $id,
                'status' => $statusCode,
                'test'   => file_get_contents('php://input'),
            ]);
        }
        Yii::$app->response->format = Response::FORMAT_RAW;

        return 'success';
    }


    /**
     * @return array
     * @throws Throwable
     */
    public function actionBusinessCallback()
    {
        $rawBody      = Yii::$app->getRequest()->getRawBody();
        $dataArray    = (array)json_decode($rawBody, true);
        $businessKey  = $dataArray['business_key'] ?? '';
        $auditId      = $dataArray['audit_id'] ?? 0;
        $status       = $dataArray['status'] ?? '';
        $memo         = $dataArray['memo'] ?? '';
        $statusDetail = $dataArray['status_detail'] ?? '';
        $finishTime   = $dataArray['finish_time'] ?? '';
        if (in_array($status, [Audit::BUSINESS_END, Audit::BUSINESS_FAILURE])) {
            BusinessCallbackTask::make([
                'key'           => $auditId,
                'status'        => $status,
                'finish_time'   => $finishTime ?:date("Y-m-d H:i:s"),
                'audit_source'  => 'audit_id',
                'memo'          => $memo,
                'status_detail' => $statusDetail,
            ]);
        }
        Yii::$app->response->format = Response::FORMAT_JSON;

        return ['code' => 0, 'message' => 'success'];
    }
}
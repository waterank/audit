<?php

namespace waterank\tests\controllers;

use waterank\audit\service\AuditService;
use waterank\tests\models\User;
use Yii;
use yii\web\Controller;

class DemoController extends Controller
{
    public function actionIndex()
    {
        $user = User::findOne([
            'username' => 'admin',
        ]);

        Yii::$app->getUser()->login($user);

        $redirectUrl = AuditService::oaAudit('data', 'workflowApply', [
            'a' => 1,
            'b' => 2,
        ], [
            'c1' => 123,
        ], true);

        return $this->redirect($redirectUrl);

        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $query);

        return $this->redirect([
            '/audit/api/oa-redirect',
            'state' => $query['state'],
            'code'  => Yii::$app->getSecurity()->generateRandomString(),
        ]);
    }

    public function actionInfo()
    {
        $user = User::findOne([
            'username' => 'admin',
        ]);

        $cache = Yii::$app->getCache();

        dd([
            'refresh token' => $cache->get($user->getId() . AuditService::$oaRefreshTokenKey),
            'access token'  => $cache->get($user->getId() . AuditService::$oaAccessTokenKey),
        ]);
    }
}

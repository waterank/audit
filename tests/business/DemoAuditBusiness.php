<?php

namespace waterank\tests\business;

use waterank\audit\models\Audit;
use waterank\audit\service\AbstractAuditBusiness;
use Yii;

class DemoAuditBusiness extends AbstractAuditBusiness
{
    /**
     * 同意授权
     *
     * @return array|string redirect url
     */
    public function onResolve()
    {
        $redirectUrl = parent::onResolve();

        // todo 授权成功后处理逻辑
        Yii::debug('fulfilled', __CLASS__);

        return $redirectUrl;
    }

    /**
     * 拒绝授权
     *
     * @param $error
     *
     * @return array|string redirect url
     */
    public function onReject($error)
    {
        return parent::onReject($error);
    }

    /**
     * 完成审核
     *
     * @param Audit $audit
     *
     * @return void
     */
    public function fulfilled(Audit $audit)
    {
        echo 'a';
    }
}

<?php

namespace waterank\audit\controllers;

use waterank\audit\models\Audit;
use waterank\audit\models\AuditSearch;
use yii\base\Object;
use yii\web\Controller;
use yii;
use yii\web\NotFoundHttpException;

class AuditController extends Controller
{

    public function actionIndex()
    {
        $searchModel  = new AuditSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * 查看
     *
     * @param $id
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }


    /**
     * 获取Audit模型
     *
     * @param $id
     *
     * @return OfflineFeeRule
     * @throws NotFoundHttpException
     */
    protected function findModel($id): Audit
    {
        if (($model = Audit::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
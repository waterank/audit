<?php

use waterank\audit\models\AuditSearch;
use xlerr\common\widgets\GridView;
use yii\data\ActiveDataProvider;
use waterank\audit\models\Audit;
use yii\helpers\Html;
use yii\web\View;

/* @var $this View */
/* @var $dataProvider ActiveDataProvider */
/* @var $searchModel AuditSearch */


$this->title                   = '审核列表';
$this->params['breadcrumbs'][] = $this->title;?>
<?= $this->render('_search', ['model' => $searchModel]); ?>
<?=
 GridView::widget([
    'dataProvider' => $dataProvider,
    'columns'      => [
        [
            'class'    => 'yii\grid\ActionColumn',
            'template' => '{view}',
            'buttons'  => [
                'view' => function ($url) {
                    return Html::a('查看', $url, [
                        'class'  => 'btn btn-xs btn-success',
                        'title'  => '查看',
                    ]);
                },
            ],
        ],
        [
            'attribute' => 'audit_id',
        ],
        [
            'attribute' => 'audit_oa_id',
        ],
        [
            'attribute' => 'audit_status',
            'format'    => ['in', Audit::OA_STATUS_LIST],

        ],
        [
            'attribute' => 'audit_type',
            'format'    => ['in', Audit::getAuditTypeList()],
        ],
        [
            'attribute' => 'business_status',
            'format'    => ['in', Audit::BUSINESS_STATUS_LIST],
        ],
        [
            'attribute' => 'audit_creator_name',
        ],
    ],
]); ?>

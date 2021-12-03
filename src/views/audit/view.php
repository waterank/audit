<?php

use yii\widgets\DetailView;
use waterank\audit\models\Audit;

/* @var $this yii\web\View */
/* @var $model Audit */

$this->title                   = '审核单' . $model->audit_id;
$this->params['breadcrumbs'][] = ['label' => '审核列表', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<p>
    <a class="btn btn-default" href="<?= Yii::$app->getRequest()->getReferrer() ?>">返回列表</a>
</p>
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">详情</h3>
    </div>

    <div class="box-body no-padding">
        <?= DetailView::widget([
            'model'      => $model,
            'attributes' => [
                'audit_id',
                'audit_oa_id',
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
                'business_note',
                'business_key',
                [
                    'attribute' => 'audit_oa_params',
                    'format'    => 'raw',
                    'captionOptions' => [
                        'style' => 'width: 10%',
                    ],
                    'value'     => function (Audit $model) {
                        if (class_exists("\\xlerr\\jsoneditor\\JsonViewer")) {
                            return \xlerr\jsoneditor\JsonViewer::widget([
                                'value' => $model->audit_oa_params,
                            ]);
                        }
                        if (class_exists("\\xlerr\\CodeEditor\\CodeEditor")) {
                            return \xlerr\CodeEditor\CodeEditor::widget([
                                'name'          => 'value_show',
                                'value'         => $model->audit_oa_params,
                                'clientOptions' => [
                                    'readOnly' => true,
                                    'mode'     => \xlerr\CodeEditor\CodeEditor::MODE_SQL,
                                    'maxLines' => 40,
                                ],
                            ]);
                        }
                    },
                ],
                [
                    'attribute' => 'audit_oa_response',
                    'format'    => 'raw',
                    'captionOptions' => [
                        'style' => 'width: 10%',
                    ],
                    'value'     => function (Audit $model) {
                        if (class_exists("\\xlerr\\jsoneditor\\JsonViewer")) {
                            return \xlerr\jsoneditor\JsonViewer::widget([
                                'value' => $model->audit_oa_response,
                            ]);
                        }
                        if (class_exists("\\xlerr\\CodeEditor\\CodeEditor")) {
                            return \xlerr\CodeEditor\CodeEditor::widget([
                                'name'          => 'value_show',
                                'value'         => $model->audit_oa_response,
                                'clientOptions' => [
                                    'readOnly' => true,
                                    'mode'     => \xlerr\CodeEditor\CodeEditor::MODE_SQL,
                                    'maxLines' => 40,
                                ],
                            ]);
                        }
                    },
                ],
                [
                  'audit_created_at',      
                ],
                ['audit_oa_finished_at'],
                ['business_finished_at']
            ],
        ]) ?>
    </div>
</div>

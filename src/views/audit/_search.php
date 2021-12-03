<?php

use waterank\audit\models\AuditSearch;
use waterank\audit\models\Audit;
use common\models\Asset;
use xlerr\common\widgets\ActiveForm;
use xlerr\common\widgets\Select2;
use yii\helpers\Html;
use yii\web\View;

/* @var $this View */
/* @var $model AuditSearch */
/* @var $form ActiveForm */
?>
<div class="box search">
    <div class="box-header with-border">
        <h3 class="box-title">搜索</h3>
    </div>

    <div class="box-body">
        <?php $form = ActiveForm::begin([
            'action'        => ['index'],
            'method'        => 'get',
            'type'          => ActiveForm::TYPE_INLINE,
            'waitingPrompt' => ActiveForm::WAITING_PROMPT_SEARCH,
        ]) ?>

        <?= $form->field($model, 'audit_status', [
            'options' => [
                'class' => 'form-group',
                'style' => 'min-width: 150px',
            ],
        ])->widget(Select2::class, [
            'data'          => Audit::OA_STATUS_LIST,
            'pluginOptions' => [
                'allowClear' => true,
            ],
            'options'       => [
                'prompt' => $model->getAttributeLabel('audit_status'),
            ],
        ]) ?>

        <?= $form->field($model, 'audit_type', [
            'options' => [
                'class' => 'form-group',
                'style' => 'min-width: 180px',
            ],
        ])->widget(Select2::class, [
            'data'          => Audit::getAuditTypeList(),
            'pluginOptions' => [
                'allowClear' => true,
            ],
            'options'       => [
                'prompt' => $model->getAttributeLabel('audit_type'),
            ],
        ]) ?>
        <?= $form->field($model, 'audit_oa_id')->textInput() ?>
        
        <?= $form->field($model, 'business_status', [
            'options' => [
                'class' => 'form-group',
                'style' => 'min-width: 180px',
            ],
        ])->widget(Select2::class, [
            'data'          => Audit::BUSINESS_STATUS_LIST,
            'pluginOptions' => [
                'allowClear' => true,
            ],
            'options'       => [
                'prompt' => $model->getAttributeLabel('business_status'),
            ],
        ]) ?>
        <?= $form->field($model, 'business_key')->textInput() ?>


        <?= Html::submitButton('<i class="fa fa-search"></i> 搜索', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('重置搜索条件', ['index'], ['class' => 'btn btn-default']); ?>
        <?php ActiveForm::end() ?>
    </div>
</div>
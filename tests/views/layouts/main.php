<?php

use dmstr\widgets\Alert;
use yii\helpers\Html;
use yii\web\View;

/* @var $this View */
/* @var $content string */

$this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php
    $this->head() ?>
</head>
<body class="skin-blue sidebar-mini sidebar-none">
<div class="body loading"></div>
<?php
$this->beginBody() ?>
<div class="wrapper">
    <div class="content-wrapper">
        <?php
        if ($this->title) : ?>
            <section class="content-header">
                <h1>
                    <?= Html::encode($this->title) ?>
                </h1>

            </section>
        <?php
        endif; ?>

        <section class="content">
            <?= Alert::widget() ?>
            <?= $content ?>
        </section>
    </div>
</div>
<?php
$this->endBody() ?>
</body>
</html>
<?php
$this->endPage() ?>

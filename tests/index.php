<?php

use waterank\audit\components\OaXlerrComponent;
use waterank\audit\Module;
use waterank\tests\models\User;
use xlerr\common\behaviors\FormatterBehavior;
use yii\caching\FileCache;
use yii\db\Connection;
use yii\web\Application;

define('YII_ENV', 'dev');
define('YII_DEBUG', true);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = [
    'id'                  => 'audit_demo',
    'name'                => 'audit demo',
    'basePath'            => __DIR__,
    'defaultRoute'        => 'demo',
    'controllerNamespace' => '\\waterank\\tests\\controllers',
    'vendorPath'          => dirname(__DIR__) . '/vendor',
    'modules'             => [
        'debug' => \yii\debug\Module::class,
        'audit' => Module::class,
    ],
    'layout'              => '@app/views/layouts/main.php',
    'aliases'             => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@app'   => __DIR__,
    ],
    'components'          => [
        'db'                              => [
            'class'             => Connection::class,
            'enableSchemaCache' => false,
            'masters'           => [
                ['dsn' => 'mysql:host=mysql;dbname=biz2'],
            ],
            'masterConfig'      => [
                'username' => 'root',
                'password' => 'adminqwer',
                'charset'  => 'utf8',
            ],
            //            'slaves'            => [
            //                ['dsn' => 'mysql:host=mysql;dbname=biz2'],
            //                ['dsn' => 'mysql:host=mysql;dbname=biz2'],
            //            ],
            //            'slaveConfig'       => [
            //                'username' => 'root',
            //                'password' => 'adminqwer',
            //                'charset'  => 'utf8',
            //            ],
        ],
        'user'                            => [
            'identityClass' => User::class,
        ],
        'cache'                           => [
            'class' => FileCache::class,
        ],
        'request'                         => [
            'cookieValidationKey' => 'asdlkfjasdf',
        ],
        'urlManager'                      => [
            'enablePrettyUrl' => true,
        ],
        'formatter'                       => [
            'as formatter' => FormatterBehavior::class,
        ],
        OaXlerrComponent::componentName() => function () {

            $config = [
                "oauth"         => [
                    "oa_url"        => "https://stage-oa.kuainiu.io/",
                    "client_id"     => "5",
                    "redirect_url"  => "http://capital-7.k8s-ingress-nginx.kuainiujinke.com/audit/api/oa-redirect",
                    "response_type" => "code",
                    "scope"         => "employee.approval approval",
                    "client_secret" => "oJUURkZ14CCbt7evTEKfJ4YEPXpqVpoBvl4OS1pR",
                ],
                "workflowApply" => [
                    "flow_key"             => "OA61c9720eb917c",
                    "business_logic_class" => "dcs\services\WorkflowApplyOaService",
                    "note"                 => "线下制单",
                ],
            ];

            return new OaXlerrComponent([
                'baseUri'      => $config['oauth']['oa_url'] ?? '',
                'oaConfig'     => $config,
                'oaUrl'        => $config['oauth']['oa_url'] ?? '',
                'clientId'     => $config['oauth']['client_id'] ?? '',
                'redirectUrl'  => $config['oauth']['redirect_url'] ?? '',
                'responseType' => $config['oauth']['response_type'] ?? '',
                'scope'        => $config['oauth']['scope'] ?? '',
                'clientSecret' => $config['oauth']['client_secret'] ?? '',
            ]);
        },
    ],
];

$app = new Application($config);
$app->run();
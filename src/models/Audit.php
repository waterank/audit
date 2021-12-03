<?php

namespace waterank\audit\models;

use waterank\audit\components\OaHttpComponent;
use yii;

/**
 * This is the model class for table "audit".
 *
 * @property int         $audit_id                            主键
 * @property int         $audit_oa_id                         OA审核单ID
 * @property int         $audit_status                        审核状态
 * @property string      $audit_type                          审核类型
 * @property int         $business_status                     业务状态
 * @property string      $business_note                       业务备注
 * @property string      $business_key                        业务KEY
 * @property int         $audit_user_id                       审核人ID
 * @property string      $audit_user_name                     审核人姓名
 * @property string      $audit_user_email                    审核人邮箱
 * @property string      $audit_oa_params                     向OA发起请求参数
 * @property string      $audit_oa_response                   OA响应信息
 * @property string|null $audit_created_at                    审核创建时间
 * @property string|null $audit_updated_at                    审核修改时间
 * @property string|null $audit_oa_finished_at                OA完成时间
 * @property string|null $business_finished_at                业务完成时间
 */
class Audit extends yii\db\ActiveRecord
{

    public const STATUS_NEW = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_SUCCESS = 2;
    public const STATUS_FAILURE = 3;

    public const BUSINESS_NO_PROCESS = 0;
    public const BUSINESS_PROCESSING = 1;
    public const BUSINESS_SUCCESS = 2;
    public const BUSINESS_FAILURE = 3;

    public const OA_AGREE_STATUS = 9;
    public const OA_REFUSE_STATUS = -3;

    const OA_STATUS_LIST = [
        self::STATUS_PROCESSING => '审核中',
        self::STATUS_SUCCESS    => '审核通过',
        self::STATUS_FAILURE    => '审核拒绝',
    ];

    const BUSINESS_STATUS_LIST = [
        self::BUSINESS_NO_PROCESS => '业务未处理',
        self::BUSINESS_PROCESSING => '业务处理中',
        self::BUSINESS_SUCCESS    => '业务完成',
        self::BUSINESS_FAILURE    => '业务处理失败，等待重试',
    ];

    public static function tableName(): string
    {
        return 'audit';
    }

    public static function getAuditTypeList()
    {
        $oaComponent = new OaHttpComponent();

        return $oaComponent->getAuditTypeList();
    }


    public function rules()
    {
        return [
            [
                [
                    'audit_oa_response',
                    'audit_oa_params',
                    'audit_user_email',
                    'audit_user_name',
                    'audit_user_id',
                    'business_key',
                    'business_note',
                    'business_status',
                    'audit_status',
                    'audit_type',
                    'audit_oa_id',
                    'audit_created_at',
                    'audit_updated_at',
                    'audit_oa_finished_at',
                    'business_finished_at',
                ],
                'safe',
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'audit_oa_response'    => 'OA响应信息',
            'audit_oa_params'      => 'OA请求信息',
            'audit_user_email'     => '审核人邮箱',
            'audit_user_name'      => '审核人姓名',
            'audit_user_id'        => '审核人ID',
            'business_key'         => '业务KEY',
            'business_note'        => '业务备注',
            'business_status'      => '业务状态',
            'audit_status'         => '审核状态',
            'audit_type'           => '审核类型',
            'audit_oa_id'          => '审核OAID',
            'audit_id'             => '审核ID',
            'audit_created_at'     => '审核创建时间',
            'audit_updated_at'     => '审核更新时间',
            'audit_oa_finished_at' => '审核完成时间',
            'business_finished_at' => '业务完成时间',
        ];
    }
}
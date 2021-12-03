<?php

namespace waterank\audit\models;

use yii\data\ActiveDataProvider;

class AuditSearch extends Audit
{
    public $create_start;
    public $create_end;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                [
                    'audit_oa_id',
                    'audit_status',
                    'audit_type',
                    'business_status',
                    'business_key',
                    'audit_created_at',
                    'create_start',
                    'create_end',
                ],
                'safe',
            ],
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = self::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => [
                'attributes'   => ['audit_created_at'],
                'defaultOrder' => ['audit_created_at' => SORT_DESC],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');

            return $dataProvider;
        }
        // grid filtering conditions

        $query->andFilterWhere([
            'audit_oa_id'     => $this->audit_oa_id,
            'audit_status'    => $this->audit_status,
            'audit_type'      => $this->audit_type,
            'business_status' => $this->business_status,
            'business_key'    => $this->business_key,
        ]);

        return $dataProvider;
    }
}
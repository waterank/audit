<?php

namespace waterank\audit\task;

use xlerr\task\TaskHandler;

class XlerrTaskProxyHandler extends TaskHandler
{
    public $data;
    public $processor;

    public function rules()
    {
        return [
            [['processor'], 'required'],
            [['data'], 'safe'],
        ];
    }

    public function process()
    {
        return call_user_func($this->processor, $this->data);
    }
}

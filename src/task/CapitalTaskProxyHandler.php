<?php

namespace waterank\audit\task;

use common\business\task\TaskHandler;
use common\models\Task;

class CapitalTaskProxyHandler extends TaskHandler
{
    public function process(Task $task)
    {
        $requestData = json_decode($task->task_request_data, true);

        return call_user_func($requestData['processor'], $requestData['data']);
    }
}
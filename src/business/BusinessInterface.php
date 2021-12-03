<?php

namespace waterank\audit\business;

use waterank\audit\models\Audit;

interface BusinessInterface
{
    public function process(Audit $audit);
}
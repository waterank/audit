<?php

namespace waterank\audit\components;

use GuzzleHttp\RequestOptions;
use xlerr\httpca\ComponentTrait;
use xlerr\httpca\RequestClient;

class OaXlerrComponent extends RequestClient implements  OaComponentInterface
{
    use ComponentTrait;

    
    public function createOa()
    {
        // TODO: Implement createOa() method.
    }
    
    public function getAccessToken()
    {
        // TODO: Implement getAccessToken() method.
    }
}
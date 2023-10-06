<?php

namespace AuthFee\Controller\Api;

class Example extends \AuthFee\Controller\Api\Authenticated
{
    public function postPing()
    {
        return ['pong' => true];
    }
}

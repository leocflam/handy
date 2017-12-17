<?php

namespace App\Bankings\Apis;

class MockFailureClient
{
    public function get($endpoint)
    {
        return $this;
    }

    public function getBody()
    {
        $result = (object) [
            'status' => 'failure'
        ];
        return json_encode($result);
    }
}

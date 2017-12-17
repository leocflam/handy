<?php

namespace App\Bankings\Apis;

use GuzzleHttp\Client;

class HandyAPI
{
    private $client = null;

    public function __construct()
    {
        $this->client = app()->environment('testing') && config('mocking_api_failure')
            ? new MockFailureClient()
            : new Client([
                'base_uri' => 'http://handy.travel/'
            ]);
    }

    public static function mockFailure()
    {
        config(['mocking_api_failure' => 1]);
    }

    public function getTransferApprove()
    {
        $response = $this->client->get('/test/success.json');
        $result   = json_decode($response->getBody());
        return $result->status === 'success';
    }
}

<?php

namespace App\Utility;

use Ixudra\Curl\Facades\Curl;

class CurlRequest
{
    public static function sendGetRequest($url, $headers = [])
    {
        return Curl::to($url)
            ->withHeaders($headers)
            ->get();
    }
}

<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiService
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function fetchData(string $url): array
    {
        $response = $this->client->request('GET', $url);
        return $response->toArray();
    }
}

<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class checkURLTest extends TestCase
{
    private $client;

    public function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => 'http://localhost/nica-india/',
            'http_errors' => false
        ]);
    }

    public function testNoUrlProvided()
    {
        $response = $this->client->get('checkURL.php');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertFalse($data['status']);
        $this->assertEquals('No URL provided', $data['message']);
    }

    public function testInvalidUrlFormat()
    {
        $response = $this->client->get('checkURL.php', ['query' => ['url' => 'invalid-url']]);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertFalse($data['status']);
        $this->assertEquals('Invalid URL format', $data['message']);
    }

    public function testValidUrl()
    {
        // Replace with a URL that is known to be valid
        $response = $this->client->get('checkURL.php', ['query' => ['url' => 'https://www.google.com']]);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertTrue($data['status']);
        $this->assertGreaterThanOrEqual(200, $data['http_code']);
        $this->assertLessThan(400, $data['http_code']);
    }

    public function testNotFoundUrl()
    {
        // Replace with a URL that is known to return a 404 status
        $response = $this->client->get('checkURL.php', ['query' => ['url' => 'https://www.google.com/non-existent-page']]);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertFalse($data['status']);
        $this->assertEquals(404, $data['http_code']);
    }

    public function testConnectionError()
    {
        // A non-existent domain should cause a connection error
        $response = $this->client->get('checkURL.php', ['query' => ['url' => 'http://this-domain-does-not-exist.com']]);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertFalse($data['status']);
        $this->assertEquals('Connection error', $data['message']);
    }
}

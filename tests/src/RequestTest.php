<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Fastpress\Http\Request;

/**
 * Test cases for Request class.
 */
class RequestTest extends TestCase
{
    private $request;

    protected function setUp(): void
    {
        $_GET = ['get_var' => 'value1'];
        $_POST = ['post_var' => 'value2'];
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test-uri',
            'HTTP_REFERER' => 'http://localhost',
            'HTTPS' => 'on',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'REQUEST_SCHEME' => 'http',  // Added
            'SERVER_NAME' => 'localhost' // Added
        ];
        $_COOKIE = ['cookie_var' => 'value3'];
    
        $this->request = new Request();
    }

    public function testIsGetMethod()
    {
        $this->assertTrue($this->request->isGet());
    }

    public function testIsPostMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = new Request(); // Reinitialize Request with updated $_SERVER
        $this->assertTrue($request->isPost());
    }

    public function testGetFunction()
    {
        $this->assertEquals('value1', $this->request->get('get_var'));
    }

    public function testPostFunction()
    {
        $this->assertEquals('value2', $this->request->post('post_var'));
    }

    public function testGetUri()
    {
        $this->assertEquals('/test-uri', $this->request->getUri());
    }

    public function testGetReferer()
    {
        $this->assertEquals('http://localhost', $this->request->getReferer());
    }

    public function testIsSecure()
    {
        $this->assertTrue($this->request->isSecure());
    }

    public function testIsXhr()
    {
        $this->assertTrue($this->request->isXhr());
    }

    public function testBuildUrl()
    {
        $expectedUrl = [
            'scheme' => 'http',
            'host' => 'localhost',
            'path' => '/test-uri'
        ];

        $this->assertEquals($expectedUrl, $this->request->build_url());
    }

    // Add more tests as needed for complete coverage.
}


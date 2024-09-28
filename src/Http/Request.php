<?php

declare(strict_types=1);

namespace Fastpress\Http;

/**
 * Represents an immutable HTTP request and provides methods to access various request parameters such as GET, POST,
 * headers, cookies, server variables, and more.
 *
 * This class follows best practices for handling HTTP requests, including support for JSON input, file uploads,
 * and security considerations.
 *
 * @author
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    1.0.0
 */
class Request
{
    /**
     * @var array
     */
    private $get;

    /**
     * @var array
     */
    private $post;

    /**
     * @var array
     */
    private $server;

    /**
     * @var array
     */
    private $cookie;

    /**
     * @var array
     */
    private $files;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var string|null
     */
    private $rawInput;

    /**
     * @var array|null
     */
    private $parsedJson;

    /**
     * Initializes the request object with superglobal arrays or custom arrays.
     *
     * @param array|null $get
     * @param array|null $post
     * @param array|null $server
     * @param array|null $cookie
     * @param array|null $files
     * @param array|null $headers
     */
    public function __construct(
        array $get = null,
        array $post = null,
        array $server = null,
        array $cookie = null,
        array $files = null,
        array $headers = null
    ) {
        $this->get = $get ?? $_GET;
        $this->post = $post ?? $_POST;
        $this->server = $server ?? $_SERVER;
        $this->cookie = $cookie ?? $_COOKIE;
        $this->files = $files ?? $_FILES;
        $this->headers = $headers ?? $this->parseHeaders();
        $this->rawInput = null;
        $this->parsedJson = null;
    }

    /**
     * Parses HTTP request headers.
     *
     * @return array
     */
    private function parseHeaders()
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($this->server as $name => $value) {
                if (strpos($name, 'HTTP_') === 0) {
                    $headerName = str_replace('_', '-', substr($name, 5));
                    $headers[$headerName] = $value;
                }
            }
        }

        return $headers;
    }

    /**
     * Retrieves a value from the GET parameters.
     *
     * @param string      $key
     * @param mixed       $default Default value to return if key does not exist.
     * @param int|null    $filter  FILTER_* constant.
     *
     * @return mixed
     */
    public function get($key, $default = null, $filter = null)
    {
        return $this->filter($this->get, $key, $default, $filter);
    }

    /**
     * Retrieves a value from the POST parameters.
     *
     * @param string      $key
     * @param mixed       $default Default value to return if key does not exist.
     * @param int|null    $filter  FILTER_* constant.
     *
     * @return mixed
     */
    public function post($key, $default = null, $filter = null)
    {
        return $this->filter($this->post, $key, $default, $filter);
    }

    /**
     * Retrieves a value from the COOKIE parameters.
     *
     * @param string      $key
     * @param mixed       $default Default value to return if key does not exist.
     * @param int|null    $filter  FILTER_* constant.
     *
     * @return mixed
     */
    public function cookie($key, $default = null, $filter = null)
    {
        return $this->filter($this->cookie, $key, $default, $filter);
    }

    /**
     * Retrieves a value from the SERVER parameters.
     *
     * @param string      $key
     * @param mixed       $default Default value to return if key does not exist.
     *
     * @return mixed
     */
    public function server($key, $default = null)
    {
        return isset($this->server[$key]) ? $this->server[$key] : $default;
    }

    /**
     * Retrieves a value from the uploaded files.
     *
     * @param string $key
     *
     * @return array|null
     */
    public function file($key)
    {
        return isset($this->files[$key]) ? $this->files[$key] : null;
    }

    /**
     * Retrieves a header value.
     *
     * @param string $name
     * @param mixed  $default Default value to return if header does not exist.
     *
     * @return string|null
     */
    public function header($name, $default = null)
    {
        $normalized = str_replace('-', '_', strtoupper($name));
        return isset($this->headers[$normalized]) ? $this->headers[$normalized] : $default;
    }

    /**
     * Retrieves all headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Retrieves raw input data.
     *
     * @return string
     */
    public function getRawInput()
    {
        if ($this->rawInput === null) {
            $this->rawInput = file_get_contents('php://input');
        }
        return $this->rawInput;
    }

    /**
     * Retrieves parsed JSON input data.
     *
     * @param bool $assoc Whether to return the result as an associative array.
     *
     * @return mixed
     */
    public function getJson($assoc = true)
    {
        if ($this->parsedJson === null) {
            $input = $this->getRawInput();
            $this->parsedJson = json_decode($input, $assoc);
        }
        return $this->parsedJson;
    }

    /**
     * Retrieves a value from the parsed JSON input data.
     *
     * @param string   $key
     * @param mixed    $default Default value to return if key does not exist.
     *
     * @return mixed
     */
    public function json($key, $default = null)
    {
        $data = $this->getJson();
        return isset($data[$key]) ? $data[$key] : $default;
    }

    /**
     * Retrieves a value from any input source (POST, GET, JSON).
     *
     * @param string   $key
     * @param mixed    $default Default value to return if key does not exist.
     * @param int|null $filter  FILTER_* constant.
     *
     * @return mixed
     */
    public function input($key, $default = null, $filter = null)
    {
        if ($this->post($key, null) !== null) {
            return $this->post($key, $default, $filter);
        }
        if ($this->get($key, null) !== null) {
            return $this->get($key, $default, $filter);
        }
        if ($this->json($key, null) !== null) {
            return $this->json($key, $default);
        }
        return $default;
    }

    /**
     * Gets the request method.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->server('REQUEST_METHOD', 'GET');
    }

    /**
     * Checks if the request method is GET.
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Checks if the request method is POST.
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Checks if the request method is PUT.
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->getMethod() === 'PUT';
    }

    /**
     * Checks if the request method is DELETE.
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->getMethod() === 'DELETE';
    }

    /**
     * Checks if the request method is PATCH.
     *
     * @return bool
     */
    public function isPatch()
    {
        return $this->getMethod() === 'PATCH';
    }

    /**
     * Checks if the request method is OPTIONS.
     *
     * @return bool
     */
    public function isOptions()
    {
        return $this->getMethod() === 'OPTIONS';
    }

    /**
     * Checks if the request method is HEAD.
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->getMethod() === 'HEAD';
    }

    /**
     * Checks if the connection is secure (HTTPS).
     *
     * @return bool
     */
    public function isSecure()
    {
        return (!empty($this->server('HTTPS')) && $this->server('HTTPS') !== 'off')
            || $this->server('SERVER_PORT') == 443;
    }

    /**
     * Checks if the request is an XMLHttpRequest.
     *
     * @return bool
     */
    public function isXhr()
    {
        return strtolower($this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    /**
     * Gets the request URI.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->server('REQUEST_URI', '/');
    }

    /**
     * Gets the current URL.
     *
     * @return string
     */
    public function getUrl()
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $this->server('HTTP_HOST', '');
        $uri = $this->getUri();
        return $scheme . '://' . $host . $uri;
    }

    /**
     * Parses and returns the query string parameters.
     *
     * @return array
     */
    public function getQueryParams()
    {
        $query = parse_url($this->getUrl(), PHP_URL_QUERY);
        parse_str($query, $params);
        return $params;
    }

    /**
     * Filters a value using filter_var().
     *
     * @param array    $input
     * @param string   $key
     * @param mixed    $default
     * @param int|null $filter
     *
     * @return mixed
     */
    private function filter(array $input, $key, $default, $filter)
    {
        if (!isset($input[$key])) {
            return $default;
        }
        $value = $input[$key];
        if ($filter !== null) {
            $value = filter_var($value, $filter);
        }
        return $value;
    }

    /**
     * Validates input data based on the provided rules.
     *
     * @param array $rules An associative array of validation rules.
     *
     * @return array An array of validation errors.
     */
    public function validate(array $rules)
    {
        $errors = [];
        foreach ($rules as $field => $rule) {
            $value = $this->input($field);
            if ($rule === 'required' && ($value === null || $value === '')) {
                $errors[$field] = 'The ' . $field . ' field is required.';
            }
            // Additional validation rules can be implemented here.
        }
        return $errors;
    }

    /**
     * Returns all input data from GET, POST, and JSON.
     *
     * @return array
     */
    public function all()
    {
        return array_merge($this->get, $this->post, $this->getJson());
    }

    /**
     * Returns the client's IP address.
     *
     * @return string|null
     */
    public function getClientIp()
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];
        foreach ($ipKeys as $key) {
            $ip = $this->server($key);
            if ($ip !== null) {
                return $ip;
            }
        }
        return null;
    }

    /**
     * Returns the requested content type.
     *
     * @return string|null
     */
    public function getContentType()
    {
        return $this->server('CONTENT_TYPE');
    }

    /**
     * Checks if the content type is JSON.
     *
     * @return bool
     */
    public function isJson()
    {
        $contentType = $this->getContentType();
        return strpos($contentType, 'application/json') !== false;
    }

    /**
     * Generates a CSRF token and stores it in the session.
     *
     * @return string
     */
    public function getCsrfToken()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    /**
     * Validates the provided CSRF token against the session token.
     *
     * @param string $token
     *
     * @return bool
     */
    public function validateCsrfToken($token)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], $token);
    }

    /**
     * Magic method to prevent setting properties.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @throws \LogicException
     */
    public function __set($name, $value)
    {
        throw new \LogicException('Cannot modify immutable Request object.');
    }

    /**
     * Magic method to prevent unsetting properties.
     *
     * @param string $name
     *
     * @throws \LogicException
     */
    public function __unset($name)
    {
        throw new \LogicException('Cannot modify immutable Request object.');
    }
}

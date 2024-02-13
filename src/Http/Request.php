<?php

declare(strict_types=1);

/**
 * HTTP request object.
 *
 * PHP version 7.0
 *
 * @category   fastpress
 * @package    Http
 * @subpackage Request
 *
 * @author     https://github.com/samayo
 * @copyright  Copyright (c) samayo
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @version    0.1.0
 */

namespace Fastpress\Http;

/**
 * HTTP request object.
 *
 * This class represents an HTTP request and provides methods to access various request parameters such as GET, POST,
 * server variables, and more.
 *
 * @category   fastpress
 * @package    Http
 * @subpackage Request
 *
 * @author     https://github.com/samayo
 */
class Request implements \ArrayAccess
{
    protected array $get = [];
    protected array $post = [];
    protected array $server = [];
    protected array $cookie = [];
/**
     * Constructor to initialize the request object.
     * It populates the internal arrays with values from superglobal arrays ($_GET, $_POST, $_SERVER, $_COOKIE).
     *
     * @param array|null $get
     * @param array|null $post
     * @param array|null $server
     * @param array|null $cookie
     */
    public function __construct(array $get = null, array $post = null, array $server = null, array $cookie = null)
    {
        $this->get = $get ?? $_GET;
        $this->post = $post ?? $_POST;
        $this->server = $server ?? $_SERVER;
        $this->cookie = $cookie ?? $_COOKIE;
    }

    /**
     * Checks if the request method is GET.
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Checks if the request method is POST.
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Checks if the request method is PUT.
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->getMethod() === 'PUT';
    }

    /**
     * Checks if the request method is DELETE.
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->getMethod() === 'DELETE';
    }

    /**
     * Fetches a value from the GET array.
     *
     * @param string $var
     * @param mixed  $filter
     *
     * @return mixed|null Returns the filtered value if found, otherwise null.
     */
    public function get(string $var, $filter = null): mixed
    {
        return $this->filter($this->get, $var, $filter);
    }

    /**
     * Fetches a value from the POST array.
     *
     * @param string $var
     * @param mixed  $filter
     *
     * @return mixed|null Returns the filtered value if found, otherwise null.
     */
    public function post(string $var, $filter = null): mixed
    {
        return $this->filter($this->post, $var, $filter);
    }

    /**
     * Deletes a value from the POST array.
     *
     * @param string $var
     * @param mixed  $filter
     *
     * @return mixed|null Returns the filtered value if found, otherwise null.
     */
    public function delete(string $var, $filter = null): mixed
    {
        return $this->filter('DELETE', $var, $filter);
    }

    /**
     * Fetches a value from the SERVER array.
     *
     * @param string $var
     * @param mixed  $filter
     *
     * @return mixed|null Returns the filtered value if found, otherwise null.
     */
    public function server(string $var, $filter = null): mixed
    {
        return $this->filter($this->server, $var, $filter);
    }

    /**
     * Gets the current URI.
     *
     * @return string|null Returns the current URI if found, otherwise null.
     */
    public function getUri(): ?string
    {
        return $this->filter($this->server, 'REQUEST_URI');
    }

    /**
     * Gets the HTTP referer.
     *
     * @return string|null Returns the HTTP referer if found, otherwise null.
     */
    public function getReferer(): ?string
    {
        return $this->filter($this->server, 'HTTP_REFERER');
    }

    /**
     * Gets the request method type.
     *
     * @return string|null Returns the request method type if found, otherwise null.
     */
    public function getMethod(): ?string
    {
        return $this->filter($this->server, 'REQUEST_METHOD');
    }

    /**
     * Checks if the connection is secure.
     *
     * @return bool Returns true if the connection is secure, otherwise false.
     */
    public function isSecure(): bool
    {
        return array_key_exists('HTTPS', $this->server)
            && $this->server['HTTPS'] !== 'off';
    }

    /**
     * Checks if the connection is made with XMLHttpRequest.
     *
     * @return bool Returns true if connection is made with XMLHttpRequest, otherwise false.
     */
    public function isXhr(): bool
    {
        return $this->filter($this->server, 'HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    /**
     * A utility function to filter values using filter_var().
     *
     * @param array  $input
     * @param string $var
     * @param mixed  $filter
     *
     * @return mixed|null Returns the filtered value if found, otherwise null.
     */
    protected function filter(array $input, string $var, $filter = null): mixed
    {
        $value = $input[$var] ?? false;
        if (!$filter) {
            return $value;
        }

        return filter_var($value, $filter);
    }

    /**
     * Returns superglobal arrays.
     *
     * @return array Returns the superglobal arrays.
     */
    public function requestGlobals(): array
    {
        return [
            'get' => $this->get,
            'post' => $this->post,
            'server' => $this->server,
        ];
    }

    /**
     * Builds a URL.
     *
     * @return mixed Returns the parsed URL.
     */
    public function build_url()
    {
        return  parse_url($this->server('REQUEST_SCHEME') . '://' .
            $this->server('SERVER_NAME') .
            $this->server('REQUEST_URI'));
    }

    /**
     * Call class methods from array context.
     *
     * @param mixed $offset
     *
     * @return mixed Returns the result of the called method.
     */
    public function offsetGet($offset): mixed
    {
        if (in_array($offset, ['isGet', 'isPut', 'isPost', 'isDelete', 'isXhr', 'isSecure'])) {
            return $this->$offset();
        }
    }

    /**
     * Set values.
     *
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        // Implementation for setting values.
        // You can add your logic here if necessary.
    }

    /**
     * Checks if an offset exists.
     *
     * @param mixed $offset
     *
     * @return bool Returns true if the offset exists, otherwise false.
     */
    public function offsetExists($offset): bool
    {
        return isset($this->$offset);
    }

    /**
     * Unsets an offset.
     *
     * @param mixed $offset
     *
     * @return void
     */
    public function offsetUnset($offset): void
    {
        // Implementation for unsetting offset.
        // You can add your logic here if necessary.
    }
}

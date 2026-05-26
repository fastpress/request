<?php

declare(strict_types=1);

namespace Fastpress\Http;

class Request
{
    /**
     * @var array Query parameters from the URL
     */
    private array $get;

    /**
     * @var array Data sent with the POST method
     */
    private array $post;

    /**
     * @var array Server and execution environment information
     */
    private array $server;

    /**
     * @var array Uploaded files
     */
    private array $files;

    /**
     * @var array Cookies
     */
    private array $cookies;

    /**
     * @var array|null HTTP headers
     */
    private ?array $headers = null;

    /**
     * @var string|null Raw request body
     */
    private ?string $rawBody = null;

    /**
     * @var array|null Parsed JSON request body
     */
    private ?array $parsedBody = null;

    /**
     * @var array URL parameters
     */
    private array $urlParams = [];

    /**
     * @var array Acceptable content types from the Accept header
     */
    private array $acceptableContentTypes = [];

    /**
     * Constructor.
     *
     * @param array|null $get Query parameters
     * @param array|null $post POST data
     * @param array|null $server Server information
     * @param array|null $cookies Cookies
     * @param array|null $files Uploaded files
     */
    public function __construct(
        ?array $get = null,
        ?array $post = null,
        ?array $server = null,
        ?array $cookies = null,
        ?array $files = null
    ) {
        $this->get = $get ?? $_GET;
        $this->post = $post ?? $_POST;
        $this->server = $server ?? $_SERVER;
        $this->cookies = $cookies ?? $_COOKIE;
        $this->files = $files ?? $_FILES;
    }

    /**
     * Get HTTP headers.
     *
     * @return array
     */
    private function headers(): array
    {
        if ($this->headers === null) {
            $this->headers = $this->parseHeaders();
        }
        return $this->headers;
    }

    /**
     * Parse HTTP headers from server information.
     *
     * @return array
     */
    private function parseHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', substr($key, 5))));
                $headers[$header] = $value;
            }
        }

        // Content-Type and Content-Length are not prefixed with HTTP_
        if (isset($this->server['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $this->server['CONTENT_TYPE'];
        }
        if (isset($this->server['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $this->server['CONTENT_LENGTH'];
        }

        return $headers;
    }

    /**
     * Get input data with optional sanitization.
     *
     * @param string $key Input key
     * @param mixed $default Default value
     * @param bool $sanitize Whether to sanitize the input
     * @return mixed
     */
    public function input(string $key, mixed $default = null, bool $sanitize = true): mixed
    {
        $value = match(true) {
            $this->isPost() => $this->post($key, $default),
            $this->isJson() => $this->json($key, $default),
            default => $this->get($key, $default)
        };

        return $sanitize ? $this->sanitize($value) : $value;
    }

    /**
     * Sanitize input data.
     *
     * @param mixed $value Value to sanitize
     * @return mixed
     */
    private function sanitize(mixed $value): mixed
    {
        if (is_string($value)) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return $value;
    }

    /**
     * Get uploaded file information.
     *
     * @param string $key File input key
     * @return array|null
     */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        if (!$file) {
            return null;
        }

        // Handle multiple file uploads
        if (is_array($file['name'])) {
            $files = [];
            foreach (array_keys($file['name']) as $index) {
                $files[] = [
                    'name' => $file['name'][$index],
                    'type' => $file['type'][$index],
                    'tmp_name' => $file['tmp_name'][$index],
                    'error' => $file['error'][$index],
                    'size' => $file['size'][$index]
                ];
            }
            return $files;
        }

        return $file;
    }

    /**
     * Check if a file was uploaded successfully.
     *
     * @param string $key File input key
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) &&
               $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Check if a cookie exists.
     *
     * @param string $key Cookie key
     * @return bool
     */
    public function hasCookie(string $key): bool
    {
        return isset($this->cookies[$key]);
    }

    /**
     * Get a cookie value.
     *
     * @param string $key Cookie key
     * @param mixed $default Default value
     * @return mixed
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Check if the request content type is JSON.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type') ?? '';
        return stripos($contentType, 'application/json') !== false;
    }

    /**
     * Check if the request is an AJAX request.
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Get the HTTP method.
     *
     * @return string
     */
    public function getMethod(): string
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';

        // Check for method override (PUT/DELETE/PATCH via POST only)
        if ($method === 'POST') {
            $override = strtoupper(
                $this->header('X-HTTP-Method-Override')
                ?? $this->post('_method')
                ?? $method
            );

            // Only allow safe overrides
            if (in_array($override, ['PUT', 'DELETE', 'PATCH'], true)) {
                return $override;
            }
        }

        return $method;
    }

    /**
     * Get the client's IP address.
     *
     * @return string|null
     */
    public function getIp(): ?string
    {
        return $this->server['REMOTE_ADDR'] ?? null;
    }

    /**
     * Get the request URI.
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    /**
     * Get the request path (without query string).
     *
     * @return string
     */
    public function getPath(): string
    {
        return parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    }

    /**
     * Get the query string.
     *
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->server['QUERY_STRING'] ?? '';
    }

    /**
     * Get the full URL including scheme, host, and URI.
     *
     * @return string
     */
    public function getFullUrl(): string
    {
        $scheme = (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? 'localhost';
        $uri = $this->server['REQUEST_URI'] ?? '/';

        return $scheme . '://' . $host . $uri;
    }

    /**
     * Get the request scheme (http or https).
     *
     * @return string
     */
    public function getScheme(): string
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ? 'https' : 'http';
    }

    /**
     * Get the host.
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? 'localhost';
    }

    /**
     * Get the bearer token from the Authorization header.
     *
     * @return string|null
     */
    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    /**
     * Check if the client accepts a given content type.
     *
     * @param string $contentType Content type
     * @return bool
     */
    public function accepts(string $contentType): bool
    {
        if (empty($this->acceptableContentTypes)) {
            $accept = $this->header('Accept') ?? '*/*';
            $this->acceptableContentTypes = array_map('trim', explode(',', $accept));
        }

        foreach ($this->acceptableContentTypes as $acceptableType) {
            if ($acceptableType === '*/*' || $acceptableType === $contentType) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get an HTTP header value.
     *
     * @param string $key Header key
     * @param mixed $default Default value
     * @return mixed
     */
    public function header(string $key, mixed $default = null): mixed
    {
        return $this->headers()[$key] ?? $default;
    }

    /**
     * Get a query parameter value.
     *
     * @param string $key Query parameter key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    /**
     * Get a POST data value.
     *
     * @param string $key POST data key
     * @param mixed $default Default value
     * @return mixed
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get a value from the parsed JSON request body.
     *
     * @param string $key JSON key
     * @param mixed $default Default value
     * @return mixed
     */
    public function json(string $key, mixed $default = null): mixed
    {
        if ($this->parsedBody === null && $this->isJson()) {
            $this->parsedBody = json_decode($this->getBody(), true) ?? [];
        }
        return $this->parsedBody[$key] ?? $default;
    }

    /**
     * Get the raw request body.
     *
     * @return string
     */
    public function getBody(): string
    {
        if ($this->rawBody === null) {
            $this->rawBody = file_get_contents('php://input');
        }
        return $this->rawBody;
    }

    /**
     * Validate input data against rules.
     *
     * @param array $rules Validation rules
     * @return array
     */
    public function validate(array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleSet) {
            $fieldRules = explode('|', $ruleSet);
            $value = $this->input($field, null, false);

            foreach ($fieldRules as $rule) {
                if ($error = $this->validateField($field, $value, $rule)) {
                    $errors[$field] = $error;
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * Validate a single field against a rule.
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Validation rule
     * @return string|null
     */
    private function validateField(string $field, mixed $value, string $rule): ?string
    {
        if ($rule === 'required' && empty($value)) {
            return "The {$field} field is required.";
        }

        if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "The {$field} must be a valid email address.";
        }

        if (str_starts_with($rule, 'min:')) {
            $min = (int) substr($rule, 4);
            if (strlen((string)$value) < $min) {
                return "The {$field} must be at least {$min} characters.";
            }
        }

        if (str_starts_with($rule, 'max:')) {
            $max = (int) substr($rule, 4);
            if (strlen((string)$value) > $max) {
                return "The {$field} must not exceed {$max} characters.";
            }
        }

        return null;
    }

    /**
     * Set URL parameters.
     *
     * @param array $params URL parameters
     */
    public function setUrlParams(array $params): void
    {
        $this->urlParams = $params;
    }

    /**
     * Get a URL parameter value.
     *
     * @param string $key URL parameter key
     * @param mixed $default Default value
     * @return mixed
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->urlParams[$key] ?? $default;
    }

    /**
     * Get all input data.
     *
     * @return array
     */
    public function all(): array
    {
        return array_merge(
            $this->get,
            $this->post,
            $this->isJson() ? ($this->parsedBody ?? []) : []
        );
    }

    /**
     * Check if the request method is GET.
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Check if the request method is POST.
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Check if the request method is PUT.
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->getMethod() === 'PUT';
    }

    /**
     * Check if the request method is DELETE.
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->getMethod() === 'DELETE';
    }

    /**
     * Check if the request method is PATCH.
     *
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->getMethod() === 'PATCH';
    }
}

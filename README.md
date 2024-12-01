# Fastpress HTTP Request

This repository contains the `Request` class, a crucial component of the `fastpress/framework`. The `Request` class provides a comprehensive interface for handling HTTP requests in PHP applications. It simplifies accessing request data such as GET, POST, COOKIE, and SERVER variables, and includes methods for common request operations.

## Features

- Easy retrieval of GET, POST, COOKIE, and SERVER data.
- Convenience methods for checking the HTTP request method (GET, POST, PUT, DELETE).
- Utilities for common tasks like checking for Ajax requests and secure connections.
- Flexibility to extend or modify for custom use-cases.

## Installation

To use this component, first ensure you have `fastpress/framework` installed. This `Request` class is a dependency of the framework and is meant to be used within its context.

If you are managing your project with Composer, you can add this dependency directly by running:

```bash
composer require fastpress/request
```
Ensure that this aligns with the version constraints of fastpress/framework.

## Usage
To use the Request class, create an instance of it in your PHP application:
```php
use Fastpress\Http\Request;

$request = new Request();
```
## Methods

### `validateCsrf(): bool`

Validates the CSRF token.

```php
$isValid = $request->validateCsrf();
```

* Returns: true if the CSRF token is valid, otherwise throws a RuntimeException.
```php
generateCsrfToken(): string
```
* Generates a new CSRF token and stores it in the session.

```php
$token = $request->generateCsrfToken();
```

Returns: The generated CSRF token.
### `input(string $key, mixed $default = null, bool $sanitize = true): mixed`
Retrieves an input value from GET, POST, or JSON data.

```php
$name = $request->input('name');
$age = $request->input('age', 25);
$rawInput = $request->input('comment', null, false); 
```

$key: The input key.
$default: Optional. The default value to return if the key is not found.
$sanitize: Optional. Whether to sanitize the value. Defaults to true.
Returns: The input value.
### `file(string $key): ?array`
Retrieves an uploaded file.

```php
$uploadedFile = $request->file('image');
```

$key: The file key.
Returns: An array containing file information, or null if the file is not found.
```php
hasFile(string $key): bool
```
Checks if an uploaded file exists and was uploaded successfully.

```php
if ($request->hasFile('document')) {
  // Process the file
}
```

$key: The file key.
Returns: true if the file exists and was uploaded successfully, false otherwise.
### `isJson(): bool`
Checks if the request content type is JSON.

```php
if ($request->isJson()) {
  // Process the JSON data
}
```

Returns: true if the content type is JSON, false otherwise.
getMethod(): string
Gets the HTTP request method.

```php
$method = $request->getMethod(); 
```

Returns: The HTTP request method.
getIp(): ?string
Gets the client's IP address.

```php
$ipAddress = $request->getIp();
```

Returns: The client's IP address, or null if it cannot be determined.
accepts(string $contentType): bool
Checks if the client accepts the given content type.

```php
if ($request->accepts('application/json')) {
  // Send JSON response
}
```

$contentType: The content type to check.
Returns: true if the client accepts the content type, false otherwise.
header(string $key, mixed $default = null): mixed
Retrieves a header value.

PHP
$authToken = $request->header('Authorization');
```

$key: The header key.
$default: Optional. The default value to return if the header is not found.
Returns: The header value.
get(string $key, mixed $default = null): mixed
Retrieves a GET parameter.

```php
$page = $request->get('page', 1);
```

$key: The GET parameter key.
$default: Optional. The default value to return if the key is not found.
Returns: The GET parameter value.
post(string $key, mixed $default = null): mixed
Retrieves a POST parameter.

```php
$email = $request->post('email');
```

$key: The POST parameter key.
$default: Optional. The default value to return if the key is not found.
Returns: The POST parameter value.
json(string $key, mixed $default = null): mixed
Retrieves a value from the parsed JSON request body.

```php
$userId = $request->json('user_id');
```

$key: The JSON key.
$default: Optional. The default value to return if the key is not found.
Returns: The JSON value.
getBody(): string
Gets the raw request body.

```php
$requestBody = $request->getBody();
```

Returns: The raw request body.
validate(array $rules): array
Validates the request data against the given rules.

```php
$rules = [
  'name' => 'required|min:3',
  'email' => 'required|email',
];

$errors = $request->validate($rules);

if (!empty($errors)) {
  // Handle validation errors
}
```

$rules: An array of validation rules.
Returns: An array of validation errors.
setUrlParams(array $params): void
Sets the URL parameters.

```php
$request->setUrlParams(['id' => 10, 'slug' => 'my-article']);
```

$params: An array of URL parameters.
param(string $key, mixed $default = null): mixed
Retrieves a URL parameter.

```php
$articleId = $request->param('id');
```


$key: The URL parameter key.
$default: Optional. The default value to return if the key is not found.
Returns: The URL parameter value.
all(): array
Gets all input data (GET, POST, JSON).

```php
$allInputData = $request->all();
```

Returns: An array containing all input data.

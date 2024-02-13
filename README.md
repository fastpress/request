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
You can then access various parts of the HTTP request:
```php
// Get a value from the GET array
$value = $request->get('key');

// Check if the request method is POST
if ($request->isPost()) {
    // Handle POST request
}
```

## Contributing
Contributions are welcome! Please feel free to submit a pull request or open issues to improve the library.


## License
This library is open-sourced software licensed under the MIT license.

## Support
If you encounter any issues or have questions, please file them in the issues section on GitHub.


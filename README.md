# Cloudflare D1 Query Builder for PHP

This repository contains a PHP class that provides a query builder for Cloudflare D1 database. It allows you to build and execute SQL queries using the Cloudflare D1 API in a more intuitive and object-oriented manner.

## Features

- Easy-to-use query builder interface
- Support for common SQL operations (SELECT, INSERT, UPDATE, DELETE)
- Methods for table creation, modification, and deletion
- Integration with Cloudflare D1 API

## Installation

To use this class in your project, you can simply include the `CloudflareD1.php` file in your PHP script.

```php
require_once 'path/to/CloudflareD1.php';
use App\Helpers\CloudflareD1;
```

## Configuration

Before using the class, make sure to set the following environment variables:

- `CLOUDFLARE_ACCOUNT_ID`: Your Cloudflare account ID
- `CLOUDFLARE_DATABASE_ID`: Your Cloudflare D1 database ID
- `CLOUDFLARE_API_KEY`: Your Cloudflare API key

## Usage

Here are some examples of how to use the CloudflareD1 class:

### Select Query

```php
$results = CloudflareD1::table('users')
    ->select(['name', 'email'])
    ->where('age', '>', 18)
    ->orderBy('name')
    ->limit(10)
    ->get();
```

### Insert Query

```php
$data = ['name' => 'John Doe', 'email' => 'john@example.com'];
CloudflareD1::table('users')->insert($data);
```

### Update Query

```php
CloudflareD1::table('users')
    ->update(['status' => 'active'])
    ->where('id', '=', 1)
    ->get();
```

### Delete Query

```php
CloudflareD1::table('users')
    ->delete()
    ->where('id', '=', 1)
    ->get();
```

### Create Table

```php
$columns = [
    'id INT PRIMARY KEY',
    'name VARCHAR(255)',
    'email VARCHAR(255)'
];
CloudflareD1::createTable('users', $columns);
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is open-source and available under the [MIT License](LICENSE).

## Links

- [Cloudflare D1 Documentation](https://developers.cloudflare.com/d1/)
- [Source Code](https://github.com/nandunkz/Cloudflare-D1)

## Support

If you encounter any problems or have any questions, please open an issue in this repository.

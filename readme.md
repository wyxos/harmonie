# Harmonie

Backend utilities for Laravel and @wyxos/vision.

## Description

Harmonie is a comprehensive package that provides a set of utilities to streamline backend development in Laravel applications, especially when working with the @wyxos/vision frontend library. It offers tools for handling data listings, imports, exports, and resource management.

## Requirements

- PHP 8.1 or higher
- Laravel 11.31+ or 12.0+
- league/csv 9.0+
- laravel/scout 10.6+
- phpoffice/phpspreadsheet 2.2+

## Installation

You can install the package via composer:

```bash
composer require wyxos/harmonie
```

The package will automatically register its service providers.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=harmonie:harmonie-config
```

## Features

### Listing

The Listing component provides a powerful way to handle data listings with filtering, pagination, and formatting. It supports both Eloquent and Scout (search) queries.

Example usage:

```php
use Wyxos\Harmonie\Listing\ListingBase;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Builder as ScoutBuilder;

class UserListing extends ListingBase
{
    public function baseQuery()
    {
        return User::query();
    }

    public function filters(Builder|ScoutBuilder $base)
    {
        $this->whereLike($base, 'search', 'name');
        $this->whereIn($base, 'roles');
        $this->whereRange($base, 'created_at');
    }

    public function filterLabels(): array
    {
        return [
            'search' => 'Search',
            'roles' => 'Roles',
            'created_from' => 'Created From',
            'created_to' => 'Created To',
        ];
    }

    public function load($pagination)
    {
        $pagination->load('roles');
    }
}
```

### Export

The Export component allows you to export data to CSV files with support for chunking large datasets.

Example usage:

```php
use Wyxos\Harmonie\Export\ExportBase;
use Illuminate\Database\Eloquent\Builder;

class UserExport extends ExportBase
{
    public function filename($parameters = [])
    {
        return 'users-export-' . now()->format('Y-m-d');
    }

    public function query(array $parameters = []): Builder
    {
        return User::query();
    }

    public function format($row)
    {
        return [
            'ID' => $row->id,
            'Name' => $row->name,
            'Email' => $row->email,
            'Created At' => $row->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function chunkQuery(): Builder
    {
        return $this->query();
    }
}
```

### Import

The Import component provides functionality for importing data from CSV files with support for validation and error handling.

### Resource

The Resource component extends Laravel's resource functionality with additional features for API development.

### Commands

Harmonie includes several useful Artisan commands:

- `php artisan harmonie:clear-all-cache` - Clears various cache types
- `php artisan harmonie:flush-redis` - Clears Redis cache
- `php artisan harmonie:generate-administrator` - Creates an admin user
- `php artisan harmonie:scout-reset` - Resets Laravel Scout indexes
- `php artisan harmonie:make-model` - Custom model generator
- `php artisan harmonie:install-git-hook` - Installs a git pre-push hook that runs tests
- `php artisan harmonie:uninstall-git-hook` - Uninstalls the git pre-push hook

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Wyxos](https://github.com/wyxos)

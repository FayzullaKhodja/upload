# File upload for Laravel

## Requirements

- PHP >=5.4
- [Intervention Image](https://github.com/Intervention/image)

## Installation

Require this package with composer:

```
composer require khodja/upload
```

In Laravel 5.5, the service provider and facade will automatically get registered. For older versions of the framework, follow the steps below:

Register the provider directly in your app configuration file config/app.php
```php
'providers' => [
    // ...
    Intervention\Image\ImageServiceProvider::class,
    Khodja\Upload\UploadServiceProvider::class, 
];
```

Add the facade aliases in the same file:
```php
'aliases' => [
    ...
    'Upload' => Khodja\Upload\Facades\Upload::class
];
```

### Package Configuration

Publish configuration

```
php artisan vendor:publish --provider="Khodja\Upload\UploadServiceProvider"
```

## Methods

* `getFile($catalog, $id)`: Get the path to the file.
* `getFiles($catalog, $id [, $filter])`: Get the path to the files.
* `saveFile($catalog, $id, $file [, $options])`: Save file.
* `saveFiles($catalog, $id, $files [, $options])`: Save files.
* `swapFirst($catalog, $id, $i)`: Swap the first file with given file by index.
* `getImage($catalog, $id [, $class=''])`: Get image tag with source.
* `getThumbImage($catalog, $id, $size [, $class=''])`: Get thumb image tag.
* `getThumbFile($catalog, $id, $size)`: Generate path for a thumb image.
* `getThumbFiles($catalog, $id, $size)`: Get all thumb files path.
* `hasFile($catalog, $id)`: Whether the file contains a catalog folder.
* `hasFiles($catalog, $id)`: Whether the files contains a catalog folder.
* `removeFile($catalog, $id)`: Remove file.
* `removeFiles($catalog, $id [, $file_names])`: Remove files.

## Code example

Usage inside a laravel route
```php
Route::get('/post-image/{id}', function($id)
{
    return Upload::getImage('image', $id);
});
```


## Support

Feel free to post your issues in the issues section.

## Security

If you discover any security related issues, please email fayzulla@khodja.uz instead of using the issue tracker.

## License

This library is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).


# Lumen Framework API Versioning

Versioning your lumen api using `Accept` Header and calls the corresponding controller based on version given.

## Installation

Just copy the `app` folder and `config`. **NOTE:** Under the `app/Providers` folder `AppServiceProvider` class is there so just copy paste the app binding to your `AppServiceProvider` class. 

## Usage

In your `routes/web.php`, register the `App\Http\Middleware\VersionControl` class to your routes.

```php
$app->group(['prefix', '/', 'middleware' => App\Http\Middleware\VersionControl::class], function ($app) {
    // routes resides here
});
```

Also do not forget to autoload `config/api.php` in your `bootstrap/app.php`.

```php
$app->configure('api');
```

## Author

Feel free to modify the code and open-sourced it. I did it because i love programming. :)
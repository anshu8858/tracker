# Laravel Stats Tracker

[![Latest Stable Version](https://img.shields.io/packagist/v/anshu8858/tracker.svg?style=flat-square)](https://packagist.org/packages/anshu8858/tracker) [![License](https://img.shields.io/badge/license-BSD_3_Clause-brightgreen.svg?style=flat-square)](LICENSE) [![Downloads](https://img.shields.io/packagist/dt/anshu8858/tracker.svg?style=flat-square)](https://packagist.org/packages/anshu8858/tracker)

### Tracker gathers a lot of information from your requests to identify and store:

- **Sessions**
- **Page Views (hits on routes)**
- **Users (logged users)**
- **Devices** (computer, smartphone, tablet...)
- **Languages** (preference, language range)
- **User Devices** (by, yeah, storing a cookie on each device)
- **Browsers** (Chrome, Mozilla Firefox, Safari, Internet Explorer...)
- **Operating Systems** (iOS, Mac OS, Linux, Windows...)
- **Geo Location Data** (Latitute, Longitude, Country and City)
- **Routes and all its parameters**
- **Events**
- **Referers** (url, medium, source, search term...)
- **Exceptions/Errors**
- **Sql queries and all its bindings**
- **Url queries and all its arguments**
- **Database connections**

## Index

- [Why?](#why)
- [How To Use It](#usage)
- [Table Schemas](#how-data-is-stored)
- [System Requirements](#requirements)
- [Installing](#installing)
- [Upgrading](upgrading.md)
- [Changelog](changelog.md)
- [Contributing](#contributing)

## Why?

Storing user tracking information, on indexed and normalized database tables, wastes less disk space and ease the extract of valuable information about your application and business.

## Usage

As soon as you install and enable it, Tracker will start storing all information you tell it to, then you can in your application use the Tracker Facade to access everything. Here are some of the methods and relationships available:

#### Current Session/Visitor

```php
$visitor = Tracker::currentSession();
```

Most of those methods return an Eloquent model or collection, so you can use not only its attributes, but also relational data:

```php
var_dump( $visitor->client_ip );
var_dump( $visitor->device->is_mobile );
var_dump( $visitor->device->platform );
var_dump( $visitor->geoIp->city );
var_dump( $visitor->language->preference );
```

#### Sessions (visits)

```php
$sessions = Tracker::sessions(60 * 24); // get sessions (visits) from the past day
```

```php
foreach ($sessions as $session)
{
    var_dump( $session->user->email );
    var_dump( $session->device->kind . ' - ' . $session->device->platform );
    var_dump( $session->agent->browser . ' - ' . $session->agent->browser_version );
    var_dump( $session->geoIp->country_name );
    foreach ($session->session->log as $log)
    {
    	var_dump( $log->path );
    }
}
```

#### Online Users 

Brings all online sessions (logged and unlogged users)

```php
$users = Tracker::onlineUsers(); // defaults to 3 minutes
```

#### Users

```php
$users = Tracker::users(60 * 24);
```

#### User Devices

```php
$users = Tracker::userDevices(60 * 24, $user->id);
```

#### Events

```php
$events = Tracker::events(60 * 24);
```

#### Errors

```php
$errors = Tracker::errors(60 * 24);
```

#### PageViews summary

```php
$pageViews = Tracker::pageViews(60 * 24 * 30);
```

#### PageViews By Country summary

```php
$pageViews = Tracker::pageViewsByCountry(60 * 24);
```

#### Filter range

You can send timestamp ranges to those methods using the Minutes class:

```php
$range = new Minutes();

$range->setStart(Carbon::now()->subDays(2));
$range->setEnd(Carbon::now()->subDays(1));

Tracker::userDevices($range);
```

#### Routes By Name

Having a route of

```php
Route::get('user/{id}', ['as' => 'user.profile', 'use' => 'UsersController@profile']);
```

You can use this method to select all hits on that particular route and count them using Laravel:

```php
return Tracker::logByRouteName('user.profile')
        ->where(function($query)
        {
            $query
                ->where('parameter', 'id')
                ->where('value', 1);
        })
        ->count();
```

And if you need count how many unique visitors accessed that route, you can do:

```php
return Tracker::logByRouteName('tracker.stats.log')
        ->where(function($query)
        {
            $query
                ->where('parameter', 'uuid')
                ->where('value', '8b6faf82-00f1-4db9-88ad-32e58cfb4f9d');
        })
        ->select('tracker_log.session_id')
        ->groupBy('tracker_log.session_id')
        ->distinct()
        ->count('tracker_log.session_id');
```


## How data is stored

All tables are prefixed by `tracker_`, and here's an extract of some of them, showing columns and contents:

### sessions

```
+-----+--------------------------------------+---------+-----------+----------+-----------------+------------+-----------+----------+-------------+
| id  | uuid                                 | user_id | device_id | agent_id | client_ip       | referer_id | cookie_id | geoip_id | language_id |
+-----+--------------------------------------+---------+-----------+----------+-----------------+------------+-----------+----------+-------------+
| 1   | 09465be3-5930-4581-8711-5161f62c4373 | 1       | 1         | 1        | 186.228.127.245 | 2          | 1         | 2        | 3           |
| 2   | 07399969-0a19-47f0-862d-43b06d7cde45 |         | 2         | 2        | 66.240.192.138  |            | 2         | 2        | 2           |
+-----+--------------------------------------+---------+-----------+----------+-----------------+------------+-----------+----------+-------------+
```

### devices

```
+----+----------+-------------+-------------+------------------+-----------+
| id | kind     | model       | platform    | platform_version | is_mobile |
+----+----------+-------------+-------------+------------------+-----------+
| 1  | Computer | unavailable | Windows 8   |                  |           |
| 2  | Tablet   | iPad        | iOS         | 7.1.1            | 1         |
| 3  | Computer | unavailable | Windows XP  |                  |           |
| 5  | Computer | unavailable | Other       |                  |           |
| 6  | Computer | unavailable | Windows 7   |                  |           |
| 7  | Computer | unavailable | Windows 8.1 |                  |           |
| 8  | Phone    | iPhone      | iOS         | 7.1              | 1         |
+----+----------+-------------+-------------+------------------+-----------+
```

### agents

```
+----+-----------------------------------------------------------------------------------------------------------------------------------------+-------------------+-----------------+
| id | name                                                                                                                                    | browser           | browser_version |
+----+-----------------------------------------------------------------------------------------------------------------------------------------+-------------------+-----------------+
| 1  | Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.114 Safari/537.36                           | Chrome            | 35.0.1916       |
| 2  | Mozilla/5.0 (iPad; CPU OS 7_1_1 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) CriOS/34.0.1847.18 Mobile/11D201 Safari/9537.53 | Chrome Mobile iOS | 34.0.1847       |
| 3  | Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)                                                                                      | IE                | 6.0             |
+----+-----------------------------------------------------------------------------------------------------------------------------------------+-------------------+-----------------+
```

### languages

```
+----+------------+----------------+
| id | preference | language_range |
+----+------------+----------------+
| 1  | en         | ru=0.8,es=0.5  |
| 2  | es         | en=0.7,ru=0.3  |
| 3  | ru         | en=0.5,es=0.5  |
+----+------------+----------------+
```


### domains

```
+----+--------------------------+
| id | name                     |
+----+--------------------------+
| 1  | antoniocarlosribeiro.com |
+----+--------------------------+
```

### errors

```
+----+------+------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| id | code | message                                                                                                                                                                                                                      |
+----+------+------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| 1  | 404  |                                                                                                                                                                                                                              |
| 2  | 500  | Call to undefined method PragmaRX\Tracker\Tracker::sessionLog()                                                                                                                                                              |
| 3  | 500  | Trying to get property of non-object (View: /home/forge/stage.antoniocarlosribeiro.com/app/views/admin/tracker/log.blade.php)                                                                                                |
| 4  | 500  | syntax error, unexpected 'foreach' (T_FOREACH)                                                                                                                                                                               |
| 5  | 500  | Call to undefined method PragmaRX\Tracker\Tracker::pageViewsByCountry()                                                                                                                                                      |
| 6  | 500  | Class PragmaRX\Firewall\Vendor\Laravel\Artisan\Base contains 1 abstract method and must therefore be declared abstract or implement the remaining methods (Illuminate\Console\Command::fire)                                 |
+----+------+------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
```

### events

```
+----+------------------------------------------------+
| id | name                                           |
+----+------------------------------------------------+
| 1  | illuminate.log                                 |
| 2  | router.before                                  |
| 3  | router.matched                                 |
| 4  | auth.attempt                                   |
| 5  | auth.login                                     |
| 6  | composing: admin.tracker.index                 |
| 7  | creating: admin.tracker._partials.menu         |
| 8  | composing: admin.tracker._partials.menu        |
+----+------------------------------------------------+
```

### geoip

```
+----+----------+-----------+--------------+---------------+---------------------------+--------+----------------+-------------+-----------+----------+------------+----------------+
| id | latitude | longitude | country_code | country_code3 | country_name              | region | city           | postal_code | area_code | dma_code | metro_code | continent_code |
+----+----------+-----------+--------------+---------------+---------------------------+--------+----------------+-------------+-----------+----------+------------+----------------+
| 1  | 37.4192  | -122.057  | US           | USA           | United States             | CA     | Mountain View  | 94043       | 650       | 807      | 807        | NA             |
| 2  | -10      | -55       | BR           | BRA           | Brazil                    |        |                |             |           |          |            | SA             |
| 3  | 30.3909  | -86.3161  | US           | USA           | United States             | FL     | Miramar Beach  | 32550       | 850       | 686      | 686        | NA             |
| 4  | 38.65    | -90.5334  | US           | USA           | United States             | MO     | Chesterfield   | 63017       | 314       | 609      | 609        | NA             |
| 5  | 42.7257  | -84.636   | US           | USA           | United States             | MI     | Lansing        | 48917       | 517       | 551      | 551        | NA             |
| 6  | 42.8884  | -78.8761  | US           | USA           | United States             | NY     | Buffalo        | 14202       | 716       | 514      | 514        | NA             |
+----+----------+-----------+--------------+---------------+---------------------------+--------+----------------+-------------+-----------+----------+------------+----------------+
```

### log

```
+-----+------------+---------+----------+--------+---------------+---------+-----------+---------+------------+----------+
| id  | session_id | path_id | query_id | method | route_path_id | is_ajax | is_secure | is_json | wants_json | error_id |
+-----+------------+---------+----------+--------+---------------+---------+-----------+---------+------------+----------+
| 1   | 1          | 1       |          | GET    | 1             |         | 1         |         |            |          |
| 2   | 1          | 2       |          | GET    | 2             |         | 1         |         |            |          |
| 3   | 1          | 3       |          | GET    | 3             |         | 1         |         |            |          |
| 4   | 1          | 3       |          | POST   | 4             |         | 1         |         |            |          |
+-----+------------+---------+----------+--------+---------------+---------+-----------+---------+------------+----------+
```

### paths

```
+----+--------------------------------------------------------+
| id | path                                                   |
+----+--------------------------------------------------------+
| 1  | /                                                      |
| 2  | admin                                                  |
| 3  | login                                                  |
| 4  | admin/languages                                        |
| 5  | admin/tracker                                          |
| 6  | admin/pages                                            |
+----+--------------------------------------------------------+
```

### route_paths

```
+----+----------+--------------------------------------------------------+
| id | route_id | path                                                   |
+----+----------+--------------------------------------------------------+
| 1  | 1        | /                                                      |
| 2  | 2        | admin                                                  |
| 3  | 3        | login                                                  |
| 4  | 4        | login                                                  |
+----+----------+--------------------------------------------------------+
```

### routes

```
+----+--------------------------------------+----------------------------------------------------------+
| id | name                                 | action                                                   |
+----+--------------------------------------+----------------------------------------------------------+
| 1  | home                                 | ACR\Controllers\Home@index                               |
| 2  | admin                                | ACR\Controllers\Admin\Admin@index                        |
| 3  | login.form                           | ACR\Controllers\Logon@form                               |
| 4  | login.do                             | ACR\Controllers\Logon@login                              |
| 5  | admin.languages.index                | ACR\Controllers\Admin\Pages@store                        |
| 6 | bio                                  | ACR\Controllers\StaticPages@show                         |
| 7 | logout.do                            | ACR\Controllers\Logon@logout                             |
| 8 | admin.tracker.index                  | ACR\Controllers\Admin\UsageTracker@index                 |
| 9 | admin.tracker.api.pageviewsbycountry | ACR\Controllers\Admin\UsageTracker@apiPageviewsByCountry |
| 10 | admin.tracker.api.pageviews          | ACR\Controllers\Admin\UsageTracker@apiPageviews          |
+----+--------------------------------------+----------------------------------------------------------+
```

### sql_queries                   ;

```
+----+------------------------------------------+-------------------------------------------------------------------------------------------------+-------+---------------+
| id | sha1                                     | statement                                                                                       | time  | connection_id |
+----+------------------------------------------+-------------------------------------------------------------------------------------------------+-------+---------------+
| 1  | 5aee121018ac16dbf26dbbe0cf35fd44a29a5d7e | select * from "users" where "id" = ? limit 1                                                    | 3.13  | 1             |
| 2  | 0fc3f3a722b0f9ef38e6bee44fc3fde9fb1fd1d9 | select "created_at" from "articles" where "published_at" is not null order by "created_at" desc | 1.99  | 1             |
+----+------------------------------------------+-------------------------------------------------------------------------------------------------+-------+---------------+
```

## Manually log things

If your application has special needs, you can manually log things like:

#### Events  

```php
Tracker::trackEvent(['event' => 'cart.add']);
Tracker::trackEvent(['event' => 'cart.add', 'object' => 'App\Cart\Events\Add']);
```

#### Routes

```php
Tracker::trackVisit(
    [
        'name' => 'my.dynamic.route.name',
        'action' => 'MyDynamic@url'
    ],
    ['path' => 'my/dynamic/url']
);
```

## Requirements

- Laravel 7+, 8+
- PHP 7.2+
- Package "geoip/geoip":"~1.14" or "geoip2/geoip2":"~2.1"
  (If you are planning to store Geo IP information)


## Installing

#### Require the `tracker` package by **executing** the following command in your command line:

    composer require anshu8858/tracker

#### Add the service provider to your app/config/app.php:

```php
 PragmaRX\Tracker\Vendor\Laravel\ServiceProvider::class,
```

#### Add the alias to the facade on your app/config/app.php:

```php
'Tracker' => 'PragmaRX\Tracker\Vendor\Laravel\Facade',
```

#### Publish tracker configuration:

    php artisan config:publish anshu8858/tracker

**Laravel 7**

    php artisan vendor:publish --provider="PragmaRX\Tracker\Vendor\Laravel\ServiceProvider"

#### Enable the Middleware (Laravel 7)

Open the newly published config file found at `app/config/tracker.php` and enable `use_middleware`:

```php
'use_middleware' => true,
```

#### Add the Middleware to Laravel Kernel (Laravel 7)

Open the file `app/Http/Kernel.php` and add the following to your web middlewares:

```php
\PragmaRX\Tracker\Vendor\Laravel\Middlewares\Tracker::class,
```

#### Enable Tracker in your tracker.php (Laravel 7)

```php
'enabled' => true,
```

#### Publish the migration

    php artisan tracker:tables

`vendor:publish` does it for you in Laravel 7.

#### Create a database connection for it on your `config/database.php`

```php
'connections' => [
    'mysql' => [
        ...
    ],
    
    'tracker' => [
    	'driver'   => '...',
    	'host'     => '...',
    	'database' => ...,
        'strict' => false,    // to avoid problems on some MySQL installs
    	...
    ],
],
```

#### Migrate it

If you have set the default connection to `tracker`, you can

    php artisan migrate

Otherwise you'll have to

    php artisan migrate --database=tracker

#### If you are planning to store Geo IP information, also install the geoip package:

    composer require "geoip/geoip":"~1.14"

    or

    composer require "geoip2/geoip2":"~2.0"

#### And make sure you don't have the PHP module installed. This is a Debian/Ubuntu example:

	sudo apt-get purge php5-geoip

## Everything Is Disabled By Default

Tracker has a lot of logging options, but you need to decide what you want to log. Starting by enabling this one:

```php
'log_enabled' => true,
```

It is responsible for logging page hits and sessions, basically the client IP address.

## Multiple authentication drivers

You just have to all your auth IOC bidings to the array:

```php
'authentication_ioc_binding' => ['auth', 'admin'],
```


## Troubleshooting

### Is everything enabled?

Make sure Tracker is enabled in the config file. Usually this is the source of most problems.

### Tail your laravel.log file

``` php
tail -f storage/logs/laravel.log
``` 

Usually non-trackable IP addresses and other messages will appear in the log:

```
[2018-03-19 21:28:08] local.WARNING: TRACKER (unable to track item): 127.0.0.1 is not trackable.
```

### SQLSTATE[42000]: Syntax error or access violation: 1067 Invalid default value for 'field name' 

This is probably related to SQL modes on MySQL, specifically with `NO_ZERO_IN_DATE` and `NO_ZERO_DATE` modes:

https://stackoverflow.com/questions/36882149/error-1067-42000-invalid-default-value-for-created-at


Because Laravel's defaults to  

```sql
set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'
```

You may need to change your Tracker database connection configuration to

```php
'connections' => [
    ...

    'tracker' => [
        ...

        'strict'    => false,
    ],
],

```

## Not able to track users?

If you get an error like:

    Base table or view not found: 1146 Table 'tracker.users' doesn't exist

You probably need to change: 

    'user_model' => 'PragmaRX\Tracker\Vendor\Laravel\Models\User',

To create (or use a current) a User model:

    'user_model' => 'App\TrackerUser',

And configure the Connection related to your users table:

    protected $connection = 'mysql';
    
## Not able to track API's?

In your kernel 

    protected $middlewareGroups = [
        'web' => [
            .......
            \PragmaRX\Tracker\Vendor\Laravel\Middlewares\Tracker::class,
        ],

        'api' => [
           .......
            \PragmaRX\Tracker\Vendor\Laravel\Middlewares\Tracker::class,
        ],
    ];


## Author

[Antonio Carlos Ribeiro](https://twitter.com/anshu_kushawaha)
[All Contributors](https://github.com/anshu8858/tracker/graphs/contributors)

## License

Tracker is licensed under the BSD 3-Clause License - see the `LICENSE` file for details

## Contributing

Pull requests and issues are more than welcome.

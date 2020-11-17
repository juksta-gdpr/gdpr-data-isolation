# Laravel GDPR Article 32 Data Isolation #

According to the GDPR Article 32 personal data should be isolated in order to track access to it, be aware of the storate period etc. This package provides an easy way to keep personal data in a separate database/table with slight models modification. Also it can helps you get compliant with GDPR Article 17 (Right to be forgotten), GDPR Arctile 20 (Right to data portability).


## Installation

First, install the package via the Composer package manager:

```bash
$ composer require juksta/gdpr-data-isolation
```

After installing the package, you should publish the configuration file:

```bash
$ artisan vendor:publish --provider="Juksta\GdprDataIsolation\GdprDataIsolationServiceProvider"
```

## Configuration

#### Set GDPR Database connection details

Update .env file with the GDPR Database details. It can be a separate database or default one.

```php
DB_GDPR_PII_HOST=maria
DB_GDPR_PII_PORT=3306
DB_GDPR_PII_DATABASE=gdprpersonal
DB_GDPR_PII_USERNAME=gdprpersonal
DB_GDPR_PII_PASSWORD=password123!
```

#### Run migrations

This package utilize many to many relation between models contain personal information and the PersonalInformation model. So in case of the default laravel User model name and email fields should be nullable. If you want to alter the users table with the package migration, please set and export FORCE_ALTER_USERS_TABLE environment variable

```bash
export FORCE_ALTER_USERS_TABLE=1
```
Run migrations

```bash
artisan migrate
```

## Usage

To add the private information isolation functionality:

1. Use PrivateInformationOperational trait inside any model that contains personal information.
2. Set the privateInformationAttributes model property in format 'Field name' => 'Export label'
3. Overwrite getPersonIdentificatorGdrp() method 

```php
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Juksta\GdprDataIsolation\Eloquent\PrivateInformationOperational;

class User extends Authenticatable
{
    use Notifiable;
    use PrivateInformationOperational;

	protected $privateInformationAttributes = [                                                                
        'name' => 'Full Name',                                                                                 
        'email' => 'Email',                                                                                    
	];
                                                                                                               
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getPersonIdentificatorGdrp()                                
    {                                                                           
        return $this->id;                                                       
    } 
}
```

#### Laravel authentication

1. Create an auth provider, e.g. \App\Auth\UserProvider that extends the package user provider

```php
namespace App\Auth;

use Juksta\GdprDataIsolation\Auth\UserProvider as BaseUserProvider;

class UserProvider extends BaseUserProvider
{
}
```
2. Register the auth provider inside App\Providers\AppServiceProvider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Auth\UserProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->auth->provider('gdpr_data_isolation_driver', function ($app, array $config) {
            $provider = new UserProvider($app['hash'], $config['model']);

            return $provider;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
``` 

3. Change auth configuration
Update config/auth.php

```php
    'providers' => [
        'users' => [
            'driver' => 'gdpr_data_isolation_driver',
            'model' => App\Models\User::class,
        ],
    ],
```

4. Update Auth/RegisterController::create() method

```php
    protected function create(array $data)
    {
        $user = User::create([
            'password' => Hash::make($data['password']),
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->save();

        return $user;
    }
```

## Data Format

```bash

MariaDB [gdprdev]> select * from users \G
*************************** 1. row ***************************
               id: 1
             name: 9a295e1d-3029-40fa-8452-3b77453846ac
            email: 2c854f65-e206-4465-8c77-483a82b56633
email_verified_at: NULL
         password: $2y$10$T1DOezyrcFYzV9enXjPvOu/DpREXl9Gb33gm0/fWIFrx5Xr1/4U/K
   remember_token: NULL
       created_at: 2020-11-17 14:20:47
       updated_at: 2020-11-17 14:20:47


MariaDB [gdprpersonal]> show tables;
+-----------------------------+
| Tables_in_gdprpersonal      |
+-----------------------------+
| personal_information        |
| personal_information_lookup |
+-----------------------------+
2 rows in set (0.001 sec)


MariaDB [gdprpersonal]> select * from personal_information_lookup \G
*************************** 1. row ***************************
                     id: 1
                  token: 9a295e1d-3029-40fa-8452-3b77453846ac
              person_id: 1
personal_information_id: 1 
                deleted: 0
             created_at: 2020-11-17 14:20:47
             updated_at: 2020-11-17 14:20:47
*************************** 4. row ***************************
                     id: 2
                  token: 2c854f65-e206-4465-8c77-483a82b56633
              person_id: 2
personal_information_id: 
                deleted: 0
             created_at: 2020-11-17 14:20:47
             updated_at: 2020-11-17 14:20:47

MariaDB [gdprpersonal]> select * from personal_information \G
*************************** 1. row ***************************
        id: 1
     value: John Doe
 db_source: users.name
     label: Full Name
   deleted: 0
created_at: 2020-11-17 13:34:14
updated_at: 2020-11-17 13:34:14
*************************** 2. row ***************************
        id: 2 
     value: john.doe@example.com
 db_source: users.email
     label: Email
   deleted: 0
created_at: 2020-11-17 13:34:14
updated_at: 2020-11-17 13:34:14

```

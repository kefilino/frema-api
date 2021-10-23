# Frema API

Frema (Freelancer Mahasiswa) application aims to provide a platform for college students who wants to sell their expertise as a freelancer. Laravel Lumen is used as the framework which is a PHP micro-framework for building web applications with expressive, elegant syntax. More about the framework Lumen can be found on the [Lumen website](https://lumen.laravel.com/docs). Integration with front-end coming soon.

# Installation

1. Clone this repo

```
git clone https://github.com/kefilino/frema-api.git
```

2. Install composer packages

```
cd frema-api
$ composer install
```

3. Create and setup .env file

```
make a copy of .env.example
$ copy .env.example .env
$ php artisan key:generate
put database credentials in .env file
$ php artisan jwt:secret
```

4. Migrate and insert records

```
$ php artisan migrate
```
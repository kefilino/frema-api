<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});


$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('login', ['uses' => 'UserController@login']);
    $router->post('register', ['uses' => 'UserController@create']);

    $router->group(['middleware' => 'auth'], function () use ($router) {
        $router->post('logout', ['uses' => 'UserController@logout']);

        $router->group(['prefix' => 'users'], function () use ($router) {
            $router->get('/', ['uses' => 'UserController@showUserInfo']);
            $router->get('{id}', ['uses' => 'UserController@showUserById']);
            $router->put('/', ['uses' => 'UserController@update']);
            $router->delete('/', ['uses' => 'UserController@delete']);
            $router->delete('/deleteall', ['uses' => 'UserController@deleteAll']);
        });

        $router->group(['prefix' => 'portfolio'], function () use ($router) {
            $router->get('/', ['uses' => 'PortfolioController@showPortfolio']);
            $router->get('{id}', ['uses' => 'PortfolioController@showPortfolioByUserId']);
            $router->post('/', ['uses' => 'PortfolioController@create']);
            $router->put('{id}', ['uses' => 'PortfolioController@update']);
            $router->delete('{id}', ['uses' => 'PortfolioController@delete']);
        });

        $router->group(['prefix' => 'product'], function () use ($router) {
            $router->get('/', ['uses' => 'ProductController@showProducts']);
            $router->get('{id}', ['uses' => 'ProductController@showProductById']);
            $router->get('/user/{id}', ['uses' => 'ProductController@showProductsByUserId']);
            $router->post('/', ['uses' => 'ProductController@create']);
            $router->put('{id}', ['uses' => 'ProductController@update']);
            $router->delete('{id}', ['uses' => 'ProductController@delete']);
        });

        $router->group(['prefix' => 'post'], function () use ($router) {
            $router->get('/', ['uses' => 'PostController@showAllPosts']);
            $router->get('{id}', ['uses' => 'PostController@showPostById']);
            $router->post('/', ['uses' => 'PostController@create']);
            $router->put('{id}', ['uses' => 'PostController@update']);
            $router->delete('{id}', ['uses' => 'PostController@delete']);
        });
    });
});
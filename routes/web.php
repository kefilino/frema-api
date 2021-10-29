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

        $router->group(['prefix' => 'user'], function () use ($router) {
            $router->get('/', ['uses' => 'UserController@showUserInfo']);
            $router->get('{id}', ['uses' => 'UserController@showUserById']);
            $router->put('/', ['uses' => 'UserController@update']);
            $router->delete('/', ['uses' => 'UserController@delete']);
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

        $router->group(['prefix' => 'review'], function () use ($router) {
            $router->get('/', ['uses' => 'ReviewController@showReviewsAsClient']);
            $router->get('/sales', ['uses' => 'ReviewController@showReviewsAsFreelancer']);
            $router->get('{id}', ['uses' => 'ReviewController@showReviewById']);
            $router->get('/product/{id}', ['uses' => 'ReviewController@showReviewByProductId']);
            $router->get('/user/{id}', ['uses' => 'ReviewController@showReviewByUserId']);
            $router->post('{id}', ['uses' => 'ReviewController@create']);
        });

        $router->group(['prefix' => 'transaction'], function () use ($router) {
            $router->get('/', ['uses' => 'TransactionController@showUserTransactions']);
            $router->get('/purchases', ['uses' => 'TransactionController@showUserPurchases']);
            $router->get('/sales', ['uses' => 'TransactionController@showUserSales']);
            $router->get('{id}', ['uses' => 'TransactionController@showUserTransactionsById']);
            $router->post('/', ['uses' => 'TransactionController@create']);
            $router->put('/pay/{id}', ['uses' => 'TransactionController@insertPaymentProof']);
            $router->put('/submit/{id}', ['uses' => 'TransactionController@insertProductProof']);
        });

        $router->group(['prefix' => 'job'], function () use ($router) {
            $router->get('/all', ['uses' => 'JobController@showJobs']);
            $router->get('{id}', ['uses' => 'JobController@showJobsById']);
            $router->get('/', ['uses' => 'JobController@showUserJobs']);
            $router->get('/user/{id}', ['uses' => 'JobController@showJobsByUserId']);
            $router->post('/', ['uses' => 'JobController@create']);
            $router->put('{id}', ['uses' => 'JobController@update']);
            $router->delete('{id}', ['uses' => 'JobController@delete']);
        });
    });
});
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
            $router->get('/notification', ['uses' => 'UserController@showUserNotifications']);
            $router->post('/notification', ['uses' => 'UserController@markNotificationsAsRead']);
            $router->post('/notification/{id}', ['uses' => 'UserController@markNotificationAsRead']);
            $router->put('/', ['uses' => 'UserController@update']);
            $router->delete('/', ['uses' => 'UserController@delete']);
        });

        $router->group(['prefix' => 'portfolio'], function () use ($router) {
            $router->get('/', ['uses' => 'PortfolioController@showPortfolio']);
            $router->post('/', ['uses' => 'PortfolioController@create']);
            $router->put('{id}', ['uses' => 'PortfolioController@update']);
            $router->delete('{id}', ['uses' => 'PortfolioController@delete']);
            $router->delete('/image/{id}', ['uses' => 'PortfolioController@deletePortfolioImage']);
        });

        $router->group(['prefix' => 'product'], function () use ($router) {
            $router->get('/me', ['uses' => 'ProductController@showUserProducts']);
            $router->post('/', ['uses' => 'ProductController@create']);
            $router->put('{id}', ['uses' => 'ProductController@update']);
            $router->delete('{id}', ['uses' => 'ProductController@delete']);
            $router->delete('{id}/image/{index}', ['uses' => 'ProductController@deleteProductImage']);
        });

        $router->group(['prefix' => 'review'], function () use ($router) {
            $router->get('/', ['uses' => 'ReviewController@showReviewsAsClient']);
            $router->get('/sales', ['uses' => 'ReviewController@showReviewsAsFreelancer']);
            $router->post('{id}', ['uses' => 'ReviewController@create']);
        });

        $router->group(['prefix' => 'transaction'], function () use ($router) {
            $router->get('/', ['uses' => 'TransactionController@showUserTransactions']);
            $router->get('/purchases', ['uses' => 'TransactionController@showUserPurchases']);
            $router->get('/sales', ['uses' => 'TransactionController@showUserSales']);
            $router->get('{id}', ['uses' => 'TransactionController@showUserTransactionsById']);
            $router->get('/file/{id}', ['uses' => 'TransactionController@getProductFile']);
            $router->post('/', ['uses' => 'TransactionController@create']);
            $router->post('/complete/{id}', ['uses' => 'TransactionController@completeTransaction']);
            $router->post('/confirm/{id}', ['uses' => 'TransactionController@confirmPayment']);
            $router->put('/pay/{id}', ['uses' => 'TransactionController@insertPaymentProof']);
            $router->put('/submit/{id}', ['uses' => 'TransactionController@insertProductProof']);
        });

        $router->group(['prefix' => 'job'], function () use ($router) {
            $router->get('/', ['uses' => 'JobController@showUserJobs']);
            $router->post('/', ['uses' => 'JobController@create']);
            $router->put('{id}', ['uses' => 'JobController@update']);
            $router->delete('{id}', ['uses' => 'JobController@delete']);
        });
    });

    $router->group(['prefix' => 'user'], function () use ($router) {
        $router->get('{id}', ['uses' => 'UserController@showUserInfoById']);
    });

    $router->group(['prefix' => 'portfolio'], function () use ($router) {
        $router->get('{id}', ['uses' => 'PortfolioController@showPortfolioByUserId']);
    });

    $router->group(['prefix' => 'product'], function () use ($router) {
        $router->get('/', ['uses' => 'ProductController@showAllProducts']);
        $router->get('{id}', ['uses' => 'ProductController@showProductById']);
        $router->get('/user/{id}', ['uses' => 'ProductController@showProductsByUserId']);
    });

    $router->group(['prefix' => 'review'], function () use ($router) {
        $router->get('{id}', ['uses' => 'ReviewController@showReviewById']);
        $router->get('/product/{id}', ['uses' => 'ReviewController@showReviewByProductId']);
        $router->get('/user/{id}', ['uses' => 'ReviewController@showReviewByUserId']);
    });

    $router->group(['prefix' => 'job'], function () use ($router) {
        $router->get('/all', ['uses' => 'JobController@showJobs']);
        $router->get('{id}', ['uses' => 'JobController@showJobsById']);
        $router->get('/user/{id}', ['uses' => 'JobController@showJobsByUserId']);
    });
});

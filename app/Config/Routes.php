<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// --- Default Home Route ---
$routes->get('/', 'UrlController::index');

// --- Specific GET Routes ---
$routes->get('/register', 'AuthController::registerShow', ['as' => 'AuthController::registerShow']);
$routes->get('/login', 'AuthController::loginShow', ['as' => 'AuthController::loginShow']);
$routes->get('/logout', 'AuthController::logout', ['as' => 'AuthController::logout']);
$routes->get('/simple', 'SimpleTestController::index');

// --- Specific POST Routes ---
$routes->post('/create', 'UrlController::create', ['as' => 'UrlController::create']);
$routes->post('/register', 'AuthController::registerAttempt', ['as' => 'AuthController::registerAttempt']);
$routes->post('/login', 'AuthController::loginAttempt', ['as' => 'AuthController::loginAttempt']);

// --- Add this route for QR Code generation ---
$routes->get('/qrcode/(:segment)', 'UrlController::qrcode/$1', ['as' => 'url.qrcode']);

// Route for deleting a URL
$routes->post('/delete/(:num)', 'UrlController::delete/$1', [
    'as' => 'UrlController::delete',
    'filter' => 'authGuard'
]);

$routes->get('/(:segment)', 'UrlController::redirect/$1');

$routes->get('/(:segment)', 'UrlController::redirect/$1');

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
$routes->get('/simple', 'SimpleTestController::index'); // Or ::simpletest if that's the method name
// $routes->get('/history', 'UrlController::history'); // Add history route here when ready

// --- Specific POST Routes ---
$routes->post('/create', 'UrlController::create', ['as' => 'UrlController::create']);
$routes->post('/register', 'AuthController::registerAttempt', ['as' => 'AuthController::registerAttempt']);
$routes->post('/login', 'AuthController::loginAttempt', ['as' => 'AuthController::loginAttempt']);

// --- Wildcard Redirection Route ---
// !! MUST BE PLACED AFTER other specific GET routes !!
$routes->get('/(:segment)', 'UrlController::redirect/$1');

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 * There will be default routes file imports here:
 * require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
 */
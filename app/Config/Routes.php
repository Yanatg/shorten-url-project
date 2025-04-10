<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// --- Default Home Route ---
// Map the root URL ('/') to the 'index' method of 'UrlController'
$routes->get('/', 'UrlController::index');

// --- URL Creation Route ---
// Map POST requests to '/create' to the 'create' method of 'UrlController'
// Give it a name so we can use url_to('url.create') in the form action
// Updated to match url_to convention used in the view:
$routes->post('/create', 'UrlController::create', ['as' => 'UrlController::create']);

// --- Short URL Redirection Route ---
// Map GET requests with any single segment after the base URL (e.g., /aBcDeF)
// to the 'redirect' method of 'UrlController', passing the segment as an argument ($1)
// IMPORTANT: Place this *after* other specific GET routes (like '/', '/history' etc. if you add them later)
// to avoid it capturing those URLs.
$routes->get('/(:segment)', 'UrlController::redirect/$1');


// --- Optional: Route for History Page (if implementing later) ---
// $routes->get('/history', 'UrlController::history', ['as' => 'url.history', 'filter' => 'login']); // 'login' filter assumes auth setup

// --- Add any other specific routes ABOVE the /(:segment) route ---


/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 * There will be default routes file imports here:
 * require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
 */
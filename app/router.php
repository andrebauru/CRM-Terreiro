<?php

declare(strict_types=1);

// Basic Router
// This router is intentionally simple and can be expanded later.

use App\Helpers\Session;

$requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Handle _method spoofing for PUT/DELETE
if (isset($_POST['_method'])) {
    $requestMethod = strtoupper($_POST['_method']);
}

// Define routes
$routes = [
    'GET' => [
        '' => 'HomeController@index', // Default route, e.g., /
        'dashboard' => 'HomeController@dashboard', // Corrected controller
        'login' => 'AuthController@showLoginForm',
        'logout' => 'AuthController@logout',
        // Clients CRUD
        'clients' => 'ClientController@index',
        'clients/create' => 'ClientController@create',
        'clients/(\d+)' => 'ClientController@show',
        'clients/(\d+)/edit' => 'ClientController@edit',
        // Services CRUD
        'services' => 'ServiceController@index',
        'services/create' => 'ServiceController@create',
        'services/(\d+)' => 'ServiceController@show',
        'services/(\d+)/edit' => 'ServiceController@edit',
        // Jobs CRUD
        'jobs' => 'JobController@index',
        'jobs/create' => 'JobController@create',
        'jobs/(\d+)' => 'JobController@show',
        'jobs/(\d+)/edit' => 'JobController@edit',
        // Users CRUD
        'users' => 'UserController@index',
        'users/create' => 'UserController@create',
        'users/(\d+)' => 'UserController@show', // Although not explicitly asked, it's good practice for CRUD
        'users/(\d+)/edit' => 'UserController@edit',
    ],
    'POST' => [
        'login' => 'AuthController@login',
        'clients' => 'ClientController@store',
        'services' => 'ServiceController@store',
        'jobs' => 'JobController@store',
        'jobs/(\d+)/notes' => 'JobController@addNote', // New route for adding notes
        'users' => 'UserController@store', // New route for creating users
    ],
    'PUT' => [
        'clients/(\d+)' => 'ClientController@update', // RESTful update
        'services/(\d+)' => 'ServiceController@update', // RESTful update
        'jobs/(\d+)' => 'JobController@update', // RESTful update
        'users/(\d+)' => 'UserController@update', // New route for updating users
    ],
    'DELETE' => [
        'clients/(\d+)' => 'ClientController@destroy', // RESTful delete
        'services/(\d+)' => 'ServiceController@destroy', // RESTful delete
        'jobs/(\d+)' => 'JobController@destroy', // RESTful delete
        'jobs/notes/(\d+)' => 'JobController@deleteNote', // New route for deleting notes
        'jobs/attachments/(\d+)' => 'JobController@deleteAttachment', // New route for deleting attachments
        'users/(\d+)' => 'UserController@destroy', // New route for deleting users
    ]
];

// Dispatcher
function dispatch(string $controllerAction, array $params = []): void
{
    list($controllerName, $actionName) = explode('@', $controllerAction);
    $controllerFile = BASE_PATH . '/app/Controllers/' . $controllerName . '.php'; // Corrected path

    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        // Prepend namespace to controller name
        $fullControllerName = 'App\\Controllers\\' . $controllerName;
        if (class_exists($fullControllerName)) {
            $controller = new $fullControllerName();
            if (method_exists($controller, $actionName)) {
                call_user_func_array([$controller, $actionName], $params);
                return;
            }
        }
    }

    // Fallback for 404 or other errors
    http_response_code(404);
    echo "404 Not Found - Controller or method not found.";
    // A proper view for 404 should be rendered here
}

$routeFound = false;

// Check for exact matches first
if (isset($routes[$requestMethod])) {
    foreach ($routes[$requestMethod] as $route => $controllerAction) {
        if ($route === $requestUri) {
            dispatch($controllerAction);
            $routeFound = true;
            break;
        }
    }
}

// If no exact match, check for dynamic routes with regex
if (!$routeFound && isset($routes[$requestMethod])) {
    foreach ($routes[$requestMethod] as $route => $controllerAction) {
        // Convert route to a regex pattern, capturing groups for parameters
        $pattern = '#^' . str_replace('/', '\/', $route) . '$#';
        if (preg_match($pattern, $requestUri, $matches)) {
            array_shift($matches); // Remove the full match, keep only captured groups
            dispatch($controllerAction, $matches);
            $routeFound = true;
            break;
        }
    }
}

if (!$routeFound) {
    http_response_code(404);
    echo "404 Not Found - No route matched.";
    // A proper view for 404 should be rendered here
}

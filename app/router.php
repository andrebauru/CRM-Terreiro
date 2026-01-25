<?php

declare(strict_types=1);

// Basic Router
// This router is intentionally simple and can be expanded later.

$requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Define routes
$routes = [
    'GET' => [
        '' => 'HomeController@index', // Default route, e.g., /
        'dashboard' => 'DashboardController@index',
        'login' => 'AuthController@showLoginForm',
        'logout' => 'AuthController@logout',
        // Example CRUD routes
        'clients' => 'ClientController@index',
        'clients/create' => 'ClientController@create',
        'clients/(\d+)' => 'ClientController@show',
        'clients/(\d+)/edit' => 'ClientController@edit',
    ],
    'POST' => [
        'login' => 'AuthController@login',
        'clients' => 'ClientController@store',
        'clients/(\d+)/update' => 'ClientController@update',
        'clients/(\d+)/delete' => 'ClientController@destroy',
    ],
];

// Dispatcher
function dispatch(string $controllerAction, array $params = []): void
{
    list($controllerName, $actionName) = explode('@', $controllerAction);
    $controllerFile = BASE_PATH . '/app/controllers/' . $controllerName . '.php';

    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        if (class_exists($controllerName)) {
            $controller = new $controllerName();
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

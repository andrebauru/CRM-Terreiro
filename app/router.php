<?php

declare(strict_types=1);

define('IS_API_REQUEST', false); // Definir inicialmente como false

// Basic Router
// This router is intentionally simple and can be expanded later.

use App\Helpers\Session;
use App\Helpers\Logger; // Adicionado: Uso do Logger

// Obtém a URI da requisição
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Calcula o caminho base (subdiretório onde a aplicação está instalada)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = '';

if (strpos($scriptName, '/public/index.php') !== false) {
    $basePath = str_replace('/public/index.php', '', $scriptName);
} elseif (strpos($scriptName, '/index.php') !== false) {
    $basePath = dirname(dirname($scriptName));
    if ($basePath === '\\' || $basePath === '/') {
        $basePath = '';
    }
}

// Remove o caminho base da URI
if (!empty($basePath) && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Remove /public se presente
if (strpos($requestUri, '/public') === 0) {
    $requestUri = substr($requestUri, 7);
}

// Limpa a URI
$requestUri = trim($requestUri, '/');

// Verifica se é uma requisição de API
if (str_starts_with($requestUri, 'api/')) {
    define('IS_API_REQUEST', true); // Se a URI começar com 'api/', define como requisição de API
}

$requestMethod = $_SERVER['REQUEST_METHOD'];

// Handle _method spoofing for PUT/DELETE
if (isset($_POST['_method'])) {
    $requestMethod = strtoupper($_POST['_method']);
}

// Define routes
$routes = [
    'GET' => [
        '' => 'HomeController@index',
        'dashboard' => 'HomeController@dashboard',
        'dashboard/export/pdf' => 'ReportController@dashboardPdf',
        'dashboard/export/xls' => 'ReportController@dashboardXls',
        'login' => 'AuthController@showLoginForm',
        'logout' => 'AuthController@logout',
        'settings' => 'SettingsController@index',
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
        'users/(\d+)' => 'UserController@show',
        'users/(\d+)/edit' => 'UserController@edit',

        // API Routes
        'api/clients' => 'ClientController@apiIndex',
        'api/clients/(\d+)' => 'ClientController@apiShow',
        'api/logout' => 'AuthController@apiLogout',
    ],
    'POST' => [
        'login' => 'AuthController@login',
        'settings' => 'SettingsController@update',
        'clients' => 'ClientController@store',
        'services' => 'ServiceController@store',
        'jobs' => 'JobController@store',
        'jobs/(\d+)/notes' => 'JobController@addNote',
        'jobs/installments/(\d+)/pay' => 'JobController@payInstallment',
        'users' => 'UserController@store',

        // API Routes
        'api/clients' => 'ClientController@apiStore',
        'api/login' => 'AuthController@apiLogin',
    ],
    'PUT' => [
        'clients/(\d+)' => 'ClientController@update',
        'services/(\d+)' => 'ServiceController@update',
        'jobs/(\d+)' => 'JobController@update',
        'users/(\d+)' => 'UserController@update',

        // API Routes
        'api/clients/(\d+)' => 'ClientController@apiUpdate',
    ],
    'DELETE' => [
        'clients/(\d+)' => 'ClientController@destroy',
        'services/(\d+)' => 'ServiceController@destroy',
        'jobs/(\d+)' => 'JobController@destroy',
        'jobs/notes/(\d+)' => 'JobController@deleteNote',
        'jobs/attachments/(\d+)' => 'JobController@deleteAttachment',
        'users/(\d+)' => 'UserController@destroy',

        // API Routes
        'api/clients/(\d+)' => 'ClientController@apiDestroy',
    ]
];

// Dispatcher
function dispatch(string $controllerAction, array $params = [], string $requestUri = ''): void // Adicionado $requestUri para log
{
    list($controllerName, $actionName) = explode('@', $controllerAction);
    $controllerFile = BASE_PATH . '/app/Controllers/' . $controllerName . '.php';

    try {
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            $fullControllerName = 'App\\Controllers\\' . $controllerName;
            if (class_exists($fullControllerName)) {
                $controller = new $fullControllerName();
                if (method_exists($controller, $actionName)) {
                    call_user_func_array([$controller, $actionName], $params);
                    return;
                } else {
                    Logger::error("Método '$actionName' não encontrado no controlador '$controllerName' para URI: $requestUri");
                }
            } else {
                Logger::error("Classe controladora '$fullControllerName' não encontrada para URI: $requestUri");
            }
        } else {
            Logger::error("Arquivo do controlador '$controllerFile' não encontrado para URI: $requestUri");
        }
    } catch (\Throwable $e) {
        Logger::error("Exceção ao despachar rota para URI: $requestUri - " . $e->getMessage() . " em " . $e->getFile() . " na linha " . $e->getLine());
    }

    // Fallback for 404
    http_response_code(404);
    $notFoundView = BASE_PATH . '/app/views/errors/404.php';
    if (file_exists($notFoundView)) {
        require $notFoundView;
        return;
    }
    echo "404 Not Found - Controller or method not found.";
}

$routeFound = false;

// Check for exact matches first
if (isset($routes[$requestMethod])) {
    foreach ($routes[$requestMethod] as $route => $controllerAction) {
        if ($route === $requestUri) {
            dispatch($controllerAction, [], $requestUri); // Passa $requestUri para o dispatch
            $routeFound = true;
            break;
        }
    }
}

// If no exact match, check for dynamic routes with regex
if (!$routeFound && isset($routes[$requestMethod])) {
    foreach ($routes[$requestMethod] as $route => $controllerAction) {
        $pattern = '#^' . str_replace('/', '\/', $route) . '$#';
        if (preg_match($pattern, $requestUri, $matches)) {
            array_shift($matches);
            dispatch($controllerAction, $matches, $requestUri); // Passa $requestUri para o dispatch
            $routeFound = true;
            break;
        }
    }
}

if (!$routeFound) {
    http_response_code(404);
    Logger::warning("Rota não encontrada para método: '$requestMethod' e URI: '$requestUri'"); // Loga rota não encontrada
    $notFoundView = BASE_PATH . '/app/views/errors/404.php';
    if (file_exists($notFoundView)) {
        require $notFoundView;
    } else {
        echo "404 Not Found - No route matched for: " . htmlspecialchars($requestUri);
    }
}


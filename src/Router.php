<?php

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private string $prefix = '';

    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $previousPrefix = $this->prefix;
        $previousMiddleware = $this->middlewares;

        $this->prefix = $previousPrefix . $prefix;
        $this->middlewares = array_merge($previousMiddleware, $middleware);

        $callback($this);

        $this->prefix = $previousPrefix;
        $this->middlewares = $previousMiddleware;
    }

    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $fullPath = $this->prefix . $path;
        $this->routes[] = [
            'method'     => $method,
            'path'       => $fullPath,
            'handler'    => $handler,
            'middleware' => $this->middlewares,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = '/' . trim(parse_url($uri, PHP_URL_PATH) ?? '', '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            $pattern = $this->compilePattern($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // remove full match
                $params = array_values(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));

                // Run middleware
                foreach ($route['middleware'] as $mw) {
                    $mw();
                }

                // Call handler
                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $method] = $handler;
                    $controller = new $class();
                    echo $controller->$method(...$params);
                } else {
                    echo $handler(...$params);
                }
                return;
            }
        }

        http_response_code(404);
        echo self::renderError(404, 'Halaman tidak ditemukan');
    }

    private function compilePattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public static function renderError(int $code, string $message): string
    {
        $title = match ($code) {
            404 => '404 — Tidak Ditemukan',
            500 => '500 — Server Error',
            403 => '403 — Dilarang',
            default => 'Error',
        };

        return <<<HTML
        <!DOCTYPE html>
        <html lang="id" data-coreui-theme="light">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title}</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/coreui@5.4.1/dist/css/coreui.min.css">
        </head>
        <body class="bg-body-tertiary d-flex align-items-center justify-content-center" style="min-height:100vh">
            <div class="text-center px-3">
                <h1 class="display-1 fw-bold">{$code}</h1>
                <p class="text-medium-emphasis mb-4">{$message}</p>
                <a href="/" class="btn btn-outline-primary">&larr; Kembali ke Beranda</a>
            </div>
        </body>
        </html>
        HTML;
    }

    public static function redirect(string $url): never
    {
        if (function_exists('base_url')) {
            $url = base_url($url);
        }
        header('Location: ' . $url);
        exit;
    }

    public static function json(mixed $data, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}

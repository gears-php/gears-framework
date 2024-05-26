<?php

namespace Gears\Framework\Application\Routing;

use Gears\Storage\Storage;
use Symfony\Component\HttpFoundation\Request;

class Router
{
    /**
     * List of all available routes
     */
    private array $routes = [];

    /**
     * Build routes from given configuration
     */
    public function build(Storage $routes): void
    {
        foreach ($routes->raw() as $routeName => $route) {
            if (!isset($route['match'])) {
                throw new \InvalidArgumentException(sprintf('Route "%s" does not have the `match` pattern', $routeName));
            }

            if (!isset($route['to'])) {
                throw new \InvalidArgumentException(sprintf('Route "%s" does not have the `to` handler definition', $routeName));
            }

            $this->addRoute(new Route($routeName, $route['match'], $route['to']));
        }
    }

    /**
     * Build routes for given resource.
     *
     * @param string $resource Resource class name
     * @param string $endpoint Resource URL endpoint
     * @param string $handler Resource handler class definition
     * @param string $prefix URL prefix for all resource endpoints
     */
    public function buildResourceRoutes(string $resource, string $endpoint, string $handler, string $prefix = ''): void
    {
        $handlerMethods = [
            'list' => 'GET %s',
            'one' => 'GET %s/:id',
            'post' => 'POST %s',
            'put' => 'PUT %s/:id',
            'patch' => 'PATCH %s/:id',
            'delete' => 'DELETE %s/:id',
//                'OPTIONS',
//                'HEAD',
        ];

        foreach ($handlerMethods as $method => $matchPattern) {
            $this->addRoute(
                (new Route(
                    trim($endpoint, '/') . ':' . $method,
                    sprintf($matchPattern, rtrim($prefix, '/') . rtrim($endpoint, '/')),
                    ":$handler:$method"
                ))->setResource($resource)
            );
        }
    }


    /**
     * Add a single route
     */
    public function addRoute(Route $route): void
    {
        $this->routes[$route->getName()] = $route;
    }

    /**
     * Match the route pattern against the request uri and return the route
     */
    public function match(Request $request): ?Route
    {
        // sort routes from more specific to more `generic`
        uasort(
            $this->routes,
            function (Route $left, Route $right) {
                return $right->getMatchPattern() <=> $left->getMatchPattern();
            }
        );

        /** @var Route $route */
        foreach ($this->routes as $route) {
            // request method limitation
            if (!in_array($request->getMethod(), $route->getAllowedMethods())) {
                continue;
            }

            // replacing pattern placeholders with corresponding matching regexps
            $regexPattern = preg_replace($this->getPatterns(), $this->getMatchers(), $route->getMatchPattern());

            // try to match request path uri with current regex routing pattern
            if (!preg_match("#^$regexPattern(?:/)?$#", $request->getPathInfo(), $params)) {
                continue;
            }

            array_shift($params);

            // we have no placeholders values matched as for route parameters
            if (!count($params)) {
                return $route;
            }

            $placeholders = [];

            // find all possible placeholders inside the route rule
            foreach ($this->getResolvers() as $resolvePattern => $fn) {
                // match placeholders in the route
                preg_match_all($resolvePattern, $route->getMatchPattern(), $matchedPlaceholders);

                // remember matched placeholder names and their resolver functions
                foreach ($matchedPlaceholders[1] as $placeholder) {
                    // remember placeholder by its position inside the route
                    $placeholders[strpos($route->getMatchPattern(), $placeholder)]
                        = ['name' => $placeholder, 'fn' => $fn];
                }
            }

            // build route parameters
            if (count($placeholders) == count($params)) {
                // sort placeholders according to matched param values
                ksort($placeholders);
                $placeholders = array_values($placeholders);

                foreach ($placeholders as $k => $placeholder) {
                    $placeholder['fn']($route, $params[$k], $placeholder['name']);
                }
            }

            return $route;
        } // each route

        return null;
    }

    /**
     * Regexps to match route pattern placeholders
     */
    private function getPatterns(): array
    {
        return [
            # /content/:id
            '#/:\w+#',

            # /content*
            '/\*/',
        ];
    }

    /**
     * Regexps to be put instead of route pattern placeholder in order to match real url values
     */
    private function getMatchers(): array
    {
        return [
            # /content/(123)
            '/([\w-]+)',

            # /content([/]anything[/]else[/]here)
            '(.*?)',
        ];
    }

    /**
     * Callbacks for resolving matched route parameters
     */
    private function getResolvers(): array
    {
        return [
            # /:(id) => /123
            '#/:(\w+)#' => function (Route $route, $value, $name) {
                $route->setParam($name, $value);
            },

            # /(*) => /anything/else/here
            '#(\*)#' => function (Route $route, $value) {
                $parts = explode('/', $value);

                while (count($parts)) {
                    list($key, $value) = array_pad(array_splice($parts, 0, 2), 2, null);
                    $route->setParam($key, $value);
                }
            },
        ];
    }
}

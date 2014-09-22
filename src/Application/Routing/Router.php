<?php

namespace Gears\Framework\Application\Routing;

use Gears\Framework\Application\Request;

class Router
{
    /**
     * List of all available routes
     * @var array
     */
    protected $routes = [];

    /**
     * Add routes from given configuration
     * @param array $routes
     */
    public function addRoutingConfiguration(array $routes)
    {
        foreach ($routes as $routeName => $route) {
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
     * Add a single route
     * @param Route $route
     */
    public function addRoute(Route $route)
    {
        $this->routes[$route->getName()] = $route;
    }

    /**
     * Match the route pattern against the request uri and return the route
     * @param Request $request
     * @return Route|false
     */
    public function match(Request $request)
    {
        // sort routes from more specific to more `generic`
        uasort($this->routes, function (Route $left, Route $right) {
            return $left->getMatchPattern() < $right->getMatchPattern();
        });

        /** @var Route $route */
        foreach ($this->routes as $route) {
            // request method limitation
            if (!in_array($request->getHttpMethod(), $route->getHttpMethods())) continue;

            // replacing pattern placeholders with corresponding matching regexps
            $regexPattern = preg_replace($this->getPatterns(), $this->getMatchers(), $route->getMatchPattern());

            // try to match request path uri with current regex routing pattern
            // (query parameters are filtered out so not taken into account)
            if (preg_match('#^' . $regexPattern . '(?:/)?(?:\?.*)?$#', $request->getPathUri(), $params)) {
                array_shift($params);

                // we have placeholders values
                if (count($params)) {
                    $placeholders = [];

                    // find all possible placeholders inside the route rule
                    foreach ($this->getResolvers() as $resolvePattern => $fn) {
                        // match placeholders in the route
                        preg_match_all($resolvePattern, $route->getMatchPattern(), $matchedPlaceholders);

                        // remember matched placeholder names and their resolver functions
                        foreach ($matchedPlaceholders[1] as $placeholder) {
                            // remember placeholder by its position inside the route
                            $placeholders[strpos($route->getMatchPattern(), $placeholder)] = ['name' => $placeholder, 'fn' => $fn];
                        }
                    }

                    // build route parameters
                    if (count($placeholders) == count($params)) {
                        // sort placeholders according to matched param values
                        ksort($placeholders);
                        $placeholders = array_values($placeholders);

                        foreach ($placeholders as $k => $placeholder) {
                            $placeholder['fn']($placeholder['name'], $params[$k], $route);
                        }
                    }
                }

                return $route;
            } // route was matched
        } // each route

        return false;
    }

    /**
     * Regexps to match route pattern placeholders
     * @return array
     */
    protected function getPatterns()
    {
        return [
            # /content/:id
            '#/:\w+#',

            # /content*
            '/\*/'
        ];
    }

    /**
     * Regexps to be put instead of route pattern placeholder in order to match
     * real url values
     * @return array
     */
    protected function getMatchers()
    {
        return [
            # /content/(123)
            '/([\w-]+)',

            # /content([/]anything[/]else[/]here)
            '(.*?)'
        ];
    }

    /**
     * Callbacks for resolving matched route parameters
     * @return array
     */
    protected function getResolvers()
    {
        return [
            # /:(id) => /123
            '#/:(\w+)#' => function ($name, $value, Route $route) {
                    $route->addParam($name, $value);
                },

            # /(*) => /anything/else/here
            '#(\*)#' => function () {
                }
        ];
    }
}

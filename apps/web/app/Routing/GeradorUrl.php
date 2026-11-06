<?php
namespace App\Routing;

/** Templates de parâmetros ficam no servidor até a emissão do transporte UR4. */
class GeradorUrl extends \Illuminate\Routing\UrlGenerator
{
    public function toRoute($route, $parameters, $absolute)
    {
        if ($meta = $route->getAction('ur4')) {
            $route = clone $route;
            $route->setUri($meta['original']);
        }
        return parent::toRoute($route, $parameters, $absolute);
    }
}

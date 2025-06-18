<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->group('api', function ($routes) {
    $routes->post('coasters', 'Api\Coasters::create');
    $routes->get('coasters', 'Api\Coasters::index');
    $routes->put('coasters/(:segment)', 'Api\Coasters::update/$1');

    $routes->post('coasters/(:segment)/wagons', 'Api\Coasters::addWagon/$1');
    $routes->delete('coasters/(:segment)/wagons/(:segment)', 'Api\Coasters::deleteWagon/$1/$2');
    $routes->get('api/coasters/(:segment)/status', 'Api\Coasters::status/$1');

});


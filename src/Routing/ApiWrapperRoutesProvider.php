<?php

namespace Drupal\api_wrapper\Routing;

use Drupal\api_wrapper\Attribute\ApiWrap;
use Drupal\api_wrapper\Attribute\Endpoint;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Define dynamic routes for the API.
 */
class ApiWrapperRoutesProvider {

  /**
   * Provide dynamic routes.
   *
   * @throws \Doctrine\Common\Annotations\AnnotationException|\ReflectionException
   *
   * @return RouteCollection
   *            An array of routes
   */
  public static function routes() {
    // Get all registered services (from service container)
    $container = \Drupal::getContainer();
    $kernel = $container->get('kernel');
    $services = $kernel->getCachedContainerDefinition()['services'];
    // Create a new RouteCollection
    $routeCollection = new RouteCollection();

    // Unserialize services and for each get the class to build a reflection object
    // from it and check if it has the #[ApiWrap] class attribute.
    foreach ($services as $id => $service) {
      // Unserialize
      $unService = unserialize($service);

      if (\array_key_exists('class', $unService)) {
        $refClass = new \ReflectionClass($unService['class']);

        // Check if the class has the #[ApiWrap] class attribute
        if ($apiWrappAttr = $refClass->getAttributes(ApiWrap::class)) {
          // Get endpoint base path.
          $basePath = $apiWrappAttr[0]->getArguments()['basePath'];
          $mappedMethods = [];

          // Get all methods from ReflectionClass
          $refMethods = $refClass->getMethods();

          // Get all methods with #[Endpoint]
          foreach ($refMethods as $refMethod) {
            // Get all #[Endpoint] attributes
            $endpointAttributes = $refMethod->getAttributes(Endpoint::class);

            // Loop through eventually multiple #[Endpoint] attributes for the same method
            foreach ($endpointAttributes as $endpointAttribute) {
              // Get arguments
              $endpointArgs = $endpointAttribute->getArguments();

              // Collect all method information in an array for later use
              $tempMappedMethod = [
                'endpoint' => $endpointArgs['path'],
                'method' => $refMethod->name,
                'requestMethod' => $endpointArgs['method'],
                'methodParameters' => array_map(static function ($parameter) {
                  // Check if $parameter->getType() also has a name and then access it.
                  $parameterType = $parameter->getType();
                  $parameterType = $parameterType instanceof \ReflectionNamedType ? $parameterType->getName() : 'string';

                  if ($parameterType !== Request::class) {
                    return $parameter->name;
                  }

                  return FALSE;
                }, $refMethod->getParameters()),
              ];

              // Get method parameters' default values
              $tempMappedMethod['methodParamDefValue'] = array_combine(
                array_values($tempMappedMethod['methodParameters']),
                array_map(static function ($parameter) {
                  return $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : NULL;
                }, $refMethod->getParameters())
              );

              $mappedMethods[] = $tempMappedMethod;
            }
          }

          // Loop through all mapped methods
          foreach ($mappedMethods as $mappedMethod) {
            $exceptFalseParams = array_filter($mappedMethod['methodParameters'], static function ($param) { return $param !== FALSE; });
            $pathParams = array_map(static function ($param) { return '{' . $param . '}'; }, $exceptFalseParams);
            // Put method parameters in $path variable.
            $path = ('/' . $basePath . '/') . $mappedMethod['endpoint'] . (empty($pathParams) ? '' : '/' . implode('/', $pathParams));

            // Put method parameters in $defaults variable.
            $defaults = [
              '_controller' => $id . ':' . $mappedMethod['method'],
              '_title' => $mappedMethod['method'],
            ];

            // Put method parameters in $defaults variable.
            foreach ($mappedMethod['methodParamDefValue'] as $key => $value) {
              if ($value === NULL) {
                continue;
              }
              $defaults[$key] = $value;
            }
            // Put method parameters in $requirements variable.
            $requirements = ['_permission' => 'access content'];

            // Create route object and add it to the collection.
            $route = new Route($path, $defaults, $requirements, [], '', [], [$mappedMethod['requestMethod']]);
            $routeCollection->add($basePath . '__' . $mappedMethod['endpoint'] . '.' . $mappedMethod['method'], $route);
          }
        }
      }
    }

    return $routeCollection;
  }

}

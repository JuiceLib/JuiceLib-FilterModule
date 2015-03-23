<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Yoel Nunez <dev@yoelnunez.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 */

namespace JuiceLib\FilterModule\Filter;

use JuiceLib\FilterModule\Exception\FilterException;
use Zend\Mvc\MvcEvent;

class FilterListener
{
  public static function route(MvcEvent $e)
  {
    $app = $e->getApplication();
    $sm = $app->getServiceManager();
    $config = $sm->get('Config');

    if (isset($config['juice'], $config['juice']['filter'], $config['juice']['filter_mapping'])) {

      $filters = $config['juice']['filter'];
      $mapping = $config['juice']['filter_mapping'];

      if (!is_array($filters)) {
        throw new FilterException("Invalid filters type. juice[filter] must be an array.");
      } else if (!is_array($mapping)) {
        throw new FilterException("Invalid mapping type. juice[filter_mapping] must be an array.");
      } else if (count($filters) != count($mapping)) {
        throw new FilterException("Mismatch in number of filters and filter mappings.");
      }

      foreach (array_keys($filters) as $filter) {
        if (!isset($mapping[$filter])) {
          throw new FilterException(sprintf("Missing mapping to filter for \"%s\"", $filter));
        }
      }

      /** @var \Zend\Http\PhpEnvironment\Request $request */
      $request = $e->getRequest();

      /** @var \Zend\Http\PhpEnvironment\Response $response */
      $response = $e->getResponse();

      $requestPath = $request->getUri()->getPath();

      foreach ($filters as $filter => $paths) {
        foreach ($paths as $url) {
          if (preg_match('(^' . $url . '$)', $requestPath)) {

            $filterObject = $mapping[$filter];

            if (!($filterObject instanceof FilterInterface) && is_string($filterObject)) {
              $filterObject = new $filterObject();

              if (!($filterObject instanceof FilterInterface)) {
                throw new FilterException(sprintf("Filter \"%s\" must be of type JuiceFilter\\Filter\\FilterInterface", $filter));
              }

              $mapping[$filter] = $filterObject;
            }

            if ($filterObject->processFilter($request, $response, $app)) {
              return $response;
            }

          }
        }
      }
    }
  }
}

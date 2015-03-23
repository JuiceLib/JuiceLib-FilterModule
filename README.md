# JuiceLib-FilterModule

Intercept requests/responses at the `route`/`MvcEvent::EVENT_ROUTE` event and transform or use the request or response.

Usage
-----
Include the `JuiceLib\FilterModule` in `application.config.php`

    'modules' => array(
        'JuiceLib\FilterModule',
        // your other modules
    ),

Create a `juice.global.php` file inside `config/autoload`. Alternatively you could add the filter config in any of your module's `module.config.php`

    <?php
    // juice.global.php
    return array(
        'juice' => array(
            'filter' => array(
                'FilterProtection' => array(
                    '/protected/(.*)',
                ),
            ),
            'filter_mapping' => array(
                'FilterProtection' => 'Protected\Filter\Namespace\MyFilter',
            ),
        ),
    );

Create your filter class, in this case I'll call it `MyFilter.php` this has to match the filter mapping in the configuration.

    <?php

    namespace Protected\Filter\Namespace;

    use JuiceLib\FilterModule\Filter\FilterInterface;
    use Zend\Http\Request as HttpRequest;
    use Zend\Http\Response as HttpResponse;
    use Zend\Mvc\Application;

    class MyFilter implements FilterInterface {

        public function processFilter(HttpRequest &$request, HttpResponse &$response, Application &$application)
        {
          /** @var \Zend\Http\Header\HeaderInterface $authKeyHeader */
          $authKeyHeader = $request->getHeader('my-auth-key');

          if (!$authKeyHeader || $authKeyHeader->getFieldValue() != 'secret-password') {
              $response->setStatusCode(401);

              return $response;
          }
        }
    }

In the previous example we are protected any path that starts with `/protected/` by checking the `my-auth-key` header. This can also be used with sessions or other methods of authentication.

Additionally this can be used to add you own response headers for each response sent from the application that matches the filter's path.

# Expressive-Route-OpenAPI
Generate OpenAPI doc skeleton from expressive routes.

`RouteOpenApiDoc` uses application's router configuration to generate OpenAPI skeleton.
The skeleton includes placeholders to write description for paths and their parameters,
and references to schemas for REST style resources that are inferred from path segments.
Besides being displayed in viewers like [swagger-ui](https://swagger.io/tools/swagger-ui/), 
these schemas can be used for validating`requestBody` in your middleware pipeline.

Other API documentation libraries focus on parsing annotations. While docblock annotations 
may be useful for MVC-style frameworks, where routing table and Controller/Actions
are in separate locations making the connection between the two difficult to keep track of 
without extra hints, middleware-style frameworks, especially the [PSR-15](https://www.php-fig.org/psr/psr-15/) compatible ones
have the routing and handlers in one place, making extra docblocks for API documentation
generators to parse redundant. Or, they try to generate code based on existing API specification, 
which behaves too CRUD-y, and may not be compatible with existing codebase.

## Usage
In your router configuration function, get an instance of `OpenApiWriter` service. 
Add instance of `Application` or `RouteCollector` (if defining routes in multiple configurations 
using [path segregated piplines](https://docs.zendframework.com/zend-expressive/v3/cookbook/path-segregated-routing/),
that has the routes needing to be documented, and call `writeSpec`.

```php
use RouteApiDoc\RouterStrategy\RouterStrategyInterface;

return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container) : void {
    // your API resources and handlers
    $app->get('/api/resources/:resource_id', []);
    $app->post('/api/resources', []);
    
    $apiWriter = $container->get(OpenApiWriter::class);
    $appWriter->addApplication($app);
    $apiWriter->writeSpec($app);
}
```

This will produce a json file `api_doc.json` in the output directory configured as described next.

If you made changes to the generated file, as you most likely will since this is just a skeleton,
and rerun the writer, changes will be merged.

By default, each guessed resource will be written to its own json schema file. 
If prefer to have all schemas as part of one single document, pass `true` as a parameter
of `writeSpec()`.

> Since the generation of documents is a development task, and will slow down requests,
> it is recommended to have the above lines conditional only for development mode.


#### Base path

If you are adding multiple route containers using `$apiWriter->addRouteCollector()` or `$apiWriter->addApplication()`,
chances are you are using _path segregated pipelines_ technique to organize your routes. In this case, 
you will need to let the api writer know about the pipeline basepath because is not exposed by expressive to the route 
collection. Specify it as a second parameter of the `add` functions. Base path will also become a tag for all the routes
starting from that path.

## Configuration
Configuration is read from `openapi_writer` key of your application config. It is expected
to have these options:
```
[
    'router_strategy' => RouteApiDoc\RouterStrategy\ZendRouterStrategy::class
    'output_directory' => __DIR__ . '/spec'
]
```

- `router_strategy` - name of strategy that knows how to interpret the syntax of your chosen 
route matching library. (So far only Zend Router is supported. FastRoute will be coming next)
- `output_directory` - directory path where schema files will be written


# API Wrapper Module Documentation

The `api_wrapper` module defines two attributes, `#[ApiWrap]` and `#[Endpoint]`, which, when applied to an existing service, allow for the automatic definition of dynamic routes based on the parameters assigned to the two attributes.

## Usage Example

Consider the following code, which could be that of a service making calls to a fictional soccer API:

```php
#[ApiWrap(basePath: 'soccer-api')]
class SoccerApi {
    // ....

    #[Endpoint(method: 'GET', path: 'favourite-team', label: 'Return favourite team based on the given State')]
    public function getBestTeam(string $state = 'Italy') {
        $response = $this->httpClient->get($fakeApiEndpoint . '/fake-call/get-favourite-team/' . $state);
        // Handle $response...

        return new JsonResponse($response);
    }
    // ..
}
```

The module will scan all services marked with the `#[ApiWrap]` attribute, using the value expressed for basePath as the base endpoint.
Once all services to be wrapped are identified, it will proceed to scan the methods that have been defined as `#[Endpoint]`. For each of these, it will generate Route objects, collect them in a RouteCollection, and then register them in the Drupal routes registry.

In the case of the example, a single dynamic route will be generated, or rather two. This is because the getBestTeam method accepts a parameter with a default value. In this case, it will be possible to call the route in two ways:

- `GET /soccer-api/favourite-team`: which for Drupal will simply be a call to getBestTeam('Italy').
- `GET /soccer-api/favourite-team/{state}`: in this case, the value of the $state parameter in the URL will vary.

**Attributes**
- `ApiWrap`
  - `basePath`: `<string>`
- `Endpoint`
  - `method`: `GET`|`POST`|...
  - `path`: `<string>`
  - `label`: `<string>`
  - `description`: `<string>`

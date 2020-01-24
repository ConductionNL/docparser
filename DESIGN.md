# Design decisions for API Documentation parser
This component parses OAS documentation files and checks it for a number of properties to test compliance to the NL API Strategy (Nederlandse API strategie/Dutch API Strategy)
and the existence of NLX headers.

## Documents

- [NL API Strategie](https://docs.geostandaarden.nl/api/vv-hr-API-Strategie-20200117/) (Dutch) 
- [NL API Strategie design rules](https://docs.geostandaarden.nl/api/vv-st-API-Designrules-20200117/)
- [NL API Strategie design rules extensions](https://docs.geostandaarden.nl/api/cv-hr-API-Strategie-ext-20200117/)
- [NLX Documentation](https://docs.nlx.io/reference-information/transaction-log-headers/)

This version of the document is based on the proposed version of the NL API strategy of January 17 2020.

## Checks implemented in this version

### NL API Strategy design rules:

- API-03: Only apply default HTTP operations
    - We check if the OAS documentation mention other HTTP operations than `GET`, `POST`, `PUT`, `PATCH` or `DELETE`
        - Level: 1
- API-09: Implement custom representation if supported
    - We check if the parameter `fields` is accepted
        - Level: 1
- API-13: Accept tokens as HTTP headers only
    - We check if there is a parameter `Authorization` that is only available as header
        - Level: 2
- API-16: Use OAS 3.0 for documentation
    - We check if the `openapi`-parameter of the documentation page has a value with first digit greater then 3
        - Level: 1
- API-22: JSON first - APIs receive and send JSON
    - Check if the first (default) media type of response contents is a JSON media type
        - Level: 2
- API-24: Support content negotiation
    - We check if multiple content types are defined
        - Level: 2
- API-25: Check the Content-Type header settings
    - This is tested implicitly by testing API-24
        - Level: 2
- API-26: Define field names in in camelCase
    - We check if the properties are alphabetic, and if the first character is not capitalised
        - Level: 2
- API-29: Support JSON-encoded POST, PUT, and PATCH payloads
    - This is tested implicitly by testing API-22
        - Level: 2
- API-42: Use JSON+HAL with media type `application/hal+json` for pagination
    - We check if the content type `application/hal+json` is available for response content
        - Level: 2
- API-45: Provide rate limiting information
    - We check if the headers `X-Rate-Limit-Limit`, `X-Rate-Limit-Remaining`, `X-Rate-Limit-Reset` headers are defined for response content. This also implicitly checks API-44: Apply rate limiting
        - Level: 2
- API-46: Use default error handling
    - We check if not HTTP statuses outside the regular 200, 300, 400 or 500 ranges are supported
        - Level: 2
- API-47: Use the required HTTP status codes
    - We check if the HTTP status codes 200, 201, 204, 304, 400, 401,403, 404, 405, 406, 409, 410, 415, 422, 429 500 an 503 are supported.
        - Level: 2
- API-48: Leave off trailing slashes from API endpoints
    - We check the endpoints for trailing slashes
        - Level: 1
- API-50: Use CORS to control access
    - We check if the header `Access-Control-Allow-Origin` is available and not wildcarded
        - Level: 2
- Time Travelling
    - We check if the request parameters `geldigOp` or `validOn`, `inWerkingOp` or `validFrom` and `beschikbaarOp` or `availableFrom` are accepted
        - Level: 2

### NLX Headers

We check the NLX compliance by checking which headers are accepted.

Headers that should be accepted:
- `X-NLX-Logrecord-ID`
- `X-NLX-Request-Process-Id`
- `X-NLX-Request-Data-Elements`
- `X-NLX-Request-Data-Subject`

Headers that should ***not*** be accepted:
- `X-NLX-Requester-User-Id`
- `X-NLX-Request-Application-Id`
- `X-NLX-Request-Subject-Identifier`
- `X-NLX-Requester-Claims`
- `X-NLX-Request-User`

## Checks not implemented in this version
See also our [Roadmap](ROADMAP.md)
- API-06: Create relations of nested resources within the endpoint
- API-10: Implement operations that do not fit the CRUD model as sub-resources
- API-21: Inform users of a deprecated API actively
    -   We check if the API can return `Warning`-headers
        - Level: 2
- API-30: Use query parameters corresponding to the queryable fields
- API-31: Use the query parameter `sorteer` to sort
- API-32: Use the query parameter `zoek` for full-text search
- API-43: Apply caching to improve performance


## Design rules we can not check
There is a number of design rules for the NL API Strategie that we can not check.

#### Design rules that can not be tested because they require calls to the API
- API-01: Operations are Safe and/or Idempotent
- API-12: Allow access to an API only if an API key is provided
- API-20: Include the major version number only in ihe URI
- API-23: APIs may provide a JSON Schema
- API-27: Disable pretty print
- API-28: Send a JSON-response without enclosing envelope
- API-33: Support both * and ? wildcard characters for full-text search APIs
- API-51: Operations are Safe and/or Idempotent


#### Design rules that can not be tested because they require natural language processing
- API-04: Define interfaces in Dutch unless there is an official English glossary
- API-05: Use plural nouns to indicate resources
- API-17: Publish documentation in Dutch unless there is existing documentation in English or there is an official English glossary available

#### Design rules that can not be tested because we do not have access to the server or intentions of the developer
- API-02: Do not maintain state information at the server
- API-11: Encrypt connections using at least TLS v1.3
- API-18: Include a deprecation schedule when publishing API changes
- API-19: Allow for a maximum 1 year transition period to a new API version
- API-49: Use public API-keys

#### Design rules that can not be tested because they apply to very specific APIs in specific entities or properties:
- API-34: Support GeoJSON for GEO APIs
- API-35: Include GeoJSON as part of the embedded resource in the JSON response
- API-36: Provide a POST endpoint for GEO queries
- API-37: Support mixed queries at POST endpoints
- API-38: Put results of a global spatial query in the relevant geometric context
- API-39: Use ETRS89 as the preferred coordinate reference system (CRS)
- API-40: Pass the coordinate reference system (CRS) of the request and the response in the headers
- API-41: Use content negotiation to serve different CRSs

#### Other design rules that can not be checked because they are not documented in the OAS-documentation
- API-14: Use OAuth 2.0 for authorisation
- API-15: Use PKIoverheid certificates for access-restricted or purpose-limited API authentication
- API-52: API-52: Use OAuth 2.0 for authorisation with rights delegation

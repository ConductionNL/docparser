# Roadmap

## Checks
At the moment a number of checks has not been implemented yet, because of technical complexity or because of ongoing discussion.

The design rules for which checks have not been implemented yet, but are planned are:
- API-06: Create relations of nested resources within the endpoint
- API-10: Implement operations that do not fit the CRUD model as sub-resources
- API-21: Inform users of a deprecated API actively
    -   We check if the API can return `Warning`-headers
        - Level: 2
- API-30: Use query parameters corresponding to the queryable fields
- API-43: Apply caching to improve performance
- API-45: Provide rate limiting information
    - We check if the headers `X-Rate-Limit-Limit`, `X-Rate-Limit-Remaining`, `X-Rate-Limit-Reset` headers are defined for response content. This also implicitly checks API-44: Apply rate limiting
        - Level: 2
- API-46: Use default error handling
    - We check if not HTTP statuses outside the regular 200, 300, 400 or 500 ranges are supported
        - Level: 2
- API-47: Use the required HTTP status codes
    - We check if the HTTP status codes 200, 201, 204, 304, 400, 401, 403, 404, 405, 406, 409, 410, 415, 422, 429 500 an 503 are supported.
        - Level: 2
        
API 46 and 47 are not implemented yet as not all status codes are appropriate for each method in each endpoint. Most status codes can be checked,
but a number of status codes might not be mentioned in the OpenAPI documentation. Therefore, this check is postponed to give some time to research this.

## Additional Features
At this moment we only accept APIdocs send as request body. 
In a further iteration we can also implement a route to check APIdocs using an URL of the APIdocs and retrieve the documentation from that URL

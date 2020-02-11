<?php


namespace App\Service;


class ApiDocService
{
    private function array_flatten(array $array)
    {
        $response = [];
        foreach ($array as $arr) {
            $response = array_merge($response, $arr);
        }
        return $response;
    }

    private function getPathParameters(array $methods)
    {
        $response = [];
        foreach ($methods as $key=>$method){
            if($key != 'parameters' && array_key_exists('parameters', $method))
                array_push($response, $method['parameters']);
        }
        return $this->array_flatten($response);
    }

    private function getSchema($path, $oas):?array{
        if(key_exists('post', $path)){
            $requestBody = $path['post']['requestBody'];
        }
        if(!isset($requestBody)) {
            $statuses = $path[array_key_first($path)]['responses'];
            foreach ($statuses as $key=>$status) {
                //echo $key;
                if(key_exists('content', $status)) {
                    $keys = array_keys($status['content']);
                    if (!empty($status['content']) && key_exists('schema', $status['content'][$keys[0]])) {

                        if (key_exists('type', $status['content'][$keys[0]]['schema']))
                            return $status['content'][$keys[0]]['schema'];

                        elseif (key_exists('$ref', $status['content'][$keys[0]]['schema']))
                            $ref = explode('/', $status['content'][$keys[0]]['schema']['$ref']);
                        else {
                            $ref = explode('/', $status['content'][$keys[0]]['schema']['items']['$ref']);
                        }
                    }
                }
            }
        }
        else{
            if(array_key_exists('content', $requestBody)) {
                $keys = array_keys($requestBody['content']);
                if (!empty($requestBody['content']) && key_exists('schema', $requestBody['content'][$keys[0]])) {
                    if (key_exists('type', $requestBody['content'][$keys[0]]['schema']))
                        return $requestBody['content'][$keys[0]]['schema'];
                    else
                        $ref = explode('/', $requestBody['content'][$keys[0]]['schema']['$ref']);
                }
            }
        }
        if(!isset($ref))
            return null;
        if ($ref[0] != '#')
            return null;
        array_shift($ref);
        $bag = $oas;
        foreach ($ref as $search) {
            $bag = $bag[$search];
        }
        return $bag;
    }
    private function getContentTypesPerStatus($statuses)
    {
        $response = ['API-22' => 'ok', 'API-24' => 'ok', 'API-42' => 'ok'];
        foreach ($statuses as $status) {

            if (key_exists('content', $status)) {
                //var_dump(array_keys($status['content'])[0]);
                $keys = array_keys($status['content']);
                if (strpos($keys[0], 'json') === false)
                    $response['API-22'] = 'warning';
                if (count($keys) <= 1)
                    $response['API-24'] = 'warning';
                if (!in_array('application/hal+json', $keys))
                    $response['API-42'] = 'warning';

            }
        }
        return $response;
    }

    private function checkContentTypes(array $path)
    {
        $response = ['API-22' => 'ok', 'API-24' => 'ok', 'API-42' => 'ok', 'schema'=>''];
        foreach ($path as $method) {
            if (
                key_exists('requestBody', $method) &&
                key_exists('content', $method['requestBody'])) {
                $keys = array_keys($method['requestBody']['content']);
                if (strpos($keys[0], 'json') === false)
                    $response['API-22'] = 'warning';
                if (count($keys) <= 1)
                    $response['API-24'] = 'warning';

            }
            if (key_exists('responses', $method)) {
                $responseResponse = $this->getContentTypesPerStatus($method['responses']);
                if ($responseResponse['API-22'] != 'ok')
                    $response['API-22'] = $responseResponse['API-22'];
                if ($responseResponse['API-24'] != 'ok')
                    $response['API-24'] = $responseResponse['API-24'];
                if ($responseResponse['API-42'] != 'ok')
                    $response['API-42'] = $responseResponse['API-42'];
                if(key_exists('schema', $responseResponse))
                    $response['schema'] = $responseResponse['schema'];
            }
        }
        return $response;
    }
    private function checkReferencedParameterForValue($parameter, $oas, ?string $value, ?array $values, string $in = 'query')
    {
        if(key_exists('$ref', $parameter) && substr($parameter['$ref'], 0,1) == '#') {
            $path = explode('/', $parameter['$ref']);
            //var_dump($path);
            array_shift($path);
            //var_dump($path);
            $bag = $oas;
            foreach ($path as $search) {
                $bag = $bag[$search];
            }
            if($value != null && substr($bag['name'],0,strlen($value)) === $value && $bag['in'] == $in)
                return 'ok';
            elseif($values == null && !empty($values) && in_array($bag['name'], $values))
                return 'ok';
        }
        elseif (key_exists('$ref', $parameter) && substr($parameter['$ref'], 0,1) != '#')
            return 'warning';
        return 'danger';
    }
    private function checkAuthorizationHeader(array $parameters, $oas)
    {
        foreach ($parameters as $parameter) {
            if (key_exists('name', $parameter) && ($parameter['name'] == 'fields[]' && $parameter['in'] == 'header'))
                return 'ok';
            elseif (!key_exists('name', $parameter)) {
                switch ($this->checkReferencedParameterForValue($parameter, $oas, 'fields[]', null, 'header')) {
                    case 'ok':
                        return 'ok';
                        break;
                    case 'warning':
                        $response = 'warning: cannot parse external files';
                        break;
                }
            }

        }
        if(!isset($response))
            $response = 'warning';
        return $response;
    }
    private function checkParametersForFields(array $parameters, $oas)
    {
        //var_dump($parameters);
        foreach ($parameters as $parameter) {
            if (key_exists('name', $parameter) && ($parameter['name'] == 'fields[]' && $parameter['in'] == 'query'))
                return 'ok';
            elseif (!key_exists('name', $parameter)) {
                switch ($this->checkReferencedParameterForValue($parameter, $oas, 'fields[]', null)) {
                    case 'ok':
                        return 'ok';
                        break;
                    case 'warning':
                        $response = 'warning: cannot parse external files';
                        break;
                }
            }

        }
        if(!isset($response))
            $response = 'danger';
        return $response;
    }
    private function checkParametersForSearch(array $parameters, $oas)
    {
        //var_dump($parameters);
        foreach ($parameters as $parameter) {
            if (key_exists('name', $parameter) && ($parameter['name'] == 'search' || $parameter['name'] == 'zoek') && $parameter['in'] == 'query')
                return 'ok';
            elseif (!key_exists('name', $parameter)) {
                switch ($this->checkReferencedParameterForValue($parameter, $oas, null, ['zoek', 'search'])) {
                    case 'ok':
                        return 'ok';
                        break;
                    case 'warning':
                        $response = 'warning: cannot parse external files';
                        break;
                }
            }
        }
        if(!isset($response))
            $response = 'warning';
        return $response;
    }
    private function checkParametersForSort(array $parameters, $oas)
    {
        //var_dump($parameters);
        foreach ($parameters as $parameter) {
            if (key_exists('name', $parameter) && (substr($parameter['name'],0,strlen('order')) === 'order' || substr($parameter['name'],0,strlen('sorteer')) === 'sorteer') && $parameter['in'] == 'query')
                return 'ok';
            elseif (!key_exists('name', $parameter)) {
                switch ($this->checkReferencedParameterForValue($parameter, $oas, null, ['order', 'sorteer'])) {
                    case 'ok':
                        return 'ok';
                        break;
                    case 'warning':
                        $response = 'warning: cannot parse external files';
                        break;
                }
            }
        }
        if(!isset($response))
            $response = 'warning';
        return $response;
    }

    private function checkDefaultMethods(array $path)
    {
        $methods = array_keys($path);
        foreach ($methods as $method) {
            switch ($method) {
                case 'post':
                case 'get':
                case 'put':
                case 'patch':
                case 'delete':
                    break;
                default:
                    return 'danger';
            }
            return 'ok';
        }
    }

    private function checkOpenApiVersion(array $oas)
    {
        if ((int)substr($oas['openapi'], 0, 1) >= 3)
            return 'ok';
        return 'danger';
    }
    private function checkPropertyName(string $name)
    {
        if(ctype_alpha($name) && !ctype_upper($name[0]))
            return 'ok';
        return 'warning';
    }
    private function checkEndpoint($endpoint){
        if(substr($endpoint, -1) == '/')
            return 'danger';
        return 'ok';
    }
    private function checkSchema(array $schema)
    {
        if(key_exists('properties', $schema)) {
            $response = [];

            foreach ($schema['properties'] as $key => $property) {
                $response[$key]['API-26: camel case'] = $this->checkPropertyName($key);
            }
            return $response;
        }
        return 'ok';
    }

    public function checkNLX(array $parameters, $oas):array
    {
        $response = [
            //Headers that should be present
            'Expected headers'=>[
            'X-NLX-Logrecord-ID'=>'warning',
            'X-NLX-Request-Process-Id'=>'warning',
            'X-NLX-Request-Data-Elements'=>'warning',
            'X-NLX-Request-Data-Subject'=>'warning',
                ],
            //Headers that should not be present
            'Unexpected headers'=>[
            'X-NLX-Requester-User-Id'=>'ok',
            'X-NLX-Request-Application-Id'=>'ok',
            'X-NLX-Request-Subject-Identifier'=>'ok',
            'X-NLX-Requester-Claims'=>'ok',
            'X-NLX-Request-User'=>'ok',
                ],
            ];

        foreach($parameters as $parameter){
            if(key_exists('name', $parameter)) {
                switch ($parameter['name']) {
                    case 'X-NLX-Logrecord-ID':
                        if ($parameter['in'] == 'header')
                            $response['Expected headers']['X-NLX-Logrecord-ID'] = 'ok';
                        break;
                    case 'X-NLX-Request-Process-Id':
                        if ($parameter['in'] == 'header')
                            $response['Expected headers']['X-NLX-Request-Process-Id'] = 'ok';
                        break;
                    case 'X-NLX-Request-Data-Elements':
                        if ($parameter['in'] == 'header')
                            $response['Expected headers']['X-NLX-Request-Data-Elements'] = 'ok';
                        break;
                    case 'X-NLX-Request-Data-Subject':
                        if ($parameter['in'] == 'header')
                            $response['Expected headers']['X-NLX-Request-Data-Subject'] = 'ok';
                        break;
                    case 'X-NLX-Requester-User-Id':
                        $response['Unexpected headers']['X-NLX-Requester-User-Id'] = 'warning';
                        break;
                    case 'X-NLX-Request-Application-Id':
                        $response['Unexpected headers']['X-NLX-Request-Application-Id'] = 'warning';
                        break;
                    case 'X-NLX-Request-Subject-Identifier':
                        $response['Unexpected headers']['X-NLX-Request-Subject-Identifier'] = 'warning';
                        break;
                    case 'X-NLX-Requester-Claims':
                        $response['Unexpected headers']['X-NLX-Requester-Claims'] = 'warning';
                        break;
                    case 'X-NLX-Request-User':
                        $response['Unexpected headers']['X-NLX-Request-User'] = 'warning';
                        break;
                    default:
                        break;
                }
            }
            elseif(key_exists('$ref', $parameter)){
                foreach($response['Expected headers'] as $key=>$value) {
                    $searchResponse = $this->checkReferencedParameterForValue($parameter, $oas, $key, null, 'header');
                    if($searchResponse == 'danger')
                        $response['Expected headers'][$key] = 'warning';
                    else
                        $response['Expected headers'][$key] = $searchResponse;
                }
                foreach($response['Unexpected headers'] as $key=>$value) {
                    $searchResponse = $this->checkReferencedParameterForValue($parameter, $oas, $key, null, 'header');
                    if($searchResponse == 'danger')
                        $response['Unexpected headers'][$key] = 'ok';
                    elseif($searchResponse == 'ok')
                        $response['Unexpected headers'][$key] = 'warning';
                    else
                        $response['Unexpected headers'][$key] = $searchResponse;
                }
            }
        }

        return $response;
    }
    public function checkTimeTravel(array $parameters, $oas)
    {
        $response = [
            'geldigOp'=>'warning',
            'inwerkingOp'=>'warning',
            'beschikbaarOp'=>'warning',
            ];

        foreach($parameters as $parameter) {
            if (key_exists('name', $parameter)) {
                if (($parameter['name'] == 'geldigOp' || $parameter['name'] == 'validOn'))
                    $response['geldigOp'] = 'ok';

                if ($parameter['name'] == 'inWerkingOp' || $parameter['name'] == 'validFrom')
                    $response['inwerkingOp'] = 'ok';
                if ($parameter['name'] == 'beschikbaarOp' || $parameter['name'] == 'availableFrom')
                    $response['beschikbaarOp'] = 'ok';
            }
            elseif(!key_exists('name', $parameter) && key_exists('$ref', $parameter)) {
                $searchResponse = $this->checkReferencedParameterForValue($parameter, $oas, null, ['geldigOp', 'validOn']);
                if($searchResponse != 'danger')
                    $response['geldigOp'] = $searchResponse;
                else
                    $response['geldigOp'] = 'warning';
                $searchResponse = $this->checkReferencedParameterForValue($parameter, $oas, null, ['inwerkingOp', 'validFrom']);
                if($searchResponse != 'danger')
                    $response['inwerkingOp'] = $searchResponse;
                else
                    $response['inwerkingOp'] = 'warning';
                $searchResponse = $this->checkReferencedParameterForValue($parameter, $oas, null, ['beschikbaarOp', 'availableFrom']);
                if($searchResponse != 'danger')
                    $response['beschikbaarOp'] = $searchResponse;
                else
                    $response['beschikbaarOp'] = 'warning';
            }
        }

        return $response;
    }
    public function assessDocumentation(array $oas): array
    {
        $responses = [];
        //$parameterCheck = $this->checkParameters($oas);
        //$methodCheck = $this->getAllMethods($oas);

        $responses['API-16: OpenApi-version'] = $this->checkOpenApiVersion($oas);

        //var_dump($oas['paths']);
        foreach ($oas['paths'] as $key => $path) {
           // var_dump($key);
            $parameters = $this->getPathParameters($path);
            $responses[$key]['API-03: Default HTTP-methods'] = $this->checkDefaultMethods($path);
            $responses[$key]['API-09: Custom representation'] = $this->checkParametersForFields($parameters, $oas);
            $responses[$key]['API-13: Authorization only as header'] = $this->checkAuthorizationHeader($parameters, $oas);
            $contentTypes = $this->checkContentTypes($path);
            $responses[$key]['API-22: JSON First'] = $contentTypes['API-22'];
            $responses[$key]['API-24: Content Negotiation'] = $responses[$key]['API-25: Content-Type'] = $contentTypes['API-24'];
            $responses[$key]['API-29: JSON Payloads'] = $contentTypes['API-22'];
            $responses[$key]['API-31: Sorting'] = $this->checkParametersForSort($parameters, $oas);
            $responses[$key]['API-32: Searching'] = $this->checkParametersForSearch($parameters, $oas);
            $responses[$key]['API-42: JSON Pagination'] = $contentTypes['API-42'];
            $responses[$key]['API-48: Leave off trailing slashes'] = $this->checkEndpoint($key);
            $responses[$key]['Time Travel'] = $this->checkTimeTravel($parameters, $oas);
            $responses[$key]['NLX'] = $this->checkNLX($parameters, $oas);
            //var_dump($contentTypes['schema']);
            $schema = $this->getSchema($path, $oas);
            //var_dump($schema);
            if($schema != null)
                $responses[$key]['properties'] = $this->checkSchema($schema);

        }
        return $responses;
    }
}

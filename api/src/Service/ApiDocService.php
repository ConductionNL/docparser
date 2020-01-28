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
        foreach ($methods as $method) {
            array_push($response, $method['parameters']);
        }
        return $this->array_flatten($response);
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
                if (!empty($status['content']) && key_exists('schema', $status['content'][$keys[0]])) {
                   // var_dump($status['content'][$keys[0]]['schema']);
                    if(key_exists('$ref', $status['content'][$keys[0]]['schema']))
                        $ref = explode('/', $status['content'][$keys[0]]['schema']['$ref']);
                    else{
                        $ref = explode('/', $status['content'][$keys[0]]['schema']['items']['$ref']);
                    }
                    $response['schema'] = end($ref);
                }
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
                if(!empty($method['requestBody']['content']) && key_exists('schema', $method['requestBody']['content'][$keys[0]])) {
                    $ref = explode('/', $method['requestBody']['content'][$keys[0]]['schema']['$ref']);
                    $response['schema'] = end($ref);
                }
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

    private function checkAuthorizationHeader(array $parameters)
    {
        foreach ($parameters as $parameter) {
            if ($parameter['name'] == 'Authorization' && $parameter['in'] == 'header')
                return 'ok';
        }
        return 'warning';
    }

    private function checkParametersForFields(array $parameters)
    {
        //var_dump($parameters);
        foreach ($parameters as $parameter) {
            if (($parameter['name'] == 'fields[]' && $parameter['in'] == 'query'))
                return 'ok';
        }
        return 'danger';
    }
    private function checkParametersForSearch(array $parameters)
    {
        //var_dump($parameters);
        foreach ($parameters as $parameter) {
            if ((($parameter['name'] == 'search' || $parameter['name'] == 'zoek') && $parameter['in'] == 'query'))
                return 'ok';
        }
        return 'warning';
    }
    private function checkParametersForSort(array $parameters)
    {
        //var_dump($parameters);
        foreach ($parameters as $parameter) {
            if ((($parameter['name'] == 'sort' || $parameter['name'] == 'sorteer') && $parameter['in'] == 'query'))
                return 'ok';
        }
        return 'warning';
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
        $response = [];

        foreach($schema['properties'] as $key=>$property){
            $response[$key]['API-26: camel case'] = $this->checkPropertyName($key);
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
//            var_dump($key);
            $parameters = $this->getPathParameters($path);
            $responses[$key]['API-03: Default HTTP-methods'] = $this->checkDefaultMethods($path);
            $responses[$key]['API-09: Custom representation'] = $this->checkParametersForFields($parameters);
            $responses[$key]['API-13: Authorization only as header'] = $this->checkAuthorizationHeader($parameters);
            $contentTypes = $this->checkContentTypes($path);
            $responses[$key]['API-22: JSON First'] = $contentTypes['API-22'];
            $responses[$key]['API-24: Content Negotiation'] = $responses[$key]['API-25: Content-Type'] = $contentTypes['API-24'];
            $responses[$key]['API-29: JSON Payloads'] = $contentTypes['API-22'];
            $responses[$key]['API-31: Sorting'] = $this->checkParametersForSort($parameters);
            $responses[$key]['API-32: Searching'] = $this->checkParametersForSort($parameters);
            $responses[$key]['API-42: JSON Pagination'] = $contentTypes['API-42'];
            $responses[$key]['API-48: Leave off trailing slashes'] = $this->checkEndpoint($key);
            //var_dump($contentTypes['schema']);
            $responses[$key]['schema'] = $contentTypes['schema'];
            $responses[$key]['properties'] = $this->checkSchema($oas['components']['schemas'][$contentTypes['schema']]);

        }
        return $responses;
    }
}

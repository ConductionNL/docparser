<?php


namespace App\Service;


class ApiDocService
{
    private function array_flatten(array $array){
        $response = [];
        foreach($array as $arr){
            $response = array_merge($response, $arr);
        }
        return $response;
    }
    private function getPathParameters(array $methods)
    {
        $response = [];
        foreach($methods as $method){
             array_push($response, $method['parameters']);
        }
        return $this->array_flatten($response);
    }
    private function getContentTypesPerStatus($statuses)
    {
        $response = ['API-22'=>'ok', 'API-24'=>'ok', 'API-42'=>'ok'];
        foreach($statuses as $status){

            if(key_exists('content', $status )) {
                //var_dump(array_keys($status['content'])[0]);
                $keys = array_keys($status['content']);
                if (strpos($keys[0], 'json') === false)
                    $response['API-22'] = 'warning';
                if (count($keys) <= 1)
                    $response['API-24'] = 'warning';
                if(!in_array('application/hal+json', $keys))
                    $response['API-42'] = 'warning';
            }
        }
        return $response;
    }
    private function checkContentTypes(array $path)
    {
        $response = ['API-22'=>'ok', 'API-24'=>'ok', 'API-42'=>'ok'];
        foreach($path as $method) {
            if (
                key_exists('requestBody', $method) &&
                key_exists('content', $method['requestBody'])){
                $keys = array_keys($method['requestBody']['content']);
                if(strpos($keys[0], 'json') === false)
                    $response['API-22'] = 'warning';
                if(count($keys)<=1)
                    $response['API-24'] = 'warning';
            }
            if(key_exists('responses', $method)){
                $responseResponse = $this->getContentTypesPerStatus($method['responses']);
                if($responseResponse['API-22'] != 'ok')
                    $response['API-22'] = $responseResponse['API-22'];
                if($responseResponse['API-24'] != 'ok')
                    $response['API-24'] = $responseResponse['API-24'];
                if($responseResponse['API-42'] != 'ok')
                    $response['API-42'] = $responseResponse['API-42'];
            }
        }
        return $response;
    }
    private function checkAuthorizationHeader(array $parameters)
    {
        foreach($parameters as $parameter){
            if($parameter['name'] == 'Authorization'&& $parameter['in'] == 'header')
                return true;
        }
        return false;
    }
    private function checkParametersForFields(array $parameters)
    {
    //var_dump($parameters);
    foreach($parameters as $parameter){
        if(($parameter['name'] == 'fields[]' && $parameter['in'] == 'query'))
            return 'ok';
        }
    return 'danger';
    }
    private function checkDefaultMethods(array $path){
        $methods = array_keys($path);
        foreach($methods as $method){
            switch($method){
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
    private function checkOpenApiVersion(array $oas){
        if((int)substr($oas['openapi'],0,1)>=3)
            return 'ok';
        return 'danger';
    }

    public function assessDocumentation(array $oas) :array
    {
        $responses = [];
        //$parameterCheck = $this->checkParameters($oas);
        //$methodCheck = $this->getAllMethods($oas);
        $responses['API-03: Default HTTP-methods'] = 'ok';
        $responses['API-09: Custom representation'] = 'ok';
        $responses['API-13: Authorization only as header'] = 'ok';
        $responses['API-16: OpenApi-version'] = $this->checkOpenApiVersion($oas);
        $responses['API-22: JSON First'] = 'ok';
        $responses['API-24: Content Negotiation'] = 'ok';
        $responses['API-25: Content-Type'] = 'ok';
        $responses['API-42: JSON Pagination'] = 'ok';

        foreach($oas['paths'] as $path){

            $parameters = $this->getPathParameters($path);
            $api03 = $this->checkDefaultMethods($path);
            $api09 = $this->checkParametersForFields($parameters);
            $api13 = $this->checkAuthorizationHeader($parameters);
            $contentTypes  = $this->checkContentTypes($path);
            $api22 = $contentTypes['API-22'];
            $api24 = $contentTypes['API-24'];
            $api42 = $contentTypes['API-42'];
            if($api03 != 'ok')
                $responses['API-03: Default HTTP-methods'] = $api03;
            if($api09 != 'ok')
                $responses['API-09: Custom representation'] = $api09;
            if($api13 != 'ok')
                $responses['API-13: Authorization only as header'] = $api13;
            if($api22 != 'ok'){
                $responses['API-22: JSON First'] = $api22;
                $responses['API-29: JSON Payloads'] = $api22;
            }
            if($api24 != 'ok'){
                $responses['API-24: Content Negotiation'] = $api24;
                $responses['API-25: Content-Type'] = $api24;
            }

            if($api42 != 'ok')
                $responses['API-42: JSON Pagination'] = $api42;
        }


        return $responses;
    }
}

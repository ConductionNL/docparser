<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\ApiDoc;
use App\Service\ApiDocService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Yaml\Yaml;

class ApiDocSubscriber implements EventSubscriberInterface
{
    private $params;
    private $em;
    private $serializer;
    private $client;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->params = $params;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->client = new Client();
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['apidoc', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function apidoc(ViewEvent $event)
    {
        $result = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $route = $event->getRequest()->attributes->get('_route');
        //var_dump($route);
        if (!$result instanceof ApiDoc || $route != 'api_api_docs_post_parse_collection') {
            //var_dump('a');
            return;
        }
        //var_dump('b');
        if ($route == 'api_api_docs_post_parse_collection') {
            $contentType = $event->getRequest()->headers->get('Content-Type');
            $content = $event->getRequest()->getContent();
        } else {
            $response = $this->client->get(json_decode($event->getRequest(), true)['url']);
            $contentType = $response->getHeaderLine('Content-Type');
            $content = $response->getBody();
        }

        switch ($contentType) {
            case 'application/json':
                $oas = json_decode($content, true);
                break;
            case 'application/x-yaml':
                $oas = Yaml::parse($content);
                break;
            default:
                if (!is_array($oas = json_decode($content, true))) {
                    $oas = Yaml::parse($content);
                }
                break;
        }
        //var_dump($oas);
        $apiDocService = new ApiDocService();
        $data = $apiDocService->assessDocumentation($oas);

        $response = [];
        $response['results'] = $data;
        $response['_links'] = ['self'=>'/apidocs/parse'];

        $json = $this->serializer->serialize(
            $response,
            'jsonhal',
            ['enable_max_depth'=> true]
        );
//        return;
        $response = new Response(
            $json,
            Response::HTTP_OK,
            ['content-type' => 'application/json+hal']
        );
        $event->setResponse($response);
    }
}

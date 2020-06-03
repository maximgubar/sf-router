<?php

namespace App\Routing\Matcher;

use App\Controller\CategoryController;
use App\Controller\CmsController;
use App\Controller\ProductController;
use Predis\Client as RedisClient;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

class RequestMatcher implements RequestMatcherInterface
{
    /**
     * @var RedisClient
     */
    private $redisClient;
//
    public function __construct()
    {
        $this->redisClient = RedisAdapter::createConnection('redis://localhost');
    }

    public function matchRequest(Request $request)
    {
        $website = $this->getWebsiteByHost($request->getHost());
        $requestPath = $request->getPathInfo();
        $redisKey = "url:{$website}:{$requestPath}";
        $value = $this->redisClient->get($redisKey);
        return $value === false ? [] : $this->getRouteParameters($value);
    }

    private function getWebsiteByHost(string $host)
    {
        $config = $this->getConfig();
        foreach ($config['websites'] as $code => $website) {
            if (in_array($host, $website['hosts'], true)) {
                return $code;
            }
        }
        throw new \Exception("Website not found by host '{$host}'");
    }

    private function getRouteParameters($json): array
    {
        $data = json_decode($json, true);
//        dd($data);
        switch ($data['entity']) {
            case 'category':
                return [
                    '_route' => 'category',
                    '_controller' => sprintf('%s::index', CategoryController::class),
                    'entityId' => $data['entity_id'],
                    'locale' => $data['locale'],
                ];
            case 'product':
                return [
                    '_route' => 'product',
                    '_controller' => sprintf('%s::index', ProductController::class),
                    'entityId' => $data['entity_id'],
                    'locale' => $data['locale'],
                ];
            case 'cms':
                return [
                    '_route' => 'cms',
                    '_controller' => sprintf('%s::index', CmsController::class),
                    'entityId' => $data['entity_id'],
                    'locale' => $data['locale'],
                ];
            default:
                return [];
        }
    }

    private function getConfig(): array
    {
        return [
            'websites' => [
                'drink_ch' => [
                    'description' => 'Drinks Switzerland',
                    'hosts' => [
                        'drinks.ch',
                        'www.drinks.ch',
                        'staging.drinks.ch',
                        'drink.loc',
                        'www.drink.loc',
                        'magento2.loc',
                        'www.magento2.loc',
                        '127.0.0.1',
                        'dev.drinks.ch',
                    ],
                ],
                'b2b_drink_ch' => [
                    'description' => 'Drinks Switzerland B2B',
                    'hosts' => [
                        'business.drinks.ch',
                        'business.staging.drinks.ch',
                        'business.drink.loc',
                        'business.magento2.loc',
                    ],
                    'required_customer_groups' => [
                        'handel',
                        'gastro',
                    ]
                ],
                'b2c_drinks_de' => [
                    'description' => 'Drinks Germany',
                    'hosts' => [
                        'drinks.de',
                        'www.drinks.de',
                        'staging.drinks.de',
                        'drink.deloc',
                        'www.drink.deloc',
                    ],
                ],
                'b2b_drinks_de' => [
                    'description' => 'Drinks Germany B2B',
                    'hosts' => [
                        'business.drinks.de',
                        'business.staging.drinks.de',
                        'business.drink.deloc',
                    ],
                    'required_customer_groups' => [
                        'handel',
                        'gastro',
                    ]
                ],
            ]
        ];
    }
}

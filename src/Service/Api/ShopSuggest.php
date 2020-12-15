<?php

namespace App\Service\Api;

use App\Entity\Product;
use App\Entity\Shop;
use App\Repository\ProductRepository;
use App\Repository\ShopRepository;
use App\Service\CurlHandler;
use App\Service\Parser;
use App\Service\simple_html_dom;
use App\Traits\ApiTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use function App\Service\file_get_html;

class ShopSuggest
{
    use ApiTrait;

    public function __construct(RequestStack $requestStack,CurlHandler $curlHandler,Parser $parser,EntityManagerInterface $em,ShopRepository $shopRepository)
    {

        $this->curlHandler = $curlHandler;
        $this->parser = $parser;
        $this->em = $em;
        $this->shopRepository = $shopRepository;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @param Request $request
     * @return array
     */
    public function run(): array
    {

        $search = $this->request->query->get('s');

        if (null === $search){

            return ['status'=>500,'message'=>'You need to pass a suggest(s) parameter'];
        }

        /**
         * Search shops
         */
        $shops = $this->shopRepository->search($search);

        if ($shops){
            /** @var Shop $shop */
            foreach ($shops as $shop){

                if (!is_string($shop->getUuid())){

                    $shop->setUuid($shop->getUuid()->toString());
                }
            }

            /**
             * Normalize objects as json to return
             */
            $encoder = new JsonEncoder();
            $normalizer = new ObjectNormalizer();
            $serializer = new Serializer(array($normalizer), array($encoder));
            $shops = $serializer->serialize($shops, 'json');
            $shops = json_decode($shops,true);
        }

        $resp = [
            'status' => 200,
            'total'  => count($shops),
            'shops'  => $shops,
        ];

        return $resp;
    }
}
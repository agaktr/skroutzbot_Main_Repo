<?php

namespace App\Service\Api;

use App\Entity\Shop;
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

class FetchShops
{
    use ApiTrait;

    public function __construct(RequestStack $requestStack,CurlHandler $curlHandler,Parser $parser,EntityManagerInterface $em)
    {

        $this->curlHandler = $curlHandler;
        $this->parser = $parser;
        $this->em = $em;
        $this->request = $requestStack->getCurrentRequest();
    }

    public function run(Request $request)
    {

        $this->request = $request;

        $abc = 'A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,other';

        $arr = explode(',',$abc);
        foreach ($arr as $letter){
            $urls[] = 'https://www.skroutz.gr/m/by/'.$letter;
        }

        /**
         * Get actual content
         */
        $this->options['ArrayChunkSize'] = 2;
        $this->options['SleepCounterMax'] = 10;
        $content = $this->makeCurl($urls);

        $shops = [];
        $total = 0;

        /**
         * Iterate through fetched content
         */
        foreach ( $content as $page){

            /**
             * Initialize HTML
             */
            $html = $this->parser->parseHTML($page);


            /** @var simple_html_dom $product */
            foreach($html->find('.shop-list .card') as $aShop) {

                /**
                 * Count total shops
                 */
                ++$total;

                /**
                 * Populate shops array
                 */
                $sid = str_replace('shop_','',$aShop->id);

                $shops[$sid] = [
                    'name' => $aShop->find('h2 a',0)->plaintext,
                    'externalId' => $sid,
                    'image' => $aShop->find('.pic img',0)->src,
                    'url' => 'https://skroutz.gr'.$aShop->find('.pic',0)->href,
                ];
            }
        }

        /**
         * Todo check
         */
        foreach ($shops as $shop){

            $theShop = new Shop();
            $theShop->setName($shop['name']);
            $theShop->setExternalId($shop['externalId']);
            $theShop->setImage($shop['image']);
            $theShop->setUrl($shop['url']);
            $this->em->persist($theShop);
        }
        $this->em->flush();

        $resp = [
            'status' => 200,
            'total'  => $total,
            'shops'  => $shops,
        ];

        return $resp;
    }
}
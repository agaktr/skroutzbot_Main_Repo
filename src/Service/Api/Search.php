<?php

namespace App\Service\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\CurlHandler;
use App\Service\Parser;
use App\Service\simple_html_dom;
use App\Traits\ApiTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use function App\Service\file_get_html;

class Search
{
    use ApiTrait;

    public function __construct(CurlHandler $curlHandler,Parser $parser,EntityManagerInterface $em,ProductRepository $productRepository)
    {

        $this->curlHandler = $curlHandler;
        $this->parser = $parser;
        $this->em = $em;
        $this->productRepository = $productRepository;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function run(Request $request): array
    {
        $products = [];
        $total = $active = 0;
        $this->request = $request;

        $name = $this->request->query->get('name');
        $url = $this->request->query->get('url');

        if (null === $name && null == $url){

            return ['status'=>500,'message'=>'You need to pass either name or url parameter'];
        }

        if (null !== $name){
            $args = [
                'mode' => 'name',
                'search' => 'https://www.skroutz.gr/search?keyphrase='.str_replace(' ','+',$name)
            ];
        }

        /**
         * If we search via url we send directly 1 product url in order to skip step
         */
        if (null !== $url){
            $args = [
                'mode' => 'url',
                'search' => $url
            ];

            /**
             * Populate product array
             */
            $products[] = [
                'name' => 'Direct Search',
                'url' => $args['search'],
                'specs' => 'N/A',
                'rating' => 'N/A',
                'price' => 'Direct Search',
            ];

            $resp = [
                'status' => 200,
                'total'  => 1,
                'active'  => 1,
                'products'  => $products,
            ];

            return $resp;
        }

//        var_dump($args);

        /**
         * Get actual content
         */
        $urls = [$args['search']];
        $content = $this->makeCurl($urls);
//var_dump($content);
        /**
         * Iterate through fetched content
         */

//var_dump($content);
        foreach ( $content as $page){

            /**
             * Initialize HTML
             */
            $html = $this->parser->parseHTML($page);

            /** @var simple_html_dom $product */
            foreach($html->find('.list .cf.card') as $product) {

                /**
                 * Count total products
                 */
                ++$total;

                /**
                 * Get only available items
                 */
                $productBtn = $product->find('.js-sku-link.sku-link',0);
                if (null === $productBtn){
                    continue;
                }

                /**
                 * Count available products
                 */
                ++$active;

                /**
                 * Check specs area if any
                 */
                $specs = $product->find('.specs',0);
                if (null !== $specs){
                    $specs = $specs->title;
                }else{
                    $specs = 'N/A';
                }

                /**
                 * Check rating area if any
                 */
                $rating = $product->find('.rating.stars',0);
                if (null !== $rating){
                    $rating = $rating->title;
                }else{
                    $rating = -1;
                }

                /**
                 * Populate product array
                 */
                $pid = explode('/',str_replace('/s/','',$product->find('.js-sku-link.sku-link',0)->href))[0];
                $productIds[] = $pid;
                $products[] = [
                    'name' => $productBtn->title,
                    'url' => 'https://www.skroutz.gr/'.$product->find('.js-sku-link.sku-link',0)->href,
                    'photo' => $product->find('.js-sku-link.pic img',0)->src,
                    'externalId' => $pid,
                    'specs' => $specs,
                    'rating' => $rating,
                    'price' => $productBtn->plaintext,
                ];
            }
        }

        /**
         * Get saved products
         */
        $savedProducts = $this->productRepository->findByIds($productIds);

        /**
         * Check if we have to add any new product to db
         */
        $respProducts = [];
        foreach ($products as $product){

            $saved = false;
            foreach ($savedProducts as $savedProduct){

                if ($savedProduct->getExternalId() == $product['externalId']){
                    $saved = true;
                    $theProduct = $savedProduct;
                    break;
                }
            }

            if (!$saved){

                $theProduct = new Product();
            }

            $theProduct->setName($product['name']);
            $theProduct->setUrl($product['url']);
            $theProduct->setExternalId($product['externalId']);
            $theProduct->setSpecs($product['specs']);
            $theProduct->setRating(floatval($product['rating']));
            $theProduct->setUpdated(-1);
            $theProduct->setPhoto($product['photo']);
            $this->em->persist($theProduct);

            $respProducts[] = $theProduct;
        }

        /**
         * Save to db
         */
        $this->em->flush();

        foreach ($respProducts as $product){
            if (!is_string($product->getUuid())){
                $product->setUuid($product->getUuid()->toString());
            }
        }

        /**
         * Normalize objects as json to return
         */
        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();
        $serializer = new Serializer(array($normalizer), array($encoder));
        $products = $serializer->serialize($respProducts, 'json');
        $products = json_decode($products,true);

        $resp = [
            'status' => 200,
            'total'  => $total,
            'active'  => $active,
            'products'  => $products,
        ];

        return $resp;
    }
}
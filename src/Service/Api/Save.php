<?php

namespace App\Service\Api;

use App\Entity\Job;
use App\Entity\Price;
use App\Entity\Product;
use App\Repository\PriceRepository;
use App\Repository\ProductRepository;
use App\Repository\ShopRepository;
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

class Save
{
    use ApiTrait;

    public function __construct(EntityManagerInterface $em,CurlHandler $curlHandler,Parser $parser,ShopRepository $shopRepository,ProductRepository $productRepository,PriceRepository $priceRepository)
    {

        $this->curlHandler = $curlHandler;
        $this->parser = $parser;
        $this->shopRepository = $shopRepository;
        $this->productRepository = $productRepository;
        $this->priceRepository = $priceRepository;
        $this->em = $em;
    }

    public function run(Request $request)
    {

        $this->request = $request;

        $postData = $_POST;

        if (!isset($postData['competitors'])){
            $postData['competitors'] = [];
        }
        //TODO CHECK IF EXISTS

        $job = new Job();

        $job->setProductUuid($postData['productId']);
        $job->setMode($postData['mode']);
        $job->setCompetitors($postData['competitors']);
        $job->setLowestPrice($postData['lowestPrice']);
        $job->setIncrement($postData['increment']);
        $job->setCreated(time());
        $job->setLastRun(-1);

        $this->em->persist($job);
        $this->em->flush();

        if (!is_string($job->getUuid())){

            $job->setUuid($job->getUuid()->toString());

        }
        if (!is_string($job->getProductUuid())){

            $job->setProductUuid($job->getProductUuid()->toString());
        }

        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();
        $serializer = new Serializer(array($normalizer), array($encoder));

        $responseArr = $serializer->serialize($job, 'json');
        $responseArr = json_decode($responseArr,true);

        $resp = [
            'status' => 200,
            'job'  => $responseArr,
        ];

        return $resp;

        return ['george'];
        die();
//
//        $

        if (null === $urls && null == $ids){

            return ['status'=>500,'message'=>'You need to pass either urls or ids parameter'];
        }

        if (null !== $urls){

            $urls = explode(',',$urls);
        }

        $theProducts = false;
        if (null !== $ids){

            $ids = explode(',',$ids);
            $theProducts = $this->productRepository->findByUUids($ids);

        }

        if ($theProducts){

            foreach ($theProducts as $theProduct){
                $urls[] = $theProduct->getUrl();
            }
        }

        /**
         * Get actual content
         */
        $content = $this->makeCurl($urls);

        /**
         * Iterate through fetched content
         */
        foreach ( $content as $page){

            /**
             * Initialize HTML
             */
            $html = $this->parser->parseHTML($page);

            $desc = $html->find('.summary .description.hide-small-viewport.long p',0);
            if (null !== $desc){
                $desc = $desc->plaintext;
            }else{
                $desc = '';
            }

            /**
             * Get main data
             */
            $productData = [
                'uuid' => $theProduct->getUuid(),
                'name' => $html->find('.page-title',0)->plaintext,
                'photo' => $html->find('.sku-image',0)->href,
                'rating' => $html->find('.rating-wrapper span',0)->plaintext,
                'desc' => $desc,
            ];

            /** @var simple_html_dom $product */
            foreach($html->find('.cf.card') as $product) {

                /**
                 * Count total products
                 */
                ++$total;

                /**
                 * Get only available items
                 */
                $productBtn = $product->find('h3 .js-product-link',0);
                if (null === $productBtn){
                    continue;
                }

                /**
                 * Count available products
                 */
                ++$active;

                /**
                 * Populate product array
                 */
                $pid = str_replace('/products/show/','',$productBtn->href);
                $prices[$pid] = [
                    'name' => $productBtn->title,
                    'id' => str_replace('/products/show/','',$productBtn->href),
                    'shop' => 'N/A',
                    'net_price' => trim(str_replace('€','',$product->find('.price .js-product-link',0)->plaintext)),
                    'url' => 'https://skroutz.gr'.$productBtn->href,
                ];
            }
        }

        /**
         * Get products info endpoint
         */
        $pids = array_keys($prices);
        $this->options['CURLOPT_POST'] = 1;
        $this->options['CURLOPT_HTTPHEADER'] = ['Content-Type:application/x-www-form-urlencoded'];

        $this->options['CURLOPT_POSTFIELDS'] = '';
        foreach ($pids as $pid){
            $this->options['CURLOPT_POSTFIELDS'] .= '&product_ids[]='.$pid;
        }

        $urls = ['https://www.skroutz.gr/personalization/product_prices.json'];
        $content = $this->makeCurl($urls);
        $content = json_decode($content[0],true);
        $shopIds = [];
        foreach ($content as $item){

            $shopIds[] = $item['shop_id'];

            $prices[$item['id']]['net_price'] = $item['net_price'];
            $prices[$item['id']]['shop'] = $item['shop_id'];
            if ($item['final_price'] === null){
                $item['final_price'] = -1;
            }
            $prices[$item['id']]['final_price'] = $item['final_price'];
            preg_match('/\d+,\d+/', $item['shipping_cost'], $costs);
            if (empty($costs)){
                $costs[] = -1;
            }
            $prices[$item['id']]['shipping_cost'] = floatval($costs[0]);
            preg_match('/\d+,\d+/', $item['payment_method_cost'], $costs);
            if (empty($costs)){
                $costs[] = -1;
            }
            $prices[$item['id']]['payment_method_cost'] = floatval($costs[0]);
        }
        $net_array = array_column($prices,'net_price');

        array_multisort($net_array,SORT_ASC,$prices);

        $shops = $this->shopRepository->findByIds($shopIds);

        foreach ($prices as $key=>$price){
            $shopId = $price['shop'];
            foreach ($shops as $shop){

                if ($shop->getExternalId() == $shopId){

                    break;
                }
            }
            unset($prices[$key]['shop']);

            $prices[$key]['shop']['id'] = $shop->getId();
            $prices[$key]['shop']['name'] = $shop->getName();
            $prices[$key]['shop']['uuid'] = $shop->getUuid();
            $prices[$key]['shop']['externalId'] = $shop->getExternalId();
            $prices[$key]['shop']['image'] = $shop->getImage();
            $prices[$key]['shop']['url'] = $shop->getUrl();
        }

        $uuids = [$theProduct->getUuid()];
        $savedPrices = $this->priceRepository->findByProductUUids($uuids);
        $respPrices = [];
        $responseArr = [];
        foreach ($prices as $key=>$price){

            $saved = false;
            foreach ($savedPrices as $savedPrice){

                if ($savedPrice->getShopUuid() == $price['shop']['uuid']){
                    $saved = true;
                    $thePrice = $savedPrice;
                    break;
                }
            }

            if (!$saved){

                $thePrice = new Price();
            }

            /** @var Product $theProduct */
            $thePrice->setProductUuid($theProduct->getUuid()->toString());
            $thePrice->setNetPrice($price['net_price']);
            $thePrice->setShippingCost($price['shipping_cost']);
            $thePrice->setPaymentCost($price['payment_method_cost']);
            $thePrice->setFinalPrice($price['final_price']);
            $thePrice->setShopUuid($price['shop']['uuid']->toString());
            $thePrice->setUrl($price['url']);
            $this->em->persist($thePrice);

//            $respPrices[] = $thePrice;

            $responseArr[$key] = [
                'price' => $thePrice,
                'shop' => $price['shop'],
            ];
        }

        /**
         * Save to db
         */
        $this->em->flush();

//        foreach ($respPrices as $price){
//            if (!is_string($price->getUuid())){
//                $price->setUuid($price->getUuid()->toString());
//                $price->setProductUuid($price->getProductUuid()->toString());
//                $price->setShopUuid($price->getShopUuid()->toString());
//            }
//        }

        foreach ($responseArr as $key=>$data){
//var_dump($data);

            if (!is_string($data['price']->getUuid())){

                $data['price']->setUuid($data['price']->getUuid()->toString());

            }
            if (!is_string($data['price']->getProductUuid())){

                $data['price']->setProductUuid($data['price']->getProductUuid()->toString());
            }
            if (!is_string($data['price']->getShopUuid())){

                $data['price']->setShopUuid($data['price']->getShopUuid()->toString());
            }
            if (!is_string($data['shop']['uuid'])){

                $data['shop']['uuid'] = $data['shop']['uuid']->toString();
            }
            $responseArr[$key] = $data;
        }
//var_dump($responseArr);
        /**
         * Normalize objects as json to return
         */
        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();
        $serializer = new Serializer(array($normalizer), array($encoder));
//        $prices = $serializer->serialize($respPrices, 'json');
//        $prices = json_decode($prices,true);

        $responseArr = $serializer->serialize($responseArr, 'json');
        $responseArr = json_decode($responseArr,true);
        $resp = [
            'status' => 200,
            'total'  => $total,
            'active'  => $active,
            'productData'  => $productData,
            'prices'  => $responseArr,
        ];

        return $resp;
    }
}
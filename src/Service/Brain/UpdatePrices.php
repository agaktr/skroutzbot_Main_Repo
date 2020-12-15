<?php /** @noinspection PhpFieldAssignmentTypeMismatchInspection */

namespace App\Service\Brain;

use App\Entity\Price;
use App\Entity\Product;
use App\Entity\ProfileRawData;
use App\Entity\Shop;
use App\Entity\UserProfile;
use App\Traits\BrainTrait;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\CurlHandler;
use App\Service\Parser;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class UpdatePrices
{
    use BrainTrait;

    private $hasPrices = false;

    public function __construct(EntityManagerInterface $em,CurlHandler $curlHandler,Parser $parser)
    {


        $this->em = $em;
        $this->userProfileRepository = $this->em->getRepository(UserProfile::class);
        $this->profileRawDataRepository = $this->em->getRepository(ProfileRawData::class);
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->shopRepository = $this->em->getRepository(Shop::class);
        $this->priceRepository = $this->em->getRepository(Price::class);
        $this->curlHandler = $curlHandler;
        $this->parser = $parser;
        $this->externalIds = [];
    }

    public function run()
    {

        $prices = $productData = [];
        $total = $active = 0;

        /**
         * Get raw data
         */
        $products = $this->productRepository->findToUpdate();

//        var_dump($products);

        $productIds = [];
        if ($products){

            foreach ($products as $theProduct){

                $productIds[] = $theProduct->getUuid()->toString();
                $urls[$theProduct->getUuid()->toString()] = $theProduct->getUrl();
            }
        }

        if (empty($productIds)){
            $resp = [
                'status' => 200,
                'total'  => 0,
                'active'  => 0,
                'productData'  => [],
                'prices'  => [],
            ];

            return $resp;
        }

//        $productsPrices = $this->priceRepository->findByProductUUids($productIds);
//
//        if ($productsPrices){
//            $this->hasPrices = true;
//        }

        /**
         * Get actual content if we do not already have prices
         */
        if (!$this->hasPrices){


            $this->options['RETURN_HEADER'] =1;
            $this->options['ArrayChunkSize'] = 5;
            $this->options['SleepCounterMax'] = 50;
            $content = $this->makeCurl($urls);

            /**
             * Iterate through fetched content
             */
            foreach ( $content as $productKey=>$page){

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

                $rating = $html->find('.rating-wrapper span',0);
                if (null !== $rating){
                    $rating = $rating->plaintext;
                }else{
                    $rating = -1;
                }

                /**
                 * Get main data
                 */
                $productData[$productKey] = [
                    'uuid' => $productKey,
                    'name' => $html->find('.page-title',0)->plaintext,
                    'photo' => $html->find('.sku-image',0)->href,
                    'rating' => $rating,
                    'desc' => $desc,
                ];

                /**
                 * Get competitors
                 */
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
                        'productUuid' => $productKey,
                        'name' => $productBtn->title,
                        'id' => $pid,
                        'shop' => 'N/A',
                        'net_price' => trim(str_replace('€','',$product->find('.price .js-product-link',0)->plaintext)),
                        'url' => 'https://skroutz.gr'.$productBtn->href,
                    ];
                }
            }
        }else{

//            $prices = [];
//
//            /** @var Price $price */
//            foreach ($productsPrices as $price){
//
//                $pid = str_replace('https://skroutz.gr/products/show/','',$price->getUrl());
//                $prices[$pid] = [
//                    'name' => 'N/A',
//                    'id' => $pid,
//                    'shop' => 'N/A',
//                    'net_price' => -1,
//                    'url' => $price->getUrl(),
//                ];
//            }

        }


        /**
         * Get products info endpoint
         */
        $pids = array_keys($prices);
//        var_dump($pids);
////        var_dump($productsPrices);
//        die();
        $this->options['CURLOPT_POST'] = 1;
        $this->options['CURLOPT_HTTPHEADER'] = ['Content-Type:application/x-www-form-urlencoded'];

        $this->options['CURLOPT_POSTFIELDS'] = '';
        foreach ($pids as $pid){
            $this->options['CURLOPT_POSTFIELDS'] .= '&product_ids[]='.$pid;
        }

        $this->options['ArrayChunkSize'] = 5;
        $this->options['SleepCounterMax'] = 50;
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

        $uuids = $productIds;
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
            $thePrice->setProductUuid($price['productUuid']);
            $thePrice->setNetPrice($price['net_price']);
            $thePrice->setShippingCost($price['shipping_cost']);
            $thePrice->setPaymentCost($price['payment_method_cost']);
            $thePrice->setFinalPrice($price['final_price']);
            $thePrice->setShopUuid($price['shop']['uuid']->toString());
            $thePrice->setUrl($price['url']);
            $thePrice->setUpdated(time()+3600);
            $this->em->persist($thePrice);

//            $respPrices[] = $thePrice;

            $responseArr[$key] = [
                'price' => $thePrice,
                'shop' => $price['shop'],
            ];
        }

        foreach ($products as $product){
            $product->setUpdated(time()+3600);
        }
//        var_dump();
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
<?php /** @noinspection PhpFieldAssignmentTypeMismatchInspection */

namespace App\Service\Brain;

use App\Entity\Product;
use App\Entity\ProfileRawData;
use App\Entity\UserProfile;
use App\Traits\BrainTrait;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\CurlHandler;
use App\Service\Parser;

class MakeMatching
{
    use BrainTrait;

    public function __construct(EntityManagerInterface $em,CurlHandler $curlHandler,Parser $parser)
    {


        $this->em = $em;
        $this->userProfileRepository = $this->em->getRepository(UserProfile::class);
        $this->profileRawDataRepository = $this->em->getRepository(ProfileRawData::class);
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->curlHandler = $curlHandler;
        $this->parser = $parser;
        $this->externalIds = [];
    }

    public function run()
    {


        /**
         * Get raw data
         */
        $profileRawData = $this->profileRawDataRepository->getUndone();

        $undoneItems = [];
        $userProfiles = [];
        $solo = $multiple = $this->empty = $error = $miss = 0;

        if ($profileRawData) {


            /** @var UserProfile $userProfile */
            foreach ($profileRawData as $fetchedPage) {

                $this->fetchedPage = $fetchedPage;
                $this->UserProfile = $this->fetchedPage->getUserProfileUuid()->toString();
                $this->shopItem = $this->fetchedPage->getShopItem();
                $userProfiles[] = $this->fetchedPage->getUserProfileUuid()->toString();

                /**
                 * Initialize HTML
                 */
                $this->html = $this->parser->parseHTML($this->fetchedPage->getHTML());

                /**
                 * Handle page according to type
                 */

                switch ($this->determingSearchResults()) {

                    case 'solo':

                        ++$solo;
                        $profileProducts[$this->fetchedPage->getUuid()->toString()] = $this->handleSoloProduct();
                        break;
                    case 'multiple':

                        ++$multiple;
                        $profileProducts[$this->fetchedPage->getUuid()->toString()] = $this->handleMultipleProduct();
                        break;
                    case 'not-found':

                        ++$this->empty;
                        $profileProducts[$this->fetchedPage->getUuid()->toString()] = [[
                            'shopItem' => $this->shopItem,
                            'userProfile' => $this->UserProfile,
                            'case' => 'not-found'
                        ]];
                        break;
                    case 'error':

                        ++$error;
                        $profileProducts[$this->fetchedPage->getUuid()->toString()] = [[
                            'shopItem' => $this->shopItem,
                            'userProfile' => $this->UserProfile,
                            'case' => 'error'
                        ]];
                        break;
                    case 'cookie-miss':

                        ++$miss;
                        $profileProducts[$this->fetchedPage->getUuid()->toString()] = [[
                            'shopItem' => $this->shopItem,
                            'userProfile' => $this->UserProfile,
                            'case' => -1
                        ]];
                        break;
                    case 'N/A':

                        $profileProducts[$this->fetchedPage->getUuid()->toString()] = [];
                        var_dump('check shit');
                        die();
                        break;
                }

                /**
                 * Delete raw data entry
                 */
                $this->em->remove($this->fetchedPage);
            }


            /**
             * Get saved products
             */
            $savedProducts = $this->productRepository->findByIds($this->externalIds);

            /**
             * Get profiles
             */
            $userProfilesRes = $this->userProfileRepository->getByUuids($userProfiles);
            $userProfiles = [];
            foreach ($userProfilesRes as $helper) {
                $userProfiles[$helper->getUuid()->toString()] = $helper;
            }

            /**
             * Check if we have to add any new product to db
             */
            $respProducts = [];
            foreach ($profileProducts as $profileId => $products) {

                $matchedUuids = [];
                $shopItem = $products[0]['shopItem'];
                $userProfile = $products[0]['userProfile'];
                $case = $products[0]['case'];

                if (
                    $case == 'not-found' ||
                    $case == 'error'
                ) {

                    /**
                     * match product as invalid
                     */
                    $profileProducts = $userProfiles[$userProfile]->getProducts();
                    $profileProducts[$shopItem]["Product"] = $case;
                    $userProfiles[$userProfile]->setProducts($profileProducts);
                    $userProfiles[$userProfile]->setItemsProcessed($userProfiles[$userProfile]->getItemsProcessed() - 1);
                } else {


                    foreach ($products as $product) {

                        $saved = false;
                        foreach ($savedProducts as $savedProduct) {

                            if ($savedProduct->getExternalId() == $product['externalId']) {
                                $saved = true;
                                $theProduct = $savedProduct;
                                break;
                            }
                        }

                        if (!$saved) {

                            $theProduct = new Product();
                        }

                        $theProduct->setName($product['name']);
                        $theProduct->setUrl($product['url']);
                        $theProduct->setExternalId($product['externalId']);
                        $theProduct->setSpecs($product['specs']);
                        $theProduct->setRating(floatval($product['rating']));
                        $theProduct->setPhoto($product['photo']);
                        $theProduct->setUpdated(-1);
                        $this->em->persist($theProduct);

                        $respProducts[] = $theProduct;

                        if (!is_string($theProduct->getUuid())) {
                            $matchedUuids[] = $theProduct->getUuid()->toString();
                        } else {
                            $matchedUuids[] = $theProduct->getUuid();
                        }

                    }

                    /**
                     * Make the match of the product
                     */
                    $profileProducts = $userProfiles[$userProfile]->getProducts();
                    $profileProducts[$shopItem]["Product"] = $matchedUuids;
                    $userProfiles[$userProfile]->setProducts($profileProducts);
                }
//                var_dump($profileProducts[$shopItem]);
            }
//        var_dump( $userProfiles);
//        var_dump( $userProfiles[$userProfile]->getProducts()[$shopItem]);
            /**
             * Save to db
             */
            $this->em->flush();
        }

        $resp = [
            'status' => 200,
            'fetched'  => count($undoneItems),
            'profiles'  => count($userProfiles),
            'solo'  => $solo,
            'multiple'  => $multiple,
            'empty'  => $this->empty,
            'error'  => $error,
            'miss'  => $miss,
        ];

//        var_dump($solo);
//        var_dump($resp);

        return $resp;
    }

    private function determingSearchResults(){
//var_dump($this->fetchedPage);
        $type = 'N/A';
        /**
         * If we are caught go as unfetched
         */
        if (strpos($this->fetchedPage->getHTML(),"Are you sure you're not a robot?") !== false){

            return 'cookie-miss';
        }
        if (is_bool($this->html)){
            return 'error';
        }
//        if ($this->html->find('.js-product-card',1)){
//
//            return 'multiple';
//        }
        if ($this->html->find('.js-product-card')){

            return 'solo';
        }

        if ($this->html->find('.list .cf.card')){

            return 'multiple';
        }
        if (
            null === $this->html->find('h1.page-title',0) ||
            strpos($this->html->find('h1.page-title',0)->plaintext,'Δεν βρέθηκαν αποτελέσματα στην αναζήτησή σου') !== false ||
            strpos($this->html->find('.filters-no-results-content h4',0)->plaintext,'Δεν βρέθηκαν προϊόντα') !== false
        ){

            return 'not-found';
        }

        return $type;
    }

    private function handleSoloProduct(){


        /**
         * Get main data
         */
        $desc = $this->html->find('.summary .description.hide-small-viewport.long p',0);
        if (null !== $desc){
            $desc = $desc->plaintext;
        }else{
            $desc = '';
        }
        $rating = $this->html->find('.rating-wrapper span',0);
        if (null !== $rating){
            $rating = $rating->plaintext;
        }else{
            $rating = -1;
        }

        $productData = [
//            'uuid' => $theProduct->getUuid(),
            'externalId' => $this->html->find('[itemprop=sku]',0)->content,
            'url' => $this->html->find('[itemprop=url]',0)->content,
            'name' => $this->html->find('.page-title',0)->plaintext,
            'photo' => $this->html->find('.sku-image',0)->href,
            'rating' => $rating,
            'desc' => $desc,
            'specs' => 'N/A',
            'shopItem' => $this->shopItem,
            'userProfile' => $this->UserProfile,
            'case' => 'solo',
        ];

        $this->externalIds[] = $productData['externalId'];

        return [$productData];
    }

    private function handleMultipleProduct(){


        /** @var simple_html_dom $product */
        foreach($this->html->find('.list .cf.card') as $product) {

            /**
             * Get only available items
             */
            $productBtn = $product->find('.js-sku-link.sku-link',0);
            if (null === $productBtn){
                continue;
            }

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
            $productData[] = [
                'name' => $productBtn->title,
                'url' => 'https://www.skroutz.gr/'.$product->find('.js-sku-link.sku-link',0)->href,
                'externalId' => $pid,
                'specs' => $specs,
                'rating' => $rating,



                'price' => $productBtn->plaintext,
                'photo' => $this->html->find('.js-sku-link.pic img',0)->src,
                'desc' => '',
                'shopItem' => $this->shopItem,
                'userProfile' => $this->UserProfile,
                'case' => 'multiple',
            ];

            $this->externalIds[] = $pid;
        }

//        if (!isset($productData)){
//            ++$this->empty;
//            return [[
//                'shopItem' => $this->shopItem,
//                'userProfile' => $this->UserProfile,
//                'case' => 'not-found'
//            ]];
//        }
        return $productData;
    }
}
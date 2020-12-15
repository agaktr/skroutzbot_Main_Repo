<?php /** @noinspection PhpFieldAssignmentTypeMismatchInspection */

namespace App\Service\Brain;

use App\Entity\ProfileRawData;
use App\Entity\UserData;
use App\Entity\UserProfile;
use App\Traits\BrainTrait;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\CurlHandler;
use App\Service\Parser;

class MatchProfiles
{
    use BrainTrait;

    public function __construct(EntityManagerInterface $em,CurlHandler $curlHandler)
    {


        $this->em = $em;
        $this->userProfileRepository = $this->em->getRepository(UserProfile::class);
        $this->userDataRepository = $this->em->getRepository(UserData::class);
        $this->curlHandler = $curlHandler;
    }

    public function run()
    {

        /**
         * Vars init
         */
        $undoneItems = [];
        $requestsMade = [];
        $cookieMiss = 0;

        /**
         * Get Unprocessed user profiles
         */
        $userProfiles = $this->userProfileRepository->getUnprocessed();

        /**
         * Iterate through Unprocessed user profiles
         */
        if ($userProfiles) {

            /** @var UserProfile $userProfile */
            foreach ($userProfiles as $userProfile) {

                $requestsMade[$userProfile->getUserUuid()->toString()] = 0;

                /**
                 * Get undone items
                 */
                $profileProducts = $userProfile->getProducts();
                $undoneItems = [];
                foreach ($profileProducts as $key=>$productData){

                    if ($productData["Product"] == -1){
                        $undoneItems[$key] = $productData['ShopItem'];
                    }

                    if (count($undoneItems) > 1){
                        break;
                    }
                }

                /**
                 * Create search queries for the products
                 */
                $urls = [];
                foreach ($undoneItems as $key=>$undoneItem){

                    /**
                     * If title error search with EAN
                     * We search by title instead of ean as it has misses
                     * $urls[$key] = 'https://www.skroutz.gr/search?keyphrase='.str_replace(' ','+',$undoneItem['EAN']);
                     */
                    if (is_array($undoneItem['Title'])){
                        $undoneItem['Title'] = $undoneItem['EAN'];
                    }
                    if (is_array($undoneItem['Title'])){
                        $undoneItem['Title'] = $undoneItem['MPN'];
                    }

                    $urls[$key] = 'https://www.skroutz.gr/search?keyphrase='.str_replace(' ','+',$undoneItem['Title']);
                }

                /**
                 * If done continue to next user profile
                 */
                if (empty($urls)){
                    $userProfile->setIsDone(true);
                    continue;
                }

                /**
                 * Get the content
                 */
                $requestsMade[$userProfile->getUserUuid()->toString()] += count($urls);
                $this->options['ArrayChunkSize'] = 3;
                $this->options['SleepCounterMax'] = 15;
                $content = $this->makeCurl($urls);

                foreach ($content as $itemId=>$page){

                    /**
                     * If we are caught go as unfetched
                     */
                    if (strpos($page,"Are you sure you're not a robot?") !== false){

                        ++$cookieMiss;

                        /**
                         * decrease the user request count as its not valid
                         */
                        $requestsMade[$userProfile->getUserUuid()->toString()] -= 1;
                        continue;
                    }

                    /**
                     * Add raw content to DB
                     */
                    $profileRawData = new ProfileRawData();
                    $profileRawData->setUserProfileUuid($userProfile->getUuid());
                    $profileRawData->setShopItem($itemId);
                    $profileRawData->setHTML($page);

                    $this->em->persist($profileRawData);

                    /**
                     * Mark shopItem as fetched
                     */
                    $profileProducts[$itemId]["Product"] = "fetched";

                    /**
                     * Update Processed Items
                     */
                    $itemsProcessed = $userProfile->getItemsProcessed() + 1;
                    $userProfile->setItemsProcessed($itemsProcessed);
                }

                /**
                 * Update User Profile Products
                 */
                $userProfile->setProducts($profileProducts);
            }

            /**
             * Get Users data to update requests count
             */
            $usersDataRes = $this->userDataRepository->getByUuids(array_keys($requestsMade));
            $usersData = [];
            foreach ($usersDataRes as $data){
                $usersData[$data->getUserUuid()->toString()] = $data;
            }

            foreach ($requestsMade as $uid=>$requestCount){
                $usersData[$uid]->setCurrentRequests($usersData[$uid]->getCurrentRequests() + $requestCount);
            }

            /**
             * Flush everything to database
             */
            $this->em->flush();
        }

        $resp = [
            'status' => 200,
            'fetched'  => count($undoneItems),
            'profiles'  => count($userProfiles),
            'requestsMade'  => $requestsMade,
            'cookieMiss'  => $cookieMiss
        ];

        return $resp;
    }
}
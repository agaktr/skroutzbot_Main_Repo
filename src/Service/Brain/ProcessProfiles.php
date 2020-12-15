<?php /** @noinspection PhpFieldAssignmentTypeMismatchInspection */

namespace App\Service\Brain;

use App\Entity\UserProfile;
use App\Traits\BrainTrait;
use Doctrine\ORM\EntityManagerInterface;

class ProcessProfiles
{
    use BrainTrait;

    public function __construct(EntityManagerInterface $em)
    {

        $this->em = $em;
        $this->userProfileRepository = $this->em->getRepository(UserProfile::class);
    }

    public function run()
    {

        $newItems = 0;

        /**
         * Get Undone user profiles
         */
        $userProfiles = $this->userProfileRepository->getUndone();

        if ($userProfiles) {


            /** @var UserProfile $userProfile */
            foreach ($userProfiles as $userProfile) {

                /**
                 * Get xml and convert to array
                 */
                $xml = simplexml_load_file('../public' . $userProfile->getCsvUrl());
                if ($xml === FALSE) {
                    echo "There were errors parsing the XML file.\n";
                    foreach (libxml_get_errors() as $error) {
                        echo $error->message;
                    }
                    exit;
                }
                $xml = json_encode($xml);
                $xml = json_decode($xml, TRUE);

                /**
                 * Get total Items
                 */
                $total = count($xml['post']);
                $newItems += $total;

                /**
                 * Add products to profile products array
                 * TODO map the shopItem on unique base
                 */
                $products = [];
                foreach ($xml['post'] as $item) {

                    $products[$item["UID"]]['ShopItem'] = [
                        'Title'=>$item['Title'],
                        'MPN'=>$item['MPN'],
                        'EAN'=>$item['EAN'],
                    ];
                    $products[$item["UID"]]['Product'] = -1;
                }

                $userProfile->setItemsNumber($total);
                $userProfile->setProducts($products);
            }

            $this->em->flush();
        }

        $resp = [
            'status' => 200,
            'items'  => $newItems,
            'profiles'  => count($userProfiles)
        ];

        return $resp;
    }
}
<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\Brain\MakeMatching;
use App\Service\Brain\MatchProfiles;
use App\Service\Brain\ProcessProfiles;
use App\Service\Brain\UpdatePrices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
  * Require ROLE_USER for *every* controller method in this class.
  *
  * @IsGranted("ROLE_ADMIN")
  * @Route("/brain")
  */

class BrainController extends AbstractController
{
    /**
     * @Route("/", name="brain_index")
     */
    public function index()
    {

        return $this->render('brain/brain.html.twig', [
        ]);
    }

    /**
     * @Route("/run", name="brain_run")
     */
    public function run(ProcessProfiles $processProfiles,MatchProfiles $matchProfiles,MakeMatching $makeMatching,UpdatePrices $updatePrices)
    {
        $start = microtime(true);

        $indirect = true;

        /**
         * Process any new profiles
         */
        $resp = $this->processProfiles($processProfiles,$indirect);

        if ($resp['items'] == 0 && $resp['profiles'] == 0){

            /**
             * If no more profiles start the matching
             */
            $resp = $this->matchProfiles($matchProfiles,$indirect);

            if ($resp['fetched'] == 0 && $resp['profiles'] == 0){

                /**
                 * If no more scrapping make matching
                 */
                $resp = $this->makeMatching($makeMatching,$indirect);

                if ($resp['fetched'] == 0 && $resp['profiles'] == 0){

                    /**
                     * If no more matching, start updating prices
                     */
                    $resp = $this->updatePrices($updatePrices,$indirect);

                    if ($resp['total'] == 0 && $resp['active'] == 0 && empty($resp['productData']) && empty($resp['prices'])){

                        $resp['status'] = 201;
                        $resp['message'] = 'We halt for a bit. Everything is fresh!';
                    }
                }
            }
        }

//        var_dump($resp);

//       $resp = $service->run();
        $time_elapsed_secs = microtime(true) - $start;
        $resp['executionTime'] = $time_elapsed_secs;
        return new JsonResponse($resp);
    }

    /**
     * @Route("/process-profiles", name="brain_process_profiles")
     */
    public function processProfiles(ProcessProfiles $service,&$indirect = false)
    {


       $resp = $service->run();

       if ($indirect){
           return $resp;
       }

       return new JsonResponse($resp);
    }

    /**
     * @Route("/match-profiles", name="brain_match_profiles")
     * @param MatchProfiles $service
     * @param bool $indirect
     * @return array|JsonResponse
     */
    public function matchProfiles(MatchProfiles $service,&$indirect = false)
    {


        $resp = $service->run();

        if ($indirect){
            return $resp;
        }

        return new JsonResponse($resp);
    }

    /**
     * @Route("/make-matching", name="brain_make_matching")
     * @param MakeMatching $service
     */
    public function makeMatching(MakeMatching $service,&$indirect = false)
    {


        $resp = $service->run();

        if ($indirect){
            return $resp;
        }

        return new JsonResponse($resp);
    }

    /**
     * @Route("/update-prices", name="brain_update_prices")
     * @param UpdatePrices $service
     */
    public function updatePrices(UpdatePrices $service,&$indirect = false)
    {

        $resp = $service->run();

        if ($indirect){
            return $resp;
        }

        return new JsonResponse($resp);
    }
}

<?php /** @noinspection PhpFieldAssignmentTypeMismatchInspection */

namespace App\Controller;

use App\Entity\UserData;
use App\Repository\UserDataRepository;
use App\Service\Api\Fetch;
use App\Service\Api\FetchShops;
use App\Service\Api\Save;
use App\Service\Api\Search;
use App\Service\Api\ShopSuggest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class ApiController extends AbstractController
{

    /**
     * @var JsonResponse $response
     */
    public JsonResponse $response;

    /**
     * @var UserData|null $userData
     */
    public $userData;

    public function __construct(UserDataRepository $userDataRepository,RequestStack $requestStack)
    {

        $token = $requestStack->getCurrentRequest()->headers->get('Authorization');

        $this->userData = $userDataRepository->findOneBy(['ApiKey' => $token]);
        if (!$this->userData) {
//            die(json_encode(['status' => 403, 'info' => 'Wrong Api Key.']));
        }
    }

    /**
     * @Route("/", name="api_index")
     */
    public function index()
    {
        return $this->render('api/index.html.twig', [
            'controller_name' => 'ApiController',
        ]);
    }

    /**
     * @Route("/shop/suggest", name="api_shop_suggest")
     * @param ShopSuggest $service
     * @return JsonResponse
     */
    public function shopSuggest(ShopSuggest $service): JsonResponse
    {

        $this->response = new JsonResponse($service->run());

        return $this->prepare();
    }

    /**
     * @Route("/search", name="api_search")
     * @param Search $service
     * @return Response
     */
    public function search(Search $service,Request $request)
    {

        $resp = $service->run($request);

        return new JsonResponse($resp);
    }

    /**
     * @Route("/fetch", name="api_fetch")
     * @param Fetch $service
     * @return Response
     */
    public function fetch(Fetch $service,Request $request)
    {

        $resp = $service->run($request);

        return new JsonResponse($resp);
    }

    /**
     * @Route("/fetch-shops", name="api_fetch_shops")
     * @param FetchShops $service
     * @return Response
     */
    public function fetchShops(FetchShops $service,Request $request)
    {

        $resp = $service->run($request);

        return new JsonResponse($resp);
    }

    /**
     * @Route("/save", name="api_save")
     * @param Save $service
     * @return Response
     */
    public function save(Save $service,Request $request)
    {

        $resp = $service->run($request);

        return new JsonResponse($resp);
    }

    private function prepare(): JsonResponse
    {

        $this->response->headers->add(
            [
                'X-Resource-Limit'=>$this->userData->getMaxRequests(),
                'X-Resource-Current'=>$this->userData->getCurrentRequests(),
                'X-Resource-Update'=>$this->userData->getUpdated(),
            ]
        );

        return $this->response;
    }
}

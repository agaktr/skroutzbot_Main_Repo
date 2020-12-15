<?php

namespace App\Controller;

use App\Entity\Price;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Form\UserProfileType;
use App\Repository\UserProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/user-profile")
 */
class UserProfileController extends AbstractController
{

    private $profileInfo = [];
    private $productUuids = [];
    private $profileUsers = [];
    private $products = [];
    private $prices = [];
    private EntityManagerInterface $em;

    /**
     * @Route("/", name="user_profile_index", methods={"GET"})
     * @param EntityManagerInterface $em
     * @param UserProfileRepository $userProfileRepository
     * @return Response
     */
    public function index(EntityManagerInterface $em,UserProfileRepository $userProfileRepository): Response
    {
        $this->em = $em;

        if ($this->isGranted('ROLE_ADMIN')){

            $userProfiles = $userProfileRepository->findAll();
        }else{

            $userProfiles = $userProfileRepository->findBy(['UserUuid'=>$this->getUser()->getUuid()]);
        }

        if ($userProfiles){

            $this->calculateProfilesInfo($userProfiles,$getProducts);
        }

        return $this->render('user_profile/index.html.twig', [
            'user_profiles' => $userProfiles,
            'profiles_info' => $this->profileInfo,
            'profile_users' => $this->profileUsers,
        ]);
    }

    /**
     * @Route("/get-products", name="user_profile_get_products")
     */
    public function getProducts(EntityManagerInterface $em)
    {

        $this->em = $em;
        /** @var UserProfile $userProfile */
        $userProfile = $this->em->getRepository(UserProfile::class)->findOneBy(['uuid'=>$_GET['profile']]);
        $getProducts = true;
        $this->calculateProfilesInfo([$userProfile],$getProducts);

        $this->matchProfileProducts($userProfile);

//        var_dump($userProfile->getProducts());

        $respItems = [];
//        $xml = new SimpleXMLElement('<xml/>');
//
        foreach ($userProfile->getProducts() as $shopSku=>$product){

            $theProduct = reset($product["Product"]);
            if (is_bool($theProduct)){
                continue;
            }
            $theProduct = $theProduct['product'];

            $bestPrice = reset($product["Product"])['prices'];
            if (is_int($bestPrice)){
                continue;
            }
            $priceCount = count(reset($product["Product"])['prices']);
            $bestPrice = reset($product["Product"])['prices'][0];

//            var_dump($theProduct);
//            var_dump($bestPrice);

            $respItems[$shopSku] = [
                'sku'=> $shopSku,
                'photo'=> '<img src="'.$theProduct->getPhoto().'" height="100">',
                'name'=> $product['ShopItem']['Title'],
                'mpn'=> $product['ShopItem']['MPN'],
                'ean'=> $product['ShopItem']['EAN'],
                'matched'=> '<a href="'.$theProduct->getUrl().'" target="_blank" data-product-name="'.$theProduct->getName().'" data-product="'.$theProduct->getUuid().'">'.$theProduct->getName().'</a>',
                'competitors'=> $priceCount,
                'price'=> $bestPrice->getNetPrice(),
                'update'=> $theProduct->getUpdated()
            ];

//            var_dump($shopSku);
//            var_dump($product);
//            die();


//            $recPrice = $bestPrice->getNetPrice() - 0.01;
//            $track = $xml->addChild('item');
//            $track->addChild('SKU', $shopSku);
//            $track->addChild('Title', $product['ShopItem']['Title']);
//            $track->addChild('MPN', $product['ShopItem']['MPN']);
//            $track->addChild('EAN', $product['ShopItem']['EAN']);
//            $track->addChild('ExternalUrl', $theProduct->getUrl());
//            $track->addChild('LowestPrice', $bestPrice->getNetPrice());
//            $track->addChild('RecommendedPrice', $recPrice);
        }
//
//        $filename = 'uploads/generated/'.str_replace(' ','+',$userProfile->getName()).'-'.$userProfile->getUuid().'.xml';
//        $res = $xml->asXML($filename);
////        Header('Content-type: text/xml');
////        print($xml->asXML('uploads/generated/test.xml'));
//
////        var_dump($res);

        return new JsonResponse($respItems);
//        return $this->redirectToRoute('user_profile_index');
    }


    /**
     * @Route("/download", name="user_profile_download")
     */
    public function download(Request $request,EntityManagerInterface $em): Response
    {

        $this->em = $em;
        /** @var UserProfile $userProfile */
        $userProfile = $this->em->getRepository(UserProfile::class)->findOneBy(['uuid'=>$_GET['profile']]);
        $getProducts = true;
        $this->calculateProfilesInfo([$userProfile],$getProducts);

        $this->matchProfileProducts($userProfile);

        $xml = new SimpleXMLElement('<xml/>');

        foreach ($userProfile->getProducts() as $shopSku=>$product){
//            var_dump($shopSku);
//            var_dump($product["Product"]);
//            die();

            $theProduct = reset($product["Product"]);
            if (is_bool($theProduct)){
                continue;
            }
            $theProduct = $theProduct['product'];
//            var_dump(reset($product["Product"])['prices']);

            $bestPrice = reset($product["Product"])['prices'];
            if (is_int($bestPrice)){
                continue;
            }
            $bestPrice = reset($product["Product"])['prices'][0];

            $recPrice = $bestPrice->getNetPrice() - 0.01;
            $track = $xml->addChild('item');
            $track->addChild('SKU', $shopSku);
            $track->addChild('Title', $product['ShopItem']['Title']);
            $track->addChild('MPN', $product['ShopItem']['MPN']);
            $track->addChild('EAN', $product['ShopItem']['EAN']);
            $track->addChild('ExternalUrl', $theProduct->getUrl());
            $track->addChild('LowestPrice', $bestPrice->getNetPrice());
            $track->addChild('RecommendedPrice', $recPrice);
        }

        $filename = 'uploads/generated/'.str_replace(' ','+',$userProfile->getName()).'-'.$userProfile->getUuid().'.xml';
        $res = $xml->asXML($filename);
//        Header('Content-type: text/xml');
//        print($xml->asXML('uploads/generated/test.xml'));

//        var_dump($res);

        return new JsonResponse(['status'=>200,'filename'=>$filename]);
//        return $this->redirectToRoute('user_profile_index');
    }

    /**
     * @Route("/new", name="user_profile_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $userProfile = new UserProfile();
        $form = $this->createForm(UserProfileType::class, $userProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($userProfile);
            $entityManager->flush();

            return $this->redirectToRoute('user_profile_index');
        }


        return $this->render('user_profile/new.html.twig', [
            'user_profile' => $userProfile,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/save", name="user_profile_save", methods={"POST"})
     */
    public function save(Request $request): Response
    {

        $fileResp = $this->uploadFile($_FILES['csvInput']);

        $userProfile = new UserProfile();

        $name = 'Profile '.uniqid();
        if (isset($_POST['name']) && $_POST['name'] !== ''){
            $name = $_POST['name'];
        }
        $userProfile->setName($name);
        $userProfile->setUserUuid($this->getUser()->getUuid());
        $userProfile->setCsvUrl('/'.$fileResp['src']);
        $userProfile->setItemsNumber(0);
        $userProfile->setProducts([]);
        $userProfile->setItemsProcessed(0);
        $userProfile->setIsDone(0);
        $competitors = [];
        if (isset($_POST['shopCheckbox'])){
            $competitors = $_POST['shopCheckbox'];
        }
        $userProfile->setCompetitors($competitors);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($userProfile);
        $entityManager->flush();

        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();
        $serializer = new Serializer(array($normalizer), array($encoder));

        $responseArr = $serializer->serialize($userProfile, 'json');
        $responseArr = json_decode($responseArr,true);
        $resp = [
            'status' => 200,
            'userProfile'  => $responseArr,
        ];

        return new JsonResponse($resp);
//
//            return $this->redirectToRoute('user_profile_index');
//        var_dump($fileResp);
//        var_dump($_POST);
//        var_dump($_REQUEST);
//        var_dump($_GET);



    }

    /**
     * @Route("/new_back", name="user_profile_new_back", methods={"GET","POST"})
     */
    public function new_back(Request $request): Response
    {
        $userProfile = new UserProfile();
        $form = $this->createForm(UserProfileType::class, $userProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($userProfile);
            $entityManager->flush();

            return $this->redirectToRoute('user_profile_index');
        }


        return $this->render('user_profile/new.back.html.twig', [
            'user_profile' => $userProfile,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{uuid}", name="user_profile_show", methods={"GET"})
     */
    public function show(UserProfile $userProfile,EntityManagerInterface $em): Response
    {

        $this->em = $em;

        /**
         * Calculate products info
         */
        $getProducts = true;
        $this->calculateProfilesInfo([$userProfile],$getProducts);

        $this->matchProfileProducts($userProfile);


//        $products = $em->getRepository(Product::class)->

        return $this->render('user_profile/show.html.twig', [
            'user_profile' => $userProfile,
        ]);
    }

    /**
     * @Route("/{uuid}/edit", name="user_profile_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, UserProfile $userProfile): Response
    {
        $form = $this->createForm(UserProfileType::class, $userProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_profile_index');
        }

        return $this->render('user_profile/edit.html.twig', [
            'user_profile' => $userProfile,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{uuid}/match", name="user_profile_match", methods={"GET","POST"})
     */
    public function match(Request $request, UserProfile $userProfile,EntityManagerInterface $em): Response
    {
        $form = $this->createForm(UserProfileType::class, $userProfile);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $this->getDoctrine()->getManager()->flush();
//
//            return $this->redirectToRoute('user_profile_index');
//        }


        $this->em = $em;

        /**
         * Calculate products info
         */
        $getProducts = true;
        $this->calculateProfilesInfo([$userProfile],$getProducts);

        $this->matchProfileProducts($userProfile);

//        var_dump($this->profileInfo);
//        var_dump($this->profileUsers);

        return $this->render('user_profile/match.html.twig', [
            'user_profile' => $userProfile,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/match", name="user_profile_make_match", methods={"GET","POST"})
     */
    public function makeMatch(Request $request,EntityManagerInterface $em): Response
    {

        parse_str($request->request->get('match'),$match);

        /** @var UserProfile $profile */
        $profile = $em->getRepository(UserProfile::class)->findOneBy(['uuid'=>$match['profile']]);

        if ($profile){

            $products = $profile->getProducts();
            $products[$match['sku']]['Product'] = [$match['product']];
            $profile->setProducts($products);

            $em->flush();
        }
//        var_dump($profile->getProducts());
//var_dump($match);
//var_dump($_POST);
        return new JsonResponse(["status"=>200]);
    }

    /**
     * @Route("/{uuid}", name="user_profile_delete", methods={"DELETE"})
     */
    public function delete(Request $request, UserProfile $userProfile): Response
    {
        if ($this->isCsrfTokenValid('delete'.$userProfile->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($userProfile);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_profile_index');
    }



    private function matchProfileProducts(UserProfile $userProfile){
//var_dump($this->products);
        $profileProducts = $userProfile->getProducts();
        foreach ($profileProducts as $shopSku=>$data){

            if (isset($this->products[$userProfile->getUuid()->toString()][$shopSku])){

                $profileProducts[$shopSku]['Product'] = $this->products[$userProfile->getUuid()->toString()][$shopSku];
            }else{
                $profileProducts[$shopSku]['Product'] = [];
            }

            if (is_array($profileProducts[$shopSku]['ShopItem']['EAN'])){

                $profileProducts[$shopSku]['ShopItem']['EAN'] = 'N/A';
            }

            if (is_array($profileProducts[$shopSku]['ShopItem']['Title'])){
                $profileProducts[$shopSku]['ShopItem']['Title'] = 'N/A';
            }
        }
//        var_dump($profileProducts);
        $userProfile->setProducts($profileProducts);
    }

    private function calculateProfilesInfo($profiles,&$getProducts = false){

        $this->profileInfo = [];
        $this->productUuids = [];
        $this->profileUsers = [];

        foreach ($profiles as $userProfile){

            $this->profileInfo[$userProfile->getUuid()->toString()] = [
                'solo'=>0,
                'multiple'=>0,
                'empty'=>0,
                'fetched'=>0,
                'unfetched'=>0,
            ];

            $this->profileUsers[$userProfile->getUserUuid()->toString()] = '';

            if ($getProducts) {

                $this->productUuids[$userProfile->getUuid()->toString()] = [];
            }

            foreach ($userProfile->getProducts() as $shopSku => $item){

                if (is_array($item['Product'])){

                    if (count($item['Product']) == 1){
                        ++$this->profileInfo[$userProfile->getUuid()->toString()]["solo"];
                    }else{
                        ++$this->profileInfo[$userProfile->getUuid()->toString()]["multiple"];
                    }

                    if ($getProducts) {

                        foreach ($item['Product'] as $pid) {

                            if (!is_string($pid)){
                                $pid = $pid->toString();
                            }
                            $this->productUuids[$userProfile->getUuid()->toString()][$shopSku][] = $pid;
                        }
                    }
                }else{

                    switch ($item['Product']){

                        case 'not-found':
                            ++$this->profileInfo[$userProfile->getUuid()->toString()]["empty"];
                            break;
                        case 'fetched':
                            ++$this->profileInfo[$userProfile->getUuid()->toString()]["fetched"];
                            break;
                        case -1:
                            ++$this->profileInfo[$userProfile->getUuid()->toString()]["unfetched"];
                            break;
                    }
                }
            }

            /**
             * Get actual products and prices
             */
            if ($getProducts) {

                $productPrices = $profileProducts = [];

                $ids = [];
                foreach ($this->productUuids[$userProfile->getUuid()->toString()] as $shopSku=>$prods){
                    foreach ($prods as $prod){
                        $ids[] = $prod;
                    }
                }

                $profileProductsRes = $this->em->getRepository(Product::class)->findByUUids($ids);
                foreach ($profileProductsRes as $prod){
                    $profileProducts[$prod->getUuid()->toString()] = $prod;
                }
                $productPricesRes = $this->em->getRepository(Price::class)->findByProductUUids($ids);
                foreach ($productPricesRes as $price){
                    $productPrices[$price->getProductUuid()->toString()][] = $price;
                }


                foreach ($this->productUuids[$userProfile->getUuid()->toString()] as $shopSku=>$prods){

                    foreach ($prods as $prod){

                        $this->products[$userProfile->getUuid()->toString()][$shopSku][$prod]['product'] = $profileProducts[$prod];
                        if (isset($productPrices[$prod])){
                            $prices = $productPrices[$prod];
                        }else{
                            $prices = -1;
                        }
                        $this->products[$userProfile->getUuid()->toString()][$shopSku][$prod]['prices'] = $prices;
                    }
                }
            }
        }

        /**
         * Get profile users
         */
        $usersRes = $this->em->getRepository(User::class)->findByUuids(array_keys($this->profileUsers));
        foreach ($usersRes as $user){

            if (!is_string($user->getUuid())){
                $user->setUuid($user->getUuid()->toString());
            }
            $this->profileUsers[$user->getUuid()] = $user;
        }

//        var_dump($this->profileUsers);
//        var_dump($this->profileInfo);
//        var_dump($this->products);
    }

    private function uploadFile($file){


        $filename = $file['name'];

        $filesize = $file['size'];

        $uid = uniqid();

        $location = "uploads/csv/".$uid."-".$filename;

        $return_arr = array();

        if(move_uploaded_file($_FILES['csvInput']['tmp_name'],$location)){

            $return_arr = array("name" => $filename,"size" => $filesize, "src"=> $location);
        }

        return $return_arr;
    }
}

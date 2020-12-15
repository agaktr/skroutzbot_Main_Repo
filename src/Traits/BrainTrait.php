<?php
namespace App\Traits;

use App\Entity\UserData;
use App\Repository\ProductRepository;
use App\Repository\ProfileRawDataRepository;
use App\Repository\UserDataRepository;
use App\Repository\UserProfileRepository;
use App\Service\CurlHandler;
use App\Service\Parser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

trait BrainTrait{

    /**
     * @var Parser $parser
     */
    private Parser $parser;

    /**
     * @var Request $request
     */
    private Request $request;

    /**
     * @var $options
     */
    private array $options = [];

    /**
     * @var CurlHandler $curlHandler
     */
    private CurlHandler $curlHandler;

    private EntityManagerInterface $em;
    private UserProfileRepository $userProfileRepository;
    private UserDataRepository $userDataRepository;
    private ProfileRawDataRepository $profileRawDataRepository;
    private ProductRepository $productRepository;
    private $html;
    private array $externalIds;

    public function makeCurl($urls){

        $this->options['CURLOPT_HTTPHEADER'][] = 'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9
accept-language: en-US,en;q=0.9
cache-control: max-age=0
cookie: logged_in=true; policy_level=%7B%22essential%22%3A%22true%22%2C%22performance%22%3A%22true%22%2C%22preference%22%3A%22true%22%2C%22targeting%22%3A%22true%22%7D; _ga=GA1.2.531425810.1600784437; _hjid=27243b72-8f2f-4835-a5e2-930dd04f1831; _fbp=fb.1.1602261575067.236721593; __skr_nltcs_ss=%7B%22version%22%3A1%2C%22session%22%3A%227fdf35dd-14f6-4741-8113-3e1db330e2dd%22%7D; _gid=GA1.2.1744073845.1607941871; _hjIncludedInSessionSample=1; _hjTLDTest=1; _hjAbsoluteSessionInProgress=1; retat=84.254.4.129x1608023535-6264d613fa4d5cb5b80af7ea5918fde95f6c9ed5; _helmet_couch=c2FNVzVsK1NZTVhCcEtCTzlyZTF0TnFMY3czbjZsbnFFcjhxWkpOdEV3Tm1lOUJNK2cyMkVGU2wrK2ZVQ2JDaXRJcVkxZkY1OVB3YldHTjBmZDRRUTNKZ3VTcnBxbjQxMFZsVXlrQnJkTkNRdXd2eVNIczBkLzRvQXVNbkxQbjROdDFoZzhpc1cyaTZQT1F2QVVaVzVGdmxKdVEyZkV3OE0vSHlZRWFkMEUvMDczSThBS3BWS2hMSXgrWFlCdTFESW5TWkxnY1p2R1l2elZiSkxxTE81bHZtZ0hzZU5JeXdJbnFCSWJVSHI2K045eW80b0dLS0pFUUU3WUlySHR5NmpWTUpvL0loM1p4b1VRQ0Npck9tZnhnZGFicXdDdUh1UFlPbWIwSTRvcVR6TldJQ0IrdTBNaE0ybXkxMlpjYk9yMGpsUHkyd1crbXhST1poK09zaUJZdldETGtwSzg5U0k5QWZGOVF1RXJHcjVnQW9CUzlqWmMydkVKcGdkNzJWeEk3alJwNXpydDRWb2RyWjdOZk5qRUw3cmpqbmFMVCtUQXo3aHNlb3VNT3dQR25XVTQvS0FXRlF4dmFMVWloZUZOeGx1UGVsWTk3ZHlpNlIxSStDYjY3VmZlMjMvKzFRTzFrRXh1WmtkTzdUN0tPaWtxSXdGMVBVcWtOU0l2d2RhSTgrbld3dVZKWmY5TnpsT2owa1diWkhBcDMzQnFiVlBOUGlTNEgyTHprV2lQRGdBWURjTmdwQWc0Y01HMnlaOW15S0RQTDQ3NUpMbGJ5eGZTa3l0S1l0aDlHT2pNdW80ZDA5RzMwbjNORWdoTUtDeU1EZlZwckpyTHhrNGtLeXR2S0RpTDZmRVUwMWRYWkl3Rk1reWkxTlp5U1Y4NTRlc2pwd1ZUYVlIUGlLSnI5YU9EdEJVUmxPbXhQVmR6M21QU1RmSkRUNWdGVDhyZ3gvUzNkWnN0b2NYdVZpbmN0OHlwOE1vS3VlL0piMUhBMVNRU29jNlk4Yjd6em05LzU1MWg2UFE4TkdxOE1DNDBjOWpNaGtZdU9WQUJ5STYwNTdNdmw4TlNCVStaOE9YaFhBaDk5UHVXRGtPNEkyVDkzMml3ZHF4bTVDenNGeTM1WmFJV0t1SmZrWFpZMXBRUlNnRUxtQXB5UDVHbS9zaEYwZDFhb3NaR3NYRFVBM3pib1dBdm44VHZpVTFZa0FWNGxDK0R4UnRadFhXTnVFaVphN2xLZk1rei81dlc0WU0wWHdsRlNRa3Vpbkd5ZVBnTDhyUGZTRmgwa2s2V1VTZUtQQlkxSnZpSi8ybGVwd1JnQmVwczB1NG1GVWFtVHIxY1pzalVNbmM1aTlsazZCb3FpZjdaK1FWSUhvUXFFcytQcU1EdW5HY0pia1M4VTZqRklhSVBJcTN2azUwWmw5TWd2Rk11SmlGYWwrZ2w4VFk3NERVbDBFV0NZV1Z6OGhYVklscjZLdzNoWkNMcUZaQ3JucXdGNWtZNHNSMzQreDBxT2sxdHIzZ3V2SmhDb2tidEN0TFFlRmwrQzBsOXdOenRNY0Q4d0NOQT09LS1Yb0k1dkcvVkVGei9rUjBFVDczbG1BPT0%3D--d4804f8dea6ba0c26ef003cc3ecbe91728838a2d
if-none-match: W/"6f42d74f3cace217910478f26fa26584"
referer: https://www.skroutz.gr/m
sec-fetch-dest: document
sec-fetch-mode: navigate
sec-fetch-site: same-origin
sec-fetch-user: ?1
upgrade-insecure-requests: 1
user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36';
//        var_dump($this->options);
        $content = $this->curlHandler->multiRequest($urls,$this->options);

        return $content;
    }
}
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class NinauthController extends AbstractController
{
    /**
     * @Route("/ninauth", name="ninauth")
     */
    public function index()
    {
        return $this->render('ninauth/index.html.twig', [
            'controller_name' => 'NinauthController',
        ]);
    }
}

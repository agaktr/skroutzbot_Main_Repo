<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
  * Require ROLE_USER for *every* controller method in this class.
  *
  * @IsGranted("ROLE_USER")
  * @Route("/dashboard")
  */
class DashboardController extends AbstractController
{
    /**
     * @Route("/", name="dashboard_index")
     */
    public function index()
    {

        $user = $this->getUser();
       var_dump($user);

        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }

    /**
     * @Route("/steps", name="dashboard_steps")
     */
    public function steps()
    {

        return $this->render('dashboard/steps.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }

    /**
     * @Route("/csv", name="dashboard_csv")
     */
    public function csv()
    {

        return $this->render('dashboard/csv.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }
}

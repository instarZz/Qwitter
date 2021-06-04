<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QwitterController extends AbstractController
{
    #[Route('/', name: 'qwitter')]
    public function index(): Response
    {
        return $this->render('qwitter/index.html.twig', [
            'controller_name' => 'QwitterController',
        ]);
    }
}

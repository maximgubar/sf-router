<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    public function index(string $slug): Response
    {
        return new Response('<h1>HUY</h1>' . $slug);
    }
}

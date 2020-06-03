<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends AbstractController
{
    public function index($categoryId): Response
    {
        return new Response(sprintf('<h1>%s</h1><h3>%s</h3>', $categoryId, __METHOD__));
    }
}

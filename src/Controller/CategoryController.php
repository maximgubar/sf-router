<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends AbstractController
{
    public function index(Request $request, $entityId): Response
    {
        dump($entityId, $request->getLocale(), $request->getPathInfo());
        return $this->render('base.html.twig');
//        return new Response(sprintf('<h1>%s</h1><h3>%s</h3>', $entityId, __METHOD__));
    }
}

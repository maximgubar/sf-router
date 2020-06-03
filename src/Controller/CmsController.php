<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CmsController extends AbstractController
{
    public function index($entityId): Response
    {
        return new Response(sprintf('<h1>%s</h1><h3>%s</h3>', $entityId, __METHOD__));
    }
}

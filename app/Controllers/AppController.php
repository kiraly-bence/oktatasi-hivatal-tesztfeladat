<?php

declare(strict_types=1);

namespace App\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AppController
{
    public function index(Request $request, array $data): Response
    {
        $html = file_get_contents(__DIR__ . '/../../resources/views/App/index.php');
        
        return new Response($html);
    }
}
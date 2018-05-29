<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UploadController
 * @package App\Controller
 */
class UploadController extends Controller
{
    /**
     * @param Request $request
     */
    public function index(Request $request)
    {
        $file = $request->files->get('upload');
        $fileName = md5(uniqid()) . '.' . $file->guessExtension();
        $file->move($this->container->getParameter('env(UPLOAD_ROOT)'), $fileName);

        return JsonResponse::create([
            'uploaded' => 1,
            'fileName' => $fileName,
            'url' => $this->container->getParameter('app.path.upload') . '/' . $fileName
        ], 200);
    }
}

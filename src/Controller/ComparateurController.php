<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class ComparateurController extends AbstractController
{
    #[Route('/comparateur', name: 'app_comparateur')]
    public function index(Request $request): Response
    {
        $marque = $request->query->get('marque');
        $modelId = $request->query->get('modelId');

        $marquesValides = ['xiaomi', 'honor'];
        $data = null;
        $modelData = null;

        if ($marque && in_array($marque, $marquesValides)) {
            $jsonFilePath = $this->getParameter('kernel.project_dir') . "/public/json/{$marque}.json";
            $jsonContent = file_get_contents($jsonFilePath);
            $data = json_decode($jsonContent, true);

            if ($modelId) {
                foreach ($data['models'] as $model) {
                    if ($model['id'] === $modelId) {
                        $modelData = $model;
                        break;
                    }
                }
            }
        }

        return $this->render('comparateur/index.html.twig', [
            'marques' => $marquesValides,
            'models' => $data['models'] ?? [],
            'modelData' => $modelData,
            'selectedMarque' => $marque,
            'selectedModelId' => $modelId,
        ]);
    }
}

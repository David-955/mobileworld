<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class ComparatorController extends AbstractController
{
    #[Route('/comparator', name: 'app_comparator')]
    public function index(Request $request): Response
    {
        $marque1 = $request->query->get('marque1');
        $modelId1 = $request->query->get('modelId1');
        $marque2 = $request->query->get('marque2');
        $modelId2 = $request->query->get('modelId2');

        $marquesValides = ['apple', 'samsung', 'google', 'xiaomi', 'honor', 'oneplus','sony'];
        $data1 = null;
        $modelData1 = null;
        $data2 = null;
        $modelData2 = null;

        if ($marque1 && in_array($marque1, $marquesValides)) {
            $jsonFilePath1 = $this->getParameter('kernel.project_dir') . "/public/json/{$marque1}.json";
            $jsonContent1 = file_get_contents($jsonFilePath1);
            
            $data1 = json_decode($jsonContent1, true);

            if ($modelId1) {
                foreach ($data1['models'] as $model1) {
                    if ($model1['id'] === $modelId1) {
                        $modelData1 = $model1;
                        break;
                    }
                }
            }
        }

        if ($marque2 && in_array($marque2, $marquesValides)) {
            $jsonFilePath2 = $this->getParameter('kernel.project_dir') . "/public/json/{$marque2}.json";
            $jsonContent2 = file_get_contents($jsonFilePath2);
            $data2 = json_decode($jsonContent2, true);
             // var_dump(json_decode($jsonContent2, true));

            if ($modelId2) {
                foreach ($data2['models'] as $model2) {
                    if ($model2['id'] === $modelId2) {
                        $modelData2 = $model2;
                        break;
                    }
                }
            }
        }

        return $this->render('comparator/index.html.twig', [
            'marques' => $marquesValides,
            'models1' => $data1['models'] ?? [],
            'modelData1' => $modelData1,
            'selectedMarque1' => $marque1,
            'selectedModelId1' => $modelId1,
            'models2' => $data2['models'] ?? [],
            'modelData2' => $modelData2,
            'selectedMarque2' => $marque2,
            'selectedModelId2' => $modelId2,
        ]);
    }
}
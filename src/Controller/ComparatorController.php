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
        $brand1 = $request->query->get('brand1');
        $modelId1 = $request->query->get('modelId1');
        $brand2 = $request->query->get('brand2');
        $modelId2 = $request->query->get('modelId2');

        $brandsValides = ['apple', 'samsung', 'google', 'xiaomi', 'honor', 'oneplus','sony'];
        $data1 = null;
        $modelData1 = null;
        $data2 = null;
        $modelData2 = null;

        if ($brand1 && in_array($brand1, $brandsValides)) {
            $jsonFilePath1 = $this->getParameter('kernel.project_dir') . "/public/json/{$brand1}.json";
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

        if ($brand2 && in_array($brand2, $brandsValides)) {
            $jsonFilePath2 = $this->getParameter('kernel.project_dir') . "/public/json/{$brand2}.json";
            $jsonContent2 = file_get_contents($jsonFilePath2);
            $data2 = json_decode($jsonContent2, true);

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
            'brands' => $brandsValides,
            'models1' => $data1['models'] ?? [],
            'modelData1' => $modelData1,
            'selectedbrand1' => $brand1,
            'selectedModelId1' => $modelId1,
            'models2' => $data2['models'] ?? [],
            'modelData2' => $modelData2,
            'selectedbrand2' => $brand2,
            'selectedModelId2' => $modelId2,
        ]);
    }
}
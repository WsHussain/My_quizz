<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategorieController extends AbstractController 
{
    #[Route('/categories', name: 'app_categories')]
    public function index(CategorieRepository $categorieRepository): Response
    {
        return $this->render('category/index.html.twig', [
            'categories' => $categorieRepository->findAll(),
        ]);
    }

    #[Route('/categorie/{id}', name: 'app_categorie_show')]
    public function show(int $id, CategorieRepository $categorieRepository): Response
    {
        $categorie = $categorieRepository->find($id);
        
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        return $this->render('category/show.html.twig', [
            'categorie' => $categorie,
            'questions' => $categorie->getQuestions(),
        ]);
    }
}

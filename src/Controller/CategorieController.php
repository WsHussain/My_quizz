<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Reponse;
use App\Entity\QuizResult;
use App\Repository\CategorieRepository;
use App\Repository\ReponseRepository;
use App\Repository\QuizResultRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategorieController extends AbstractController 
{
    #[Route('/categories', name: 'app_categories')]
    public function index(CategorieRepository $categorieRepository, QuizResultRepository $quizResultRepository): Response
    {
        $categories = $categorieRepository->findAll();
        $results = [];
        
        if ($this->getUser()) {
            foreach ($categories as $categorie) {
                $result = $quizResultRepository->findOneBy([
                    'user' => $this->getUser(),
                    'categorie' => $categorie
                ]);
                if ($result) {
                    $results[$categorie->getId()] = $result;
                }
            }
        }

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
            'results' => $results
        ]);
    }

    #[Route('/categorie/{id}', name: 'app_categorie_show')]
    public function show(int $id, Request $request, CategorieRepository $categorieRepository, QuizResultRepository $quizResultRepository): Response
    {
        $categorie = $categorieRepository->find($id);
        
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        $previousResult = null;
        if ($this->getUser()) {
            $previousResult = $quizResultRepository->findOneBy([
                'user' => $this->getUser(),
                'categorie' => $categorie
            ]);
        }

        return $this->render('category/show.html.twig', [
            'categorie' => $categorie,
            'questions' => $categorie->getQuestions(),
            'previousResult' => $previousResult
        ]);
    }

    #[Route('/categorie/{id}/submit', name: 'app_submit_answers', methods: ['POST'])]
    public function submitAnswers(
        int $id, 
        Request $request, 
        CategorieRepository $categorieRepository, 
        ReponseRepository $reponseRepository,
        QuizResultRepository $quizResultRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $categorie = $categorieRepository->find($id);
        
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        $reponses = $request->request->all('reponses');
        $score = 0;
        $questions = $categorie->getQuestions();
        $total_questions = count($questions);

        foreach ($questions as $question) {
            $questionId = $question->getId();
            
            if (!isset($reponses[$questionId])) {
                $this->addFlash('feedback_' . $questionId, '✗ Veuillez sélectionner une réponse');
                continue;
            }

            $reponse = $reponseRepository->find($reponses[$questionId]);
            
            if (!$reponse) {
                continue;
            }

            if ($reponse->isReponseExpected()) {
                $score++;
                $this->addFlash('feedback_' . $questionId, '✓ Bonne réponse !');
            } else {
                $this->addFlash('feedback_' . $questionId, '✗ Mauvaise réponse');
            }
        }

        $previousResult = null;
        if ($this->getUser()) {
            $previousResult = $quizResultRepository->findOneBy([
                'user' => $this->getUser(),
                'categorie' => $categorie
            ]);

            if (!$previousResult) {
                $previousResult = new QuizResult();
                $previousResult->setUser($this->getUser());
                $previousResult->setCategorie($categorie);
            }

            $previousResult->setResult($score);
            $previousResult->setTotalQuestions($total_questions);
            $entityManager->persist($previousResult);
            $entityManager->flush();
        }

        if (!$this->getUser()) {
            $session = $request->getSession();
            $localResults = $session->get('quiz_results', []);
            $localResults[$categorie->getId()] = [
                'score' => $score,
                'total' => $total_questions,
                'date' => new \DateTime()
            ];
            $session->set('quiz_results', $localResults);
        }

        return $this->render('category/show.html.twig', [
            'categorie' => $categorie,
            'questions' => $questions,
            'score' => $score,
            'total_questions' => $total_questions,
            'previousResult' => $previousResult
        ]);
    }
}

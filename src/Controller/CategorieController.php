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
    #[Route('/', name: 'app_categories')]
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
    public function show(int $id, Request $request, CategorieRepository $categorieRepository, QuizResultRepository $quizResultRepository, EntityManagerInterface $entityManager): Response
    {
        $categorie = $categorieRepository->find($id);
        
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        $questions = $categorie->getQuestions()->toArray();
        $session = $request->getSession();
        
        $previousResult = null;
        if ($this->getUser()) {
            $previousResult = $quizResultRepository->findOneBy([
                'user' => $this->getUser(),
                'categorie' => $categorie
            ]);
        }
        
        $currentQuestionIndex = $session->get('current_question_index_' . $id, 0);
        $previousAnswers = $session->get('previous_answers_' . $id, []);
        
        if ($currentQuestionIndex >= count($questions)) {
            $totalQuestions = count($questions);
            $correctAnswers = array_sum(array_map(function($answer) {
                return $answer['correct'] ? 1 : 0;
            }, $previousAnswers));
            
            if ($this->getUser()) {
                $existingResult = $previousResult;
                
                if (!$existingResult || $correctAnswers > $existingResult->getResult()) {
                    if (!$existingResult) {
                        $existingResult = new QuizResult();
                        $existingResult->setUser($this->getUser());
                        $existingResult->setCategorie($categorie);
                    }
                    
                    $existingResult->setResult($correctAnswers);
                    $existingResult->setTotalQuestions($totalQuestions);
                    
                    $entityManager->persist($existingResult);
                    $entityManager->flush();
                }
            }
            
            $session->remove('current_question_index_' . $id);
            $session->remove('previous_answers_' . $id);
            
            return $this->render('category/show.html.twig', [
                'categorie' => $categorie,
                'completed' => true,
                'score' => [
                    'correct' => $correctAnswers,
                    'total' => $totalQuestions
                ],
                'previousAnswers' => $previousAnswers,
                'previousResult' => $previousResult
            ]);
        }

        $currentQuestion = $questions[$currentQuestionIndex];

        return $this->render('category/show.html.twig', [
            'categorie' => $categorie,
            'question' => $currentQuestion,
            'questionIndex' => $currentQuestionIndex,
            'totalQuestions' => count($questions),
            'previousResult' => $previousResult,
            'previousAnswers' => $previousAnswers
        ]);
    }

    #[Route('/categorie/{id}/submit', name: 'app_submit_answers', methods: ['POST'])]
    public function submitAnswers(
        int $id, 
        Request $request, 
        CategorieRepository $categorieRepository, 
        ReponseRepository $reponseRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $categorie = $categorieRepository->find($id);
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        $session = $request->getSession();
        $questions = $categorie->getQuestions()->toArray();
        $currentQuestionIndex = $session->get('current_question_index_' . $id, 0);
        $previousAnswers = $session->get('previous_answers_' . $id, []);

        if ($currentQuestionIndex >= count($questions)) {
            return $this->redirectToRoute('app_categorie_show', ['id' => $id]);
        }

        $currentQuestion = $questions[$currentQuestionIndex];
        $selectedAnswerId = $request->request->get('reponse');
        $selectedAnswer = $reponseRepository->find($selectedAnswerId);

        if (!$selectedAnswer || $selectedAnswer->getQuestion() !== $currentQuestion) {
            throw $this->createNotFoundException('Réponse non trouvée');
        }

        $previousAnswers[] = [
            'question' => $currentQuestion->getQuestion(),
            'selectedAnswer' => $selectedAnswer->getReponse(),            'correct' => $selectedAnswer->isReponseExpected(),
            'correctAnswer' => $currentQuestion->getReponses()->filter(function($r) {
                return $r->isReponseExpected();
            })->first()->getReponse()
        ];

        $session->set('current_question_index_' . $id, $currentQuestionIndex + 1);
        $session->set('previous_answers_' . $id, $previousAnswers);

        return $this->redirectToRoute('app_categorie_show', ['id' => $id]);
    }

    #[Route('/categorie/{id}/restart', name: 'app_restart_quiz')]
    public function restartQuiz(int $id, Request $request): Response
    {
        $session = $request->getSession();
        $session->remove('current_question_index_' . $id);
        $session->remove('previous_answers_' . $id);

        return $this->redirectToRoute('app_categorie_show', ['id' => $id]);
    }
}

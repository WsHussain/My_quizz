<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Categorie;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;

#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin_dashboard/index.html.twig', [
            'controller_name' => 'AdminDashboardController',
            'users' => $userRepository->findAll(),
        ]);
    }


    #[Route('/admin/user/{id}/reminder', name: 'connexion_reminder', methods: ['POST'] )]
    public function reminder(User $user, EntityManagerInterface $entityManager, MailerInterface $mailer): Response{
        $email = (new Email())
            ->from(new Address('s.czestkowski@gmail.com', 'Quiz App'))
            ->to($user->getEmail())
            ->subject('Rappel de connexion')
            ->html($this->renderView('emails/reminder.html.twig', [
                'user' => $user,
            ]));
        $mailer->send($email);

        return $this->redirectToRoute('app_admin_dashboard');
    }
    #[Route('/admin/user/{id}/toggle-role/{role}', name: 'app_admin_toggle_role', methods: ['POST'])]
    public function toggleUserRole(
        User $user,
        string $role,
        EntityManagerInterface $entityManager
    ): Response {
        $roles = $user->getRoles();

        // On s'assure que le rôle commence par 'ROLE_'
        $role = strtoupper($role);
        if (!str_starts_with($role, 'ROLE_')) {
            $role = 'ROLE_' . $role;
        }

        // Si l'utilisateur a déjà ce rôle, on le retire
        // Sinon, on l'ajoute
        if (in_array($role, $roles)) {
            $roles = array_diff($roles, [$role]);
        } else {
            $roles[] = $role;
        }

        // On retire ROLE_USER car il est ajouté automatiquement
        $roles = array_diff($roles, ['ROLE_USER']);

        $user->setRoles($roles);
        $entityManager->flush();

        $this->addFlash('success', 'Les rôles de l\'utilisateur ont été mis à jour.');

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/admin/category/add', name: 'app_admin_add_category', methods: ['POST'])]
    public function addCategory(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categoryName = $request->request->get('category_name');
        
        if (!$categoryName) {
            $this->addFlash('error', 'Le nom de la catégorie est requis');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        $category = new Categorie();
        $category->setName($categoryName);
        $category->setAddedBy($this->getUser()->getEmail());

        $entityManager->persist($category);
        $entityManager->flush();

        $this->addFlash('success', 'La catégorie a été créée avec succès');
        return $this->redirectToRoute('app_admin_dashboard');
    }
}
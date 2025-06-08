<?php

namespace App\Controller;

use App\Form\UserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;

class UserController extends AbstractController
{
    public function __construct(
        private EmailVerifier $emailVerifier,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $userPasswordHasher,
        private MailerInterface $mailer
    ) {
    }

    #[Route('/user/profile', name: 'app_user_profile')]
    #[IsGranted('ROLE_USER')]
    public function profile(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            
            if ($user->getEmail() !== $form->get('email')->getData()) {
                $oldEmail = $user->getEmail();
                $newEmail = $form->get('email')->getData();
                
                try {
                    $notificationEmail = (new TemplatedEmail())
                        ->from(new Address($_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@example.com', $_ENV['MAILER_FROM_NAME'] ?? 'Quiz App'))
                        ->to($newEmail)
                        ->subject('Changement d\'adresse email')
                        ->htmlTemplate('email/email_changed_notification.html.twig')
                        ->context([
                            'email' => $newEmail
                        ]);
                    
                    $this->mailer->send($notificationEmail);
                    
                    $user->setEmail($newEmail);
                    $user->setIsVerified(false);
            
                    $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                        (new TemplatedEmail())
                            ->from(new Address($_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@example.com', $_ENV['MAILER_FROM_NAME'] ?? 'Quiz App'))
                            ->to($newEmail)
                            ->subject('Veuillez confirmer votre email')
                            ->htmlTemplate('registration/confirmation_email.html.twig')
                    );
                    
                    // $this->addFlash('success', sprintf(
                    //     'Votre adresse email a été modifiée de %s vers %s. Un email de confirmation a été envoyé à votre nouvelle adresse.',
                    //     $oldEmail,
                    //     $newEmail
                    // ));
                    
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l envoi des emails');
                    return $this->redirectToRoute('app_user_profile');
                }
            }

            // Si un nouveau mot de passe est fourni
            if ($plainPassword) {
                $user->setPassword(
                    $this->userPasswordHasher->hashPassword(
                        $user,
                        $plainPassword
                    )
                );
                $this->addFlash('success', 'Votre mot de passe a été mis à jour avec succès.');
            }

            $this->entityManager->flush();
            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}

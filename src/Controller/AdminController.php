<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;



final class AdminController extends AbstractController
{
    #[Route('/admin/deshboard', name: 'admin_dashboard')]
    public function index(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
         // Additional check for blocked status
        $currentUser = $this->getUser();
        if ($currentUser && $currentUser->isBlocked()) {
            $this->addFlash('error', 'Your account has been blocked.');
            return $this->redirectToRoute('app_logout');
        } 

        $users = $userRepository->findBy([], ['lastLogin' => 'DESC']);
  
         return $this->render('admin/index.html.twig', [
        'users' => $users,   // <-- pass users to Twig
    ]);

    }

    #[Route('/admin/action', name: 'admin_bulk_action', methods: ['POST'])]
public function bulkAction(
    Request $request,
    UserRepository $userRepository,
    EntityManagerInterface $em,
    MailerInterface $mailer,
    TokenStorageInterface $tokenStorage ,
    UrlGeneratorInterface $urlGenerator

): Response {
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    $ids = $request->request->all('selected');  
    $action = $request->request->get('action');

    if (!$ids || !$action) {
        $this->addFlash('error', 'No users selected or no action chosen.');
        return $this->redirectToRoute('admin_dashboard');
    }

    $users = $userRepository->findBy(['id' => $ids]);
       $currentUser = $this->getUser();
        $currentUserId = $currentUser ? $currentUser->getId() : null;

        $isCurrentUserAffected = false;
        $affectedAction = '';

    foreach ($users as $user) {
        switch ($action) {
            case 'block':
                $user->setIsBlocked(true);
                 if ($currentUserId && $user->getId() === $currentUserId) {
                        $isCurrentUserAffected = true;
                        // $affectedAction = 'block';
                    }
                break;
            case 'unblock':
                $user->setIsBlocked(false);
                break;
            case 'delete':
                 if ($currentUserId && $user->getId() === $currentUserId) {
                        $isCurrentUserAffected = true;
                        // $affectedAction = 'delete';
                    }
                $em->remove($user);
                break;
            case 'email':
                $text = $user->isBlocked()
                    ? "Hello, {$user->getName()}. Your login and registration was successful, but you are blocked."
                    : "Hello, {$user->getName()}. Your login and registration was successful.";

                $mailer->send(
                    (new \Symfony\Component\Mime\Email())
                        ->from('jobairhasnat@gmail.com')
                        ->to($user->getEmail())
                        ->subject('Account Notification')
                        ->text($text)
                );
                break;
        }
    }

    $em->flush();
    
     // ✅ If current user is blocked or deleted, log them out
   if ($isCurrentUserAffected) {
            
           
          $tokenStorage->setToken(null);
            $request->getSession()->invalidate();

               
            $this->addFlash('success', 'Action completed. You have been logged out.');
            return new RedirectResponse($urlGenerator->generate('app_login'));
        }

    $this->addFlash('success', 'Action completed.');

    return $this->redirectToRoute('admin_dashboard');
}
}

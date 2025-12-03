<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;


final class MailTestController extends AbstractController
{
   #[Route('/test-email', name: 'test_email')]
    public function sendTest(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('jobairhasnat@gmail.com')   // must match your Gmail account
            ->to('jobairhasnat@programmer.net')      // replace with a real recipient
            ->subject('Test Email from Symfony')
            ->text('This is a test email using Gmail SMTP.');

        $mailer->send($email);

        return new Response('✅ Email sent successfully!');
    }

}

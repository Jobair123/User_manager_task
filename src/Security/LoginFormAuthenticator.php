<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
         private TokenStorageInterface $tokenStorage   // 
    ) {}

    public function authenticate(Request $request): Passport
    {
   $email = $request->getPayload()->getString('email');
    $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

    return new Passport(
        // 👇 Add a callback to load the user and check blocked status
        new UserBadge($email, function ($userIdentifier) {
            $user = $this->userRepository->findOneBy(['email' => $userIdentifier]);
            if (!$user) {
                throw new CustomUserMessageAuthenticationException('User not found.');
            }
            if ($user->isBlocked()) {
                throw new CustomUserMessageAuthenticationException('Your account is blocked.');
            }
            return $user;
        }),
        new PasswordCredentials($request->getPayload()->getString('password')),
        [
            new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
            new RememberMeBadge(),
        ]
    );

    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();

        // Blocked users cannot login
        if ($user->isBlocked()) {
            throw new CustomUserMessageAuthenticationException('Your account is blocked.');
        }

        // Update last login timestamp
        $user->setLastLogin(new \DateTime());
        $this->em->flush();

        // Always redirect to admin dashboard
        return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
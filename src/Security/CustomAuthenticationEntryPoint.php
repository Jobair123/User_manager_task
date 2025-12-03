<?php
namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class CustomAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator) {}

    public function start(Request $request, \Throwable $authException = null): RedirectResponse
    {
        // Add a flash message before redirecting
        $request->getSession()->getFlashBag()->add('warning', 'Your account is blocked or deleted. Please contact the administrator.');

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
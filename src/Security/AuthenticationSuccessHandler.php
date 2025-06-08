<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('app_user_profile'));
    }
}

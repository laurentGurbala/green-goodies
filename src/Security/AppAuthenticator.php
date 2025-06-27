<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;


class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    // Nom de la route du formulaire de connexion
    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    // Cette méthode est appelée quand l'utilisateur soumet le formulaire de connexion
    public function authenticate(Request $request): Passport
    {
        // On récupère l'email soumis dans le formulaire
        $email = $request->getPayload()->getString('email');

        // Symfony garde le dernier email saisi pour le réafficher si la connexion échoue
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        // On crée un objet Passport avec :
        // - l'identité de l'utilisateur (email)
        // - les identifiants (mot de passe)
        // - un badge de sécurité pour vérifier le token CSRF
        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->getPayload()->getString('password')),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
            ]
        );
    }

    // Cette méthode est appelée quand la connexion réussit
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Si l'utilisateur essayait d'accéder à une page protégée avant de se connecter,
        // on le redirige vers cette page
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Sinon, on le redirige vers la page d’accueil
        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }

    // Cette méthode indique quelle est l’URL du formulaire de connexion
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login');
    }
}

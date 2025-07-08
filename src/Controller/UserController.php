<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\User;
use App\Form\RegistrationForm;
use App\Security\AppAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class UserController extends AbstractController
{
    /**
     * Gère l'inscription des utilisateurs.
     */
    #[Route('/register', name: 'register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator,
        AppAuthenticator $authenticator
    ): Response {
        // Création d'une nouvelle instance de User (utilisateur vide)
        $user = new User();

        // Création et liaison du formulaire d'inscription à l'objet User
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide...
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération du mot de passe "en clair" saisi dans le formulaire
            /** @var string $plainPassword */
            $plainPassword = $form->get('password')->getData();

            // Hachage sécurisé du mot de passe
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Désactive l'accès à l'API par défaut (peut être activé plus tard)
            $user->setApiActivated(false);
            // Définit le rôle utilisateur standard
            $user->setRoles(["ROLE_USER"]);

            // Création du panier 
            $cart = new Cart();
            $cart->setUser($user);
            $user->setCart($cart);

            // Enregistre l'utilisateur et le panier en base de données
            $entityManager->persist($user);
            $entityManager->persist($cart);
            $entityManager->flush();

            // Connecte automatiquement l'utilisateur après inscription
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        // Affiche le formulaire d'inscription (ou renvoie en cas d'erreur)
        return $this->render('user/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    /**
     * Gère l'affichage du formulaire de connexion et les erreurs éventuelles.
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Récupère la dernière erreur d’authentification, s’il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();

        // Récupère le dernier email saisi (pratique en cas d’échec)
        $email = $authenticationUtils->getLastUsername();

        // Affiche le formulaire de login avec les infos récupérées
        return $this->render('user/login.html.twig', [
            'email' => $email,
            'error' => $error,
        ]);
    }

    /**
     * Point d'entrée pour la déconnexion (géré automatiquement par Symfony).
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Symfony intercepte cette méthode grâce à la config de sécurité
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Gère l'affichage du compte
     */
    #[IsGranted("ROLE_USER")]
    #[Route(path:"/account", name: "account")]
    public function account() : Response
    {
        $user = $this->getUser();

        return $this->render("user/account.html.twig", [
            "user" => $user
        ]);
    }
}

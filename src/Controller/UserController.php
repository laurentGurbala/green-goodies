<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\User;
use App\Form\RegistrationForm;
use App\Security\AppAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Builder\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class UserController extends AbstractController
{
    /**
     * Gère l'inscription d'un nouvel utilisateur.
     *
     * - Affiche un formulaire de création de compte.
     * - Hash le mot de passe.
     * - Crée automatiquement un panier associé à l'utilisateur.
     * - Connecte l'utilisateur après validation du formulaire.
     *
     * @param Request $request Données de la requête HTTP.
     * @param UserPasswordHasherInterface $userPasswordHasher Pour le hash du mot de passe.
     * @param EntityManagerInterface $entityManager Persistance des données.
     * @param UserAuthenticatorInterface $userAuthenticator Pour connecter l'utilisateur après inscription.
     * @param AppAuthenticator $authenticator L'authenticator personnalisé.
     *
     * @return Response Page d'inscription ou redirection après inscription.
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
     * Affiche le formulaire de connexion avec gestion des erreurs.
     *
     * @param AuthenticationUtils $authenticationUtils Outils pour récupérer erreurs et identifiants précédents.
     * @return Response Vue de connexion.
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
     * Gère la déconnexion de l'utilisateur.
     * Cette méthode est interceptée par Symfony selon la config du firewall.
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Symfony intercepte cette méthode grâce à la config de sécurité
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Affiche la page "Mon compte" avec l’historique des commandes.
     *
     * @return Response
     */
    #[IsGranted("ROLE_USER")]
    #[Route(path: "/account", name: "account")]
    public function account(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $orders = $user->getOrders();

        return $this->render("user/account.html.twig", [
            'orders' => $orders,
        ]);
    }

    /**
     * Active l'accès à l'API pour l'utilisateur connecté.
     *
     * @param EntityManagerInterface $em
     * @return Response Redirection vers le compte avec message de confirmation.
     */
    #[IsGranted("ROLE_USER")]
    #[Route('/mon-compte/api-activer', name: 'account_api_enable')]
    public function enableApi(EntityManagerInterface $em): Response
    {
        /** @var User */
        $user = $this->getUser();
        $user->setApiActivated(true);
        $em->flush();

        $this->addFlash('success', 'Accès API activé.');
        return $this->redirectToRoute('account');
    }

    /**
     * Désactive l'accès à l'API pour l'utilisateur connecté.
     *
     * @param EntityManagerInterface $em
     * @return Response Redirection vers le compte avec message de confirmation.
     */
    #[IsGranted("ROLE_USER")]
    #[Route('/mon-compte/api-desactiver', name: 'account_api_disable')]
    public function disableApi(EntityManagerInterface $em): Response
    {
        /** @var User */
        $user = $this->getUser();
        $user->setApiActivated(false);
        $em->flush();

        $this->addFlash('success', 'Accès API désactivé.');
        return $this->redirectToRoute('account');
    }

    /**
     * Supprime définitivement le compte de l'utilisateur connecté.
     * - Déconnecte l'utilisateur.
     * - Supprime son compte (et son panier + commandes via cascade).
     *
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenStorage
     * @return RedirectResponse Redirection vers la page d'accueil.
     */
    #[IsGranted("ROLE_USER")]
    #[Route(path: "/account/delete", name: "account_delete", methods:["GET"])]
    public function deleteAccount(
        EntityManagerInterface $em, 
        TokenStorageInterface $tokenStorage
    ): RedirectResponse 
    {
        $user = $this->getUser();

        if(!$user) {
            throw $this->createAccessDeniedException();
        }

        // Déconnecter l'utilisateur avant suppression
        $tokenStorage->setToken(null);

        // Supprimer l'utilisateur (cascade supprimera tout le reste)
        $em->remove($user);
        $em->flush();

        $this->addFlash("success", "Votre compte a bien été supprimé.");

        return $this->redirectToRoute("homepage");
    }
}

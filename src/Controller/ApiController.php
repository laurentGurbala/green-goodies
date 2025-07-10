<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ApiController extends AbstractController
{
    /**
     * Endpoint API permettant de récupérer la liste des produits disponibles.
     *
     * ### Sécurité :
     * Seuls les utilisateurs connectés avec un accès API activé peuvent appeler cette route.
     *
     * ### Réponse :
     * - 200 OK : Retourne la liste des produits (en JSON).
     * - 403 FORBIDDEN : Si l'utilisateur n'est pas connecté ou n'a pas l'accès API activé.
     *
     * @param ProductRepository $productRepository Le repository permettant d'accéder aux produits.
     * @return JsonResponse La réponse JSON contenant la liste des produits ou un message d'erreur.
     */
    #[Route('/api/products', name: 'products', methods: ["GET"])]
    public function getProducts(ProductRepository $productRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || !$user->isApiActivated()) {
            return $this->json([
                "error" => "Accès API désactivé."
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $products = $productRepository->findAll();

        return $this->json($products, JsonResponse::HTTP_OK);
    }
}

<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

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
    #[OA\Get(
        path: "/api/products",
        summary: "Retourne la liste des produits disponibles",
        description: "Accessible uniquement si l'utilisateur est connecté et que l'accès API est activé"
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste des produits retournée avec succès.',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class)),
            example: [
                [
                    "id" => 1,
                    "name" => "Brosse à dents biodégradable",
                    "shortDescription" => "description",
                    "fullDescription" => "description complète",
                    "price" => 3.99,
                    "picture" => "brosse.jpg"
                ],
                [
                    "id" => 2,
                    "name" => "Shampoing solide",
                    "shortDescription" => "description",
                    "fullDescription" => "description complète",
                    "price" => 7.50,
                    "picture" => "shampoing.jpg"
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Accès interdit',
        content: new OA\JsonContent(
            example: ['error' => 'Accès API désactivé.']
        )
    )]
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

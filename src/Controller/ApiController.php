<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class ApiController extends AbstractController
{
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

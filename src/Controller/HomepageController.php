<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomepageController extends AbstractController
{
    /**
     * Affiche la page d'accueil du site avec la liste complète des produits.
     *
     * @param ProductRepository $productRepository Le repository permettant de récupérer les produits.
     * @return Response La vue Twig affichant les produits.
     */
    #[Route('/', name: 'homepage')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->render('homepage/index.html.twig', [
            'products' => $products,
        ]);
    }
}

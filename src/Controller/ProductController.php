<?php

namespace App\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    /**
     * Affiche le détail d'un article
     */
    #[Route('/product/{id}', name: 'product_detail')]
    public function getProduct(?Product $product): Response
    {
        // Affiche le détail d'un article
        return $this->render('product/detail.html.twig', [
            'product' => $product,
        ]);
    }
}

<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
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
        $cartItem = null;
        /** @var User $user */
        $user = $this->getUser();

        // Recherche si le produit est présent dans le panier
        if ($user) {
            $cart = $user->getCart();
            foreach ($cart->getCartItems() as $item) {
                if ($item->getProduct()->getId() === $product->getId()) {
                    $cartItem = $item;
                    break;
                }
            }
        }

        // Affiche le détail d'un article
        return $this->render('product/detail.html.twig', [
            'product' => $product,
            "cartItem" => $cartItem
        ]);
    }
}

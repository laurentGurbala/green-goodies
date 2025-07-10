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
     * Affiche la fiche détaillée d’un produit.
     *
     * Si l'utilisateur est connecté, on vérifie si le produit est déjà présent dans son panier.
     * Cela permet d'afficher dynamiquement la quantité ou d'autres infos dans la vue.
     *
     * @param Product|null $product Le produit à afficher (résolu automatiquement via param converter).
     * @return Response La page contenant les détails du produit.
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

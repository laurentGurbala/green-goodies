<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CartController extends AbstractController
{
    /**
     * Gère l'affichage du panier
     */
    #[IsGranted("ROLE_USER")]
    #[Route('/cart', name: 'cart', methods: ["GET"])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Cart $cart */
        $cart = $user->getCart();

        $total = 0;

        foreach ($cart->getCartItems() as $item) {
            $total += $item->getProduct()->getPrice() * $item->getQuantity();
        }

        return $this->render('cart/cart.html.twig', [
            "cart" => $cart,
            "total" => $total
        ]);
    }

    /**
     * Met à jour l'item du panier
     */
    #[IsGranted("ROLE_USER")]
    #[Route('/cart/product/{id}', name: 'cart_add', methods: ["POST"])]
    public function addOrUpdateProductInCart(Product $product, Request $request, EntityManagerInterface $em): Response 
    {
        /** @var User $user */
        $user = $this->getUser();
        $quantity = (int) $request->request->get("quantity", 1);
        /** @var Cart $cart */
        $cart = $user->getCart();

        // Recherche si le produit est déjà dans le panier
        $existingItem = null;
        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct()->getId() === $product->getId()) {
                $existingItem = $item;
                break;
            }
        }

        // Si la quantité est à 0 => suppression
        if ($quantity === 0 && $existingItem) {
            $cart->getCartItems()->removeElement($existingItem);
            $em->remove($existingItem);
            $em->flush();

            // TODO : Ajouter un message flash "produit retiré du panier"
            return $this->redirectToRoute("product_detail", ["id" => $product->getId()]);
        }

        // Mis à jour de la quantité
        if ($existingItem) {
            $existingItem->setQuantity($quantity);
            // TODO : Ajouter un message flash "Quantité mise à jour dans le panier."
        }
        // Ajout du produit dans le panier
        else {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);

            $em->persist($cartItem);
            $cart->getCartItems()->add($cartItem); // bidirectionnel
            // TODO : Ajouter un message flash "Produit ajouter au panier."
        }

        $em->flush();

        return $this->redirectToRoute("product_detail", ["id" => $product->getId()]);
    }

    #[IsGranted("ROLE_USER")]
    #[Route("/cart/clear", name: "cart_clear", methods: ["GET"])]
    public function clearToCart(EntityManagerInterface $em): response {

        /** @var User $user */
        $user = $this->getUser();
        $cart = $user->getCart();
        
        // Supprime les produits du panier
        foreach ($cart->getCartItems() as $item) {
            $em->remove($item);
        }

        $cart->getCartItems()->clear();

        $em->flush();
        // TODO : Ajouter un message flash "Le panier a bien été vidé."
        
        return $this->redirectToRoute("cart");
    }
}

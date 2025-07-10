<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\OrderItem;
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
     * Affiche le contenu du panier de l'utilisateur connecté.
     *
     * @return Response La vue du panier avec le total des articles.
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
     * Ajoute un produit au panier ou met à jour sa quantité.
     * Si la quantité est 0, supprime l'article du panier.
     *
     * @param Product $product Le produit concerné.
     * @param Request $request La requête contenant la quantité.
     * @param EntityManagerInterface $em L'EntityManager Doctrine.
     *
     * @return Response Redirige vers la page du produit.
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
        if ($quantity === 0) {
            if ($existingItem) {
                $cart->getCartItems()->removeElement($existingItem);
                $em->remove($existingItem);
                $em->flush();
                $this->addFlash("success", "Le produit à été retiré du panier.");
            }

            return $this->redirectToRoute("product_detail", ["id" => $product->getId()]);
        }

        // Mis à jour de la quantité
        if ($existingItem) {
            $existingItem->setQuantity($quantity);
            $this->addFlash("success", "Quantité mise à jour dans le panier.");
        }
        // Ajout du produit dans le panier
        else {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);

            $em->persist($cartItem);
            $cart->getCartItems()->add($cartItem); // bidirectionnel
            $this->addFlash("success", "Produit ajouter au panier.");
        }

        $em->flush();

        return $this->redirectToRoute("product_detail", ["id" => $product->getId()]);
    }

    /**
     * Vide entièrement le panier de l'utilisateur connecté.
     *
     * @param EntityManagerInterface $em L'EntityManager Doctrine.
     * @return Response Redirige vers la page du panier.
     */
    #[IsGranted("ROLE_USER")]
    #[Route("/cart/clear", name: "cart_clear", methods: ["GET"])]
    public function clearToCart(EntityManagerInterface $em): response
    {

        /** @var User $user */
        $user = $this->getUser();
        $cart = $user->getCart();

        // Supprime les produits du panier
        foreach ($cart->getCartItems() as $item) {
            $em->remove($item);
        }

        $cart->getCartItems()->clear();

        $em->flush();
        $this->addFlash("success", "Le panier a bien été vidé.");

        return $this->redirectToRoute("cart");
    }


    /**
     * Valide le panier et transforme les articles en commande.
     *
     * - Crée une nouvelle commande (`Order`) pour l'utilisateur.
     * - Copie chaque article du panier dans la commande (`OrderItem`).
     * - Calcule le total et vide le panier.
     *
     * @param EntityManagerInterface $em L'EntityManager Doctrine.
     * @return Response Redirige vers la page panier avec un message de succès.
     */
    #[IsGranted("ROLE_USER")]
    #[Route(path: "/cart/validate", name: "cart_validate", methods: ["GET"])]
    public function validateCart(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $cart = $user->getCart();

        if ($cart->getCartItems()->isEmpty()) {
            return $this->redirectToRoute("cart");
        }

        $order = new Order();
        $order->setUser($user);
        $order->setCreatedAt(new \DateTimeImmutable());

        $total = 0;

        foreach ($cart->getCartItems() as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setCustomOrder($order);
            $orderItem->setProduct($cartItem->getProduct());
            $orderItem->setQuantity($cartItem->getQuantity());
            $orderItem->setPriceAtOrder($cartItem->getProduct()->getPrice());

            $em->persist($orderItem);

            $total += $cartItem->getProduct()->getPrice() * $cartItem->getQuantity();
        }

        $order->setTotal($total);
        $em->persist($order);

        // Vider le panier
        foreach ($cart->getCartItems() as $item) {
            $em->remove($item);
        }
        $cart->getCartItems()->clear();

        $em->flush();

        $this->addFlash("success", "Votre commande a été enregistrée avec succès !");
        return $this->redirectToRoute("cart");
    }
}

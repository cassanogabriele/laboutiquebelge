<?php

namespace App\Service;

use App\Classes\Cart;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ShoppingCart;
use App\Entity\Wishlist;

class CartService
{
    private $cart;
    private $security;
    private $session;
    private $entityManager;

    public function __construct(Cart $cart, Security $security, SessionInterface $session, EntityManagerInterface $entityManager)
    {
        $this->cart = $cart;
        $this->security = $security;
        $this->session = $session;
        $this->entityManager = $entityManager;
    }

    public function getCartItemCount(): int
    {
        if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            // L'utilisateur est connecté
            $shoppingCart = new ShoppingCart($this->entityManager, $this->security);
            $cartItems = $shoppingCart->getFull();

            $cartItemCount = count($cartItems);
        } else {
            // L'utilisateur n'est pas connecté
            $cartData = $this->cart->getFull();
            $cartItemCount = count($cartData);
        }

        return $cartItemCount;
    }

    public function getWishlistItemCount(): int
    {
        $userId = $this->security->getUser()->getId();     
       
        $wishlistItems = $this->entityManager->getRepository(Wishlist::class)->findBy([
            'user' => $userId,
        ]);

        $wishlistItemCount = count($wishlistItems);
    
        return $wishlistItemCount;
    }
    
}

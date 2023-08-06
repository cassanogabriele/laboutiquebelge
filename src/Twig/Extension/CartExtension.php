<?php
// src/Twig/CartExtension.php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use App\Service\CartService;

class CartExtension extends AbstractExtension
{
    private $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cartItemCount', [$this, 'getCartItemCount']),
            new TwigFunction('wishlistItemCount', [$this, 'getWishlistItemCount']),   
        ];
    }

    public function getCartItemCount(): int
    {
        return $this->cartService->getCartItemCount();
    }

    public function getWishlistItemCount(): int
    {
       return $this->cartService->getWishlistItemCount();
    }
}

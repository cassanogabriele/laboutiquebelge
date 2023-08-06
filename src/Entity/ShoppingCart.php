<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;


/**
 * @ORM\Entity
 * @ORM\Table(name="shopping_cart")
 */
class ShoppingCart
{
      
    private $entityManager;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }
  

   /**
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $userId;


    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="bigint", options={"unsigned": true})
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $productId;

    /**
     * @ORM\Column(type="string", length=191, nullable=true)
     */
    private $productName;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $productPrice;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $qty;

    /**
     * @ORM\Column(type="string", length=191, nullable=true)
     */
    private $productImage;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $total;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(int $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): void
    {
        $this->productName = $productName;
    }

    public function getProductPrice(): ?int
    {
        return $this->productPrice;
    }

    public function setProductPrice(int $productPrice): void
    {
        $this->productPrice = $productPrice;
    }

    public function getQty(): ?int
    {
        return $this->qty;
    }

    public function setQty(int $qty): void
    {
        $this->qty = $qty;
    }

    public function getProductImage(): ?string
    {
        return $this->productImage;
    }

    public function setProductImage(string $productImage): void
    {
        $this->productImage = $productImage;
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
   
    public function getFull(): array
    {
        $cartComplete = [];

        if ($this->security && $this->security->getUser()) {
            $userId = $this->security->getUser()->getId();
           
            $cartItems = $this->entityManager->getRepository(ShoppingCart::class)->findBy([
                'userId' => $userId,
            ]);

            foreach ($cartItems as $cartItem) {
                $productData = [
                    'id' => $cartItem->getId(),
                    'productId' => $cartItem->getProductId(),
                    'name' => $cartItem->getProductName(),
                    'price' => $cartItem->getProductPrice(),
                    'qty' => $cartItem->getQty(),
                    'illustration' => $cartItem->getProductImage(),
                    'total' => $cartItem->getTotal(),
                ];

                $cartComplete[] = [
                    'product' => $productData,
                    'quantity' => $cartItem->getQty()
                ];
            }
        }

        return $cartComplete;
    }

    public function getUserCart()
    {
        if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user = $this->security->getUser();

            // Utilisez l'EntityManager pour obtenir le panier de l'utilisateur actuel
            $cartItems = $this->entityManager->getRepository(ShoppingCart::class)->findBy([
                'userId' => $user->getId(),
            ]);

            return $cartItems;
        }

        return [];
    }
}

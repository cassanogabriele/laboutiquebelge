<?php 

namespace App\Classes;

use App\Entity\Product;
use App\Entity\User;
use App\Entity\ShoppingCart;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;

class Cart{
    private $session;
    private $entityManager;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session, Security $security){
        $this->session = $session;
        $this->entityManager = $entityManager;
        $this->security = $security;
    }
   
    public function add($id)
    {
        if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user = $this->security->getUser();
            $userId = $user->getId();

            $shoppingCart = new ShoppingCart($this->entityManager, $this->security);

            $cartItems = $shoppingCart->getUserCart();

            $productExists = false;

            foreach ($cartItems as $cartItem) {
                if ($cartItem->getProductId() == $id) {
                    $newQuantity = $cartItem->getQty() + 1;
                    $cartItem->setQty($newQuantity);
                    $this->entityManager->persist($cartItem);
                    $productExists = true;
                    break;
                }
            }

            if (!$productExists) {
                $product = $this->entityManager->getRepository(Product::class)->find($id);

                if ($product) {
                    $shoppingCart = new ShoppingCart($this->entityManager, $this->security);
                    $shoppingCart->setProductId($product->getId());
                    $shoppingCart->setProductName($product->getName());
                    $shoppingCart->setProductPrice($product->getPrice());
                    $shoppingCart->setQty(1);
                    $shoppingCart->setProductImage($product->getIllustration());
                    $shoppingCart->setTotal($product->getPrice());
                    $shoppingCart->setUserId($userId);
                    $shoppingCart->setCreatedAt(new \DateTime());
                    $shoppingCart->setUpdatedAt(new \DateTime());

                    $this->entityManager->persist($shoppingCart);
                }
            }

            $this->entityManager->flush();
        } else {
            $cart = $this->session->get('cart', []);

            if (!empty($cart[$id])) {
                $cart[$id]++;
            } else {
                $cart[$id] = 1;
            }

            $this->session->set('cart', $cart);
        }
    }

    public function get(){
        return $this->session->get('cart');
    }

    public function remove(){
        return $this->session->get('cart');
    }

    public function delete($id)
    {
        if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            // Récupérez le panier de l'utilisateur connecté
            $user = $this->security->getUser();
            $userId = $user->getId();
    
            $shoppingCart = new ShoppingCart($this->entityManager, $this->security);
    
            $cartItems = $shoppingCart->getUserCart();
    
            foreach ($cartItems as $cartItem) {
                if ($cartItem->getProductId() == $id) {
                    $this->entityManager->remove($cartItem);
                    $this->entityManager->flush();
                    break;
                }
            }
        } else {
            $cart = $this->session->get('cart');
    
            if (isset($cart[$id])) {
                unset($cart[$id]);
                $this->session->set('cart', $cart);
            }
        }
    }    

    public function decrease($id){
        $cart = $this->session->get('cart', []);

        if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) { 
            // Récupérer le panier de l'utilisateur connecté dans l'entité "ShoppingCart"   
            $shoppingCart = new ShoppingCart($this->entityManager, $this->security);

            $cartItems = $shoppingCart->getUserCart();

            // Mise à jour de la quantité
            foreach ($cartItems as $cartItem) {
                if ($cartItem->getProductId() == $id) {
                    if ($cartItem->getQty() > 1) {
                        $newQuantity = $cartItem->getQty() - 1;
                        $cartItem->setQty($newQuantity);
                        $this->entityManager->persist($cartItem);
                    } else {
                        $this->entityManager->remove($cartItem);
                    }

                    $this->entityManager->flush();
                    break;
                }
            }       
        } else{
            // Vérifier si le produit à une qté supérieur à 1
            if($cart[$id] > 1){
                // Retirer une qté
                $cart[$id]--;
            } else{
                // On supprimer le produit
                unset($cart[$id]);
            }
        }        

        return $this->session->set('cart', $cart);
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
}

<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Classes\Cart;
use Stripe\Checkout\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Order;
use App\Entity\User;
use App\Entity\Product;

class StripeController extends AbstractController
{
    /**
     * @Route("/commande/create-checkout-session/{reference}", name="stripe_create_session")
     */
    public function index(EntityManagerInterface $entityManager, Cart $cart, $reference)
    {

        // Initialisation 
        \Stripe\Stripe::setApiKey('sk_test_51MmMexI1PfFa9viOiFBApIUDIvAPP36mJpYchDJoQ45QbP8fusojSYtejg2uCnFX8rR04ljCMo1RYCFcnZgRgRQw0001pussSD');  

        $YOUR_DOMAIN  = 'http://127.0.0.1:8000';      

        // Trouver moi un seul enregistrement par la référence
        $order = $entityManager->getRepository(Order::class)->findOneByReference($reference);   
        
        if(!$order){
            new JsonResponse(['error' => 'order']);       
        }      

        // Enregistrer les détails de la commande (les produits)
        foreach($order->getOrderDetails()->getValues() as $product){
            $product_object = $entityManager->getRepository(Product::class)->findOneByName($product->getProduct());   

            $price = \Stripe\Price::create([
                'unit_amount' => $product->getPrice(), // Le prix est en centimes
                'currency' => 'eur',
                'product_data' => [
                    'name' => $product->getProduct(),
                ],
            ]);

            $lineItems[] = [
                'price' => $price->id, // Utiliser l'identifiant d'objet de prix
                'quantity' => $product->getQuantity(),
            ];               
        }

        $lineItems[] = [
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $order->getCarrierPrice(),
                'product_data' => [
                    'name' => $order->getCarrierName(),
                ],
            ],
            'quantity' => 1,
        ];            
        
        // Création de checkout session      
        $checkout_session = \Stripe\Checkout\Session::create([
            'customer_email' => $this->getUser()->getEmail(), 
            'line_items' => $lineItems,
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'success_url' => $YOUR_DOMAIN . '/commande/merci/{CHECKOUT_SESSION_ID}',
            'cancel_url' => $YOUR_DOMAIN . '/commande/erreur/{CHECKOUT_SESSION_ID}',
        ]);

        $order->setStripeSessionId($checkout_session->id);
        $entityManager->flush();

        $response = new JsonResponse(['id' => $checkout_session->id]);       

        return $response;       
    }
}

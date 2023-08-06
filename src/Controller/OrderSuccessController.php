<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Order;
use App\Classes\Cart;
use App\Classe\Mail;

class OrderSuccessController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/commande/merci/{stripeSessionId}", name="order_validate")
     */
    public function index(Cart $cart, $stripeSessionId): Response
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneByStripeSessionId($stripeSessionId);

        // Vérifier si la commande existe et si c'est bien la commande de l'utilisateur connecté
        if(!$order || $order->getUser() != $this->getUser()){
            // On n'aura pas le droit d'y accéder et on sera redirigé vers la page d'accueil 
            return $this->redirectToRoute('home');
        }

        // Vérifier que la commande n'a pas déjà été payée
        if(!$order->getPaid()){
            // Vider le panier de l'utiisateur  
            $cart->remove();

            // Modifier le statut "isPaid" de la commande en passant le statut à 1
            $order->setPaid(1);
            $this->entityManager->flush(); 
        }

        // Afficher les informations de la commande de l'utilisateur 
        return $this->render('order_success/index.html.twig', [
            'order' => $order
        ]);
    }
}

<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Classes\Cart;
use App\Entity\User;
use App\Entity\Product;
use App\Entity\Order;
use App\Form\OrderType;
use App\Entity\OrderDetails;
use Doctrine\ORM\EntityManagerInterface;
use App\Classe\Mail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
    }
    
    /**
     * @Route("/commande", name="order")
     */
    public function index(Cart $cart, Request $request): Response
    {
                
        // Si l'utilisateur n'a pas d'adresse
        if(!$this->getUser()->getAddresses()->getValues()){
            // On redirige vers la page d'ajout d'adresse
           return $this->redirectToRoute('account_address_add');
        }
        
        $form = $this->createForm(OrderType::class, null, [
            'user' => $this->getUser()
        ]);

        $form->handleRequest($request);
        
        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'cart' => $cart->getFull()
        ]);
    }

    /**
     * @Route("/commande/recapitulatif", name="order_recap", methods={"GET", "POST"})
     */
    public function add(Cart $cart, Request $request): Response
    {
        $form = $this->createForm(OrderType::class, null, [
            'user' => $this->getUser()
        ]);
    
        $form->handleRequest($request);

        // Enregistrer la commande avec la récupération de l'adresse et le transporteur

        // Enregistrer la commande
        if($form->isSubmitted() && $form->isValid()){
            $date = new \DateTime();
            $carriers = $form->get('carriers')->getData();
            $delivery = $form->get('addresses')->getData();
            $delivery_content = $delivery->getFirstName() .' '.$delivery->getLastName();
            $delivery_content .= '<br/>'.$delivery->getPhone();

            if($delivery->getCompany()){
                $delivery_content .= '<br/>'.$delivery->getCompany();
            }
         
            $delivery_content .= '<br/>'.$delivery->getAddress();
            $delivery_content .= '<br/>'.$delivery->getPostal().' '.$delivery->getCity();
            $delivery_content .= '<br/>'.$delivery->getPostal().' '.$delivery->getCountry();
        
            // Enregistrer la commande 
            $order = new Order();
            $reference = $date->format('dmY').'-'.uniqid();
            $order->setReference($reference);
            $order->setUser($this->getUser());
            $order->setCreatedAt($date);
            $order->setCarrierName($carriers->getName());
            $order->setCarrierPrice($carriers->getPrice());
            $order->setDelivery($delivery_content);
            $order->setPaid(1);        

            // Stockage des données 
            $this->entityManager->persist($order);      
           
            // Enregistrer les détails de la commande (les produits)
            foreach ($cart->getFull() as $item) {
                $orderDetails = new OrderDetails();
                $orderDetails->setMyOrder($order);
                $orderDetails->setProduct($item['product']['name']); // Use 'name' instead of 'getProductName'
                $orderDetails->setQuantity($item['quantity']); // Use 'quantity' directly
                $orderDetails->setPrice($item['product']['price']); // Use 'price' instead of 'getProductPrice'
                $orderDetails->setTotal($item['product']['price'] * $item['quantity']); // Calculate the total price
            
                $this->entityManager->persist($orderDetails);
            }            

            $this->entityManager->flush();

            // Envoyer un email au client pour lui confirmer sa commande
            $mail = new Mail();
            $content = "Bonjour ".$order->getUser()->getFirstName()."<br>Merci pour votre commande.<br><br>Nous sommes ravis de satisfaire vos besoins.";
            $mail->send($order->getUser()->getEmail(), $order->getUser()->getFirstName(), 'Votre commande La Boutique Belge est bien validée', $content); 

            // Récupérer le numéro de commande qui vient d'être enregistré
            $orderNumber = $order->getReference();
            
            return $this->render('order/add.html.twig', [         
                'cart' => $cart->getFull(),
                'orderNumber' => $orderNumber,
                'carrier' => $carriers,
                'delivery' => $delivery_content,
                'reference' => $order->getReference() ? $order->getReference() : "",
            ]);
        }
        
       return $this->redirectToRoute('cart');
    }

   /**
     * @Route("/commande/confirmation", name="order_confirmation")
     */
    public function confirmation(Cart $cart, Request $request): Response
    {
        // Retrieve the order number from the request parameters
        $orderNumber = $request->query->get('orderNumber');

        // Use the $orderNumber to fetch the relevant order data (assuming you have an Order entity and an OrderRepository)
        $order = $this->getDoctrine()->getRepository(Order::class)->findOneBy(['reference' => $orderNumber]);
        $orderDetails = $this->getDoctrine()->getRepository(OrderDetails::class)->findBy(['myOrder' => $order]);

        $orderDetailsArray = array();

        foreach ($orderDetails as $detail) {
            $product = $detail->getProduct();

            $productEntity = $this->getDoctrine()->getRepository(Product::class)->findOneBy(['name' => $product]);
           
            if($productEntity){
                $illustrationFileName = $productEntity->getIllustration();    
                $price = $productEntity->getPrice();             
            }    
          
            $detailData = array(
                'id' => $detail->getId(),
                'product' => $detail->getProduct(),
                'quantity' => $detail->getQuantity(),
                'illustration' => $illustrationFileName,
                'price' => $price,
            );

            // Ajouter l'objet $detailData au tableau $orderDetailsArray

            // Il me faut aussi les infos de $order

            $orderDetailsArray[] = $detailData;
        }

        $orderData = array(
            'order_id' => $order->getId(),
            'order_reference' => $order->getReference(),
            'order_transporter' => $order->getCarrierName(),
            'order_date' => $order->getCreatedAt()->format('d-m-Y'), 
            'order_carrier' => $order->getCarrierPrice(),
            'order_details' => $orderDetailsArray,
        );    

        return $this->render('order/confirmation.html.twig', [
            'order' => $orderData,
        ]);
    }
}

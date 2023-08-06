<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Classe\Mail;

class RegisterController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
    }
    
    /**
     * @Route("/inscription", name="register")
     */
    public function index(Request $request, UserPasswordEncoderInterface $encoder)
    {
        $notification = null;

        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);

        $form->handleRequest($request);
       
        if($form->isSubmitted() && $form->isValid()){
            $user = $form->getData();

            // Vérifier si l'utilisateur n'est pas déjà existant en db
            $search_email = $this->entityManager->getRepository(User::class)->findOneByEmail($user->getEmail());

            if(!$search_email){
                // Crypter le mot de passe avec la sécurité Symfony
                $password = $encoder->encodePassword($user, $user->getPassword());
                // Réinjecter le password cryptté dans l'objet user
                $user->setPassword($password);
    
                // Enregistrer les informations dans la base de données
    
                // Appeler Doctrine : $doctrine = $this->getDoctrine()->getManager();
               $this->entityManager->persist($user);
               $this->entityManager->flush();

               $mail = new Mail();
               $content = "Bonjour ".$user->getFirstName()."<br>Bienvenu sur la première boutique dédiée aux produits belges.<br><br>";
               $mail->send($user->getEmail(), $user->getFirstName(), 'Bienvenu sur La Boutique Belge', $content);               

               $notification = "Votre inscription s'est correctement déroulée. Vous pouvez dès à présent vous connecter à votre compte";
            } else{
                $notification = "L'email que vous avez renseigné existe déjà";
            }   
        }
        
        return $this->render('register/index.html.twig',[
            'form' => $form -> createView(),
            'notification' => $notification
        ]);
    }
}

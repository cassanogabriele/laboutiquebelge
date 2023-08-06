<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class HomeController extends AbstractController
{
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @Route("/", name="home")
     */
    public function index()
    {        
        $em = $this->getDoctrine()->getManager();
    
        $categories = $em->getRepository(Category::class)
            ->createQueryBuilder('c')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
    
        $products = $em->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->where('p.category NOT IN (:categories)')
            ->setParameter('categories', $categories)
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();    

        return $this->render('home/index.html.twig', [
            'categories' => $categories,
            'products' => $products,
        ]);
    }      

    /**
     * @Route("/home/about", name="about")
     */
    public function about()
    {
        return $this->render('home/about.html.twig');
    }

    /**
     * @Route("/home/cgv", name="cgv")
     */
    public function cgv()
    {
        return $this->render('home/cgv.html.twig');
    }
}

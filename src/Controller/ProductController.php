<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

     /**
     * @Route("/nos-produits", name="products")
     */
    public function index()
    {
        $products = $this->entityManager->getRepository(Product::class)->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    /**
     * @Route("/produit/{slug}", name="produit")
     */
    public function show($slug)
    {
        $product = $this->entityManager->getRepository(Product::class)->findOneBySlug($slug);

        if(!$product){
            return $this->redirectToRoute('products');
        }        

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }   

    /**
     * @Route("/product_by_category/{category}", name="product_by_category")
     */
    public function showProductsCategory($category)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
    
        $queryBuilder->select('p')
            ->from('App\Entity\Product', 'p')
            ->join('p.category', 'c')
            ->where($queryBuilder->expr()->eq('c.name', ':category'))
            ->setParameter('category', $category);
    
        $productsByCategory = $queryBuilder->getQuery()->getResult();
    
        return $this->render('product/showProductByCategory.html.twig', [
            'productsByCategory' => $productsByCategory,
            'category' => $category,
        ]);
    }
    
}

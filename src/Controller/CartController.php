<?php

namespace App\Controller;

use App\Classes\Cart;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductWishlist;
use App\Entity\Wishlist;
use App\Entity\ShoppingCart;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\CartService;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\ORM\Query\QueryException;
use App\Repository\WishlistRepository;
use DateTime;

class CartController extends AbstractController
{
    private $entityManager;
    private $cartService;
    private $session;

    public function __construct(EntityManagerInterface $entityManager, CartService $cartService){
        $this->entityManager = $entityManager;
        $this->cartService = $cartService;
    }    

    // Nombre de produits dans le panier
    public function getCartItemCount()
    {
        $this->session = new Session();
        $cart = $this->session->get('cart', []);
    
        // Utilisez la fonction count() pour obtenir le nombre d'articles distincts
        $cartItemCount = count($cart);
    
        return $cartItemCount;
    }    
    
    // Récupérer le nombre d'articles dans le panier pour l'afficher
    public function addTotalCart(): Response
    {
        $cartItemCount = $this->cartService->getCartItemCount();

        return $this->render('base.html.twig', [
            'cartItemCount' => $cartItemCount,
        ]);
    }

    // Nombre de listes de souhaits
    public function getWishlistItemCount()
    {
        $this->session = new Session();
        $wishlist = $this->session->get('wishlist', []);

        // Utilisez la fonction count() pour obtenir le nombre d'articles distincts dans la liste de souhaits
        $wishlistItemCount = count($wishlist);

        return $wishlistItemCount;
    }

    // Récupérer le nombre de listes de souhaits pour l'affichage
    public function addTotalWishlist(): Response
    {
        $wishlistItemCount = $this->cartService->getWishlistItemCount();      

        return $this->render('base.html.twig', [
            'wishlistItemCount' => $wishlistItemCount,
        ]);
    }
      
    /**
     * @Route("/mon-panier", name="cart")
     */
    public function index(Cart $cart, Security $security, EntityManagerInterface $entityManager, SessionInterface $session)
    {
        $cartData = [];

        // Vérifier si l'utilisateur est connecté
        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {
            // L'utilisateur est connecté        
            $shoppingCart = new ShoppingCart($entityManager, $security);

            // Appelez la méthode getFull() pour récupérer le panier de l'utilisateur
            $cartItems = $shoppingCart->getFull();
                      
            // Parcourez les éléments du panier
            foreach ($cartItems as $cartItem) {
                // Accédez aux données du produit et de la quantité dans chaque élément du panier
                $productData = $cartItem['product'];
                $quantity = $cartItem['quantity'];

                $cartData[] = [
                    'product' => $productData,
                    'quantity' => $quantity
                ];
            }           
        } else {
            // L'utilisateur n'est pas connecté
            $cartData = $cart->getFull();   
            // Récupérer le nombre d'articles dans le panier local à partir de la variable de session
            $cartItemCount = count($cart->getFull());       
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cartData,
            
        ]);
    }

    /**
     * @Route("/cart/add/{id}", name="add_to_cart")
     */
    public function add(Cart $cart, $id, Request $request, SessionInterface $session)
    {
        $cart->add($id);

        $page = $request->query->get('page');

        if($page == 'products'){
            // Récupérer les informations de l'article ajouté depuis la source de données en utilisant l'id
            $entityManager = $this->getDoctrine()->getManager();
            $product = $entityManager->getRepository(Product::class)->find($id);

            // Récupérer les articles similaires depuis la source de données
            $category = $product->getCategory();
            $similarProducts = $entityManager->getRepository(Product::class)->findByCategory($category);

            // Mise à jour du nombre d'articles dans le panier
            $cartItemCount = $this->getCartItemCount();
            $session->set('cartItemCount', $cartItemCount);                    

            // Créer une page de confirmation avec les informations de l'article et les articles similaires
            return $this->render('confirmation.html.twig', [
                'product' => $product,
                'similarProducts' => $similarProducts,
                'cart' => $cart,
            ]);
        } else{
            return $this->redirectToRoute('cart');
        }      
    }

    // Gestion des listes de souhaits

    // Ajouter un produit à une liste de souhaits

    // Retourner la page de confirmation avant l'ajout du produit

    /**
     * @Route("/wishlist/add/{id}", name="add_to_wishlist")
     */
    public function addToWishlist($id, Security $security, SessionInterface $session, WishlistRepository $wishlistRepository){
        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {
            // Récupérer les informations de l'article ajouté depuis la source de données en utilisant l'id
            $entityManager = $this->getDoctrine()->getManager();
            $product = $entityManager->getRepository(Product::class)->find($id);

            // Ajoutez le produit à la liste de souhaits
            $wishlist = $session->get('wishlist', []); // Récupère la liste de souhaits existante ou un tableau vide s'il n'existe pas

            // Ajoutez le produit à la liste de souhaits en utilisant l'id du produit comme clé du tableau
            $wishlist[$id] = true;

            // Mettez à jour la variable de session 'wishlist' avec la nouvelle liste de souhaits
            $session->set('wishlist', $wishlist);

            // Pour afficher le select des listes de souhait

            // Récupérer les wishlists de l'utilisateur
            $user = $this->getUser();
            $userId = $user->getId();
            $wishlists = $wishlistRepository->findBy(['user' => $user]);

            // Créer une page de confirmation avec les informations de l'article et les articles similaires
            return $this->render('wishlist/wishlist.html.twig', [
                'product' => $product,
                'wishlists' => $wishlists,
            ]);
        } else {
            // Si l'utilisateur veut ajouter un produit à la liste de souhait, il doit être connecté
            return $this->redirectToRoute('app_login');                     
        }
    }

    // Ajouter le produit à la liste de souhaits choisies

    /**
     * @Route("/wishlist/add/article/{wishlistId}/{productId}", name="add_to_selected_wishlist")
     */
    public function addArticleToSelectedWishlist($wishlistId, $productId, Security $security, SessionInterface $session)
    {
        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) { 
            // Récupérer l'utilisateur actuellement connecté
            $user = $security->getUser();        
    
            // Créer une nouvelle instance de l'entité ProductWishlist
            $productWishlist = new ProductWishlist();
    
            $entityManager = $this->getDoctrine()->getManager();
    
            // Associer le produit et la liste de souhaits à cette instance
            $product = $entityManager->getRepository(Product::class)->find($productId);
            $wishlist = $entityManager->getRepository(Wishlist::class)->find($wishlistId);
    
            $productWishlist->setProduct($product);
            $productWishlist->setWishlist($wishlist);
    
            // Définir les horodatages created_at et updated_at pour l'instance
            $now = new DateTime();
            $productWishlist->setCreatedAt($now);
            $productWishlist->setUpdatedAt($now);
    
            // Persistez l'instance en l'ajoutant à l'EntityManager et en effectuant un flush pour enregistrer les modifications dans la base de données
            $entityManager->persist($productWishlist);
            $entityManager->flush();
    
            // Return a success JSON response
            return new JsonResponse(['success' => true, 'message' => 'Le produit à été ajouté à la liste de souhait']);
        } else {
            // Si l'utilisateur veut ajouter un produit à la liste de souhait, il doit être connecté
            return $this->redirectToRoute('app_login');                     
        }
    }

    // Créer une liste de souhaits

    /**
     * @Route("/record-wishlist", name="record_wishlist", methods={"POST"})
     */
    public function recordWishlist(Request $request, EntityManagerInterface $entityManager, Security $security)
    {
        $name = $request->request->get('name');

        $wishlist = new Wishlist();
        $wishlist->setName($name);

        $now = new DateTime();
        $wishlist->setCreatedAt($now);
        $wishlist->setUpdatedAt($now);
        
        // Récupérer l'utilisateur connecté (vous devez implémenter la gestion de l'authentification pour cela)
        $user = $this->getUser();
        $wishlist->setUser($user);
        
        $entityManager->persist($wishlist);
        $entityManager->flush();
        
        // Récupérer l'id de la wishlist qui vient d'être enregistrée
        $wishlistId = $wishlist->getId(); 
       
        $productId = $request->request->get('product_id');

        if ($productId != null) {
            $product = $entityManager->getRepository(Product::class)->find($productId);
            
            if ($product) {
                if ($security->isGranted('IS_AUTHENTICATED_FULLY')) { 
                    // Récupérer l'utilisateur actuellement connecté
                    $user = $security->getUser();        
            
                    // Créer une nouvelle instance de l'entité ProductWishlist
                    $productWishlist = new ProductWishlist();
            
                    $entityManager = $this->getDoctrine()->getManager();
            
                    // Associer le produit et la liste de souhaits à cette instance
                    $product = $entityManager->getRepository(Product::class)->find($productId);
                    $wishlist = $entityManager->getRepository(Wishlist::class)->find($wishlistId);
            
                    $productWishlist->setProduct($product);
                    $productWishlist->setWishlist($wishlist);
            
                    // Définir les horodatages created_at et updated_at pour l'instance
                    $now = new DateTime();
                    $productWishlist->setCreatedAt($now);
                    $productWishlist->setUpdatedAt($now);
            
                    // Persistez l'instance en l'ajoutant à l'EntityManager et en effectuant un flush pour enregistrer les modifications dans la base de données
                    $entityManager->persist($productWishlist);
                    $entityManager->flush();
                } else {
                    // Si l'utilisateur veut ajouter un produit à la liste de souhait, il doit être connecté
                    return $this->redirectToRoute('app_login');                     
                }
            }
        }
       
        return $this->redirectToRoute('wishlists');
    }

    // Afficher les listes de souhait de l'utilisateur 

    /**
     * @Route("/wishlists", name="wishlists")
     */
    public function getWishlists(Security $security)
    {
        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {
            // Récupérer les wishlists de l'utilisateur

            // Récupérer l'utilisateur connecté
            $user = $this->getUser();

            // Récupérer l'identifiant de l'utilisateur
            $userId = $user->getId();

            // Récupérer les wishlists de l'utilisateur
            $wishlists = $this->getDoctrine()->getRepository(Wishlist::class)->findBy([
                'user' => $user,
            ]);
            
            // Créer une page de confirmation avec les informations des wishlists
            return $this->render('wishlist/listwishlists.html.twig', [
                'wishlists' => $wishlists,
            ]);
        } else {
            // Si l'utilisateur veut ajouter un produit à la liste de souhait, il doit être connecté
            return $this->redirectToRoute('app_login');                     
        }
    }

    /**
     * @Route("/refreshWishlist", name="refreshWishlist")
     */
    public function refreshWishlist(Security $security)
    {
        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {
            // Récupérer les wishlists de l'utilisateur

            // Récupérer l'utilisateur connecté
            $user = $this->getUser();

            // Récupérer l'identifiant de l'utilisateur
            $userId = $user->getId();

            // Récupérer les wishlists de l'utilisateur
            $wishlists = $this->getDoctrine()->getRepository(Wishlist::class)->findBy([
                'user' => $user,
            ]);

            // Créer un tableau contenant les données des wishlists
            $wishlistData = [];
            foreach ($wishlists as $wishlist) {
                $wishlistData[] = [
                    'id' => $wishlist->getId(),
                    'name' => $wishlist->getName(),
                ];
            }

            // Retourner la réponse JSON avec les données des wishlists
            return new JsonResponse($wishlistData);
        } else {
            // Si l'utilisateur veut ajouter un produit à la liste de souhait, il doit être connecté
            return $this->redirectToRoute('app_login');
        }
    }

    // Afficher les informations d'une liste de souhaits
    
    /**
     * @Route("/wishlist_infos", name="wishlist_infos")
     */
    public function wishlistInfos(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $userId = $this->getUser()->getId();        
        $wishlistId = $request->get('wishlistId');

        $queryBuilder = $entityManager->createQueryBuilder()
        ->select('w.name AS wishlistName', 'w.created_at AS wishlistCreatedAt', 'p.id AS productId','p.name AS productName', 'p.price AS productPrice', 'p.description AS productDescription', 'p.illustration AS productIllustration', 'c.name AS categoryName')
        ->from('App\Entity\Wishlist', 'w')
        ->leftJoin('w.productWishlist', 'pw')
        ->leftJoin('pw.product', 'p')
        ->leftJoin('p.category', 'c')
        ->where('w.id = :wishlistId')
        ->andWhere('w.user = :userId')
        ->setParameter('wishlistId', $wishlistId)
        ->setParameter('userId', $userId);       
    

        try {
            $results = $queryBuilder->getQuery()->getArrayResult();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

        // Construire le tableau d'informations
        $data = [];

        foreach ($results as $result) {
            $wishlistName = $result['wishlistName'] ?? null;
            $productId = $result['productId'] ?? null;
            $productName = $result['productName'] ?? null;
            $price = $result['productPrice'] ?? null;  // Update key to 'productPrice'
            $description = $result['productDescription'] ?? null;  // Update key to 'productDescription'
            $illustration = $result['productIllustration'] ?? null;  // Update key to 'productIllustration'
            $categoryName = $result['categoryName'] ?? null;
        
            $data[] = [
                'wishlistName' => $wishlistName,
                'productId' => $productId,
                'productName' => $productName,
                'productPrice' => $price,
                'productDescription' => $description,
                'productImage' => $illustration,
                'categoryName' => $categoryName,
            ];
        }        

        return new JsonResponse($data);
    }


    // Supprimer une liste de souhaits

    /**
     * @Route("/wishlist/delete", name="delete_wishlist")
     */
    public function deleteWishlist(EntityManagerInterface $entityManager, Security $security, Request $request)
    {        
        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $wishlistId = $request->get('wishlistId');
            $wishlistName = $request->get('wishlistName');

            $wishlist = $entityManager->getRepository(Wishlist::class)->find($wishlistId);

            $wishlistItemCount = $this->cartService->getWishlistItemCount();   

            if ($wishlist) {
                // Récupérer le nom de la liste de souhait
                $wishlistName = $wishlist->getName(); 

                // Supprimer les produits associés à la liste de souhaits
                $productWishlistQuery = $entityManager->createQuery('DELETE FROM App\Entity\ProductWishlist pw WHERE pw.wishlist = :wishlistId');
                $productWishlistQuery->setParameter('wishlistId', $wishlistId);
                $productWishlistQuery->execute();

                $entityManager->remove($wishlist);
                $entityManager->flush();

                // Message de confirmation avec le nom de la liste de souhait supprimée
                $message = "La liste de souhait '$wishlistName' a été supprimée avec succès.";

                // Récupérer le nombre de listes de souhaits après la suppression
                $wishlistItemCount = $this->cartService->getWishlistItemCount();

                return new JsonResponse(['message' => $message, 'wishlistItemCount' => $wishlistItemCount]);
            } else {
                // Wishlist non trouvé, gestion de l'erreur
                $errorMessage = "La liste de souhait n'a pas été trouvée.";
                return new JsonResponse(['error' => $errorMessage], 404);
            }
        } else {
            // Si l'utilisateur veut ajouter un produit à la liste de souhait, il doit être connecté
            return $this->redirectToRoute('app_login');
        }
    }

     // Supprimer une article de la liste de souhaits

    /**
     * @Route("/wishlist/delete_product", name="delete_product", methods={"POST"})
     */
    public function deleteProductWishlist(EntityManagerInterface $entityManager, Security $security, Request $request)
{
    // Vérifiez si l'utilisateur est entièrement authentifié
    if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {
        // Récupérer l'ID du produit à supprimer depuis les données de la requête
        $productId = $request->request->get('productId');
        $wishlistId = $request->request->get('wishlistId');

        try {
            // Trouver l'entité ProductWishlist par ses IDs (productId et wishlistId)
            $productWishlist = $entityManager->getRepository(ProductWishlist::class)->findOneBy([
                'product' => $productId,
                'wishlist' => $wishlistId,
            ]);

            if ($productWishlist) {
                // Supprimer l'entité ProductWishlist
                $entityManager->remove($productWishlist);
                $entityManager->flush();

                return new JsonResponse(['success' => true, 'message' => 'Le produit a été supprimé de la liste de souhaits']);
            } else {
                return new JsonResponse(['success' => false, 'message' => 'Le produit n\'a pas été trouvé'], 404);
            }
        } catch (\Exception $e) {
            // En cas d'erreur lors de la suppression, renvoyer une réponse d'erreur
            return new JsonResponse(['success' => false, 'message' => 'Failed to remove product from wishlist: ' . $e->getMessage()], 500);
        }
    } else {
        return new JsonResponse(['success' => false, 'message' => 'User not authenticated'], 401);
    }
}


    /**
     * @Route("/cart/delete/{id}", name="delete_to_cart")
     */
    public function delete(Cart $cart, $id, Security $security, SessionInterface $session)
    {    
        $cart->delete($id);   

        // Mise à jour du nombre d'articles dans le panier
        $cartItemCount = $this->getCartItemCount();
        $session->set('cartItemCount', $cartItemCount);
        
        return $this->redirectToRoute('cart');
    }
    
    /**
     * @Route("/cart/decrease/{id}", name="decrease_to_cart")
     */
    public function decrease(Cart $cart, EntityManagerInterface $entityManager, $id)
    {
        $product = $entityManager->getRepository(Product::class)->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found.');
        }
        
        // Mettre à jour la quantité du produit dans le panier
        $cart->decrease($id); 

        // Enregistrer les modifications en base de données        
        $entityManager->flush(); 
        
        return $this->redirectToRoute('cart');
    }
}

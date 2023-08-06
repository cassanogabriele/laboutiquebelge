jQuery(document).ready(function($) {
  function updateWishlistInfo(wishlistId) {
    // Afficher les listes de souhait
    $.ajax({
        url: '/wishlist_infos',
        method: 'POST',
        data: {
            wishlistId: wishlistId
        },
        success: function(response) {
            if (response.length > 0) {
                let wishlistContent = $('<div>');

                response.forEach(function(wishlistInfo) {
                    var productItem = $('<div class="text-center wishlist-infos">');

                    productItem.append('<button type="button" class="btn btn-danger btn-sm btn-icon delete-product float-right mt-4"><i class="fa fa-times text-white"></i></button>');
                    productItem.append('<img class="img-fluid img-thumbnail small-square-image mt-3" src="uploads/' + wishlistInfo.productImage + '" alt="' + wishlistInfo.productName + '">');
                    productItem.append('<h3>' + wishlistInfo.productName + '</h3>');
                    productItem.append('<p><span class="font-weight-bold">Prix :</span>' + wishlistInfo.productPrice + '</p>');
                    productItem.append('<p><span class="font-weight-bold">Description :</span>' + wishlistInfo.productDescription + '</p>');
                    productItem.append('<p><span class="font-weight-bold">Catégorie :</span>' + wishlistInfo.categoryName + '</p>');
                    // Stocker les infos nécessaires pour la suppression d'un article de la liste de souhaits
                    productItem.append('<input type="hidden" class="product-id-input" value="' + wishlistInfo.productId + '">');
                    productItem.append('<input type="hidden" class="wishlist-id-input" value="' + wishlistId + '">');
                    productItem.append('<input type="hidden" class="product-name-input" value="' + wishlistInfo.productName + '">');

                    wishlistContent.append(productItem);
                });

                $('.col-6').empty().append(wishlistContent);
            } else {
                $('.col-6').empty();
            }
        },
        error: function(xhr, status, error) {
            console.log(error);
        }
    });
  }

  // Récupérer les produits de la liste de souhait sélcetionnée
  $(document).on('click', 'li[data-wishlist-id]', function(event) {  
    event.preventDefault();

    var wishlistId = $(this).data('wishlist-id');

    updateWishlistInfo(wishlistId);
  });

  // Créer une liste de souhaits 
  $('.wishlist-delete-btn').on('click', function(e) { 
    
  });
  
  // Ajout d'un produit à une liste de souhait sélectionné
  $('#wishlist-select').on('change', function(event) {
    let wishlistId = $('#wishlist-select option:selected').data('wishlist-id');
    let productId = $('#product_id').val();

    $.ajax({
      url: '/wishlist/add/article/' + wishlistId + '/' + productId,
      method: 'POST',
      data: {
        wishlistId: wishlistId,
        productId: productId
      },
      success: function(response) {
        if (response.success) {
          // Le produit a été ajouté avec succès
          var alertClass = 'alert-success';
        } else {
          // Une erreur s'est produite lors de l'ajout du produit
          var alertClass = 'alert-danger';
        }

        let message = `<div class="alert ${alertClass} mt-5 mb-5 text-center" style="position: relative;">${response.message}<button type="button" class="close" data-dismiss="alert" aria-label="Close" style="position: absolute; top: 50%; transform: translateY(-50%); right: 10px;">&times;</button></div>`;

        $('#wishlist-message-container').html(message);
      },
      error: function(xhr, status, error) {
        // Gestion des erreurs
        console.log(xhr.responseText);
      }
    });
  });

  // Supprimer une liste de souhaits
  $('.wishlist-delete-btn').on('click', function(e) {
    let wishlistId = $(this).closest('li').data('wishlist-id');
    let wishlistName = $(this).closest('li').data('wishlist-name');

    $.ajax({
      url: '/wishlist/delete',
      method: 'POST',
      data: {
          wishlistId: wishlistId,
          wishlistName: wishlistName,
      },
      success: function(response) {
        if (response.message) {
          // Mettre à jour la liste des listes de souhaits
          updateWishlistList(); 

          // Créer la div du message de confirmation
          var confirmationDiv = $('<div>', {
            class: 'alert alert-success alert-dismissible',
            role: 'alert'
          });

          // Ajouter le contenu du message
          confirmationDiv.text(response.message);

          // Créer le bouton de fermeture
          var closeButton = $('<button>', {
              type: 'button',
              class: 'close',
              'data-dismiss': 'alert',
              'aria-label': 'Close'
          });

          // Ajouter le symbole de fermeture au bouton de fermeture
          closeButton.html('<span aria-hidden="true">&times;</span>');

          // Ajouter le bouton de fermeture à la div du message de confirmation
          confirmationDiv.append(closeButton);

          // Ajouter la div du message de confirmation à l'élément avec l'id "confirmation-message"
          $('#confirmation-message').empty().append(confirmationDiv);

          // Mettre à jour le nombre de listes de souhaits dans le header
          $('#wishlist-count').text('(' + response.wishlistItemCount + ')');
        }
      },
      error: function(xhr, status, error) {
        if (xhr.responseJSON && xhr.responseJSON.error) {
          // Afficher le message d'erreur
          $('#confirmation-message').html('<div class="alert alert-danger alert-dismissible" role="alert">' + xhr.responseJSON.error + '</div>');
        }
      }      
    });

    // Mettre à jour la liste de souhaits
    function updateWishlistList() {
      $.ajax({
        url: '/refreshWishlist',
        method: 'GET',
        success: function(response) {
          var wishlistList = $('#wishlist-list');
    
          // Vider le contenu de la liste des listes de souhaits
          wishlistList.empty();          
          
          if (response.length > 0) {
            // Recréer la liste des listes de souhaits avec les nouvelles données
            let form = $('<form>').attr('method', 'POST');
      
            response.forEach(function(wishlist) {
                let li = $('<li>').addClass('d-flex justify-content-between align-items-center mb-2').attr('data-wishlist-id', wishlist.id);
                let link = $('<a>').attr('href', '#').text(wishlist.name);
                let deleteButton = $('<button>').attr('type', 'button').addClass('btn btn-danger btn-sm btn-icon wishlist-delete-btn');
                let deleteIcon = $('<i>').addClass('fas fa-trash');
        
                deleteButton.append(deleteIcon);
                li.append(link, deleteButton);
                form.append(li);
            });
            
            wishlistList.append(form);  
            
            // Continuer à afficher le message si il y a au moins une liste de souhaits
            let msg = '<div class="container">' + 
             '<div class="d-flex align-items-center justify-content-center" style="height: 40vh;">' +
             '<div class="alert alert-warning text-center" role="alert">' + 
             'Veuillez choisir une liste de souhait en cliquant sur son nom.' + 
             '</div></div></div>';

             $('.infos').html(msg);  
          } else {
            // Afficher le message de succès 
            $('#wishlist-message-delete-container').addClass('alert alert-success alert-dismissible text-center').html(
             response.message + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
            ); 

            // Afficher le message quand la liste de souhaits est vide          
            let content = $('<div>').addClass('container h-100')
              .append($('<h5>').addClass('card-title font-weight-bold text-center').text('Vos listes de souhaits'))
              .append($('<div>').addClass('row align-items-center h-100')
              .append($('<div>').addClass('col-12')
                  .append($('<div>').addClass('card h-100 justify-content-center alert alert-danger text-center')
                      .append($('<div>').text('vous n\'avez pas de liste de souhaits.'))
                  )
              )
             );

            $('#infos-wishlist').html(content);
          }          
        },
        error: function(xhr, status, error) {
          console.log(error);
        }
      });
    }   
  }); 

  // Suppression d'un produit de la liste de souhaits
  $(document).on('click', '.delete-product', function() {
    // Récupérer les infos
    let productId = parseInt($(this).siblings('.product-id-input').val());
    let wishlistId = parseInt($(this).siblings('.wishlist-id-input').val());
    let productName = $(this).siblings('.product-name-input').val();

    $.ajax({
        url: '/wishlist/delete_product',
        method: 'POST',
        data: {
            productId: productId,
            wishlistId: wishlistId,
            productName: productName,
        },
        success: function(response) {
            if (response.success) {
                $('.wishlist-info').empty();
                
                // Afficher le message de succès dans votre balise HTML souhaitée
                $('#message').addClass('alert alert-success alert-dismissible text-center').html(
                    response.message + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
                );

                $.ajax({
                  url: '/wishlist_infos',
                  method: 'POST',
                  data: {
                      wishlistId: wishlistId
                  },
                  success: function(response) {     
                    if (response.length > 0) {
                      updateWishlistInfo(wishlistId);                     
                    } else {
                      $('.col-6').empty();
                    }
                  },
                  error: function(xhr, status, error) {
                      console.log(error);
                  }
              });
            } else {
                $('.wishlist-info').empty();
                // Afficher le message d'erreur dans votre balise HTML souhaitée
                $('#message').addClass('alert alert-danger alert-dismissible text-center').html(
                    response.message + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
                );
            }
        },
        error: function(xhr, status, error) {
            console.log(error);
        }
    });
  });
});

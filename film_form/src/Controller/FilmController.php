<?php

namespace Drupal\film_form\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class FilmController.
 */
class FilmController extends ControllerBase {

  /**
   * Purge.
   *
   * @return string
   *   Return Hello string.
   */
  public function purge()
  {
    // Classe Générale Drupal
    //https://api.drupal.org/api/drupal/core!lib!Drupal.php/class/Drupal/9.3.x

    // Recherche de TOUS les films
    $listeFilms = Drupal::entityQuery("node")
      ->condition("type", "film")
      ->execute();

    // Stockage des noeuds
    $storage_handler = Drupal::entityTypeManager()->getStorage("node");

    // Uniquement les noeuds de type film
    $entities = $storage_handler->loadByProperties(["type" => "film"]);

    // Suppression
    $storage_handler->delete($entities);

    Drupal::messenger()->addMessage("Tous les films ont été supprimés");

    // Redirection sur la homepage
    return $this->redirect('view.frontpage.page_1');
  }
}

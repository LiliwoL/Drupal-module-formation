<?php

namespace Drupal\film_form\Controller;

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
    $listeFilms = \Drupal::entityQuery("node")
      ->condition("type", "film")
      ->execute();

    // Stockage des noeuds
    $storageHandler = \Drupal::entityTypeManager()
      ->getStorage("node");

    // Chargement des films
    $entities = $storageHandler->loadMultiple( $listeFilms );

    $storageHandler->delete( $entities );

    /*// En plus rapide
    $storage_handler = \Drupal::entityTypeManager()->getStorage("node");
    $entities = $storage_handler->loadByProperties(["type" => "film"]);
    $storage_handler->delete($entities);*/

    return [
      '#type' => 'markup',
      '#markup' => $this->t('Tous les films ont été supprimés')
    ];
  }

}

<?php

namespace Drupal\module_route\Controller;

use \Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

class RouteController extends ControllerBase
{
  // Action qui répond à une route spécifiée dans module_route.routing.yml
  public function introduction()
  {
    return new Response("Message envoyé depuis le module_route");
  }

  // Action avec un paramètre, mais qui renvoie un render_array
  public function route_avec_parametre( $from, $to)
  {
    // Dans un premier temps renvoi d'une réponse
    //return new Response("Paramètres reçus " . $from . " et " . $to);

    // Renvoi d'un render_array
    // https://www.drupal.org/docs/drupal-apis/render-api/render-arrays
    // Rendu d'un HTML brut
//    return [
//      '#markup' => "Paramètres reçus " . $from . " et " . $to
//    ];

    // Affichage du résultat à partir d'un template spécifique à ce module
    return [
      // Theme qui sera utilisé pour "rendre" cette route
      '#theme' => 'cle_du_theme',

      // Variables envoyées au theme
      '#from' => $from,
      '#to' => $to,
    ];
  }
}

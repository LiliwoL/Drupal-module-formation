<?php

use \Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

class RouteController extends ControllerBase
{
  // Action qui répond à une route spécifiée dans module_route.routing.yml
  public function introduction()
  {
    return new Response("Message envoyé depuis le module_route");
  }
}

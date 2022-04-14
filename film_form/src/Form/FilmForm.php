<?php

namespace Drupal\film_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Class FilmForm.
 */
class FilmForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'film_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['titre'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Titre'),
      '#description' => $this->t('Saisissez un titre de film à rechercher'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Rechercher'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Récupération du titre envoyé par le formulaire
    $data = $form_state->getValues();
    $titre = $data['titre'];

    // Les API ont un point d'entrée, une URL de départ
    $endPoint = "http://www.omdbapi.com/?apikey=185a318e&s=";

    // Construction avec les données du formualaire
    $url = $endPoint . $titre;

    $result = \Drupal::httpClient()->get(
      $url,
      [
        'headers' => [
          'Accept' => 'application/json',
        ],
      ]
    );

    if ($result->getStatusCode() == 200) {
      $data = json_decode($result->getBody(), true);
    }
    else {
      return $this->HandleFailed("Check Access token");
    }
    foreach ($data['Search'] as $movie)
    {
      //dd($movie);
      // On pourrait vérifier avant d'ajouter en base?
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'film');
      // Condtions AND
      $query->condition('title', $movie['Title'] );
      $query->condition('field_affiche', $movie['Poster'] );
      $query->condition('field_annee_de_sortie', $movie['Year'] . '-01-01');
      // On ne veut qu'un seul résultat
      $query->range(0, 1);
      // Exécution
      $films_similaires = $query->execute();

      if ( sizeof($films_similaires) != 0 )
      {
        //dd($films_similaires);

        // Update
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');

        // On est sûr qu'il n'y a qu'une seule case, donc on peut charger la première case du tableau $films_similaires

        // Update du node
        /*$node = $node_storage->load( reset($films_similaires) );
        $node->title = $movie['Title'];
        $node->field_affiche = $movie['Poster'];
        $node->field_annee_de_sortie = $movie['Year'] . '-01-01';
        $node->save();*/

        // Warning
        \Drupal::messenger()->addWarning("Le film " . $movie['Title'] . " existe déjà en base.");

      } else {
        // Ajout
        $node = Node::create([
          'type'        => 'film',
          'title'       => $movie['Title'],
          'field_affiche' => $movie['Poster'],
          // On avait choisi un champ de type date, on doit renvoyer Y-m-d
          'field_annee_de_sortie' => $movie['Year'] . '-01-01'
          // 'body' => $movie['Plot']
        ]);

        $node->save();

        // Confirmation
        \Drupal::messenger()->addMessage("Le film " . $movie['Title'] . " a été ajouté.");
      }
    }
  }

}

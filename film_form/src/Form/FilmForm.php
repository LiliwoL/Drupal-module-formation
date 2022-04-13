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
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      \Drupal::messenger()->addMessage($key . ': ' . $value);
    }

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
      // On pourrait ajouter en base?
      $node = Node::create([
        'type'        => 'film',
        'title'       => $movie['Title'],
        'field_affiche' => $movie['Poster'],
        'field_annee_de_sortie' => $movie['Year']
        // 'body' => $movie['Plot']
      ]);

      $node->save();
    }
    //dd($data);


  }

}

<?php

namespace Drupal\film_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

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

    // Récupération de la clé API dans la configuration du module
    $config = \Drupal::config('film_form.settings');
    $apiKey = $config->get('omdbapikey');

    // Les API ont un point d'entrée, une URL de départ
    $endPoint = "http://www.omdbapi.com/?apikey=" . $apiKey;

    // Construction avec les données du formualaire
    $urlBySearch = $endPoint . "&s=" . $titre;

    // Requête API Numéro 1
    $result = \Drupal::httpClient()->get(
      $urlBySearch,
      [
        'headers' => [
          'Accept' => 'application/json',
        ],
      ]
    );

    // Si le résultat de la requête est au statut 200
    if ($result->getStatusCode() == 200) {
      $data = json_decode($result->getBody(), true);
    }
    else {
      return $this->HandleFailed("Check Access token");
    }

    // Parcours des résultats reçus
    foreach ($data['Search'] as $movie)
    {

      // On vérifier avant d'ajouter en base que le film n'existe pas déjà
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

      // Si le film existe déjà
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

      } else { // Sinon, on l'ajoute

        // ****************
        // Deuxième requête API pour récupérer plus d'informations
        $urlByTitle = $endPoint . "&t=" . urlencode($movie['Title']);

        $moreResults = \Drupal::httpClient()->get(
          $urlByTitle,
          [
            'headers' => [
              'Accept' => 'application/json',
            ],
          ]
        );

        // Si le résultat de la deuxième requête est au statut 200
        if ($moreResults->getStatusCode() == 200) {

          // Analyse de ce que ce deuxième appel API a renvoyé
          $moreData = json_decode($moreResults->getBody(), true);

          // ************
          // Taxonomie des Genres
          $genresFromOmdb = explode(",", $moreData['Genre']); // Explose la string en tableau en séparant par les virgules
          $genresDuFilm = [];

          // Création des termes s'ils n'existent pas
          if ( sizeof($genresFromOmdb) != 0 ){
            // PArcours de tous les genres envoyés par Omdb
            foreach ( $genresFromOmdb as $genreEnCours )
            {
              if ($genreEnCours != "")
              {
                // Nettoyage du genre en retirant les espaces au début et à la fin
                $genreEnCours = trim($genreEnCours);

                //dump($genreEnCours);
                // Le terme existe?
                $genreExiste = taxonomy_term_load_multiple_by_name($genreEnCours);

                if ( $genreExiste == NULL )
                {
                  // Création du terme
                  Term::create(
                    [
                      'name' => $genreEnCours,
                      'vid' => 'genres' // Nom machine du vocabulaire de taxonomie
                    ]
                  )->save();

                } else {

                  // S'il existe, on l'ajoute au tableau de genres à ajouter au film en cours
                  $genresDuFilm[] = reset($genreExiste);

                }
              }
            }
          }
          // *************
        }

        // Ajout
        $node = Node::create([
          'type'        => 'film',
          'title'       => $movie['Title'],

          // Gestion si l'affiche est à N/A, on met une affiche lambda
          'field_affiche' => ($movie['Poster'] == "N/A") ? "https://dummyimage.com/150x200/000/fff" : $movie['Poster'],

          // On avait choisi un champ de type date, on doit renvoyer Y-m-d
          'field_annee_de_sortie' => $movie['Year'] . '-01-01',

          // Si Plot existe, il a été fourni dans le deuxième appel API
          'body' => ( isset($moreData['Plot']) ) ? $moreData['Plot'] : "",

          // Genres
          'field_genre' => $genresDuFilm,

          // BoxOffice
          // On aura retirer les virgules et le symbole $
          'field_boxoffice' => str_replace('$', '', str_replace(",", "", $moreData['BoxOffice'])),

          // Imdb rating et vote
          'field_imdbvotes' => str_replace(",", "", $moreData['imdbVotes']),
          'field_imdbrating' => $moreData['imdbRating']
        ]);

        $node->save();

        // Confirmation
        \Drupal::messenger()->addMessage("Le film " . $movie['Title'] . " a été ajouté.");
      }
    }
  }

}

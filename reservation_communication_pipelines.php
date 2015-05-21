<?php
/**
 * Utilisations de pipelines par Réservation Comunications
 *
 * @plugin     Réservation Comunications
 * @copyright  2015
 * @author     Rainer
 * @licence    GNU/GPL
 * @package    SPIP\Reservation_communication\Pipelines
 */

if (!defined('_ECRIRE_INC_VERSION'))
  return;

/*
 * Un fichier de pipelines permet de regrouper
 * les fonctions de branchement de votre plugin
 * sur des pipelines existants.
 */

/**
 * Ajouter les objets sur les vues de rubriques
 *
 * @pipeline affiche_enfants
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 **/
function reservation_communication_affiche_enfants($flux) {
  if ($e = trouver_objet_exec($flux['args']['exec']) AND $e['type'] == 'rubrique' AND $e['edition'] == false) {

    $id_rubrique = $flux['args']['id_rubrique'];
    $lister_objets = charger_fonction('lister_objets', 'inc');

    $bouton = '';
    if (autoriser('creercommunicationdans', 'rubrique', $id_rubrique)) {
      $bouton .= icone_verticale(_T("communication:icone_creer_communication"), generer_url_ecrire("communication_edit", "id_rubrique=$id_rubrique"), "communication-24.png", "new", "right") . "<br class='nettoyeur' />";
    }

    $flux['data'] .= $lister_objets('communications', array(
      'titre' => _T('communication:titre_communications_rubrique'),
      'id_rubrique' => $id_rubrique,
      'par' => 'titre'
    ));
    $flux['data'] .= $bouton;

  }
  return $flux;
}

/**
 * Ajout de liste sur la vue d'un auteur
 *
 * @pipeline affiche_auteurs_interventions
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function reservation_communication_affiche_auteurs_interventions($flux) {
  if ($id_auteur = intval($flux['args']['id_auteur'])) {

    $flux['data'] .= recuperer_fond('prive/objets/liste/communications', array(
      'id_auteur' => $id_auteur,
      'titre' => _T('communication:info_communications_auteur')
    ), array('ajax' => true));

  }
  return $flux;
}

/**
 * Ajoute un action dans le compteur rérvations ans l'espace admin (navigation)
 *
 * @pipeline reservation_compteur_action
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function reservation_communication_reservation_compteur_action($flux) {


  $flux['data']=recuperer_fond('inclure/reservation_compteur_action', $flux);

  return $flux;
}


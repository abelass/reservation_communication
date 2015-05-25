<?php

/**
 * Fichier gérant l'installation et désinstallation du plugin Réservation Comunications
 *
 * @plugin     Réservation Comunications
 * @copyright  2015
 * @author     Rainer
 * @licence    GNU/GPL
 * @package    SPIP\Reservation_communication\Installation
 */

if (!defined('_ECRIRE_INC_VERSION'))
  return;

/**
 * Inserer en base un objet generique
 * @param string $objet
 * @param int $id_parent
 * @param array|null $set
 * @return bool|int
 */
function reservation_communication_inserer($id_parent = null, $set = null) {
  $table_sql = table_objet_sql('reservation_communication');
  $trouver_table = charger_fonction('trouver_table', 'base');
  $desc = $trouver_table($table_sql);
  if (!$desc OR !isset($desc['field']))
    return 0;

  $lang_rub = "";
  $champs = array();

  $id_rubrique = $id_parent;

  $row = sql_fetsel("lang", "spip_rubriques", "id_rubrique=" . intval($id_rubrique));

  $champs['id_rubrique'] = $id_rubrique;
  $lang_rub = _request('lang') ? _request('lang') : $row['lang'];

  $champs['lang'] = ($lang_rub ? $lang_rub : $GLOBALS['meta']['langue_site']);


  if (isset($desc['field']['statut'])) {
    if (isset($desc['statut_textes_instituer'])) {
      $cles_statut = array_keys($desc['statut_textes_instituer']);
      $champs['statut'] = reset($cles_statut);
    }
    else
      $champs['statut'] = 'prepa';
  }

  if ((isset($desc['date']) AND $d = $desc['date']) OR isset($desc['field'][$d = 'date']))
    $champs[$d] = date('Y-m-d H:i:s');

  if ($set)
    $champs = array_merge($champs, $set);

  // Envoyer aux plugins
  $champs = pipeline('pre_insertion', array(
    'args' => array('table' => $table_sql, ),
    'data' => $champs
  ));

  $id = sql_insertq($table_sql, $champs);

  if ($id) {
    pipeline('post_insertion', array(
      'args' => array(
        'table' => $table_sql,
        'id_objet' => $id,
      ),
      'data' => $champs
    ));

    // controler si le serveur n'a pas renvoye une erreur
    // et associer l'auteur sinon
    // si la table n'a pas deja un champ id_auteur
    // et si le form n'a pas poste un id_auteur (meme vide, ce qui sert a annuler cette auto association)
    if ($id > 0 AND !isset($desc['field']['id_auteur'])) {
      $id_auteur = ((is_null(_request('id_auteur')) AND isset($GLOBALS['visiteur_session']['id_auteur'])) ? $GLOBALS['visiteur_session']['id_auteur'] : _request('id_auteur'));
      if ($id_auteur) {
        include_spip('action/editer_auteur');
        auteur_associer($id_auteur, array($objet => $id));
      }
    }
  }

  return $id;
}

/**
 * $c est un array ('statut', 'id_parent' = changement de rubrique)
 * statut et rubrique sont lies, car un admin restreint peut deplacer
 * un objet publie vers une rubrique qu'il n'administre pas
 *
 * @param string $objet
 * @param int $id
 * @param array $c
 * @param bool $calcul_rub
 * @return mixed|string
 */
function objet_instituer($objet, $id, $c, $calcul_rub = true) {
  if (include_spip('action/editer_' . $objet) AND function_exists($instituer = $objet . "_instituer"))
    return $instituer($id, $c, $calcul_rub);

  $table_sql = table_objet_sql($objet);
  $trouver_table = charger_fonction('trouver_table', 'base');
  $desc = $trouver_table($table_sql);
  if (!$desc OR !isset($desc['field']))
    return _L("Impossible d'instituer $objet : non connu en base");

  include_spip('inc/autoriser');
  include_spip('inc/rubriques');
  include_spip('inc/modifier');

  $sel = array();
  $sel[] = (isset($desc['field']['statut']) ? "statut" : "'' as statut");

  $champ_date = '';
  if (isset($desc['date']) AND $desc['date'])
    $champ_date = $desc['date'];
  elseif (isset($desc['field']['date']))
    $champ_date = 'date';

  $sel[] = ($champ_date ? "$champ_date as date" : "'' as date");
  $sel[] = (isset($desc['field']['id_rubrique']) ? 'id_rubrique' : "0 as id_rubrique");

  $row = sql_fetsel($sel, $table_sql, id_table_objet($objet) . '=' . intval($id));

  $id_rubrique = $row['id_rubrique'];
  $statut_ancien = $statut = $row['statut'];
  $date_ancienne = $date = $row['date'];
  $champs = array();

  $d = ($date AND isset($c[$champ_date])) ? $c[$champ_date] : null;
  $s = (isset($desc['field']['statut']) AND isset($c['statut'])) ? $c['statut'] : $statut;

  // cf autorisations dans inc/instituer_objet
  if ($s != $statut OR ($d AND $d != $date)) {
    if ($id_rubrique ? autoriser('publierdans', 'rubrique', $id_rubrique) : autoriser('instituer', $objet, $id, null, array('statut' => $s)))
      $statut = $champs['statut'] = $s;
    else
    if ($s != 'publie' AND autoriser('modifier', $objet, $id))
      $statut = $champs['statut'] = $s;
    else
      spip_log("editer_objet $id refus " . join(' ', $c));

    // En cas de publication, fixer la date a "maintenant"
    // sauf si $c commande autre chose
    // ou si l'objet est deja date dans le futur
    // En cas de proposition d'un objet (mais pas depublication), idem
    if ($champ_date) {
      if ($champs['statut'] == 'publie' OR ($champs['statut'] == 'prop' AND !in_array($statut_ancien, array(
        'publie',
        'prop'
      ))) OR $d) {
        if ($d OR strtotime($d = $date) > time())
          $champs[$champ_date] = $date = $d;
        else
          $champs[$champ_date] = $date = date('Y-m-d H:i:s');
      }
    }
  }

  // Verifier que la rubrique demandee existe et est differente
  // de la rubrique actuelle
  if ($id_rubrique AND $id_parent = $c['id_parent'] AND $id_parent != $id_rubrique AND (sql_fetsel('1', "spip_rubriques", "id_rubrique=" . intval($id_parent)))) {
    $champs['id_rubrique'] = $id_parent;

    // si l'objet etait publie
    // et que le demandeur n'est pas admin de la rubrique
    // repasser l'objet en statut 'propose'.
    if ($statut == 'publie' AND !autoriser('publierdans', 'rubrique', $id_rubrique))
      $champs['statut'] = 'prop';
  }

  // Envoyer aux plugins
  $champs = pipeline('pre_edition', array(
    'args' => array(
      'table' => $table_sql,
      'id_objet' => $id,
      'action' => 'instituer',
      'statut_ancien' => $statut_ancien,
      'date_ancienne' => $date_ancienne,
      'id_parent_ancien' => $id_rubrique,
    ),
    'data' => $champs
  ));

  if (!count($champs))
    return '';

  // Envoyer les modifs.
  objet_editer_heritage($objet, $id, $id_rubrique, $statut_ancien, $champs, $calcul_rub);

  // Invalider les caches
  include_spip('inc/invalideur');
  suivre_invalideur("id='$objet/$id'");

  /*
   if ($date) {
   $t = strtotime($date);
   $p = @$GLOBALS['meta']['date_prochain_postdate'];
   if ($t > time() AND (!$p OR ($t < $p))) {
   ecrire_meta('date_prochain_postdate', $t);
   }
   }*/

  // Pipeline
  pipeline('post_edition', array(
    'args' => array(
      'table' => $table_sql,
      'id_objet' => $id,
      'action' => 'instituer',
      'statut_ancien' => $statut_ancien,
      'date_ancienne' => $date_ancienne,
      'id_parent_ancien' => $id_rubrique,
    ),
    'data' => $champs
  ));

  // Notifications
  if ($notifications = charger_fonction('notifications', 'inc')) {
    $notifications("instituer$objet", $id, array(
      'statut' => $statut,
      'statut_ancien' => $statut_ancien,
      'date' => $date,
      'date_ancienne' => $date_ancienne
    ));
  }

  return '';
  // pas d'erreur
}

/**
 * fabrique la requete d'institution de l'objet, avec champs herites
 *
 * @param string $objet
 * @param int $id
 * @param int $id_rubrique
 * @param string $statut
 * @param array $champs
 * @param bool $cond
 * @return
 */
function objet_editer_heritage($objet, $id, $id_rubrique, $statut, $champs, $cond = true) {
  $table_sql = table_objet_sql($objet);
  $trouver_table = charger_fonction('trouver_table', 'base');
  $desc = $trouver_table($table_sql);

  // Si on deplace l'objet
  // changer aussi son secteur et sa langue (si heritee)
  if (isset($champs['id_rubrique'])) {

    $row_rub = sql_fetsel("id_secteur, lang", "spip_rubriques", "id_rubrique=" . sql_quote($champs['id_rubrique']));
    $langue = $row_rub['lang'];

    if (isset($desc['field']['id_secteur']))
      $champs['id_secteur'] = $row_rub['id_secteur'];

    if (isset($desc['field']['lang']) AND isset($desc['field']['langue_choisie']))
      if (sql_fetsel('1', $table_sql, id_table_objet($objet) . "=" . intval($id) . " AND langue_choisie<>'oui' AND lang<>" . sql_quote($langue))) {
        $champs['lang'] = $langue;
      }
  }

  if (!$champs)
    return;
  sql_updateq($table_sql, $champs, id_table_objet($objet) . '=' . intval($id));

  // Changer le statut des rubriques concernees
  if ($cond) {
    include_spip('inc/rubriques');
    //$postdate = ($GLOBALS['meta']["post_dates"] == "non" AND isset($champs['date']) AND (strtotime($champs['date']) < time()))?$champs['date']:false;
    $postdate = false;
    calculer_rubriques_if($id_rubrique, $champs, $statut, $postdate);
  }
}
?>

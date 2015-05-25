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

  }

  //Attacher les déstinataires
  $objet = _request('objet');
  $id = ${"id_$objet"};
  
  switch ($objet){
    
    case  'evenement' :
      $data = sql_select('*', 'spip_reservation_details AS rd
        LEFT JOIN spip_auteurs AS a ON rd.id_auteur = a.id_auteur', 'rd.id_evenement=' . $id);
      break;
    
    case 'article' :
      $data = sql_select('*', 'spip_evenements AS e
        LEFT JOIN spip_reservation_details AS rd ON e.id_evenement = rd.id_evenement 
        LEFT JOIN spip_auteurs AS a ON rd.id_auteur = a.id_auteur', 'e.id_article=' . $id);
      
      break;
      
    case 'rubrique' :
       $data = sql_select('*', 'spip_articles AS a
        LEFT JOIN spip_evenements AS e ON a.id_article = e.id_article,
        LEFT JOIN spip_reservation_details AS rd ON e.id_evenement = rd.id_evenement 
        LEFT JOIN spip_auteurs AS a ON spip_rd.id_auteur = a.id_auteur', 'a.id_rubrique=' . $id);
      
      break;
  }
  
  return $id;
}

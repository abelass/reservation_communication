<?php

/**
 * Fichier gérant les actions relative àm l'édition de l'objet reservation_communication
 *
 * @plugin     Réservation Comunications
 * @copyright  2015
 * @author     Rainer
 * @licence    GNU/GPL
 * @package    SPIP\Reservation_communication\Action
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
  $lang_rub = "";
  $champs = array();

  $id_rubrique = $id_parent;

  $row = sql_fetsel("lang", "spip_rubriques", "id_rubrique=" . intval($id_rubrique));

  $champs['id_rubrique'] = $id_rubrique;
  $lang_rub = _request('lang') ? _request('lang') : $row['lang'];

  $champs['lang'] = ($lang_rub ? $lang_rub : $GLOBALS['meta']['langue_site']);
  $champs['statut'] = 'prepa';
  $champs['date_redac'] = date('Y-m-d H:i:s');

  if ($set)
    $champs = array_merge($champs, $set);

  // Envoyer aux plugins
  $champs = pipeline('pre_insertion', array(
    'args' => array('table' => 'spip_reservation_communications', ),
    'data' => $champs
  ));

  $id = sql_insertq('spip_reservation_communications', $champs);

  if ($id) {
    pipeline('post_insertion', array(
      'args' => array(
        'table' => 'spip_reservation_communications',
        'id_objet' => $id,
      ),
      'data' => $champs
    ));
  }

  //Attacher les déstinataires
  if ($objet = _request('objet')) {
    $id_objet = _request('id');
    $select = array(
      'aut.email AS email_auteur',
      'res.email AS email',
      'res.id_auteur'
    );

    $statut_reservation = str_replace(',', '","', _request('statut_reservation'));

    $where = array('rd.statut IN ("' . $statut_reservation . '")');
    $group_by = 'aut.email, res.email';

    switch ($objet) {

      case  'evenement' :
        $from = 'spip_reservations_details AS rd
          LEFT JOIN spip_reservations AS res USING (id_reservation)
          LEFT JOIN spip_auteurs AS aut USING (id_auteur)';
        $where[] = 'rd.id_evenement=' . $id_objet;
        break;

      case 'article' :
        $from = 'spip_evenements AS e
          LEFT JOIN spip_reservations_details AS rd USING (id_evenement)
          LEFT JOIN spip_reservations AS res USING (id_reservation)
          LEFT JOIN spip_auteurs AS aut USING (id_auteur)';
        $where[] = 'e.id_article=' . $id_objet;

        break;

      case 'rubrique' :
        $from = 'spip_articles AS a
          LEFT JOIN spip_evenements USING (id_article)
          LEFT JOIN spip_reservations_details AS rd USING (id_evenement)
          LEFT JOIN spip_reservations AS res USING (id_reservation)
          LEFT JOIN spip_auteurs AS aut USING (id_auteur)';
        $where[] = 'a.id_rubrique=' . $id_objet;

        break;
    }

    $date = date('Y-m-d H:i:s');

    $sql = sql_select($select, $from, $where, $group_by);
    $emails = array();

    while ($data = sql_fetch($sql)) {
      $email = isset($data['email_auteur']) ? $data['email_auteur'] : $data['email'];
      $id_auteur = isset($data['id_auteur']) ? $data['id_auteur'] : '';

      if ($email and !in_array($email, $emails)) {
        sql_insertq('spip_reservation_communication_destinataires', array(
          'id_reservation_communication' => $id,
          'email' => $email,
          'id_auteur' => $id_auteur,
          'date' => $date,
        ));
      }
      $emails[] = $email;
    }
  }

  return $id;
}

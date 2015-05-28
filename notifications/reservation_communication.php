<?php
if (!defined("_ECRIRE_INC_VERSION"))
  return;

function notifications_reservation_communication_dist($quoi, $id_reservation_communication, $options) {
  include_spip('inc/config');
  $config = $options['config'];
  $envoyer_mail = charger_fonction('envoyer_mail', 'inc');

  $communication = sql_fetsel('titre,texte', 'spip_reservation_communications', 'id_reservation_communication = ' . $id_reservation_communication);

  $subject = $communication['titre'];

  if (isset($options['envoi_a'])) {
    $envoi_a = $options['envoi_a'];
  }
  else {
    $envoi_a = array();
    $sql = sql_select('email', 'spip_reservation_communication_destinataires', 'id_reservation_communication = ' . $id_reservation_communication);
    while ($data = sql_fetch($sql)) {
      $envoi_a[] = $data['email'];
    }
  }

  $message = recuperer_fond('notifications/contenu_reservation_communication', array('texte' => $communication['texte']));

  //
  // Envoyer les emails
  //

  $envoyer_mail($envoi_a, $subject, array('html' => $message));

  // Si présent -  l'api de notifications_archive
  if ($archiver = charger_fonction('archiver_notification', 'inc', true)) {
    $envoi = 'reussi';
    if (!$envoyer_mail)
      $envoi = 'echec';
    if(is_array($envoyer_a)) {
      $envoi_a = implode(',',$envoi_a);
    }
    $o = array(
      'recipients' => $envoi_a,
      'sujet' => $subject,
      'texte' => $message,
      'html' => 'oui',
      'id_objet' => $id_reservation_communication,
      'objet' => 'reservation_communication',
      'envoi' => $envoi,
      'type' => $quoi
    );

    $archiver($o);
  }
}
?>

<?php
/**
 * Déclarations relatives à la base de données
 *
 * @plugin     Réservation Comunications
 * @copyright  2015
 * @author     Rainer
 * @licence    GNU/GPL
 * @package    SPIP\Reservation_communication\Pipelines
 */

if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * Déclaration des alias de tables et filtres automatiques de champs
 *
 * @pipeline declarer_tables_interfaces
 * @param array $interfaces
 *     Déclarations d'interface pour le compilateur
 * @return array
 *     Déclarations d'interface pour le compilateur
 */
function reservation_communication_declarer_tables_interfaces($interfaces) {

	$interfaces['table_des_tables']['communications'] = 'communications';

	return $interfaces;
}


/**
 * Déclaration des objets éditoriaux
 *
 * @pipeline declarer_tables_objets_sql
 * @param array $tables
 *     Description des tables
 * @return array
 *     Description complétée des tables
 */
function reservation_communication_declarer_tables_objets_sql($tables) {

	$tables['spip_communications'] = array(
		'type' => 'communication',
		'principale' => "oui",
		'field'=> array(
			"id_communication"   => "bigint(21) NOT NULL",
			"id_rubrique"        => "bigint(21) NOT NULL DEFAULT 0", 
			"id_evenement"       => "bigint(21) NOT NULL DEFAULT 0",
			"id_article"         => "bigint(21) NOT NULL DEFAULT 0",
			"titre"              => "text NOT NULL",
			"texte"              => "longtext NOT NULL",
			"date_redac"         => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			"type"               => "varchar(25) NOT NULL DEFAULT ''",
			"html_email"         => "longtext NOT NULL",
			"texte_email"        => "longtext NOT NULL",
			"recurrence"         => "text NOT NULL",
			"email_test"         => "text NOT NULL",
			"date_envoi"         => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'", 
			"statut"             => "varchar(20)  DEFAULT '0' NOT NULL", 
			"maj"                => "TIMESTAMP"
		),
		'key' => array(
			"PRIMARY KEY"        => "id_communication",
			"KEY id_rubrique"    => "id_rubrique", 
			"KEY statut"         => "statut", 
		),
		'titre' => "titre AS titre, '' AS lang",
		'date' => "date_envoi",
		'champs_editables'  => array(),
		'champs_versionnes' => array(),
		'rechercher_champs' => array(),
		'tables_jointures'  => array(),
		'statut_textes_instituer' => array(
			'prepa'    => 'texte_statut_en_cours_redaction',
			'prop'     => 'texte_statut_propose_evaluation',
			'publie'   => 'texte_statut_publie',
			'refuse'   => 'texte_statut_refuse',
			'poubelle' => 'texte_statut_poubelle',
		),
		'statut'=> array(
			array(
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie,prop,prepa',
				'post_date' => 'date', 
				'exception' => array('statut','tout')
			)
		),
		'texte_changer_statut' => 'communication:texte_changer_statut_communication', 
		

	);

	return $tables;
}



?>
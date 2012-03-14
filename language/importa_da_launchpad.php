#!/usr/bin/php
<?php

if (!is_file('launchpad-export.tar.gz')) {
	echo "File launchpad-export.tar.gz mancante. Mettilo in questa cartella.\n";
	exit;
}

`tar zxvf launchpad-export.tar.gz`;
`cp wordpresscom-popular-posts/*.po ./; rm -r wordpresscom-popular-posts`;
`mv wordpresscom-popular-posts-es.po wordpresscom-popular-posts-es_ES.po`;
`mv wordpresscom-popular-posts-it.po wordpresscom-popular-posts-it_IT.po`;
`mv wordpresscom-popular-posts-sr.po wordpresscom-popular-posts-sr_SR.po`;
`mv wordpresscom-popular-posts-ro.po wordpresscom-popular-posts-ro_RO.po`;
`rm launchpad-export.tar.gz`;


/* NOTA BENE PER IL FUTURO:
 * Quando si aggiunge una nuova lingua, per vedere il relativo codice di
 * charset, andare qui:
 * http://codex.wordpress.org/WordPress_in_Your_Language
 */

?>

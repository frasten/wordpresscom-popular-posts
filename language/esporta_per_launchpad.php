#!/usr/bin/php
<?php
/* NOTA BENE PER IL FUTURO:
 * Quando si aggiunge una nuova lingua, per vedere il relativo codice di
 * charset, andare qui:
 * http://codex.wordpress.org/WordPress_in_Your_Language
 */

@mkdir("wordpresscom-popular-posts");
`cp *.pot wordpresscom-popular-posts/`;
`cp *.po wordpresscom-popular-posts/`;

// Casi particolari
`cd wordpresscom-popular-posts; mv wordpresscom-popular-posts-es_ES.po wordpresscom-popular-posts-es.po`;
`cd wordpresscom-popular-posts; mv wordpresscom-popular-posts-it_IT.po wordpresscom-popular-posts-it.po`;
`cd wordpresscom-popular-posts; mv wordpresscom-popular-posts-sr_SR.po wordpresscom-popular-posts-sr.po`;
`cd wordpresscom-popular-posts; mv wordpresscom-popular-posts-ro_RO.po wordpresscom-popular-posts-ro.po`;



$dir = 'wordpresscom-popular-posts';
$f=opendir($dir);//apro la directory
while(false!==($g=readdir($f))){
	if($g!="." && $g!="..") {
		if(is_dir("$dir/$g")) continue;
		$maccia = preg_match("/-([^-]+.po)$/", $g, $match);
		if (!$maccia) continue;
		//rename("$dir/$g", "$dir/{$match[1]}");
		rename("$dir/$g", "$dir/{$match[1]}");
	}
}
closedir($f);//chiudo la directory


// Creo il pacchetto
`tar czf invia_a_launchpad.tar.gz wordpresscom-popular-posts`;

`rm -r wordpresscom-popular-posts`;
echo "Creato il file invia_a_launchpad.tar.gz. Invialo qui:\n";
echo "https://translations.launchpad.net/wordpresscom-popular-posts/trunk/+pots/wordpresscom-popular-posts/+upload\n";
?>

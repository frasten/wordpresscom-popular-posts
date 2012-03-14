#!/usr/bin/php
<?php
echo "Rigenero il template...";
`php pomo/makepot.php wp-plugin . ./language/wordpresscom-popular-posts.pot wppp`;
echo "fatto.\n";


if ($handle = opendir("./language/")) {
	while ($file = readdir($handle)) {
		if (!preg_match("/(.*)\.po$/",$file,$match)) continue;
		echo "Processo il file $file.\n";
		echo "* Aggiorno dal template:\n";
		`msgmerge language/$file language/wordpresscom-popular-posts.pot > language/temp.po`;
		`mv language/temp.po language/$file`;
		echo "* Compilo il file:\n";
		`msgfmt -vvvv -o language/{$match[1]}.mo language/$file`;
	}
}
closedir($handle);

echo "Ora, in caso di messaggi non tradotti, modificali con programmi\ntipo poedit, poi riesegui questo script.\n";

?>

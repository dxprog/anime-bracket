<?php

$expectedSignature = trim(file_get_contents('https://composer.github.io/installer.sig'));
$installer = file_get_contents('https://getcomposer.org/installer');
if (hash('SHA384', $installer) === $expectedSignature) {
  echo 'Installer verified';
  $installer = str_replace(array('<?php', '?>'), '', $installer);
  eval($installer);
} else {
  echo 'Installer corrupt', PHP_EOL;
}

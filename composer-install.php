<?php

$installer = file_get_contents('https://getcomposer.org/installer');
if (hash('SHA384', $installer) === 'e115a8dc7871f15d853148a7fbac7da27d6c0030b848d9b3dc09e2a0388afed865e6a3d6b3c0fad45c48e2b5fc1196ae') {
  echo 'Installer verified';
  $installer = str_replace(array('<?php', '?>'), '', $installer);
  eval($installer);
} else {
  echo 'Installer corrupt', PHP_EOL;
}

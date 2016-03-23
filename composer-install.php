<?php

$installer = file_get_contents('https://getcomposer.org/installer');
if (hash('SHA384', $installer) === '41e71d86b40f28e771d4bb662b997f79625196afcca95a5abf44391188c695c6c1456e16154c75a211d238cc3bc5cb47') {
  echo 'Installer verified';
  $installer = str_replace(array('<?php', '?>'), '', $installer);
  eval($installer);
} else {
  echo 'Installer corrupt';
}
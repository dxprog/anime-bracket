<?php

$installer = file_get_contents('https://getcomposer.org/installer');
if (hash('SHA384', $installer) === 'a52be7b8724e47499b039d53415953cc3d5b459b9d9c0308301f867921c19efc623b81dfef8fc2be194a5cf56945d223') {
  echo 'Installer verified';
  $installer = str_replace(array('<?php', '?>'), '', $installer);
  eval($installer);
} else {
  echo 'Installer corrupt';
}
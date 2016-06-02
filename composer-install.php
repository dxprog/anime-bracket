<?php

$installer = file_get_contents('https://getcomposer.org/installer');
if (hash('SHA384', $installer) === '070854512ef404f16bac87071a6db9fd9721da1684cd4589b1196c3faf71b9a2682e2311b36a5079825e155ac7ce150d') {
  echo 'Installer verified';
  $installer = str_replace(array('<?php', '?>'), '', $installer);
  eval($installer);
} else {
  echo 'Installer corrupt';
}

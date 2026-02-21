<?php

$package = rex_addon::get('virtual_urls');
echo rex_view::title($package->i18n('Virtual URLs'));
rex_be_controller::includeCurrentPageSubPath();

<?php

/**
 * Arabic-first UI strings: reuse ar translations when locale is en
 * (e.g. APP_LOCALE=en in .env) so keys like ui.interface_preferences never leak.
 */
return require dirname(__DIR__).'/ar/ui.php';

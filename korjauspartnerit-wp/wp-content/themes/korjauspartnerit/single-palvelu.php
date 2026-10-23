<?php
/**
 * Yksittäinen palvelu. Oletuksena sivupalkillinen asettelu (sama kuin Palvelusivu-pohja).
 * Jos palvelulle on valittu "Leveä sisältö" -pohja, WordPress käyttää sitä.
 */
defined( 'ABSPATH' ) || exit;
require get_template_directory() . '/templates/sivupohja-palvelu.php';

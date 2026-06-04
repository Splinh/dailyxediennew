<?php
/**
 * Custom Code module options panel — combines Custom Script + Custom CSS.
 *
 * @package HDAddons\Modules\CustomCode
 */

\defined( 'ABSPATH' ) || exit;

?>
<div class="container mt-8">

	<?php
	// Include existing sub-module options (they are self-contained).
	require __DIR__ . '/section-scripts.php';
	require __DIR__ . '/section-css.php';
	?>
</div>

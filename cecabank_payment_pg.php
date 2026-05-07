<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

if ( ! preg_match( '/^[A-Za-z0-9]{32,128}$/', $token ) ) {
	wp_die( esc_html__( 'Sesión de pago no válida.', 'givewp_cecabank' ), '', array( 'response' => 400 ) );
}

$transient_key = 'give_cecabank_pg_' . $token;
$payload       = get_transient( $transient_key );
delete_transient( $transient_key );

if ( ! is_array( $payload ) || empty( $payload['action'] ) || empty( $payload['fields'] ) || ! is_array( $payload['fields'] ) ) {
	wp_die( esc_html__( 'Sesión de pago no válida o expirada.', 'givewp_cecabank' ), '', array( 'response' => 400 ) );
}

$action_url   = $payload['action'];
$action_parts = wp_parse_url( $action_url );
if (
	! is_array( $action_parts )
	|| empty( $action_parts['scheme'] )
	|| 'https' !== strtolower( $action_parts['scheme'] )
	|| empty( $action_parts['host'] )
	|| ! preg_match( '/(^|\.)ceca\.es$/i', $action_parts['host'] )
) {
	wp_die( esc_html__( 'Destino de pago no válido.', 'givewp_cecabank' ), '', array( 'response' => 400 ) );
}

$fields = $payload['fields'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Cecabank</title>
</head>

<body>
	<p>
		<?php echo esc_html__( 'Redirigiendo a Cecabank ...', 'givewp_cecabank' ); ?>
	</p>

	<form id="cecabank-form" action="<?php echo esc_url( $action_url ); ?>" method="post">
		<?php foreach ( $fields as $field_name => $field_value ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $field_value ); ?>" />
		<?php endforeach; ?>
	</form>
	<script type="text/javascript">
		window.onload = function () {
			document.getElementById("cecabank-form").submit();
		}
	</script>

</body>

</html>

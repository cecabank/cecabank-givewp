<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Cecabank</title>
</head>

<body>
	<p>
		Redirigiendo a Cecabank ...
	</p>

	<form id="cecabank-form" action="<?php echo $_GET['action']; ?>" method="post">
		<input type="hidden" name="MerchantID" value="<?php echo $_GET['MerchantID']; ?>" />
		<input type="hidden" name="AcquirerBIN" value="<?php echo $_GET['AcquirerBIN']; ?>" />
		<input type="hidden" name="TerminalID" value="<?php echo $_GET['TerminalID']; ?>" />
		<input type="hidden" name="TipoMoneda" value="<?php echo $_GET['TipoMoneda']; ?>" />
		<input type="hidden" name="Exponente" value="<?php echo $_GET['Exponente']; ?>" />
		<input type="hidden" name="Cifrado" value="<?php echo $_GET['Cifrado']; ?>" />
		<input type="hidden" name="Pago_soportado" value="<?php echo $_GET['Pago_soportado']; ?>" />
		<input type="hidden" name="versionMod" value="<?php echo $_GET['versionMod']; ?>" />
		<input type="hidden" name="Idioma" value="<?php echo $_GET['Idioma']; ?>" />
		<input type="hidden" name="Num_operacion" value="<?php echo $_GET['Num_operacion']; ?>" />
		<input type="hidden" name="Importe" value="<?php echo $_GET['Importe']; ?>" />
		<input type="hidden" name="URL_OK" value="<?php echo $_GET['URL_OK']; ?>" />
		<input type="hidden" name="URL_NOK" value="<?php echo $_GET['URL_NOK']; ?>" />
		<input type="hidden" name="Descripcion" value="<?php echo $_GET['Descripcion']; ?>" />
		<input type="hidden" name="datos_acs_20" value="<?php echo $_GET['datos_acs_20']; ?>" />
		<input type="hidden" name="Firma" value="<?php echo $_GET['Firma']; ?>" />
		<input type="hidden" name="firma_acs_20" value="<?php echo $_GET['firma_acs_20']; ?>" />
	</form>
	<script type="text/javascript">
		window.onload = function () {
			document.getElementById("cecabank-form").submit();
		}
	</script>

</body>

</html>
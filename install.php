<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>pWBB4</title>
	<style>
	body {
		color:white;
	}
	#wrapper {
		width:600px;
		margin-top:100px;
		margin-left:auto;
		margin-right:auto;
		background: grey;
		padding: 10px;
		border-radius: 10px;
		-moz-border-radius: 10px;
		-webkit-border-radius:10px;
		-khtml-border-radius:10px;
		border: 1px solid black;
	}
	#title {
		text-align:center;
		width:100%;
		height: 30px;
		border:2px solid #2e3338;
		background-color: #333;
		font-size:20px;
	}
	
	#content {
		border: 2px solid #2e3338;
		border-top: 0px;
		width:100%;
	}
	#text {
		padding:5px;
	}
	input[type="text"] {
		width:95%;
	}
	input[type="submit"] {
		margin-top:20px;
		float:right;
	}
	.clear {
		clear:both;
	}
	</style>
</head>
<body>
	<div id="wrapper">
		<div id="title">pWBB4 Installation</div>
		<div id="content">
			<div id="text">
				<?php
					$needFunctions = array(
						'file_get_contents',
						'unlink',
						'fopen',
						'ob_start'
					);
					
					foreach ( $needFunctions as $key => $func ) {
						if ( !function_exists($func) ) {
							echo 'Oops, da ist wohl eine Funktion deaktiviert: '.$func;
							return;
						}
					}
					session_start();
					if ( !isset($_SESSION['pWBB4Install']) || empty($_SESSION['pWBB4Install']) || $_SESSION['pWBB4Install'] == null ) $_SESSION['pWBB4Install'] = array('step' => 0);
					if ( !isset($_SESSION['pWBB4Install']['dirname']) ) $_SESSION['pWBB4Install']['dirname'] = dirname(__FILE__).'/';
					if ( !isset($_SESSION['pWBB4Install']['key']) ) $_SESSION['pWBB4Install']['key'] = hash('sha256', time().'randomkeyundso'.microtime());
					if ( !isset($_SESSION['pWBB4Install']['ip']) ) $_SESSION['pWBB4Install']['ip'] = '127.0.0.1';
					function setStep($step) {
						$_SESSION['pWBB4Install']['step'] = $step;
						ob_end_clean();
						getStep();
					}
					function getStep() {
						ob_start();
						$extra = null;
						switch($_SESSION['pWBB4Install']['step']) {
							case 0:
								if ( isset($_POST['step0']) && isset($_POST['dirname']) ) {
									$_SESSION['pWBB4Install']['dirname'] = $dirname = $_POST['dirname'];
									$_SESSION['pWBB4Install']['key'] = $_POST['key'];
									$_SESSION['pWBB4Install']['ip'] = $_POST['ip'];
									$_SESSION['pWBB4Install']['wantip'] = isset($_POST['wantip']);
									if ( is_dir($_SESSION['pWBB4Install']['dirname']) ) {
										if ( !file_exists($dirname.'samp.php') || isset($_POST['overwrite']) ) {
											return setStep(1);
										} else {
											echo 'Oops, da ist wohl schon pWBB4 installiert.';
											$extra .= '<label><input type="checkbox" name="overwrite" value="1" /> &Uuml;berschreiben</label><br />';
										}
									}else{
										echo 'Oops, das ist wohl kein Verzeichnis.';
									}
								}

								echo '<form method="post">
									Verzeichnis:<br /><input type="text" name="dirname" value="'.htmlentities($_SESSION['pWBB4Install']['dirname']).'" /><br />
									Sicherheitskey:<br /><input type="text" name="key" value="'.htmlentities($_SESSION['pWBB4Install']['key']).'" /><br />
									Sicherheitskey:<br /><input type="text" name="ip" value="'.htmlentities($_SESSION['pWBB4Install']['ip']).'" /><br />
									
									<label><input type="checkbox" name="wantip" value="1" /> Zugriff nur &uuml;ber eine IP aktivieren (erh&ouml;ht die Sicherheit)</label><br />
									'.$extra.'
									<input type="submit" name="step0" value="Weiter" />
								</form>';
							break;
							case 1:
								if ( is_dir($_SESSION['pWBB4Install']['dirname']) ) {
									if ( file_exists($_SESSION['pWBB4Install']['dirname'].'samp.php') ) {
										if ( @!unlink($_SESSION['pWBB4Install']['dirname'].'samp.php') ) {
											echo 'Oops, konnte samp.php nicht l&ouml;schen.';
											return setStep(0);
										}
									}
									$h = fopen($_SESSION['pWBB4Install']['dirname'].'samp.php', 'w+');
									if ( $h ) {
										$get = @file_get_contents('https://raw.githubusercontent.com/derpierre65/pWBB4/master/samp.php');
										if ( $get ) {
											$config = "\r\n";
											if ( !empty($_SESSION['pWBB4Install']['key']) ){
												$config .= 'define(\'_SECURITY_KEY\', \''.$_SESSION['pWBB4Install']['key'].'\');'."\r\n";
											}
											if ( isset($_SESSION['pWBB4Install']['wantip']) && !empty($_SESSION['pWBB4Install']['ip']) ){
												$config .= 'define(\'_CHECK_REMOTEADDR\', \''.$_SESSION['pWBB4Install']['ip'].'\');'."\r\n";
											}
											fwrite($h, str_replace('<?php', '<?php'.$config, $get));
											echo 'Installation abgeschlossen!<br />#define pWBB4_CONNECT_KEY "'.$_SESSION['pWBB4Install']['key'].'"';
											$_SESSION['pWBB4Install'] = null;
											@unlink(dirname(__FILE__).'/install.php');
										} else {
											echo 'Oops, die Datei konnte nicht runtergeladen werden.';
											setStep(0);
										}
										fclose($h);
									} else {
										echo 'Oops, ich konnte keine Datei erstellen.';
										setStep(0);
									}
								}else{
									echo 'Oops, das ist wohl kein Verzeichnis.';
									setStep(0);
								}
							break;
						}
					}
					getStep();
				?>
				<div class="clear"></div>
			</div>
		</div>
	</div>
</body></html>
<!-- Développé par Gabriel Tresch -->
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Chemin Serveur
$pathsite = "";
// Variables connexion base de données
$dbHost = "localhost";
$dbName = "";
$dbUser = "";
$dbPassword = "";
// Variables valeurs ajoutées dans la table stats_xdsl
$tableName = "stats_xdsl";
$characterSet = "utf8";
$characterCollate = "utf8_general_ci";
$serviceNameVarcharNb = 30;
$ligneVarcharNb = 10;
$downloadFloatNb = "4,2";
$uploadFloatNb = "4,2";
$engine = "InnoDB";
// Variables clées api. Si pas encore générées => https://eu.api.ovh.com/createToken/
$applicationKey = "";
$applicationSecret = "";
$consumerKey = "";
$endPoint = 'ovh-eu';
// Variables statistiques xdsl
$statsPeriod = "daily";
// Variables pourcentage lors de la comparaison
$percentageUpload = 0.01;
$percentageDownload = 0.01;
// Variables mail Serveur Settings
$mailHost = "";
$mailUserName = "";
$mailPassword = "";
$mailSmtpSecure = "";
$mailPort = ;
// Variables mail Recipients
$sender = "";
$recipient = "";

try
{
// Connexion à MySQL
$id_connex=new PDO("mysql:host=$dbHost;dbname=$dbName", "$dbUser", "$dbPassword", array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
}
catch(PDOException $e)
{
// En cas d'erreur
die('Erreur : '.$e->getMessage());
}

// Recupère le fichier autoload.php généré grâce à composer
require "$pathsite/vendor/autoload.php";
use \Ovh\Api;

// Création de la table stats_xdsl si elle n'existe pas.
$tableSql = " CREATE TABLE IF NOT EXISTS `$dbName`.`$tableName` (
	`id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT ,
	`serviceName` VARCHAR($serviceNameVarcharNb) NULL ,
	`ligne` VARCHAR($ligneVarcharNb) NULL ,
	`download` FLOAT($downloadFloatNb) NULL ,
	`upload` FLOAT($uploadFloatNb) NULL ,
	PRIMARY KEY (`id`)) ENGINE = $engine CHARSET=$characterSet COLLATE $characterCollate";

$id_connex->query($tableSql);

$ovh = new Api( $applicationKey,
                $applicationSecret,
                $endPoint,
                $consumerKey);

try {
    $getServiceName = $ovh->get('/xdsl');
    foreach ($getServiceName as $serviceName) {

    	$getServiceName = $ovh->get("/xdsl/$serviceName");
    	$status = $getServiceName["status"]; // On recupère le statut du serviceName.
    	$description = $getServiceName["description"];

			$get_lines = $ovh->get("/xdsl/$serviceName/lines"); // On recupère les numéros de téléphone.
			foreach ($get_lines as $line){

				if($status == "active"){ // Si le statut est actif on cherche plus d'informations sinon on passe.
					$getStatsDownload = $ovh->get("/xdsl/$serviceName/lines/$line/statistics", array(
				    	"period" => "$statsPeriod",
				    	"type" => "synchronization:download",
					));

					$val_download = $getStatsDownload["values"];// Recupère le tableau contenant les valeurs.
					$count_download = count($val_download)-1; // Compte toutes les valeurs du tableau.
					$download = $val_download[$count_download]["value"]; // Sélectionne la dernière valeur du tableau.

					$getStatsUpload = $ovh->get("/xdsl/$serviceName/lines/$line/statistics", array(
					     "period" => "$statsPeriod",
					     "type" => "synchronization:upload",
					));

					$val_upload = $getStatsUpload["values"];
					$count_upload = count($val_upload)-1;
					$upload = $val_upload[$count_upload]["value"];

					$requete = "SELECT * FROM $tableName WHERE serviceName='$serviceName'";
					$reponse=$id_connex->query($requete);
					$nb_ligne=$reponse->rowCount();
					$ligne=$reponse->fetch(PDO::FETCH_ASSOC);

					$valUploadBdd = $ligne["upload"]; // Valeur upload dans la base de données.
					$valDownloadBdd = $ligne["download"]; // Valeur download dans la base de données.
					$valserviceNameBdd = $ligne["serviceName"]; // Valeur serviceName dans la base de données.

					$serviceName_sql = $id_connex->quote($serviceName); // Quote permet de protéger une chaine afin de l'utiliser dans la requête.
					$line_sql = $id_connex->quote($line);
					$download_sql = (float)($download / 1000000); // Pour passer de bps en Mbps.
					$upload_sql = (float)($upload / 1000000);

					// Si il n'y a aucune ligne dans la base de données on ajoute tout les éléments.
					if($nb_ligne == 0){
						// Insertion des différentes valeurs dans la table stats_xdsl.
						$insertDb = " INSERT INTO $tableName (serviceName, ligne, download, upload) VALUES ($serviceName_sql, $line_sql, $download_sql, $upload_sql)";
						$add_insertDb = $id_connex->exec($insertDb);
					}
					// Sinon on fait une update des valeurs déjà présentes sur la base de données.
					else{
						// Comparaison des différentes valeurs
						 if($upload_sql < ($valUploadBdd - $valUploadBdd * $percentageUpload) || $download_sql < ($valDownloadBdd - $valDownloadBdd * $percentageDownload)){
						 // Si 20% en moins par rapport à la valeur dans la bdd.
						 	if($upload_sql != 0 || $download_sql != 0){
							// On envoie un mail.
								$mail = new PHPMailer(true);
						 		try {
				 		   		//Server settings
					 		    $mail->SMTPDebug = 0;
					 		    $mail->isSMTP();
					 		    $mail->Host = "$mailHost";
					 		    $mail->SMTPAuth = true;
					 		    $mail->Username = "$mailUserName";
					 		    $mail->Password = "$mailPassword";
					 		    $mail->SMTPSecure = "$mailSmtpSecure";
					 		    $mail->Port = "$mailPort";

					 		    //Recipients
					 		    $mail->setFrom("$sender");
							    $mail->addAddress("$recipient");
					 		    // $mail->addReplyTo("", "");
					 		    //$mail->addCC('cc@example.com');
					 		    //$mail->addBCC("");
					 		    //$mail->addBCC("");
					 		    //Attachments
					 		    //$mail->addAttachment('');
					 		    //$mail->addAttachment('');

					 		    //Content
					 		    $mail->isHTML(true);
					 		    $mail->CharSet = "UTF-8";
					 		    $mail->Subject = "Perte de débits - $description";
					 		    $mail->Body = "Le client $description, dont la ligne téléphonique est $line rencontre une perte de débit.<br> Le download est passé de $valDownloadBdd Mbps à $download_sql Mbps et l'upload est passé de $valUploadBdd Mbps à $upload_sql Mbps";
					 		    //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
							    $mail->send();
						 		} catch (Exception $e) {
						 			echo $mail->ErrorInfo;
						 		}
						 	}
						}
						// Update des différentes valeurs dans la table stats_xdsl.
						$updateDb = " UPDATE $tableName SET serviceName = $serviceName_sql, ligne = $line_sql, download = $download_sql, upload = $upload_sql WHERE serviceName = $serviceName_sql";
						$add_updateDb = $id_connex->exec($updateDb);
					}
				}
			}
    }
}	catch (GuzzleHttp\Exception\ClientException $e) {
    $response = $e->getResponse();
    $responseBodyAsString = $response->getBody()->getContents();
    echo $responseBodyAsString;
}

$id_connex = null;
?>

# Arobase Communication - OVH xDSL Monitoring

## Description
Ce script permet de récupérer l'upload et le download d'un client grâce à l'API OVH et de les stocker dans une base de données automatiquement. Un fois que le script est relancé, on actualise les anciennes valeurs de la base de données avec les nouvelles. Si les valeurs qui vont être mises à jour sont 20% inférieure à la celles présentes dans la base, un mail sera envoyé au destinataire de votre choix.

## Installation
Ce script fonctionne avec [composer](https://docs.ovh.com/fr/hosting/installation-de-composer-sur-les-hebergements-mutualises/), il vous suffit donc d'ajouter le code dont vous aurez besoin dans le fichier `composer.json`.

Code nécessaire au fonctionnement du script :

```json
"phpmailer/phpmailer": "~6.0",
"ovh/ovh": "~2.0"
```

## Utilisation
La seule chose que vous devez faire pour que le script fonctionne est de modifier ou remplir les variables suivantes :

```php
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
// Variables clées api.  
$applicationKey = "";
$applicationSecret = "";
$consumerKey = "";
$endPoint = 'ovh-eu';
// Variables statistiques xdsl
$statsPeriod = "daily";
// Variables pourcentage lors de la comparaison
$percentageUpload = 0.2;
$percentageDownload = 0.2;
// Variables mail Serveur Settings
$mailHost = "";
$mailUserName = "";
$mailPassword = "";
$mailSmtpSecure = "";
$mailPort = ;
// Variables mail Recipients
$sender = "";
$recipient = "";
$mailSubject = "Statistiques download et upload xdsl";
```
Pour obtenir les différentes clées nécessaire il suffit de vous rendre [ici](https://eu.api.ovh.com/createToken/).

## Nouveautés
- OVH xDSL Monitoring 1.0

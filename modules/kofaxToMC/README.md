# CD78 — Capture Kofax (kofaxToMC)
Le code sur cette branche sert à injecter dans Maarch Courrier les lots fournis par la numérisation via Kofax.

Le script se lance dans une console avec la commande :

```bash
bash kofax_capture.sh
```

## À faire
* envoi de notifications par mail dans `php/notify.php`

## Fait
* bloquer les lots à la moindre erreur et les déplacer dans un dossier `dossierErreur/error_XXX/dir/subdir/` avec `XXX` le code de l’erreur
	* fait
* définir s’il faut supprimer ou déplacer les fichiers traités pour éviter de les traiter à chaque lancement du script
	* déplacement mis en place --> à tester --> test ok
* commentaire (champs xml `/Root/Comments`) en annotation du courrier
	* fait

## À configurer pour le client
* MAARCH_WS_USER username de webservices
* MAARCH_WS_PASS password de webservices
* MAARCH_WS_URL url de webservices
* droits et périmètre de cet utilisateur
* autoriser les formats xml et ers pour les pièces-jointes
* NOTIFY_EMAIL email à notifier en cas d’erreur

## Notes
### xml:
* DirectorateName: destination (entité traitante)
* documents: nom.pdf/nom.pdf.ers/[hash]#nom2.pdf/nom2.pdf.ers/[hash2]#
* documentCount: nombre de fichiers
* Comments: annotation (optionnel)
* BatchReceptionDate: date de réception (optionnel)

### contrôle du hashage:
* il s’agit du hash des PDF
* TimeStampHashAlgorithm: algo de hashage
* hash dans /documents

### arborescence
* dossiers "LOT": un par direction (entité)
* sous-dossiers "PLI": un par courrier + PJs + ers + xml
* dans le code, les **chemins d’accès aux fichiers** doivent être paramétrables facilement, selon l’arborescence

mettre nom fichier principal dans champ custom

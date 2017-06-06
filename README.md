# Maarch Courrier
Gestionnaire Électronique de Correspondances – Libre et Open Source –

**Dernière version stable V17.06**

Démonstration : http://demo.maarchcourrier.com/  
Build : https://sourceforge.net/projects/maarch/files/Maarch%20Courrier/MaarchCourrier-17.06.tar.gz  
VM : https://sourceforge.net/projects/maarch/files/Maarch%20Courrier/VMs/Maarch%20Courrier%2017.06%20Prod.ova  
Documentation : http://wiki.maarch.org/Maarch_Courrier  


## Installation 
1. Vérifiez que vous avez l'ensemble des [pré-requis](http://wiki.maarch.org/Maarch_Courrier/fr/Install/Prerequis/latest)
2. Décompressez *MaarchCourrier-17.06.tar.gz* dans votre zone web
3. Vérifiez votre vhost Apache
4. Laissez-vous guider par notre installeur à [http://IP.ouDomaine.tld/MaarchCourrier/install/](http://wiki.maarch.org/Maarch_Courrier/1.5/fr/Manuel_administrateur/Fonctionnalit%C3%A9s/Gestion_installeur/latest)


## Requis techniques

* Apache2.x
* PostgreSQL 9.x
* PHP 5.6.* ou PHP 7.0.*
   * Extensions PHP (adaptées à votre version de PHP) : PHP-[XSL](http://php.net/manual/en/book.xsl.php), PHP-[XML-RPC](http://php.net/manual/en/book.xmlrpc.php), PHP-[Gettext](http://php.net/manual/en/book.gettext.php), PHP-[GD](http://php.net/manual/en/book.image.php), PHP-[PostgreSQL](http://php.net/manual/en/book.pgsql.php), PHP-[PDO_PGSQL](http://php.net/manual/en/ref.pdo-pgsql.php), PHP-[Multibyte String](http://php.net/manual/en/book.mbstring.php), PHP-[Mcrypt](http://php.net/manual/en/book.mcrypt.php), PHP-[IMAP](http://php.net/manual/en/book.imap.php), PHP-[SOAP](http://php.net/manual/en/book.soap.php)
   * Bibliothèques pear/SOAP (pour php < 7.0), pear/CLITools
* [ImageMagick](http://imagemagick.org/), avec PHP-[ImageMagick](http://php.net/manual/en/book.imagick.php)
* [Ghostscript](https://www.ghostscript.com/)
* [7-zip](http://www.7-zip.org/)
* [wkhtmltopdf et wkhtmltoimage](http://wkhtmltopdf.org/downloads.html) 
* [LibreOffice](http://libreoffice.org/) pour la conversion de documents
* Java Runtime Environment >= 7


###  Recommandations pour le php.ini

error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT  
display_errors (On)  
short_open_tags (On)  
magic_quotes_gpc (Off)  


## Le coin des developpeurs
[Maarch Developer handbook](http://wiki.maarch.org/Maarch_Courrier/1.5/fr/Install/DeveloperHandbook)




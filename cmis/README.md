# CMIS returns 

Routing can generate Atom or JSON returns directly through the URL by starting the URL by __atom__ or __browser__.

For example `http://localhost/browser` and `http://localhost/atom`.
 
The output is handle by a [strategy pattern](http://www.phptherightway.com/pages/Design-Patterns.html) _via_ a factory pattern into `src/Utils::factoryOutput`.
    
If you want to implement a new output, you have to implement the `OutputStrategyInterface` to your class. 

# Routing 

The routing is handle by [Altorouter](http://altorouter.com/), if you want to add a new route, you just have to edit the `index.php` file.
 
# Configuration 

Everything is contain into an _ini_ file into `conf/conf.ini`

The default configuration is : 

```
[maarch]
vendorName = "Maarch"
productName = "Maarch"
productVersion = "1.6"

[CMIS]
repositoryId = "-default-"
repositoryName = ""
repositoryDescription = ""
rootFolder = "/"
cmisVersionSupported = "1.1"
principalIdAnonymous = "guest"
principalIdAnyone = "GROUP_EVERYONE"

[capabilities]
ACL = "none"
AllVersionsSearchable = "false"
Changes = "none"
ContentStreamUpdatability = "none"
GetDescendants = "true"
GetFolderTree = "false"
Multifiling = "false"
PWCSearchable = "false"
PWCUpdatable = "false"
Query = "metadataonly"
Renditions = "none"
Unfiling = "true"
VersionSpecificFiling = "false"
Join = "none"

[upload]
acceptedType[] = "txt"
acceptedType[] = "png"
acceptedType[] = "jpg"
acceptedType[] = "pdf"
acceptedType[] = "gif"
acceptedType[] = "doc"
acceptedType[] = "xls"
acceptedType[] = "odt"
acceptedType[] = "ods"

```

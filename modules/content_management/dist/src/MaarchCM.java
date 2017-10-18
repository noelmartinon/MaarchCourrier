/**
 * Jdk platform : 1.8
 */

/**
 * SVN version 141
 */

package com.maarch;

//import java.applet.Applet;
import java.io.*;
import java.lang.reflect.InvocationTargetException;
import java.net.MalformedURLException;
import java.net.URL;
import java.nio.file.FileVisitResult;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.nio.file.SimpleFileVisitor;
import java.nio.file.attribute.BasicFileAttributes;
import java.security.PrivilegedActionException;
import java.util.ArrayList;
import java.util.HashSet;
import java.util.Hashtable;
import java.util.Iterator;
import java.util.List;
import java.util.Set;
import java.util.logging.Level;
import java.util.logging.Logger;
//import javax.swing.JApplet;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;

import netscape.javascript.JSException;
import org.apache.http.client.config.CookieSpecs;
import org.apache.http.client.config.RequestConfig;
import org.apache.http.client.methods.CloseableHttpResponse;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.client.protocol.HttpClientContext;
import org.apache.http.entity.AbstractHttpEntity;
import org.apache.http.impl.client.*;
import org.apache.http.impl.cookie.BasicClientCookie;
import org.w3c.dom.Document;
import org.w3c.dom.NodeList;
import org.xml.sax.SAXException;
import netscape.javascript.JSObject;

import javax.swing.JOptionPane;
import org.apache.http.NameValuePair;
import org.apache.http.client.entity.UrlEncodedFormEntity;
import org.apache.http.message.BasicNameValuePair;

/**
 * MaarchCM class manages webservices between end user desktop and Maarch
 * @author maarch and DIS
 */
public class MaarchCM {

    //INIT PARAMETERS
    protected String url;
    protected String idApplet;
    protected String objectType;
    protected String objectTable;
    protected String objectId;
    protected String cookie;
    protected String clientSideCookies;
    protected String uniqueId;
    protected String convertPdf;
    protected String onlyConvert;

    protected String domain;
    protected String userLocalDirTmp;
    protected String userMaarch;
    protected String messageStatus;
    static Hashtable messageResult = new Hashtable();

    //XML PARAMETERS
    protected String status;
    protected String appPath;
    protected String appPath_convert;
    protected String fileContent;
    protected String fileContentVbs;
    protected String vbsPath;
    protected String fileContentExe;
    protected String useExeConvert;
    protected String fileExtension;
    protected String error;
    protected String endMessage;
    protected String os;
    protected String fileContentTosend;
    protected String pdfContentTosend;

    private  final HttpClientContext httpContext = HttpClientContext.create();
    private CloseableHttpClient httpClient; // Apache HttpClient yet to be instantiated

    public MyLogger logger;
    public FileManager fM;
    public String fileToEdit;
    public String editMode;    
    public String programName;


    
    public List<String> fileToDelete = new ArrayList<String>();
    
    
    public static void main(String[] args) throws JSException {
            MaarchCM MaarchCM = new MaarchCM();
            MaarchCM.start(args);
    }

    /**
     * Launch of the JNLP
     */
    public void start(String[] args) throws JSException {
        
        System.out.println("----------BEGIN----------");
        System.out.println("----------ARGUMENTS----------");
        
        int index;

        for (index = 0; index < args.length; ++index)
        {
            System.out.println("args[" + index + "]: " + args[index]);
        }
        url = args[0];
        objectType = args[1];
        objectTable = args[2];
        objectId = args[3];
        uniqueId = args[4];
        cookie = args[5];
        clientSideCookies = args[6];
        idApplet = args[7];
        userMaarch = args[8];
        convertPdf = args[9];
        onlyConvert = args[10];
        
        System.out.println("URL : " + url);
        System.out.println("OBJECT TYPE : " + objectType);
        System.out.println("ID APPLET : " + idApplet);
        System.out.println("OBJECT TABLE : " + objectTable);
        System.out.println("OBJECT ID : " + objectId);
        System.out.println("UNIQUE ID : " + uniqueId);
        System.out.println("COOKIE : " + cookie);
        System.out.println("CLIENTSIDECOOKIES : " + clientSideCookies);
        System.out.println("USERMAARCH : " + userMaarch);
        System.out.println("CONVERTPDF : " + convertPdf);
        System.out.println("ONLYCONVERT : " + onlyConvert);
        System.out.println("----------CONTROL PARAMETERS----------");
        
        if (
                isURLInvalid() ||
                isObjectTypeInvalid() ||
                isObjectTableInvalid() ||
                isObjectIdInvalid() ||
                isCookieInvalid()
        ) {
            System.out.println("PARAMETERS NOT OK ! END OF APPLICATION");
            //System.exit(0);
            try {
                //MaarchCM.getAppletContext().showDocument(new URL("error.html"));
                //Go to an appropriate error page
            } catch (Exception e) {
                //Nothing
            }
        }

        System.out.println("----------END PARAMETERS----------");
        
        if ("empty".equals(uniqueId)) {
            uniqueId = null;
        }
        
        if ("empty".equals(clientSideCookies)) {
            clientSideCookies = null;
        }
        
        // The following code is to ensure a high level of management for HTTP cookies
        BasicCookieStore cookieStore = new BasicCookieStore();
        // Loading the cookie store with the Maarch cookie provided by the server
        cookieStore.addCookie(getCookieFromString(cookie));
        if (
                clientSideCookies != null && 
                clientSideCookies.length() > 0
        ) {
            System.out.println("clientSideCookies : " + clientSideCookies);
            // Within the whole cookie string returned from JavaScript, cookies are separated by a semicolon followed by a space
            // Let's get an array where cookies are stored with a "name=value" pattern
            String[] cookies = clientSideCookies.split(";\\s");
            System.out.println("cookies : " + cookies);
            // Iterate through the cookie array to retrieve each cookie name ans value and load the cookie store
            for (String nameValue : cookies) {
                cookieStore.addCookie(getCookieFromString(nameValue)); // Loading the cookie store
            }
        }
        httpContext.setCookieStore(cookieStore); // Assign all the cookies retrieved from JavaScript
        // Apply a Cookie policy: https://hc.apache.org/httpcomponents-client-ga/tutorial/html/statemgmt.html
        RequestConfig globalConfig = RequestConfig.custom().setCookieSpec(CookieSpecs.DEFAULT).build();

        // Pick up the best Apache HttpClient to do the job
        // which will allows for the automatic retrieval of the user session Kerberos ticket,
        // so this app will be able to properly talk with a proxy
        if ("win".equals(os) && WinHttpClients.isWinAuthAvailable()) {
            // Instantiation of the Apache HttpClient for Windows 7
            httpClient = WinHttpClients.custom().setDefaultRequestConfig(globalConfig).setDefaultCookieStore(cookieStore).build();
            System.out.println("The Apache HttpClient for Windows 7 was picked up");
        } else {
            // Instantiation of the generic Apache HttpClient
            HttpClientBuilder httpClientBuilder = HttpClients.custom();
            httpClientBuilder.useSystemProperties();
            httpClientBuilder.setDefaultRequestConfig(globalConfig);
            httpClientBuilder.setDefaultCookieStore(cookieStore);
            httpClient = httpClientBuilder.build();
            System.out.println("The generic Apache HttpClient was picked up");
        }

        if (httpClient == null) {
            System.out.println("NO HTTP CLIENT WAS INSTANTIATED, THE APPLICATION WILL FAIL!");
        }

        try {
            editObject();
            //TODO exit of JNLP
            //MaarchCM.destroy();
            //MaarchCM.stop();
            System.exit(0);
        } catch (Exception ex) {
            Logger.getLogger(MaarchCM.class.getName()).log(Level.SEVERE, null, ex);
        }
    }

    /**
     * Controls the url parameter
     * @return boolean
     */
    private boolean isURLInvalid() {
        try {
            URL address = new URL(url); // Trying to build a valid URL
            address.openConnection().connect(); // Trying to open a valid connection
            domain = address.getHost(); // Retrieve the domain used
            System.out.println("DOMAIN USED IS: " + domain);
            return false; //success
        } catch (MalformedURLException e) {
            System.out.println("the URL is not a valid form " + url);
        } catch (IOException e) {
            System.out.println("the connection couldn't be etablished " + url);
        }
        return true; //default is failure
    }

    /**
     * Controls the objectType parameter
     * @return boolean
     */
    private boolean isObjectTypeInvalid() {
        Set<String> whiteList = new HashSet<>();
        whiteList.add("template");
        whiteList.add("templateStyle");
        whiteList.add("attachmentVersion");
        whiteList.add("attachmentUpVersion");
        whiteList.add("resource");
        whiteList.add("attachmentFromTemplate");
        whiteList.add("attachment");
        whiteList.add("outgoingMail");
        if (whiteList.contains(objectType)) return false; //success
        System.out.println("ObjectType not in the authorized list " + objectType);
        return true; //default is failure
    }

    /**
     * Controls the objectTable parameter
     * @return boolean
     */
    private boolean isObjectTableInvalid() {
        Set<String> whiteList = new HashSet<>();
        whiteList.add("res_letterbox");
        whiteList.add("res_business");
        whiteList.add("res_x");
        whiteList.add("res_attachments");
        whiteList.add("mlb_coll_ext");
        whiteList.add("business_coll_ext");
        whiteList.add("res_version_letterbox");
        whiteList.add("res_version_business");
        whiteList.add("res_version_x");
        whiteList.add("res_view_attachments");
        whiteList.add("res_view");
        whiteList.add("res_view_letterbox");
        whiteList.add("res_view_business");
        whiteList.add("templates");
        if (whiteList.contains(objectTable)) return false; //success
        System.out.println("objectTable not in the authorized list " + objectTable);
        return true; //default is failure
    }

    /**
     * Controls the objectId parameter
     * @return boolean
     */
    private boolean isObjectIdInvalid() {
        if (objectId != null && objectId.length() > 0) return false; //success
        System.out.println("objectId is null or empty " + objectId);
        return true; //default is failure
    }

    /**
     * Controls the cookie parameter
     * @return boolean
     */
    private boolean isCookieInvalid() {
        if (cookie != null && cookie.length() > 0) return false; //success
        System.out.println("cookie is null or empty " + cookie);
        return true; //default is failure
    }

    /**
     * Build a cookie from a String
     * @param nameValue
     * @return BasicClientCookie
     */
    private BasicClientCookie getCookieFromString(String nameValue) {
        int separator = nameValue.indexOf('='); // Locating the equal character
        String name = nameValue.substring(0, separator); // Getting everything before the equal character
        String value = nameValue.substring(separator + 1); // Getting everything after the equal character
        BasicClientCookie cookie = new BasicClientCookie(name, value);
        cookie.setPath("/");
        cookie.setDomain(domain);
        return cookie;
    }

    public void createPDF(String docxFile, String directory, boolean isUnix) {
        logger.log("createPDF ", Level.INFO);
        try {
            System.out.println("mode ! : " + editMode);
            //patch onlyConvert
            if (onlyConvert.equals("true")) {
                if ("linux".equals(os) || "mac".equals(os)) {
                    editMode = "libreoffice";
                } else {
                    if("soffice.exe".equals(programName)){
                        editMode = "libreoffice";
                    }else{
                        editMode = "office"; 
                    }
                }
            }
            boolean conversion = true;
            String cmd = "";
            if (docxFile.contains(".odt") || docxFile.contains(".ods") || docxFile.contains(".ODT") || docxFile.contains(".ODS")) {
                logger.log("This is opendocument ! ", Level.INFO);
                if (isUnix) {
                    cmd = "libreoffice -env:UserInstallation=file://"+userLocalDirTmp+idApplet+"_conv/ --headless --convert-to pdf --outdir \"" + userLocalDirTmp.substring(0, userLocalDirTmp.length() - 1) + "\" \"" + docxFile + "\"";
                } else {
                    String convertProgram;
                    convertProgram = fM.findPathProgramInRegistry("soffice.exe");
                    cmd = convertProgram + " \"-env:UserInstallation=file:///"+userLocalDirTmp.replace("\\", "/")+idApplet+"_conv/\" --headless --convert-to pdf --outdir \"" + userLocalDirTmp.substring(0, userLocalDirTmp.length() - 1) + "\" \"" + docxFile + "\" \r\n";
                }

            } else if (docxFile.contains(".doc") || docxFile.contains(".docx") || docxFile.contains(".DOC") || docxFile.contains(".DOCX")) {
                logger.log("This is MSOffice document ", Level.INFO);
                if (useExeConvert.equals("false")) {
                    if (isUnix) {
                        cmd = "libreoffice -env:UserInstallation=file://"+userLocalDirTmp+idApplet+"_conv\\ --headless --convert-to pdf --outdir \"" + userLocalDirTmp.substring(0, userLocalDirTmp.length() - 1) + "\" \"" + docxFile + "\"";
                    } else if(editMode.equals("libreoffice")){
                        String convertProgram;
                        convertProgram = fM.findPathProgramInRegistry("soffice.exe");
                        cmd = convertProgram + " \"-env:UserInstallation=file:///"+userLocalDirTmp.replace("\\", "/")+idApplet+"_conv/\" --headless --convert-to pdf --outdir \"" + userLocalDirTmp.substring(0, userLocalDirTmp.length() - 1) + "\" \"" + docxFile + "\" \r\n";
                    }else{
                        cmd = "cmd /C c:\\Windows\\System32\\cscript \"" + vbsPath + "\" \"" + docxFile + "\" /nologo \r\n";      
                    }
                } else {

                    StringBuffer buffer = new StringBuffer(docxFile);
                    buffer.replace(buffer.lastIndexOf("."), buffer.length(), ".pdf");
                    String pdfOut = buffer.toString();

                    cmd = "cmd /C \"" + userLocalDirTmp + "Word2Pdf.exe\" \"" + docxFile + "\" \"" + pdfOut + "\" \r\n";
                }
            } else {
                conversion = false;
            }

            if (conversion) {
                logger.log("EXEC PATH : " + cmd, Level.INFO);
                FileManager fM = new FileManager();

                Process proc_vbs;
                if (isUnix) {
                    //cmd = "cscript \""+vbsPath+"\" \""+docxFile+"\" /nologo \r\n";
                    final Writer outBat;
                    outBat = new OutputStreamWriter(new FileOutputStream(appPath_convert), "CP850");
                    logger.log("--- cmd sh  --- " + cmd, Level.INFO);
                    outBat.write(cmd);
                    //outBat.write("exit \r\n");
                    outBat.close();

                    File myFileBat = new File(appPath_convert);
                    myFileBat.setReadable(true, false);
                    myFileBat.setWritable(true, false);
                    myFileBat.setExecutable(true, false);

                    /*String cmd2 = "start /WAIT /MIN "+appPath_convert+" \r\n";
                    final Writer outBat2 = new OutputStreamWriter(new FileOutputStream(appPath), "CP850");
                    outBat2.write(cmd2);
                    outBat2.write("exit \r\n");
                    outBat2.close();*/

                    /*File myFileBat2 = new File(appPath);
                    myFileBat2.setReadable(true, false);
                    myFileBat2.setWritable(true, false);
                    myFileBat2.setExecutable(true, false);*/

                    final String exec_vbs = "\"" + appPath + "\"";
                    proc_vbs = fM.launchApp(appPath_convert);
                } else {
                    proc_vbs = fM.launchApp(cmd);
                }
                proc_vbs.waitFor();
            }

        } catch (Throwable e) {
            logger.log("Erreur ! : " + e, Level.SEVERE);
            e.printStackTrace();
        }
    }

    /**
     * Retrieve the xml message from Maarch and parse it
     * @param flux_xml xml content message
     */
    public void parse_xml(InputStream flux_xml) throws SAXException, IOException, ParserConfigurationException {
        logger.log("----------BEGIN PARSE XML----------", Level.INFO);
        DocumentBuilder builder = DocumentBuilderFactory.newInstance().newDocumentBuilder();

        try {
            Document doc = builder.parse(flux_xml);
            messageResult.clear();
            NodeList level_one_list = doc.getChildNodes();
            for (Integer i = 0; i < level_one_list.getLength(); i++) {
                NodeList level_two_list = level_one_list.item(i).getChildNodes();
                if ("SUCCESS".equals(level_one_list.item(i).getNodeName())) {
                    for (Integer j = 0; j < level_one_list.item(i).getChildNodes().getLength(); j++) {
                        messageResult.put(level_two_list.item(j).getNodeName(), level_two_list.item(j).getTextContent());
                    }
                    messageStatus = "SUCCESS";
                } else if ("ERROR".equals(level_one_list.item(i).getNodeName())) {
                    for (Integer j = 0; j < level_one_list.item(i).getChildNodes().getLength(); j++) {
                        messageResult.put(level_two_list.item(j).getNodeName(), level_two_list.item(j).getTextContent());
                    }
                    messageStatus = "ERROR";
                }
            }
        } catch (SAXException | IOException e) {

            logger.log("ERREUR : Le document n'a pas pu être transféré du coté client. Assurez-vous que le modèle n'est pas corrompu et que la zone de stockage des templates soit correct.", Level.SEVERE);
            messageStatus = "ERROR";
            messageResult.put("ERROR", "ERREUR : Le document n'a pas pu être transféré du coté client. Assurez-vous que le modèle n'est pas corrompu et que la zone de stockage des templates soit correct.");
            JOptionPane.showMessageDialog(null, "ERREUR ! L'édition de votre document a échoué. Assurez-vous que le modèle n'est pas corrompu et que la zone de stockage des modèles soit correct.");
        }
        logger.log("----------END PARSE XML----------", Level.INFO);
    }

    /**
     * Manage the return of program execution
     * @param result result of the program execution
     */
    public void processReturn(Hashtable result) {
        Iterator itValue = result.values().iterator();
        Iterator itKey = result.keySet().iterator();
        while (itValue.hasNext()) {
            String value = (String) itValue.next();
            String key = (String) itKey.next();
            logger.log(key + " : " + value, Level.INFO);
            if ("STATUS".equals(key)) status = value;
            if ("OBJECT_TYPE".equals(key)) objectType = value;
            if ("OBJECT_TABLE".equals(key)) objectTable = value;
            if ("OBJECT_ID".equals(key)) objectId = value;
            if ("UNIQUE_ID".equals(key)) uniqueId = value;
            if ("COOKIE".equals(key)) cookie = value;
            if ("CLIENTSIDECOOKIES".equals(key)) clientSideCookies = value;
            if ("APP_PATH".equals(key)) ; //appPath = value;
            if ("FILE_CONTENT".equals(key)) fileContent = value;
            if ("FILE_CONTENT_VBS".equals(key)) fileContentVbs = value;
            if ("VBS_PATH".equals(key)) vbsPath = value;
            if ("FILE_CONTENT_EXE".equals(key)) fileContentExe = value;
            if ("USE_EXE_CONVERT".equals(key)) useExeConvert = value;
            if ("FILE_EXTENSION".equals(key)) fileExtension = value;
            if ("ERROR".equals(key)) error = value;
            if ("END_MESSAGE".equals(key)) endMessage = value;
        }
        //send message error to Maarch if necessary
        if (!error.isEmpty()) {
            //TODO exit of the JNLP
            //destroy();
            //stop();
            System.exit(0);
        }
    }

    /**
     * Main function of the class
     * enables you to edit document with the user favorit editor
     * @return nothing
     * @throws java.lang.Exception
     */
    public String editObject() throws Exception, InterruptedException, JSException {

        System.out.println("----------BEGIN EDIT OBJECT---------- LGI by Maarch 02/10/2017");
        System.out.println("----------BEGIN LOCAL DIR TMP IF NOT EXISTS----------");
        os = System.getProperty("os.name").toLowerCase();
        boolean isUnix = os.contains("nix") || os.contains("nux");
        boolean isWindows = os.contains("win");
        boolean isMac = os.contains("mac");
        userLocalDirTmp = System.getProperty("user.home");

        fM = new FileManager();

        if (isWindows) {
            System.out.println("This is Windows");
            userLocalDirTmp = userLocalDirTmp + "\\maarchTmp\\";
            appPath = userLocalDirTmp + "start.bat";
            appPath_convert = userLocalDirTmp + "conversion_"+idApplet+".bat";
            os = "win";
        } else if (isMac) {
            System.out.println("This is Mac");
            userLocalDirTmp = userLocalDirTmp + "/maarchTmp/";
            appPath = userLocalDirTmp + "start.sh";
            appPath_convert = userLocalDirTmp + "conversion_"+idApplet+".sh";
            os = "mac";
        } else if (isUnix) {
            System.out.println("This is Unix or Linux");
            userLocalDirTmp = userLocalDirTmp + "/maarchTmp/";
            appPath = userLocalDirTmp + "start.sh";
            appPath_convert = userLocalDirTmp + "conversion_"+idApplet+".sh";
            os = "linux";
        } else {
            System.out.println("Your OS is not supported!!");
        }
        fileToDelete.add(appPath_convert);
        
        System.out.println("Create the logger");
        logger = new MyLogger(userLocalDirTmp);
        
        System.out.println("APP PATH: " + appPath);
        System.out.println("----------BEGIN LOCAL DIR TMP IF NOT EXISTS----------");

        String info = fM.createUserLocalDirTmp(userLocalDirTmp, os);

        if (info == "ERROR") {
            logger.log("ERREUR : Permissions insuffisante sur votre répertoire temporaire maarch", Level.SEVERE);
            messageStatus = "ERROR";
            messageResult.clear();
            messageResult.put("ERROR", "ERREUR : Permissions insuffisante sur votre répertoire temporaire maarch");
            JOptionPane.showMessageDialog(null, "ERREUR ! Permissions insuffisante sur votre répertoire temporaire maarch.");
            processReturn(messageResult);
        }

        System.out.println("Create the logger");
        logger = new MyLogger(userLocalDirTmp);

        /*logger.log("Delete thefile if exists", Level.INFO);
        FileManager.deleteFilesOnDir(userLocalDirTmp, "thefile");*/
        
        logger.log("----------PARAM ----------", Level.INFO);
        logger.log("URL : " + url, Level.INFO);
        logger.log("OBJECT TYPE : " + objectType, Level.INFO);
        logger.log("ID APPLET : " + idApplet, Level.INFO);
        logger.log("OBJECT TABLE : " + objectTable, Level.INFO);
        logger.log("OBJECT ID : " + objectId, Level.INFO);
        logger.log("UNIQUE ID : " + uniqueId, Level.INFO);
        logger.log("COOKIE : " + cookie, Level.INFO);
        logger.log("CLIENTSIDECOOKIES : " + clientSideCookies, Level.INFO);
        logger.log("USERMAARCH : " + userMaarch, Level.INFO);
        logger.log("ONLYCONVERT : " + onlyConvert, Level.INFO);

        logger.log("----------BEGIN OPEN REQUEST----------", Level.INFO);
        String urlToSend;
        
        urlToSend = url + "?action=editObject&objectType=" + objectType
            + "&objectTable=" + objectTable + "&objectId=" + objectId
            + "&uniqueId=" + uniqueId;
        
        
        logger.log("FIRST URL CALL : " + urlToSend, Level.INFO);
        sendHttpRequest(urlToSend, "none", false);
        logger.log("MESSAGE STATUS : " + messageStatus, Level.INFO);
        logger.log("MESSAGE RESULT : ", Level.INFO);
        processReturn(messageResult);
        logger.log("----------END OPEN REQUEST----------", Level.INFO);

        fileToEdit = "thefile_" + idApplet + "." + fileExtension;
            
        logger.log("----------BEGIN CREATE THE BAT TO LAUNCH IF NECESSARY----------", Level.INFO);
        logger.log("create the file : " + appPath, Level.INFO);
        fM.createBatFile(
                appPath,
                userLocalDirTmp,
                fileToEdit,
                os,
                idApplet
        );
        logger.log("----------END CREATE THE BAT TO LAUNCH IF NECESSARY----------", Level.INFO);

        if ("ok".equals(status)) {
            logger.log("RESPONSE OK", Level.INFO);
            
            if ("true".equals(convertPdf)) {
                logger.log("CREATE FILE IN LOCAL PATH", Level.INFO);
                if (useExeConvert.equals("false")) {
                    logger.log("---------- VBS FILE ----------", Level.INFO);
                    logger.log(" Path = " + vbsPath, Level.INFO);
                    if (vbsPath.equals("")) vbsPath = userLocalDirTmp + "DOC2PDF_VBS.vbs";
                    boolean isVbsExists = fM.isPsExecFileExists(vbsPath);
                    if (!isVbsExists) fM.createFile(fileContentVbs, vbsPath);
                } else {
                    boolean isConvExecExists = fM.isPsExecFileExists(userLocalDirTmp + "Word2Pdf.exe");
                    if (!isConvExecExists) fM.createFile(fileContentExe, userLocalDirTmp + "Word2Pdf.exe");
                }
            }

            logger.log("----------BEGIN EXECUTION OF THE EDITOR----------", Level.INFO);
            logger.log("CREATE FILE IN LOCAL PATH", Level.INFO);
            fM.createFile(fileContent, userLocalDirTmp + fileToEdit);
            fileToDelete.add(userLocalDirTmp + fileToEdit);
            
            fileContentTosend = "";
            
            if (onlyConvert.equals("false")) {
                Thread theThread;
                theThread = new Thread(new ProcessLoop(this));

                theThread.start();

                String actualContent;
                
                do {
                    theThread.sleep(3000);
                    File fileTotest = new File(userLocalDirTmp + fileToEdit);
                    if (fileTotest.canRead()) {
                        actualContent = FileManager.encodeFile(userLocalDirTmp + fileToEdit);
                        if (!fileContentTosend.equals(actualContent)) {
                            fileContentTosend = actualContent;
                            logger.log("----------[SECURITY BACKUP] BEGIN SEND OF THE OBJECT----------", Level.INFO);
                            String urlToSave = url + "?action=saveObject&objectType=" + objectType
                                    + "&objectTable=" + objectTable + "&objectId=" + objectId
                                    + "&uniqueId=" + uniqueId + "&step=backup&userMaarch=" + userMaarch;
                            logger.log("[SECURITY BACKUP] URL TO SAVE : " + urlToSave, Level.INFO);
                            sendHttpRequest(urlToSave, fileContentTosend, false);
                            logger.log("[SECURITY BACKUP] MESSAGE STATUS : " + messageStatus, Level.INFO);
                        }
                    } else {
                        logger.log(userLocalDirTmp + fileToEdit + " FILE NOT READABLE !!!!!!", Level.INFO);
                    }
                }
                while (theThread.isAlive());
            
                theThread.interrupt();
            }

            

            logger.log("----------END EXECUTION OF THE EDITOR----------", Level.INFO);

            logger.log("----------BEGIN RETRIEVE CONTENT OF THE OBJECT----------", Level.INFO);

            fileContentTosend = FileManager.encodeFile(userLocalDirTmp + fileToEdit);

            logger.log("----------END RETRIEVE CONTENT OF THE OBJECT----------", Level.INFO);
            
            logger.log("conversion pdf ? " + convertPdf , Level.INFO);

            if ("true".equals(convertPdf)) {
                if ((fileExtension.equalsIgnoreCase("docx") || fileExtension.equalsIgnoreCase("doc") || fileExtension.equalsIgnoreCase("docm") || fileExtension.equalsIgnoreCase("odt") || fileExtension.equalsIgnoreCase("ott"))) {
                    logger.log("----------CONVERSION PDF----------", Level.INFO);
                    
                    createPDF(userLocalDirTmp + fileToEdit, userLocalDirTmp, isUnix);

                    String pdfFile = userLocalDirTmp + "thefile_" + idApplet + ".pdf";

                    logger.log("----------BEGIN RETRIEVE CONTENT OF THE OBJECT----------", Level.INFO);
                    if (fM.isPsExecFileExists(pdfFile)) {
                        pdfContentTosend = FileManager.encodeFile(pdfFile);
                        fileToDelete.add(pdfFile);
                    } else {
                        pdfContentTosend = "null";
                        logger.log("ERREUR DE CONVERSION PDF !", Level.WARNING);
                        JOptionPane.showMessageDialog(null, "Attention ! La conversion PDF a échoué mais le document a bien été transféré.");
                    }
                    logger.log("----------END RETRIEVE CONTENT OF THE OBJECT----------", Level.INFO);

                    logger.log("---------- FIN CONVERSION PDF----------", Level.INFO);
                }else{
                    pdfContentTosend = "not allowed";
                    logger.log("Conversion not allowed for this extension : " + fileExtension, Level.INFO);
                }
            }
            

            String urlToSave = url + "?action=saveObject&objectType=" + objectType
                    + "&objectTable=" + objectTable + "&objectId=" + objectId
                    + "&uniqueId=" + uniqueId + "&idApplet=" + idApplet + "&step=end&userMaarch=" + userMaarch
                    + "&onlyConvert=" + onlyConvert;
            logger.log("----------BEGIN SEND OF THE OBJECT----------", Level.INFO);
            logger.log("URL TO SAVE : " + urlToSave, Level.INFO);
            sendHttpRequest(urlToSave, fileContentTosend, true);
            logger.log("MESSAGE STATUS : " + messageStatus, Level.INFO);
            logger.log("LAST MESSAGE RESULT : ", Level.INFO);
            processReturn(messageResult);

            if ("true".equals(convertPdf)) {
                if (pdfContentTosend == "null") {
                    endMessage = endMessage + " mais la conversion pdf n'a pas fonctionné (le document ne pourra pas être signé)";
                }
            }

            logger.log("----------END SEND OF THE OBJECT----------", Level.INFO);
        } else {
            logger.log("RESPONSE KO", Level.WARNING);
        }
        logger.log("----------END EDIT OBJECT----------", Level.INFO);
        
        //delete tmp files
        FileManager.deleteSpecificFilesOnDir(fileToDelete);
        
        //delete env libreoffice instance
        File dir_app = new File(userLocalDirTmp+idApplet);
        if (dir_app.exists()) {
            Path directory = Paths.get(userLocalDirTmp+idApplet);
            Files.walkFileTree(directory, new SimpleFileVisitor<Path>() {
                @Override
                public FileVisitResult visitFile(Path file, BasicFileAttributes attrs) throws IOException {
                        Files.delete(file);
                        return FileVisitResult.CONTINUE;
                }

                @Override
                public FileVisitResult postVisitDirectory(Path dir, IOException exc) throws IOException {
                        Files.delete(dir);
                        return FileVisitResult.CONTINUE;
                }
            });
        }
        //delete env libreoffice convert instance
        File dir_app_conv = new File(userLocalDirTmp+idApplet+"_conv");
        if (dir_app_conv.exists()) {
            Path directory = Paths.get(userLocalDirTmp+idApplet+"_conv");
            Files.walkFileTree(directory, new SimpleFileVisitor<Path>() {
                @Override
                public FileVisitResult visitFile(Path file, BasicFileAttributes attrs) throws IOException {
                        Files.delete(file);
                        return FileVisitResult.CONTINUE;
                }

                @Override
                public FileVisitResult postVisitDirectory(Path dir, IOException exc) throws IOException {
                        Files.delete(dir);
                        return FileVisitResult.CONTINUE;
                }
            });
        }

        return "ok";
    }

    /**
     * Launch the external program and wait his execution end
     * @return boolean
     */
    public Boolean launchProcess() throws PrivilegedActionException, InterruptedException, IllegalArgumentException, IllegalAccessException, InvocationTargetException {
        Process proc;

        logger.log("LAUNCH THE EDITOR !", Level.INFO);
        
        if ("linux".equals(os) || "mac".equals(os)) {
            editMode = "libreoffice";
            proc = fM.launchApp(appPath);
        } else {
            logger.log("FILE TO EDIT : " + userLocalDirTmp + fileToEdit, Level.INFO);

            programName = fM.findGoodProgramWithExt(fileExtension);
            String pathProgram;
            pathProgram = fM.findPathProgramInRegistry(programName);
            String options;
            System.out.println("check prog name : "+programName);
            System.out.println("check path : "+pathProgram);
            if("soffice.exe".equals(programName)){   
                if("\"null\"".equals(pathProgram)){
                    System.out.println(programName+" not found! switch to microsoft office...");
                    programName = "office.exe";
                    pathProgram = fM.findPathProgramInRegistry(programName);
                    options = fM.findGoodOptionsToEdit(fileExtension);
                }else{
                    options = "\"-env:UserInstallation=file:///" + userLocalDirTmp.replace("\\", "/") + idApplet +"/\" ";          
                }
            }else{
                if("\"null\"".equals(pathProgram)){
                    System.out.println(programName+" not found! switch to libreoffice...");
                    programName = "soffice.exe";
                    pathProgram = fM.findPathProgramInRegistry(programName);
                    options = "\"-env:UserInstallation=file:///" + userLocalDirTmp.replace("\\", "/") + idApplet +"/\" ";
                }else{
                    options = fM.findGoodOptionsToEdit(fileExtension);
                }
            }
            
            if("soffice.exe".equals(programName)){
                editMode = "libreoffice";
            }else{
                editMode = "office"; 
            }
            logger.log("PROGRAM NAME TO EDIT : " + programName, Level.INFO);
            logger.log("OPTION PROGRAM TO EDIT " + options, Level.INFO);
            logger.log("PROGRAM PATH TO EDIT : " + pathProgram, Level.INFO);
            
            
            String pathCommand;
            pathCommand = pathProgram + " " + options + "\"" + userLocalDirTmp + fileToEdit + "\"";
            logger.log("PATH COMMAND TO EDIT " + pathCommand, Level.INFO);
            proc = fM.launchApp(pathCommand);
        }
        proc.waitFor();
        logger.log("END OF THE PROCESS", Level.INFO);

        return true;
    }

    /**
     * Send an http request to Maarch
     * @param theUrl url to contact Maarch
     * @param postRequest the request
     * @param endProcess end request
     */
    public void sendHttpRequest(String theUrl, final String postRequest, final boolean endProcess) throws UnsupportedEncodingException {
        System.out.println("URL request : " + theUrl);

        // Inner class representing the payload to be posted via HTTP
        AbstractHttpEntity entity = new AbstractHttpEntity() {
            public boolean isRepeatable() {
                return false; // must be implemented
            }

            public long getContentLength() {
                return -1; // must be implemented
            }

            public boolean isStreaming() {
                return false; // must be implemented
            }

            public InputStream getContent() throws IOException {
                return new ByteArrayInputStream(postRequest.getBytes());
            }

            public void writeTo(OutputStream out) throws IOException {
                System.out.println("METHOD 'WriteTo' WAS CALLED!");
                if (!"none".equals(postRequest)) {
                    Writer writer = new OutputStreamWriter(out, "UTF-8");
                    // Using a StringBuffer rather than multiple "+" operators results in much better performance!
                    StringBuffer sb = new StringBuffer();
                    if ("true".equals(convertPdf)) {
                        if (endProcess) {
                            // Prepending "null" saves from testing "if(pdfContentTosend != null)"
                            if ("null".equalsIgnoreCase(pdfContentTosend)) {
                                sb.append("fileContent=");
                                sb.append(fileContentTosend);
                                sb.append("&fileExtension=");
                                sb.append(fileExtension);
                            } else {
                                sb.append("fileContent=");
                                sb.append(fileContentTosend);
                                sb.append("&fileExtension=");
                                sb.append(fileExtension);
                                sb.append("&pdfContent=");
                                sb.append(pdfContentTosend);
                            }
                        } else {
                            sb.append("fileContent=");
                            sb.append(fileContentTosend);
                            sb.append("&fileExtension=");
                            sb.append(fileExtension);
                        }
                    } else {
                        sb.append("fileContent=");
                        sb.append(fileContentTosend);
                        sb.append("&fileExtension=");
                        sb.append(fileExtension);
                    }
                    
                    writer.write(sb.toString());
                    writer.flush();
                }
            }
        };
        HttpPost request = new HttpPost(theUrl); // Construct a HTTP post request
        System.out.println("BUILT REQUEST: " + request);
        
        
        // Request parameters and other properties.
        List<NameValuePair> params = new ArrayList<NameValuePair>(2);
        
        if ("true".equals(convertPdf)) {
            if (endProcess) {
                // Prepending "null" saves from testing "if(pdfContentTosend != null)"
                if ("null".equalsIgnoreCase(pdfContentTosend)) {
                    params.add(new BasicNameValuePair("fileContent", fileContentTosend));
                    params.add(new BasicNameValuePair("fileExtension", fileExtension));
                } else {
                    params.add(new BasicNameValuePair("fileContent", fileContentTosend));
                    params.add(new BasicNameValuePair("fileExtension", fileExtension));
                    params.add(new BasicNameValuePair("pdfContent", pdfContentTosend));
                }
            } else {
                params.add(new BasicNameValuePair("fileContent", fileContentTosend));
                params.add(new BasicNameValuePair("fileExtension", fileExtension));
            }
        } else {
            params.add(new BasicNameValuePair("fileContent", fileContentTosend));
            params.add(new BasicNameValuePair("fileExtension", fileExtension));
        }
        
        request.setEntity(new UrlEncodedFormEntity(params, "UTF-8"));
        System.out.println("FULL REQUEST" + request);
        try {
            System.out.println("COOKIES TO BE SENT: " + httpContext.getCookieStore().getCookies()); // Show the cookies to be sent
            CloseableHttpResponse response = httpClient.execute(request, httpContext); // Carry out the HTTP post request
            System.out.println(response);
            if (response == null) {
                System.out.println("NO RESPONSE, THE APPLICATION WILL FAIL!");
            } else {
                parse_xml(response.getEntity().getContent()); // Process the response from the server
                response.close();
            }
        } catch (Exception ex) {
            logger.log("erreur: " + ex, Level.SEVERE);
            JOptionPane.showMessageDialog(null, "ERREUR ! La connexion au serveur a été interrompue, le document édité n'a pas été sauvegardé !");
        }
    }
    
    /**
     * Class to manage the execution of an external program
     */
    public class ProcessLoop extends Thread {
        public MaarchCM MaarchCM;

        public ProcessLoop(MaarchCM MaarchCM){
            this.MaarchCM = MaarchCM;
        }

        public void run() {
            try {
                MaarchCM.launchProcess();
            } catch (PrivilegedActionException ex) {
                Logger.getLogger(MaarchCM.class.getName()).log(Level.SEVERE, null, ex);
            } catch (InterruptedException ex) {
                Logger.getLogger(MaarchCM.class.getName()).log(Level.SEVERE, null, ex);
            } catch (IllegalArgumentException ex) {
                Logger.getLogger(MaarchCM.class.getName()).log(Level.SEVERE, null, ex);
            } catch (IllegalAccessException ex) {
                Logger.getLogger(MaarchCM.class.getName()).log(Level.SEVERE, null, ex);
            } catch (InvocationTargetException ex) {
                Logger.getLogger(MaarchCM.class.getName()).log(Level.SEVERE, null, ex);
            }
        }
    }
}


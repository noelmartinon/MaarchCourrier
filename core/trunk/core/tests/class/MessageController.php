<?php

class MessageController
    extends DOMDocument
{
	
    private $logLevel;
    private $debug;
    private $xpath;
    
    public function __set($name, $value)
    {
        switch($name) {
            case 'logLevel' : $this->logLevel = $value;
            case 'debug' : $this->debug = $value;
        }
    }
    
    public function loadMessageFile($messageFile)
    {
        if(!$this->documentElement) {
            $this->registerNodeClass('DOMElement', 'MessageDefinition');
            $messageDefinitions = $this->CreateElement('messageDefinitions');
            $this->appendChild($messageDefinitions);
            $this->xpath = new DOMXPath($this);
        } else {
            $messageDefinitions = $this->documentElement;
        }
        
        $MessageFileXml = new DOMDocument();
        $MessageFileXml->load($messageFile);
        
        $xPath = new DOMXPath($MessageFileXml);
        
        $Messages = $xPath->query('/messages/message');
        for($i=0; $i<$Messages->length; $i++) {
           $importedMessage = $this->importNode($Messages->item($i), true);
           $messageDefinitions->appendChild($importedMessage);
        }
    
    }
    
    private function xpath($query, $contextElement=false) 
    {
        if(!$contextElement) $contextElement = $this->documentElement;
        return $this->xpath->query($query, $contextElement);
    }
    
    
    public function getMessageDefinition($messageId)
    {
        $messageDefinitions = $this->xpath("//message[@id='".$messageId."']");
        if($messageDefinitions->length === 0) return false;
        $messageDefinition = $messageDefinitions->item(0);
        return $messageDefinition;
    }
    
    public function getTexts(
        $messageIdPrefix, 
        $messageLang = false
        )
    {
        if(!$messageLang) $messageLang = $_SESSION['config']['lang'];
        
        $messagesTexts = array();
        $messageDefinitions = $this-xpath("//message[starts-with(@id, '".$messageIdPrefix."')]");
        
        for($i=0; $i<$messageDefinitions->length; $i++) {
            $messageDefinition = $messageDefinitions->item($i);
            $messageText = $this->makeMessageText($messageDefinition, $messageLang);
            $messageId = $messageDefinition->id;
            $messagesTexts[$messageId] = $messageText;
        }
        return $messagesTexts;
    }
    
    public function getMessageText(
        $messageId,
        $messageLang = false,
        $messageParams = array()
        )
    {
        if(!$messageLang) $messageLang = $_SESSION['config']['lang'];
        
        // Get message definition
        $messageDefinition = $this->getMessageDefinition($messageId);
        
        // Get Text
        $messageText = $this->makeMessageText(
            $messageDefinition,
            $messageLang,
            $messageParams
        );
        if(!$messageText) return $messageId;
        
        return $messageText;
    }
    
    private function makeMessageText(
        $messageDefinition,
        $messageLang,
        $messageParams = array()
        
        )
    {
        // Get message text in requested language
        $messageTexts = $this->xpath("./text[@lang='".$messageLang."']", $messageDefinition);
        // No text defined for language, return id
        if($messageTexts->length === 0) {
            return $messageDefinition->messageId;
        }
        $messageText = $messageTexts->item(0)->nodeValue;
        $messageText = @vsprintf($messageText, $messageParams);         
        return $messageText;
    
    }
    
    public function sendMessage(
        $messageId,
        $messageLang = false,
        $messageParams = array()
        )
    {
        if(!$messageLang) $messageLang = $_SESSION['config']['lang'];
        
        // Get message definition
        $messageDefinition = $this->getMessageDefinition($messageId);
        
        // Make Text
        $messageText = $this->makeMessageText(
            $messageDefinition,
            $messageLang,
            $messageParams           
        );

        // Get backtrace
        $backtrace = debug_backtrace();
        $messageBacktrace = $backtrace[1];
        
        // Create message object
        $message = new Message(
            $messageDefinition->level, 
            $messageDefinition->id, 
            $messageText,
            $messageLang,
            $messageBacktrace
            );
        
        $_SESSION['messages'][] = $message;
        return $message;
    }
    
}

class MessageDefinition 
    extends DOMelement
{
    
    function __get($name) 
    {
        if($this->hasAttribute($name)) {
            return $this->getAttribute($name);
        }
    }
    

}

class Message
{
    const INFO      = 0;
    const WARNING   = 1;
    const ERROR     = 2;
    const FATAL     = 3;
   
    public $timestamp;
    public $level;
    public $id;
    public $text;
    public $lang;
    public $file; 
    public $line; 
    public $class;
    public $func; 
    
    
            
    function Message($level, $id, $text, $lang, $backtrace)
    {
        $this->timestamp = date('Y-m-d H-i-s.u');
        $this->level = $level;
        $this->id = $id;
        $this->text = trim($text);
        $this->lang = $lang;
        $this->file = $backtrace['file'];
        $this->line = $backtrace['line'];
        $this->class = $backtrace['class'];
        $this->func = $backtrace['function'];
    }

}
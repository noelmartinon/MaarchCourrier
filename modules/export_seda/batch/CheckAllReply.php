<?php
$checkAllReply = new CheckAllReply();

require_once __DIR__ . '/../RequestSeda.php';
require_once __DIR__ . '/../class/AbstractMessage.php';
require_once __DIR__ . '/../CheckReply.php';

$CheckReply = new CheckReply();
$CheckReply->checkAll();

class CheckAllReply
{
    protected $token;
    protected $SAE;
    protected $db;
    protected $checkReply;

    public function __construct()
    {
        $this->initSession();
    }

    private function initSession()
    {
        try {
            include('Maarch_CLITools/ArgsParser.php');
            ;
        } catch (IncludeFileError $e) {
            echo 'Maarch_CLITools required ! \n (pear.maarch.org)\n';
            exit(106);
        }

        // Defines scripts arguments
        $argsparser = new ArgsParser();
        // The config file
        $argsparser->add_arg(
                    'config',
                    array(
                        'short' => 'c',
                        'long' => 'config',
                        'mandatory' => true,
                        'help' => 'Config file path is mandatory.',
                    )
                );

        $options = $argsparser->parse_args($GLOBALS['argv']);
        // If option = help then options = false and the script continues ...
        if ($options == false) {
            exit(0);
        }

        $txt = '';
        foreach (array_keys($options) as $key) {
            if (isset($options[$key]) && $options[$key] == false) {
                $txt .= $key . '=false,';
            } else {
                $txt .= $key . '=' . $options[$key] . ',';
            }
        }

        $xml = simplexml_load_file($options['config']);

        $_SESSION['config']['lang']               = (string)$xml->CONFIG->Lang;
        $_SESSION['config']['corepath']           = (string)$xml->CONFIG->MaarchDirectory;
        $_SESSION['config']['custom_override_id'] = (string)$xml->CONFIG->CustomId;
        $_SESSION['config']['app_id']             = (string)$xml->CONFIG->MaarchApps;

        $_SESSION['config']['databaseserver']     = (string)$xml->CONFIG_BASE->databaseserver;
        $_SESSION['config']['databaseserverport'] = (string)$xml->CONFIG_BASE->databaseserverport;
        $_SESSION['config']['databaseuser']       = (string)$xml->CONFIG_BASE->databaseuser;
        $_SESSION['config']['databasepassword']   = (string)$xml->CONFIG_BASE->databasepassword;
        $_SESSION['config']['databasename']       = (string)$xml->CONFIG_BASE->databasename;
        $_SESSION['config']['databasetype']       = (string)$xml->CONFIG_BASE->databasetype;
        $_SESSION['custom_override_id']           = (string)$xml->CONFIG->CustomId;
        $_SESSION['collection_id_choice']         = (string)$xml->COLLECTION->Id;
        $_SESSION['tablename']['docservers']      = 'docservers';
    }
}

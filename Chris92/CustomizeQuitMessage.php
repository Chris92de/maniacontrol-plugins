<?php

namespace Chris92;

use ManiaControl\ManiaControl;
use ManiaControl\Plugins\Plugin;

/**
 * ManiaControl CustomizeQuitMessage Plugin
 *
 * @author Chris92
 */

class CustomizeQuitMessage implements Plugin {
    /**
     * Constants
     */
    const PLUGIN_ID = 31; //register plugin here to receive ID: http://maniacontrol.com/user/plugins/new
    const PLUGIN_VERSION = 0.1;
    const PLUGIN_NAME = 'CustomizeQuitMessage';
    const PLUGIN_AUTHOR = 'Chris92';
    const PLUGIN_DESC = 'Allows easy customization of the screen which shows when trying to quit a server.';
    /**
     * Private Properties
     */

    const SETTING_QUITML_XML = 'Link to XML to display:';
    const SETTING_QUITML_SERVERURL = 'Send to Server URL (#qjoin=login@title)';
    const SETTING_QUITML_FAVORITES = 'Propose to add server to favorites';
    const SETTING_QUITML_BUTTON_DELAY = 'Delay Quit Button (in ms)';

    /**
     * @var maniaControl $maniaControl
     */
    private $maniaControl = null;

    /**
     * Prepares the Plugin
     *
     * @param ManiaControl $maniaControl
     * @return mixed
     */
    public static function prepare(ManiaControl $maniaControl) {
        //do nothing
        $maniaControl->settingManager->initSetting(get_class(), self::SETTING_QUITML_XML, '');
        $maniaControl->settingManager->initSetting(get_class(), self::SETTING_QUITML_SERVERURL, '');
        $maniaControl->settingManager->initSetting(get_class(), self::SETTING_QUITML_FAVORITES, true);
        $maniaControl->settingManager->initSetting(get_class(), self::SETTING_QUITML_BUTTON_DELAY, 0);

    }

    /**
     * Load the plugin
     *
     * @param ManiaControl $maniaControl
     * @return bool
     */
    public function load(ManiaControl $maniaControl) {
        $this->maniaControl = $maniaControl;
        if($this->maniaControl->settingManager->getSetting($this, self::SETTING_QUITML_XML) != ''){
            $messageXml = file_get_contents($this->maniaControl->settingManager->getSetting($this, self::SETTING_QUITML_XML));
            $serverLink = $this->maniaControl->settingManager->getSetting($this, self::SETTING_QUITML_SERVERURL);
            $boolFavorites = $this->maniaControl->settingManager->getSetting($this, self::SETTING_QUITML_FAVORITES);
            $quitDelay = $this->maniaControl->settingManager->getSetting($this, self::SETTING_QUITML_BUTTON_DELAY);
            if ($serverLink != '') {
                $this->maniaControl->client->customizeQuitDialog($messageXml, $serverLink, $boolFavorites, $quitDelay);
            } else {
                $this->maniaControl->client->customizeQuitDialog($messageXml, '', $boolFavorites, $quitDelay);
            }
        }
        else{
            $this->maniaControl->chat->sendError('Plugin "Customize QuitMessage" could not be activated. Please configure it first.');
            $this->unload();
        }
    }
    
    public function unload() {
        unset($this->maniacontrol);
    }
    
    /**
     * Get plugin id
     *
     * @return int
     */
    public static function getId() {
        return self::PLUGIN_ID;
    }

    /**
     * Get Plugin Name
     *
     * @return string
     */
    public static function getName() {
        return self::PLUGIN_NAME;
    }

    /**
     * Get Plugin Version
     *
     * @return float,,
     */
    public static function getVersion() {
        return self::PLUGIN_VERSION;
    }

    /**
     * Get Plugin Author
     *
     * @return string
     */
    public static function getAuthor() {
        return self::PLUGIN_AUTHOR;
    }

    /**
     * Get Plugin Description
     *
     * @return string
     */
    public static function getDescription() {
        return self::PLUGIN_DESC;
    }
}
?>
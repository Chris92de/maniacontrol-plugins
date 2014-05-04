<?php

namespace Chris92;

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons128x128_1;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Maps\MapManager;

/**
 * ManiaControl Checkpoints Plugin
 *
 * @author Chris92
 */

class CheckpointsPlugin implements CallbackListener, Plugin {
    /**
     * Constants
     */
    const PLUGIN_ID     = 20;
    const PLUGIN_VERSION = 1;
    const PLUGIN_NAME = 'CheckpointsWidget';
    const PLUGIN_AUTHOR = 'Chris92';

    // CPWidget properties

    const MLID_CPWIDGET               = 'CheckpointPlugin.CPWidget';
    const SETTING_CP_WIDGET_ACTIVATED = 'CP-Widget Activated';
    const SETTING_CP_WIDGET_POSX      = 'CP-Widget-Position: X';
    const SETTING_CP_WIDGET_POSY      = 'CP-Widget-Position: Y';
    const SETTING_CP_WIDGET_WIDTH     = 'CP-Widget-Size: Width';
    const SETTING_CP_WIDGET_HEIGHT    = 'CP-Widget-Size: Height';

    /**
     * Private Properties
     */
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
    }

    /**
     * Load the plugin
     *
     * @param ManiaControl $maniaControl
     * @return bool
     */
    public function load(ManiaControl $maniaControl) {
        $this->maniaControl = $maniaControl;

        // Set CustomUI Setting
        $this->maniaControl->manialinkManager->customUIManager->setChallengeInfoVisible(false);

        // Register for callbacks
        $this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_BEGINMAP, $this, 'handleOnBeginMap');
        $this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_ENDMAP, $this, 'handleOnEndMap');
        $this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_TM_PLAYERCHECKPOINT, $this, 'handleOnPlayerCheckpoint');
        $this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_TM_PLAYERFINISH, $this, 'handleOnPlayerFinish');

        $this->maniaControl->settingManager->initSetting($this, self::SETTING_CP_WIDGET_ACTIVATED, true);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_CP_WIDGET_POSX, 0);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_CP_WIDGET_POSY, -75);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_CP_WIDGET_WIDTH, 40);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_CP_WIDGET_HEIGHT, 10);

        $this->displayCPWidget();
        return true;
    }

    /**
     * Unload the plugin and its resources
     */
    public function unload() {
        $this->closeCPWidget(self::MLID_CPWIDGET);
        $this->maniaControl->callbackManager->unregisterCallbackListener($this);
        unset($this->maniaControl);
    }

    /**
     * Displays the Widget onLoad
     *
     * @param array $callback
     */
    private function displayCPWidget($checkpoint = -1, $login = false) {
        $pos_x          = $this->maniaControl->settingManager->getSetting($this, self::SETTING_CP_WIDGET_POSX);
        $pos_y          = $this->maniaControl->settingManager->getSetting($this, self::SETTING_CP_WIDGET_POSY);
        $width          = $this->maniaControl->settingManager->getSetting($this, self::SETTING_CP_WIDGET_WIDTH);
        $height         = $this->maniaControl->settingManager->getSetting($this, self::SETTING_CP_WIDGET_HEIGHT);
        $quadStyle      = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
        $quadSubStyle   = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();
        $mapdetails     = $this->maniaControl->client->getCurrentMapInfo();
        // Gamemodes: Script == 0, Rounds == 1, TA == 2, Team == 3, Laps == 4, Cup == 5, Stunts == 6, Default: unknown
        $gamemode       = $this->maniaControl->server->getGameMode();
        $forcedlaps     = $this->maniaControl->client->getCurrentGameInfo()->roundsForcedLaps;
        $maniaLink      = new ManiaLink(self::MLID_CPWIDGET);

        //Calculate total CP count
        if( $gamemode == 1 || $gamemode == 3 || $gamemode == 5 ) {
            if($forcedlaps > 0){
                $cptotal = $mapdetails->nbCheckpoints * $forcedlaps;
            }
            else if ($mapdetails->nbLaps > 0) {
                $cptotal = $mapdetails->nbCheckpoints * $mapdetails->nbLaps;
            }
            else {
                //All other game modes
                $cptotal = $mapdetails->nbCheckpoints;
            }
        }
        else if ($mapdetails->nbLaps > 0 && $gamemode == 4) {
            $cptotal = $mapdetails->nbCheckpoints * $mapdetails->nbLaps;
        } else {
            $cptotal = $mapdetails->nbCheckpoints;
        }

        // main frame

        $frame = new Frame();
        $maniaLink->add($frame);
        $frame->setSize($width, $height);
        $frame->setPosition($pos_x, $pos_y);

        // bg quad

        $bgQuad = new Quad();
        $frame->add($bgQuad);
        $bgQuad->setSize($width, $height);
        $bgQuad->setStyles($quadStyle, $quadSubStyle);

        $label = new Label_Text();
        $frame->add($label);
        $label->setY(2.8);
        $label->setX(0);
        $label->setAlign(Control::CENTER, Control::CENTER);
        $label->setZ(0.2);
        $label->setTextSize(0.95);
        $label->setText("CHECKPOINTS");
        $label->setTextColor("FFF");

        $label = new Label_Text();
        $frame->add($label);
        $label->setY(-1);
        $label->setX(0);
        $label->setAlign(Control::CENTER, Control::CENTER);
        $label->setZ(0.2);
        if (($checkpoint+1) == ($cptotal-1)) {
            if (($cptotal-1) == 0) {
                $label->setTextSize(1);
                $label->setText("NO CPs - FINISH NOW");
            }
            else {
                $label->setStyle("TextTitle2Blink");
                $label->setTextSize(1);
                $label->setText("DONE - FINISH NOW");
            }
        }
        else if (($checkpoint+1) > ($cptotal-1)) {
            $label->setTextSize(1);
            $label->setText("MAP FINISHED");
        } else {
            $label->setTextSize(3.5);
            $label->setText(($checkpoint+1) .' of '. ($cptotal-1));
        }

        $label->setTextColor("FFF");

        // Send manialink
        $manialinkText = $maniaLink->render()->saveXML();
        $this->maniaControl->manialinkManager->sendManialink($manialinkText, $login);

    }

    /**
     * Handle PlayerConnect callback
     *
     * @param array $callback
     */
    public function handlePlayerConnect(array $callback) {
        $player = $callback[1];
        // Display Map Widget
        if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_CP_WIDGET_ACTIVATED)) {
            $this->displayCPWidget($player->login);
        }
    }

    /**
     * Handle on Begin Map
     *
     * @param array $callback
     */
    public function handleOnBeginMap(array $callback) {
        // Display Map Widget
        if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_CP_WIDGET_ACTIVATED)) {
            $this->displayCPWidget();
        }
    }

    /**
     * Handle on End Map
     *
     * @param array $callback
     */
    public function handleOnEndMap(array $callback) {
        // Hide CP Widget
        if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_CP_WIDGET_ACTIVATED)) {
            $this->closeCPWidget(self::MLID_CPWIDGET);
        }
    }

    public function handleOnPlayerCheckpoint(array $callback) {
        if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_CP_WIDGET_ACTIVATED) == true) {
            $this->displayCPWidget($callback[1][4], $callback[1][1]);
        }
    }

    public function handleOnPlayerFinish(array $callback) {
        if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_CP_WIDGET_ACTIVATED) == true) {
            $this->displayCPWidget(-1, $callback[1][1]);
        }
    }

    public function closeCPWidget($widgetId) {
        $emptyManialink = new ManiaLink($widgetId);
        $manialinkText  = $emptyManialink->render()->saveXML();
        $this->maniaControl->manialinkManager->sendManialink($manialinkText);
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
        return 'Plugin offers a Checkpoint Counter, inspired by RecordsEyepiece for XAseco.';
    }
}
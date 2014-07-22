<?php
use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Label;
use FML\Controls\Quads\Quad_Icons128x128_1;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use FML\Script\Script;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Utils\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\IconManager;
use ManiaControl\Maps\Map;
use ManiaControl\Maps\MapManager;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Settings\SettingManager;

/**
 * ManiaControl InfoWidgets
 *
 * @author    Chris92 & TheM & Kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class InfoWidgets implements CallbackListener, TimerListener, Plugin {

	/**
	 * Constants
	 */
	const PLUGIN_ID      = 36;
	const PLUGIN_VERSION = 1.04;
	const PLUGIN_NAME    = 'InfoWidgets';
	const PLUGIN_AUTHOR  = 'Chris92 & TheM & Kremsy';

	// MapWidget Properties
	const MLID_MAPWIDGET               = 'InfoWidgets.MapWidget';
	const SETTING_MAP_WIDGET_ACTIVATED = 'Map-Widget Activated';
	const SETTING_MAP_WIDGET_POSX      = 'Map-Widget-Position: X';
	const SETTING_MAP_WIDGET_POSY      = 'Map-Widget-Position: Y';
	const SETTING_MAP_WIDGET_WIDTH     = 'Map-Widget-Size: Width';
	const SETTING_MAP_WIDGET_HEIGHT    = 'Map-Widget-Size: Height';

	// ClockWidget Properties
	const MLID_CLOCKWIDGET               = 'InfoWidgets.ClockWidget';
	const SETTING_CLOCK_WIDGET_ACTIVATED = 'Clock-Widget Activated';
	const SETTING_CLOCK_WIDGET_POSX      = 'Clock-Widget-Position: X';
	const SETTING_CLOCK_WIDGET_POSY      = 'Clock-Widget-Position: Y';
	const SETTING_CLOCK_WIDGET_WIDTH     = 'Clock-Widget-Size: Width';
	const SETTING_CLOCK_WIDGET_HEIGHT    = 'Clock-Widget-Size: Height';

	// NextMapWidget Properties
	const MLID_NEXTMAPWIDGET               = 'InfoWidgets.NextMapWidget';
	const SETTING_NEXTMAP_WIDGET_ACTIVATED = 'Nextmap-Widget Activated';
	const SETTING_NEXTMAP_WIDGET_POSX      = 'Nextmap-Widget-Position: X';
	const SETTING_NEXTMAP_WIDGET_POSY      = 'Nextmap-Widget-Position: Y';
	const SETTING_NEXTMAP_WIDGET_WIDTH     = 'Nextmap-Widget-Size: Width';
	const SETTING_NEXTMAP_WIDGET_HEIGHT    = 'Nextmap-Widget-Size: Height';

	// ServerInfoWidget Properties
	const MLID_SERVERINFOWIDGET               = 'InfoWidgets.ServerInfoWidget';
	const SETTING_SERVERINFO_WIDGET_ACTIVATED = 'ServerInfo-Widget Activated';
	const SETTING_SERVERINFO_WIDGET_POSX      = 'ServerInfo-Widget-Position: X';
	const SETTING_SERVERINFO_WIDGET_POSY      = 'ServerInfo-Widget-Position: Y';
	const SETTING_SERVERINFO_WIDGET_WIDTH     = 'ServerInfo-Widget-Size: Width';
	const SETTING_SERVERINFO_WIDGET_HEIGHT    = 'ServerInfo-Widget-Size: Height';

	// KarmaWidget Properties
	const MLID_KARMAWIDGET               = 'InfoWidgets.KarmaWidget';
	const SETTING_KARMA_WIDGET_ACTIVATED = 'KarmaWidget Activated';
	const SETTING_KARMA_WIDGET_POSX      = 'KarmaWidget-Position: X';
	const SETTING_KARMA_WIDGET_POSY      = 'KarmaWidget-Position: Y';
	const SETTING_KARMA_WIDGET_WIDTH     = 'KarmaWidget-Size: Width';
	const SETTING_KARMA_WIDGET_HEIGHT    = 'KarmaWidget-Size: Height';

	/**
	 * Private Properties
	 */
	/**
	 * @var maniaControl $maniaControl
	 */
	private $maniaControl = null;
	private $mxKarma = null;

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
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_AFTERINIT, $this, 'displayWidgets');
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_ENDMAP, $this, 'handleOnEndMap');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'handlePlayerDisconnect');
        	$this->maniaControl->callbackManager->registerCallbackListener(SettingManager::CB_SETTINGS_CHANGED, $this, 'handleSettingsChanged');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->callbackManager->registerCallbackListener('KarmaPlugin.Changed', $this, 'updateKarmaWidget');
		$this->maniaControl->callbackManager->registerCallbackListener('KarmaPlugin.MXUpdated', $this, 'updateMXKarma');

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAP_WIDGET_ACTIVATED, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAP_WIDGET_POSX, 160 - 21);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAP_WIDGET_POSY, 90 - 8);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAP_WIDGET_WIDTH, 48);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAP_WIDGET_HEIGHT, 16.);

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SERVERINFO_WIDGET_ACTIVATED, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SERVERINFO_WIDGET_POSX, -143);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SERVERINFO_WIDGET_POSY, 82);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SERVERINFO_WIDGET_WIDTH, 38);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SERVERINFO_WIDGET_HEIGHT, 16);

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_NEXTMAP_WIDGET_ACTIVATED, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_NEXTMAP_WIDGET_POSX, 160 - 21 - 47);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_NEXTMAP_WIDGET_POSY, 90 - 6.2);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_NEXTMAP_WIDGET_WIDTH, 46);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_NEXTMAP_WIDGET_HEIGHT, 12.5);

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_CLOCK_WIDGET_ACTIVATED, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_CLOCK_WIDGET_POSX, -155.5);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_CLOCK_WIDGET_POSY, 70);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_CLOCK_WIDGET_WIDTH, 12);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_CLOCK_WIDGET_HEIGHT, 6);

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_KARMA_WIDGET_ACTIVATED, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_KARMA_WIDGET_POSX, 160 - 21);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_KARMA_WIDGET_POSY, 65.5);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_KARMA_WIDGET_WIDTH, 48);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_KARMA_WIDGET_HEIGHT, 17.);
		return true;
	}

	/**
	 * Unload the plugin and its resources
	 */
	public function unload() {
		$this->closeWidget(self::MLID_CLOCKWIDGET);
		$this->closeWidget(self::MLID_SERVERINFOWIDGET);
		$this->closeWidget(self::MLID_MAPWIDGET);
		$this->closeWidget(self::MLID_NEXTMAPWIDGET);
	}

	/**
	 * Displays the Widgets onLoad
	 *
	 * @param array $callback
	 */
	public function displayWidgets() {
		// Display Map Widget
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_ACTIVATED)) {
			$this->displayMapWidget();
		}
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_CLOCK_WIDGET_ACTIVATED)) {
			$this->displayClockWidget();
		}
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_SERVERINFO_WIDGET_ACTIVATED)) {
			$this->displayServerInfoWidget();
		}
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_KARMA_WIDGET_ACTIVATED) &&
			$this->maniaControl->pluginManager->getPlugin('MCTeam\KarmaPlugin') != false) {
			foreach($this->maniaControl->playerManager->getPlayers() as $player) {
				$this->displayKarmaWidget($player->login);
			}
		}
	}

	/**
	 * Displays the Map Widget
	 *
	 * @param String $login
	 */
	public function displayMapWidget($login = false) {
		$pos_x        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_POSX);
		$pos_y        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_POSY);
		$width        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_WIDTH);
		$height       = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_HEIGHT);
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();
		$labelStyle   = $this->maniaControl->manialinkManager->styleManager->getDefaultLabelStyle();

		$maniaLink = new ManiaLink(self::MLID_MAPWIDGET);
		$script    = new Script();
		$maniaLink->setScript($script);

		// mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($pos_x, $pos_y);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		//$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		$backgroundQuad->setStyles('Bgs1InRace', 'BgList');
		$backgroundQuad->addMapInfoFeature();

		$titleBackgroundQuad = new Quad();
		$frame->add($titleBackgroundQuad);
		$titleBackgroundQuad->setVAlign(Control::TOP);
		$titleBackgroundQuad->setSize($width-0.4, 4);
		$titleBackgroundQuad->setY(7.2);
		$titleBackgroundQuad->setX(0.5);
		$titleBackgroundQuad->setZ(2);
		$titleBackgroundQuad->setStyles('BgsPlayerCard', 'BgRacePlayerName');

		$titleLabel = new \FML\Controls\Label();
		$frame->add($titleLabel);
		$titleLabel->setHAlign(Control::CENTER);
		$titleLabel->setPosition(0, 5.2);
		$titleLabel->setWidth($width);
		$titleLabel->setStyle($labelStyle);
		$titleLabel->setTextSize(1);
		$titleLabel->setText('Current Map');
		$titleLabel->setZ(3);
		$titleLabel->setTranslate(true);

		$titleIcon = new Quad_Icons128x128_1();
		$frame->add($titleIcon);
		$titleIcon->setSize(5, 5);
		//$posX = ($leftSide) ? 20.5 : -20.5;
		$titleIcon->setPosition(-20.5, 5.5, 3);
		$titleIcon->setZ(3);
		$titleIcon->setSubStyle($titleIcon::SUBSTYLE_NewTrack);

		$map = $this->maniaControl->client->getCurrentMapInfo();
        $mxmap = $this->maniaControl->mapManager->getCurrentMap();

		$label = new Label_Text();
		$frame->add($label);
		$label->setY(1.5);
		$label->setX(-22);
		$label->setAlign(Control::LEFT, Control::CENTER);
		$label->setZ(0.2);
		$label->setTextSize(1.3);
		$label->setText(Formatter::stripDirtyCodes($map->name));
		$label->setTextColor("FFF");
		$label->setSize($width - 5, $height);

		$titleIcon = new Quad_Icons128x128_1();
		$frame->add($titleIcon);
		$titleIcon->setSize(3, 3);
		//$posX = ($leftSide) ? 20.5 : -20.5;
		$titleIcon->setPosition(-20.4, -1.8, 3);
		$titleIcon->setZ(3);
		$titleIcon->setSubStyle($titleIcon::SUBSTYLE_Hotseat);

		$label = new Label_Text();
		$frame->add($label);
		$label->setX(-18);
		$label->setY(-1.8);
		$label->setAlign(Control::LEFT, Control::CENTER);
		$label->setZ(0.2);
		$label->setTextSize(1);
		$label->setText('by '.$map->author);
		$label->setTextColor("FFF");
		$label->setSize($width - 5, $height);
		$label->setTextEmboss(true);

		$timeIcon = new Quad_Icons64x64_1();
		$frame->add($timeIcon);
		$timeIcon->setSize(5, 5);
		//$posX = ($leftSide) ? 20.5 : -20.5;
		$timeIcon->setPosition(-20.5, -5, 3);
		$timeIcon->setZ(3);
		$timeIcon->setSubStyle($timeIcon::SUBSTYLE_FinishGrey);

		$label = new Label_Text();
		$frame->add($label);
		$label->setX(-18);
		$label->setY(-5);
		$label->setAlign(Control::LEFT, Control::CENTER);
		$label->setZ(0.2);
		$label->setTextSize(1);
		$label->setText(Formatter::formatTime($map->authorTime));
		$label->setTextColor("FFF");
		$label->setSize($width - 5, $height);
		$label->setTextEmboss(true);

		if($this->maniaControl->server->titleId == 'Trackmania_2@nadeolabs') {
			$timeIcon = new Quad_Icons128x128_1();
			$frame->add($timeIcon);
			$timeIcon->setSize(3, 3);
			//$posX = ($leftSide) ? 20.5 : -20.5;
			$timeIcon->setPosition(3, -5.2, 3);
			$timeIcon->setZ(3);
			$timeIcon->setSubStyle($timeIcon::SUBSTYLE_United);

			$label = new Label_Text();
			$frame->add($label);
			$label->setX(5);
			$label->setY(-5);
			$label->setAlign(Control::LEFT, Control::CENTER);
			$label->setZ(0.2);
			$label->setTextSize(1);
			$label->setText($mxmap->environment);
			$label->setTextColor("FFF");
			$label->setSize(10, $height);
			$label->setTextEmboss(true);
		}

		if (isset($mxmap->mx->pageurl)) {
			$quad = new Quad();
			$frame->add($quad);
			$quad->setImageFocus($this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON_MOVER));
			$quad->setImage($this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON));
			$quad->setPosition(18, -5, -0.5);
			$quad->setSize(4, 4);
			$quad->setHAlign(Control::CENTER);
			$quad->setUrl($mxmap->mx->pageurl);
		}

		// Send manialink
		$this->maniaControl->manialinkManager->sendManialink($maniaLink, $login);
	}

	/**
	 * Displays the Clock Widget
	 *
	 * @param bool $login
	 */
	public function displayClockWidget($login = false) {
		$pos_x        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_CLOCK_WIDGET_POSX);
		$pos_y        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_CLOCK_WIDGET_POSY);
		$width        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_CLOCK_WIDGET_WIDTH);
		$height       = $this->maniaControl->settingManager->getSetting($this, self::SETTING_CLOCK_WIDGET_HEIGHT);
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();

		$maniaLink = new ManiaLink(self::MLID_CLOCKWIDGET);
		$script    = $maniaLink->getScript();

		// mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($pos_x, $pos_y);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles('Bgs1InRace', 'BgList');

		$label = new Label_Text();
		$frame->add($label);
		$label->setY(1.5);
		$label->setX(0);
		$label->setAlign(Control::CENTER, Control::TOP);
		$label->setZ(0.2);
		$label->setTextSize(1);
		$label->setTextColor("FFF");
        $label->setStyle('TextListLine');
		$label->addClockFeature(false);

		// Send manialink
		$this->maniaControl->manialinkManager->sendManialink($maniaLink, $login);
	}

	/**
	 * Displays the Server Info Widget
	 *
	 * @param String $login
	 */
	public function displayServerInfoWidget($login = false) {
		$pos_x           = $this->maniaControl->settingManager->getSetting($this, self::SETTING_SERVERINFO_WIDGET_POSX);
		$pos_y           = $this->maniaControl->settingManager->getSetting($this, self::SETTING_SERVERINFO_WIDGET_POSY);
		$width           = $this->maniaControl->settingManager->getSetting($this, self::SETTING_SERVERINFO_WIDGET_WIDTH);
		$height          = $this->maniaControl->settingManager->getSetting($this, self::SETTING_SERVERINFO_WIDGET_HEIGHT);
		$quadStyle       = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();
        $labelStyle      = $this->maniaControl->manialinkManager->styleManager->getDefaultLabelStyle();
        $serverLadderMin = $this->maniaControl->client->getServerOptions()->ladderServerLimitMin;
        $serverLadderMax = $this->maniaControl->client->getServerOptions()->ladderServerLimitMax;

		$maniaLink = new ManiaLink(self::MLID_SERVERINFOWIDGET);

		// mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($pos_x, $pos_y);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles('Bgs1InRace', 'BgList');

		$maxPlayers = $this->maniaControl->client->getMaxPlayers();

		$maxSpectators = $this->maniaControl->client->getMaxSpectators();

		$serverName = $this->maniaControl->client->getServerName();

		$players        = $this->maniaControl->playerManager->getPlayers();
		$playerCount    = 0;
		$spectatorCount = 0;
		/**
		 * @var Player $player
		 */
		foreach($players as $player) {
			if ($player->isSpectator) {
				$spectatorCount++;
			} else {
				$playerCount++;
			}
		}
        $titleBackgroundQuad = new Quad();
        $frame->add($titleBackgroundQuad);
        $titleBackgroundQuad->setVAlign(Control::TOP);
        $titleBackgroundQuad->setSize($width, 4);
        $titleBackgroundQuad->setY(7.25);
        $titleBackgroundQuad->setX(-1);
        $titleBackgroundQuad->setZ(2);
        $titleBackgroundQuad->setStyles('BgsPlayerCard', 'BgRacePlayerName');

        $titleIcon = new Quad_Icons128x128_1();
        $frame->add($titleIcon);
        $titleIcon->setSize(5, 5);
        $posX = 15.5;
        $titleIcon->setPosition($posX, 5.75, 2);
        $titleIcon->setZ(3);
        $titleIcon->setSubStyle($titleIcon::SUBSTYLE_ServersAll);

        $titleLabel = new Label();
        $frame->add($titleLabel);
        $titleLabel->setHAlign(Control::CENTER);
        $titleLabel->setPosition(0, 5.2);
        $titleLabel->setWidth($width);
        $titleLabel->setStyle($labelStyle);
        $titleLabel->setTextSize(1);
        $titleLabel->setText('Server Info');
        $titleLabel->setZ(3);

		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(-$width / 2 + 3, 1.5, 3);
		$label->setAlign(Control::LEFT, Control::CENTER);
		$label->setSize($width - 5, $height);
		$label->setTextSize(1.3);
		$label->setText(Formatter::stripDirtyCodes($serverName));
		$label->setTextColor("FFF");
		//$label->setAutoNewLine(true);
		// Player Quad / Label

		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(-$width / 2 + 9.25, -2, 0.2);
		$label->setAlign(Control::LEFT, Control::CENTER);
		$label->setTextSize(1);
		$label->setScale(1);
		$label->setText($playerCount . " / " . $maxPlayers['NextValue']);
		$label->setTextColor("FFF");

		$quad = new Quad_Icons128x128_1();
		$frame->add($quad);
		$quad->setSubStyle($quad::SUBSTYLE_Multiplayer);
		$quad->setPosition(-$width / 2 + 5, -2.25, 0.2);
		$quad->setSize(4, 4);
		$quad->setHAlign(Control::CENTER);

		// Spectator Quad / Label
		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(-$width / 2 + 24.25, -2, 0.2);
		$label->setAlign(Control::LEFT, Control::CENTER);
		$label->setTextSize(1);
		$label->setScale(1);
		$label->setText($spectatorCount . " / " . $maxSpectators['NextValue']);
		$label->setTextColor("FFF");

		$quad = new Quad_Icons64x64_1();
		$frame->add($quad);
		$quad->setSubStyle($quad::SUBSTYLE_Camera);
		$quad->setPosition(-$width / 2 + 20, -2.25, 0.2);
		$quad->setSize(4, 3.5);
		$quad->setHAlign(Control::CENTER);

        $quad = new Quad_Icons128x128_1();
        $frame->add($quad);
        $quad->setSubStyle($quad::SUBSTYLE_LadderPoints);
        $quad->setPosition(-$width / 2 + 5, -5.75, 0.2);
        $quad->setSize(4, 5);
        $quad->setHAlign(Control::CENTER);

        $label = new Label_Text();
        $frame->add($label);
        $label->setPosition(-$width / 2 + 9.25, -5.5, 0.2);
        $label->setAlign(Control::LEFT, Control::CENTER);
        $label->setTextSize(1);
        $label->setScale(1);
        $label->setText($serverLadderMin . " - " . $serverLadderMax);
        $label->setTextColor("FFF");

		// Favorite quad
		$quad = new Quad_Icons64x64_1();
		$frame->add($quad);
		$quad->setSubStyle($quad::SUBSTYLE_StateFavourite);
		$quad->setPosition($width / 2 - 3, -6, -0.5);
		$quad->setSize(3, 3);
		$quad->setHAlign(Control::CENTER);
		$quad->setManialink('mcontrol?favorite=' . urlencode($this->maniaControl->server->login));

		// Send manialink
		$this->maniaControl->manialinkManager->sendManialink($maniaLink, $login);
	}

	/**
	 * Update the Karmawidget for all players
	 */
	public function updateKarmaWidget() {
		foreach($this->maniaControl->playerManager->getPlayers() as $player) {
			$this->displayKarmaWidget($player->login);
		}
	}

	/**
	 * Function changing the karma widget on change of MXKarma info
	 *
	 * @param $mxKarma
	 */
	public function updateMXKarma($mxKarma) {
		$this->mxKarma = $mxKarma;
		$this->updateKarmaWidget();
	}

	/**
	 * Displays the Karma Widget
	 *
	 * @param bool|String $login
	 */
	public function displayKarmaWidget($login) {
		$pos_x        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_KARMA_WIDGET_POSX);
		$pos_y        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_KARMA_WIDGET_POSY);
		$width        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_KARMA_WIDGET_WIDTH);
		$height       = $this->maniaControl->settingManager->getSetting($this, self::SETTING_KARMA_WIDGET_HEIGHT);
		$labelStyle   = $this->maniaControl->manialinkManager->styleManager->getDefaultLabelStyle();

		$maniaLink = new ManiaLink(self::MLID_KARMAWIDGET);
		$script    = new Script();
		$maniaLink->setScript($script);

		/** @var MCTeam\KarmaPlugin $karmaPlugin */
		$karmaPlugin = $this->maniaControl->pluginManager->getPlugin('MCTeam\KarmaPlugin');
		if(!$karmaPlugin) {
			$mltext = $maniaLink->render()->saveXML();
			$this->maniaControl->manialinkManager->sendManialink($mltext);
		}

		// mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($pos_x, $pos_y);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles('Bgs1InRace', 'BgList');
		/** @var TheM\WhoKarma $whoKarma */
  		$whoKarma = $this->maniaControl->pluginManager->getPlugin('TheM\WhoKarma');
  		if($whoKarma) {
   			$backgroundQuad->setAction($whoKarma::ACTION_SHOW_LIST);
  		}

		$titleBackgroundQuad = new Quad();
		$frame->add($titleBackgroundQuad);
		$titleBackgroundQuad->setVAlign(Control::TOP);
		$titleBackgroundQuad->setSize($width-0.4, 4);
		$titleBackgroundQuad->setY(7.6);
		$titleBackgroundQuad->setX(0.5);
		$titleBackgroundQuad->setZ(2);
		$titleBackgroundQuad->setStyles('BgsPlayerCard', 'BgRacePlayerName');

		$titleLabel = new \FML\Controls\Label();
		$frame->add($titleLabel);
		$titleLabel->setHAlign(Control::CENTER);
		$titleLabel->setPosition(0, 5.6);
		$titleLabel->setWidth($width);
		$titleLabel->setStyle($labelStyle);
		$titleLabel->setTextSize(1);
		$titleLabel->setText('Map Karma');
		$titleLabel->setZ(3);
		$titleLabel->setTranslate(true);

		$titleIcon = new Quad_Icons128x128_1();
		$frame->add($titleIcon);
		$titleIcon->setSize(5, 5);
		$posX = ($pos_x < 0) ? 20.5 : -20.5;
		$titleIcon->setPosition($posX, 5.9, 3);
		$titleIcon->setZ(3);
		$titleIcon->setSubStyle($titleIcon::SUBSTYLE_Statistics);

		$karma = $karmaPlugin->getMapKarma($this->maniaControl->mapManager->getCurrentMap());
		$votes = $karmaPlugin->getMapVotes($this->maniaControl->mapManager->getCurrentMap());
		$blocks = array();
		$x = -12.75;

		$min = 0;
		$plus = 0;
		$dontCount = 0;
		foreach($votes as $vote) {
			if(isset($vote->vote)) {
				if($vote->vote != 0.5) {
					if($vote->vote < 0.5) {
						$min = $min+$vote->count;
					} else {
						$plus = $plus+$vote->count;
					}
				} else {
					$dontCount = $vote->count;
				}
			}
		}
		$endKarma = $plus-$min;
		//$percent = round($karma * 100.);
		if($votes['count'] != 0 && ($votes['count']-$dontCount) != 0) {
			$percent = round(($plus/($votes['count']-$dontCount)) * 100.);
		} else {
			$percent = 0;
		}

		if($votes['count'] == 0) {
			for($i = 0; $i < 10; $i++) {
				$blocks[$i] = new Quad();
				$frame->add($blocks[$i]);
				$blocks[$i]->setSize(2.5, 4.5);
				$blocks[$i]->setX(($x+(2.55*$i)));
				$blocks[$i]->setY(0.2);
				$blocks[$i]->setImage('http://kreipe.patrick.coolserverhosting.de/local/grey.jpg');
				$blocks[$i]->setZ(3);
			}
		} else {
			$karmaPoints = round($percent / 10);
			for($i = 0; $i < $karmaPoints; $i++) {
				$blocks[$i] = new Quad();
				$frame->add($blocks[$i]);
				$blocks[$i]->setSize(2.5, 4.5);
				$blocks[$i]->setX(($x+(2.55*$i)));
				$blocks[$i]->setY(0.2);
				$blocks[$i]->setImage('http://kreipe.patrick.coolserverhosting.de/local/green.jpg');
				$blocks[$i]->setZ(3);
			}

			for($i = $karmaPoints; $i < 10; $i++) {
				$blocks[$i] = new Quad();
				$frame->add($blocks[$i]);
				$blocks[$i]->setSize(2.5, 4.5);
				$blocks[$i]->setX(($x+(2.55*$i)));
				$blocks[$i]->setY(0.2);
				$blocks[$i]->setImage('http://kreipe.patrick.coolserverhosting.de/local/red.jpg');
				$blocks[$i]->setZ(3);
			}
		}

		$label = new Label_Text();
		$frame->add($label);
		$label->setY(-3.85);
		$label->setX(-1);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setZ(0.2);
		$label->setTextSize(1);
		$label->setText($endKarma.' ('.$percent.'%)');
		$label->setTextColor("FFF");
		$label->setSize($width - 5, 3);
		$label->setTextEmboss(true);

		$mxText = 'MXKarma: no information';
		if($this->mxKarma != null && !$this->mxKarma["connectionInProgress"]) {
			$mxText = 'MXKarma: '.round($this->mxKarma["voteAverage"], 2).'% ('.$this->mxKarma["voteCount"].' votes)';
		}

		$label = new Label_Text();
		$frame->add($label);
		$label->setY(-6.35);
		$label->setX(-1);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setZ(0.2);
		$label->setTextSize(0.9);
		$label->setText($mxText);
		$label->setTextColor("FFF");
		$label->setSize($width - 5, 3);
		$label->setTextEmboss(true);

		$personalVote = $karmaPlugin->getPlayerVote($this->maniaControl->playerManager->getPlayer($login), $this->maniaControl->mapManager->getCurrentMap());
		$personalVoteDeducted = null;
		if(is_numeric($personalVote)) {
			if($personalVote != 0.5) {
				if($personalVote < 0.5) {
					$personalVoteDeducted = 'min';
				} else {
					$personalVoteDeducted = 'plus';
				}
			}
		}

		$minButton = new Quad();
		$frame->add($minButton);
		$minButton->setSize(6, 6);
		$minButton->setPosition(-18, 0.4, 2);
		$minButton->setStyles('Icons64x64_1', 'Sub');
		$minButton->setAction('KarmaWidget.SetVote.Min');

		$labelMin = new Label_Text();
		$frame->add($labelMin);
		$labelMin->setPosition(-18, -3.4, 2);
		$labelMin->setAlign(Control::CENTER, Control::CENTER);
		$labelMin->setTextSize(1);
		$labelMin->setText($min);
		$labelMin->setTextColor("FFF");
		$labelMin->setSize(6, 3);
		$labelMin->setTextEmboss(true);

		$labelPlus = new Label_Text();
		$frame->add($labelPlus);
		$labelPlus->setPosition(16, -3.4, 2);
		$labelPlus->setAlign(Control::CENTER, Control::CENTER);
		$labelPlus->setTextSize(1);
		$labelPlus->setText($plus);
		$labelPlus->setTextColor("FFF");
		$labelPlus->setSize(6, 3);
		$labelPlus->setTextEmboss(true);
		
		if($personalVoteDeducted == 'min') {
			$minHighlight = new Quad();
			$frame->add($minHighlight);
			$minHighlight->setSize(5.9, 5.9);
			$minHighlight->setPosition(-18, 0.4, 1.5);
			$minHighlight->setStyles('Icons64x64_1', 'LvlRed');
		} elseif($personalVoteDeducted == 'plus') {
			$plusHighlight = new Quad();
			$frame->add($plusHighlight);
			$plusHighlight->setSize(5.9, 5.9);
			$plusHighlight->setPosition(16, 0.4, 1.5);
			$plusHighlight->setStyles('Icons64x64_1', 'LvlGreen');
		}

		$plusButton = new Quad();
		$frame->add($plusButton);
		$plusButton->setSize(6, 6);
		$plusButton->setPosition(16, 0.4, 2);
		$plusButton->setStyles('Icons64x64_1', 'Add');
		$plusButton->setAction('KarmaWidget.SetVote.Plus');

		// Send manialink
		$this->maniaControl->manialinkManager->sendManialink($maniaLink, $login);
	}

	/**
	 * Handle PlayerManialinkPageAnswer callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];

		$login  = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);

		if($actionId == 'KarmaWidget.SetVote.Min' || $actionId == 'KarmaWidget.SetVote.Plus') {
			$chatCallback = array();
			$chatCallback[1] = array();
			$chatCallback[1][1] = $login;
			$chatCallback[1][3] = false;

			if($actionId == 'KarmaWidget.SetVote.Min') {
				$chatCallback[1][2] = '--';
			} elseif($actionId == 'KarmaWidget.SetVote.Plus') {
				$chatCallback[1][2] = '++';
			}

			/** @var MCTeam\KarmaPlugin $karmaPlugin */
			$karmaPlugin = $this->maniaControl->pluginManager->getPlugin('MCTeam\KarmaPlugin');
			$karmaPlugin->handlePlayerChat($chatCallback);
		}
	}

	/**
	 * Handle on Begin Map
	 *
	 * @param Map $map
	 */
	public function handleOnBeginMap(Map $map) {
		// Display Map Widget
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_ACTIVATED)) {
			$this->displayMapWidget();
		}
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_KARMA_WIDGET_ACTIVATED) && $this->maniaControl->pluginManager->getPlugin('MCTeam\KarmaPlugin') != false) {
			foreach($this->maniaControl->playerManager->getPlayers() as $player) {
				$this->displayKarmaWidget($player->login);
			}
		}
		$this->closeWidget(self::MLID_NEXTMAPWIDGET);
	}

	/**
	 * Closes a Widget
	 *
	 * @param $widgetId
	 */
	public function closeWidget($widgetId) {
		$emptyManialink = new ManiaLink($widgetId);
		$this->maniaControl->manialinkManager->sendManialink($emptyManialink);
	}

	/**
	 * Handle on End Map
	 *
	 * @param Map $map
	 */
	public function handleOnEndMap(Map $map) {
		// Display Map Widget
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_NEXTMAP_WIDGET_ACTIVATED)) {
			$this->displayNextMapWidget();
		}
	}

	/**
	 * Displays the Next Map (Only at the end of the Map)
	 *
	 * @param bool $login
	 */
	public function displayNextMapWidget($login = false) {
		$pos_x        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_NEXTMAP_WIDGET_POSX);
		$pos_y        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_NEXTMAP_WIDGET_POSY);
		$width        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_NEXTMAP_WIDGET_WIDTH);
		$height       = $this->maniaControl->settingManager->getSetting($this, self::SETTING_NEXTMAP_WIDGET_HEIGHT);
		$labelStyle   = $this->maniaControl->manialinkManager->styleManager->getDefaultLabelStyle();

		$maniaLink = new ManiaLink(self::MLID_NEXTMAPWIDGET);

		// mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($pos_x, $pos_y);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles('Bgs1InRace', 'BgList');

		$titleBackgroundQuad = new Quad();
		$frame->add($titleBackgroundQuad);
		$titleBackgroundQuad->setVAlign(Control::TOP);
		$titleBackgroundQuad->setSize($width-1, 4);
		$titleBackgroundQuad->setY(5.45);
		$titleBackgroundQuad->setX(0);
		$titleBackgroundQuad->setZ(2);
		$titleBackgroundQuad->setStyles('BgsPlayerCard', 'BgRacePlayerName');

		$titleLabel = new \FML\Controls\Label();
		$frame->add($titleLabel);
		$titleLabel->setHAlign(Control::CENTER);
		$titleLabel->setPosition(0, 3.45);
		$titleLabel->setWidth($width);
		$titleLabel->setStyle($labelStyle);
		$titleLabel->setTextSize(1);
		$titleLabel->setText('Next Map');
		$titleLabel->setZ(3);
		$titleLabel->setTranslate(true);

		$titleIcon = new Quad_Icons128x128_1();
		$frame->add($titleIcon);
		$titleIcon->setSize(5, 5);
		//$posX = ($leftSide) ? 20.5 : -20.5;
		$titleIcon->setPosition(-19.5, 3.75, 3);
		$titleIcon->setZ(3);
		$titleIcon->setSubStyle($titleIcon::SUBSTYLE_NewTrack);

		// Check if the Next Map is a queued Map
		$queuedMap = $this->maniaControl->mapManager->mapQueue->getNextMap();

		/**
		 * @var Player $requester
		 */
		$requester = null;
		// if the nextmap is not a queued map, get it from map info
		if (!$queuedMap) {
			$map    = $this->maniaControl->client->getNextMapInfo();
			$name   = Formatter::stripDirtyCodes($map->name);
			$author = $map->author;
		} else {
			$requester = $queuedMap[0];
			$map       = $queuedMap[1];
			$name      = $map->name;
			$author    = $map->authorLogin;
		}

		$label = new Label_Text();
		$frame->add($label);
		$label->setY(-0.25);
		$label->setX(-21);
		$label->setAlign(Control::LEFT, Control::CENTER);
		$label->setZ(0.2);
		$label->setTextSize(1.3);
		$label->setText($name.'$z$s$fff by '.$author);
		$label->setTextColor("FFF");
		$label->setSize($width - 5, $height);

		if($this->maniaControl->server->titleId == 'Trackmania_2@nadeolabs') {
			$timeIcon = new Quad_Icons128x128_1();
			$frame->add($timeIcon);
			$timeIcon->setSize(3, 3);
			//$posX = ($leftSide) ? 20.5 : -20.5;
			$timeIcon->setPosition(8, -3.75, 3);
			$timeIcon->setZ(3);
			$timeIcon->setSubStyle($timeIcon::SUBSTYLE_United);

			if(isset($map->environment)) {
				$environment = $map->environment;
			} else {
				$environment = $map->environnement;
			}

			$enviLabel = new Label_Text();
			$frame->add($enviLabel);
			$enviLabel->setX(10);
			$enviLabel->setY(-3.55);
			$enviLabel->setAlign(Control::LEFT, Control::CENTER);
			$enviLabel->setZ(0.2);
			$enviLabel->setTextSize(1);
			$enviLabel->setText($environment);
			$enviLabel->setTextColor("FFF");
			$enviLabel->setSize(10, $height);
			$enviLabel->setTextEmboss(true);
		}

		if($requester) {
			$requestText = 'Requester: $fff' . $requester->nickname;
		} else {
			$requestText = 'Next in maplist';
		}

		$requestLabel = new Label_Text();
		$frame->add($requestLabel);
		$requestLabel->setX(-21);
		$requestLabel->setY(-3.55);
		$requestLabel->setAlign(Control::LEFT, Control::CENTER);
		$requestLabel->setZ(2);
		$requestLabel->setWidth(40);
		$requestLabel->setTextSize(1);
		$requestLabel->setScale(0.7);
		$requestLabel->setTextColor("F80");
		$requestLabel->setText($requestText);

		// Send manialink
		$this->maniaControl->manialinkManager->sendManialink($maniaLink, $login);
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerConnect(Player $player) {
		// Display Map Widget
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_ACTIVATED)) {
			$this->displayMapWidget($player->login);
		}
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_CLOCK_WIDGET_ACTIVATED)) {
			$this->displayClockWidget($player->login);
		}
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_SERVERINFO_WIDGET_ACTIVATED)) {
			$this->displayServerInfoWidget();
		}
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_KARMA_WIDGET_ACTIVATED) &&
			$this->maniaControl->pluginManager->getPlugin('MCTeam\KarmaPlugin') != false) {
			$this->displayKarmaWidget($player->login);
		}
	}

	/**
	 * Handles the changed settings callback
	 *
	 * @param $class
	 * @param $settingName
	 * @param $value
	 */
	public function handleSettingsChanged($class, $settingName, $value) {
        if(!$class = get_class()){
            return;
        }
        
        $settings = array('WidgetPlugin.MapWidget',
                          'Map-Widget Activated',
                          'Map-Widget-Position: X',
                          'Map-Widget-Position: Y',
                          'Map-Widget-Size: Width',
                          'Map-Widget-Size: Height',
                          'WidgetPlugin.ClockWidget',
                          'Clock-Widget Activated',
                          'Clock-Widget-Position: X',
                          'Clock-Widget-Position: Y',
                          'Clock-Widget-Size: Width',
                          'Clock-Widget-Size: Height',
                          'WidgetPlugin.NextMapWidget',
                          'Nextmap-Widget Activated',
                          'Nextmap-Widget-Position: X',
                          'Nextmap-Widget-Position: Y',
                          'Nextmap-Widget-Size: Width',
                          'Nextmap-Widget-Size: Height',
                          'WidgetPlugin.ServerInfoWidget',
                          'ServerInfo-Widget Activated',
                          'ServerInfo-Widget-Position: X',
                          'ServerInfo-Widget-Position: Y',
                          'ServerInfo-Widget-Size: Width',
                          'ServerInfo-Widget-Size: Height',
			              'KarmaWidget Activated',
			              'KarmaWidget-Position: X',
			              'KarmaWidget-Position: Y',
			              'KarmaWidget-Size: Width',
			              'KarmaWidget-Size: Height');

        if (in_array($settingName, $settings)){
            $this->displayWIdgets();
        }
    }

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerDisconnect(Player $player) {
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_SERVERINFO_WIDGET_ACTIVATED)) {
			$this->displayServerInfoWidget();
		}
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
		return 'Plugin offers some Widgets';
	}
}
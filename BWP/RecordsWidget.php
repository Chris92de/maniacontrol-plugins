<?php

namespace BWP;

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons128x128_1;
use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\ManiaLink;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Utils\Formatter;
use ManiaControl\Maps\Map;
use ManiaControl\Maps\MapManager;
use ManiaControl\Settings\SettingManager;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Players\Player;

/**
 * ManiaControl RecordsWidget Plugin
 *
 * @author Chris92 & TheM
 */

class RecordsWidget implements Plugin, CallbackListener, TimerListener {
    /**
     * Constants
     */
    const PLUGIN_ID = 32; //register plugin here to receive ID: http://maniacontrol.com/user/plugins/new
    const PLUGIN_VERSION = 1.17;
    const PLUGIN_NAME = 'RecordsWidget';
    const PLUGIN_AUTHOR = 'Chris92 & TheM';
    const PLUGIN_DESC = 'Replaces default widgets for Local Records & Dedimania with more powerful ones.';

    const MLID_LOCALS = 'RecordsWidget.LocalRecords';
    const SETTING_LOCALS_WIDTH = 'Local Records Widget - Width';
    const SETTING_LOCALS_LINEHEIGHT = 'Local Records Widget - Line Height';
    const SETTING_LOCALS_POSX = 'Local Records Widget - Pos. X';
    const SETTING_LOCALS_POSY = 'Local Records Widget - Pos. Y';
    const SETTING_LOCALS_TITLE = 'Local Records Widget - Title';
    const SETTING_LOCALS_COUNT = 'Local Records Widget - # of shown records';
    const SETTING_LOCALS_TOPCOUNT = 'Local Records Widget - # of shown top records';
    const SETTING_LOCALS_ENABLE = 'Use Local Records Widget';

    const MLID_DEDIS = 'RecordsWidget.DediRecords';
    const SETTING_DEDIS_WIDTH = 'Dedi Records Widget - Width';
    const SETTING_DEDIS_LINEHEIGHT = 'Dedi Records Widget - Height';
    const SETTING_DEDIS_POSX = 'Dedi Records Widget - Pos. X';
    const SETTING_DEDIS_POSY = 'Dedi Records Widget - Pos. Y';
    const SETTING_DEDIS_TITLE = 'Dedi Records Widget - Title';
    const SETTING_DEDIS_COUNT = 'Dedi Records Widget - # of shown records';
    const SETTING_DEDIS_TOPCOUNT = 'Dedi Records Widget - # of shown top records';
    const SETTING_DEDIS_ENABLE = 'Use Dedi Records Widget';

    /**
     * Private Properties
     */
    
    /**
     * @var maniaControl $maniaControl
     */
    private $maniaControl = null;
	private $blinkRecord  = null;
	private $blinking     = 0;

	private $blinkDedi    = null;
	private $blinkingDedi = 0;

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
        if(!$this->maniaControl->pluginManager->isPluginActive('MCTeam\LocalRecordsPlugin')) {
            $errormsg = '[RecordsWidget] This plugin requires the plugin "Local Records" to be activated to function properly. Please enable it.';
            throw new \Exception($errormsg);
        }

        $this->maniaControl->settingManager->initSetting($this, self::SETTING_LOCALS_ENABLE, true);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_LOCALS_TITLE, 'Local Records');
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_LOCALS_POSX, 139);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_LOCALS_POSY, 57);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_LOCALS_WIDTH, 40);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_LOCALS_LINEHEIGHT, 4.);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_LOCALS_COUNT, 15);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_LOCALS_TOPCOUNT, 3);

        $this->maniaControl->settingManager->initSetting($this, self::SETTING_DEDIS_ENABLE, true);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_DEDIS_TITLE, 'Dedimania Records');
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_DEDIS_POSX, -139);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_DEDIS_POSY, 57);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_DEDIS_WIDTH, 40);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_DEDIS_LINEHEIGHT, 4.);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_DEDIS_COUNT, 15);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_DEDIS_TOPCOUNT, 3);

        $this->maniaControl->callbackManager->registerCallbackListener('LocalRecords.Changed', $this, 'handleLocalRecord');
        $this->maniaControl->callbackManager->registerCallbackListener('Dedimania.Changed', $this, 'handleDediRecord');
        $this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_BEGINMAP, $this, 'handleBeginMap');
        $this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_ENDMAP, $this, 'handleEndMap');
        $this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
        $this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'handlePlayerDisconnect');
        $this->maniaControl->callbackManager->registerCallbackListener(SettingManager::CB_SETTINGS_CHANGED, $this, 'handleSettingsChanged');

		$this->drawOnLoad();
    }
    
    public function unload() {

    }

	public function handleLocalRecord($newRecord) {
		$this->blinkRecord = $newRecord;
		$this->blinking++;
		$this->maniaControl->timerManager->registerOneTimeListening($this, 'handleDeblink', 10000);
		$this->updateAllManialinks();
	}

    public function handleDediRecord($newRecord) {
        $this->blinkDedi = $newRecord;
        $this->blinkingDedi++;
        $this->maniaControl->timerManager->registerOneTimeListening($this, 'handleDeblinkDedi', 10000);
        $this->updateAllManialinks();
    }

	public function handleDeblink() {
		$this->blinking--;
		if($this->blinking == 0) {
			$this->blinkRecord = null;
			$this->updateAllManialinks();
		}
	}

	public function handleDeblinkDedi() {
		$this->blinkingDedi--;
		if($this->blinkingDedi == 0) {
			$this->blinkDedi = null;
			foreach($this->maniaControl->playerManager->getPlayers() as $player) {
				$this->buildManialink($player, true);
			}
		}
	}

	public function updateAllManialinks() {
        if (!$this->maniaControl->settingManager->getSetting($this, self::SETTING_LOCALS_ENABLE)) {
            $emptyml = new Manialink(self::MLID_LOCALS);
            $mltext = $emptyml->render()->saveXML();
            $this->maniaControl->manialinkManager->sendManialink($mltext);
        } else {
            foreach($this->maniaControl->playerManager->getPlayers() as $player) {
                $this->buildManialink($player);
				if(!$this->maniaControl->pluginManager->getPlugin('MCTeam\Dedimania\DedimaniaPlugin') || $this->maniaControl->pluginManager->getPlugin('MCTeam\Dedimania\DedimaniaPlugin')->getDedimaniaRecords() != false) {
                	$this->buildManialink($player, true);
				}
            }
        }
	}

    public function drawOnLoad() {
        if (!$this->maniaControl->settingManager->getSetting($this, self::SETTING_LOCALS_ENABLE)) {
            $emptyml = new Manialink(self::MLID_LOCALS);
            $mltext = $emptyml->render()->saveXML();
            $this->maniaControl->manialinkManager->sendManialink($mltext);
        } else {
            $this->updateAllManialinks();
			$this->maniaControl->timerManager->registerOneTimeListening($this, 'drawDedimaniaRecords', 1000);
        }
    }

	public function drawDedimaniaRecords() {
		/** @var \MCTeam\Dedimania\DedimaniaPlugin $dediRecordsPlugin */
		$dediRecordsPlugin = $this->maniaControl->pluginManager->getPlugin('MCTeam\Dedimania\DedimaniaPlugin');
		if(!$dediRecordsPlugin) return;

		$records = $dediRecordsPlugin->getDedimaniaRecords();
		if ($records == null) {
			$this->maniaControl->timerManager->registerOneTimeListening($this, 'drawDedimaniaRecords', 1000);
		} else {
			foreach($this->maniaControl->playerManager->getPlayers() as $player) {
				$this->buildManialink($player, true);
			}
		}
	}

	public function handleBeginMap(Map $map) {
		$this->blinkRecord = null;
		$this->blinkDedi = null;
		$this->updateAllManialinks();
		$this->maniaControl->timerManager->registerOneTimeListening($this, 'drawDedimaniaRecords', 1000);
	}

	public function handleEndMap(Map $map) {
		$this->blinkRecord = null;
		$this->blinkDedi = null;
		$this->updateAllManialinks();
	}

	public function handlePlayerConnect(Player $player) {
		$this->buildManialink($player);
        $this->buildManialink($player, true);
        $this->updateAllManialinks();
	}

    public function handlePlayerDisconnect() {
        $this->updateAllManialinks();
    }

	public function handleSettingsChanged($class, $settingName, $value) {
		if (!$class = get_class()) {
			return;
		}

		$settings = array('RecordsWidget.LocalRecords',
						  'Local Records Widget - Width',
						  'Local Records Widget - Line Height',
						  'Local Records Widget - Pos. X',
						  'Local Records Widget - Pos. Y',
						  'Local Records Widget - Title',
						  'Local Records Widget - # of shown records',
						  'Local Records Widget - # of shown top records',
						  'Use Local Records Widget',
						  'Show Local Records Widget at Scoreboard',

						  'RecordsWidget.DediRecords',
						  'Dedi Records Widget - Width',
						  'Dedi Records Widget - Height',
						  'Dedi Records Widget - Pos. X',
						  'Dedi Records Widget - Pos. Y',
						  'Dedi Records Widget - Title',
						  'Dedi Records Widget - # of shown records',
						  'Dedi Records Widget - # of shown top records',
						  'Use Dedi Records Widget');

		if (in_array($settingName, $settings)) {
			$this->updateAllManialinks();
		}
	}

	/**
	 * Generates the widget
	 *
	 * @param Player $player
	 * @param bool   $dedi
	 * @return null
	 */
	private function buildManialink(Player $player, $dedi = false) {
		$map = $this->maniaControl->mapManager->getCurrentMap();
		if (!$map) {
			return null;
		}

		$currentPlayers = $this->maniaControl->playerManager->getPlayers();
		$currentPlayerLogins = array();
		foreach($currentPlayers as $currentPlayer) {
			$currentPlayerLogins[] = $currentPlayer->login;
		}

		$labelStyle   = $this->maniaControl->manialinkManager->styleManager->getDefaultLabelStyle();
		$lineHeight   = 4.;

		if($dedi) {
			/** @var \MCTeam\Dedimania\DedimaniaPlugin $dediRecordsPlugin */
			$dediRecordsPlugin = $this->maniaControl->pluginManager->getPlugin('MCTeam\Dedimania\DedimaniaPlugin');
			if(!$dediRecordsPlugin) return;
			$title        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_DEDIS_TITLE);
			$pos_x        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_DEDIS_POSX);
			$pos_y        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_DEDIS_POSY);
			$width        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_DEDIS_WIDTH);
			$lines        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_DEDIS_COUNT);
			$topcount     = $this->maniaControl->settingManager->getSetting($this, self::SETTING_DEDIS_TOPCOUNT);
			$mlid         = self::MLID_DEDIS;
		} else {
			/** @var \LocalRecordsPlugin $localRecordsPlugin */
			$localRecordsPlugin = $this->maniaControl->pluginManager->getPlugin('MCTeam\LocalRecordsPlugin');

			$title        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_LOCALS_TITLE);
			$pos_x        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_LOCALS_POSX);
			$pos_y        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_LOCALS_POSY);
			$width        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_LOCALS_WIDTH);
			$lines        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_LOCALS_COUNT);
			$topcount     = $this->maniaControl->settingManager->getSetting($this, self::SETTING_LOCALS_TOPCOUNT);
			$mlid         = self::MLID_LOCALS;
		}

		$leftSide = ($pos_x < 0) ? true : false;

		$manialink = new ManiaLink($mlid);
		$frame     = new Frame();
		$manialink->add($frame);
		$frame->setPosition($pos_x, $pos_y);

		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setVAlign(Control::TOP);
		$adjustOuterBorder = false;
		$height            = 7. + ($lines * $lineHeight);
		$backgroundQuad->setSize($width * 1.2, $height);
		$backgroundQuad->setStyles('Bgs1InRace', 'BgList');
		if($dedi) {
			$backgroundQuad->setAction('Dedimania.ShowDediRecordsList');
		} else {
			$backgroundQuad->setAction('LocalRecords.ShowRecordsList');
		}

		$titleBackgroundQuad = new Quad();
		$frame->add($titleBackgroundQuad);
		$titleBackgroundQuad->setVAlign(Control::TOP);
		$titleBackgroundQuad->setSize($width * 1.17, $lineHeight);
		$titleBackgroundQuad->setY(-0.8);
		$titleBackgroundQuad->setX(0);
		$titleBackgroundQuad->setZ(2);
		$titleBackgroundQuad->setStyles('BgsPlayerCard', 'BgRacePlayerName');

		$titleLabel = new Label();
		$frame->add($titleLabel);
		$titleLabel->setHAlign(Control::CENTER);
		$titleLabel->setPosition(0, $lineHeight * -0.72);
		$titleLabel->setWidth($width);
		$titleLabel->setStyle($labelStyle);
		$titleLabel->setTextSize(1);
		$titleLabel->setText($title);
		$titleLabel->setZ(3);
		$titleLabel->setTranslate(true);

		$titleIcon = new Quad_BgRaceScore2();
		$frame->add($titleIcon);
		$titleIcon->setSize(5, 5);
		$posX = ($leftSide) ? 20.5 : -20.5;
		$titleIcon->setPosition($posX, $lineHeight * -0.63, 2);
		$titleIcon->setZ(3);
		$titleIcon->setSubStyle($titleIcon::SUBSTYLE_LadderRank);

		$topBackgroundQuad = new Quad();
		$frame->add($topBackgroundQuad);
		$topBackgroundQuad->setVAlign(Control::TOP);
		$adjustOuterBorder = false;
		$height            = ($topcount * $lineHeight)+1;
		$topBackgroundQuad->setSize($width * 1.2, $height);
		$topBackgroundQuad->setY(-5.5);
		$topBackgroundQuad->setStyles('Bgs1InRace', 'BgCardList');

		if($dedi) {
			$records = $dediRecordsPlugin->getDedimaniaRecords();
			if ($records == null || !is_array($records)) {
				$y = -8. - 0 * $lineHeight;
				$recordFrame = $this->fillRecordLine(null, $y, $width, $lineHeight, $player, $currentPlayerLogins, $leftSide);
				$frame->add($recordFrame);

				$this->maniaControl->manialinkManager->sendManialink($manialink, $player->login);
				return;
			}
		} else {
			$records = $localRecordsPlugin->getLocalRecords($map);
			if ($records == null || !is_array($records) || $records == false) {
				$y = -8. - 0 * $lineHeight;
				$recordFrame = $this->fillRecordLine(null, $y, $width, $lineHeight, $player, $currentPlayerLogins, $leftSide);
				$frame->add($recordFrame);

				$this->maniaControl->manialinkManager->sendManialink($manialink, $player->login);
				return;
			}
		}

		$playerHasRecord = null;
		foreach($records as $record) {
			if($record->login == $player->login) {
				$playerHasRecord = $record;
			}
		}

		$index = 0;
		for($i = 0; $i < $topcount; $i++) {
			$y = -8. - $index * $lineHeight;

			if(isset($records[$i])) {
				$recordFrame = $this->fillRecordLine($records[$i], $y, $width, $lineHeight, $player, $currentPlayerLogins, $leftSide);
				$frame->add($recordFrame);
				$index++;
			}
		}

		$numberOfRecords = count($records);
		if($playerHasRecord == null) { // Player has no local record
			$needRecords = ($lines-$topcount)-1; // Add an additional -. --- -:--.--- line, so one less line needed
			$end = $numberOfRecords;
			$start = (($end - $needRecords) < ($topcount+1)) ? ($topcount) : ($end - $needRecords);
			$addEmptyLine = true;
		} else { // Player has a local record
			$needRecords = ($lines-$topcount);

			if($playerHasRecord->rank <= ($topcount+1)) { // Player has local record in TOP X
				$start = $topcount;
				$end = ($numberOfRecords < $needRecords) ? $numberOfRecords : ($start + $needRecords);
			} else { // Player doesn't have a record in the TOP X
				$recordsBelow = $numberOfRecords-$playerHasRecord->rank;
				$recordsAbove = $numberOfRecords-$topcount-1;
				$halfNeededBelow = floor(($needRecords-1)/2);
				$halfNeededAbove = ceil(($needRecords-1)/2);

				if($playerHasRecord->rank-$halfNeededAbove < ($topcount+1)) { // If requesting records into TOP X (or -1)
					$start = $topcount; // Set start to first record after TOP X
				} elseif($playerHasRecord->rank-$halfNeededAbove > $topcount) { // If enough records above
					$possibleExtraAbove = ($playerHasRecord->rank-$topcount-$halfNeededAbove < 0) ? 0 : $playerHasRecord->rank-$topcount-$halfNeededAbove;
					$start = $playerHasRecord->rank-$halfNeededAbove-1;

					if(!isset($records[$playerHasRecord->rank+$halfNeededBelow-1])) { // Not enough records below
						//$tooLess = count($records)-($playerHasRecord->rank+$halfNeededBelow);
						$haveBelow = count($records)-$playerHasRecord->rank;
						$tooLess = ($lines-$topcount)-$halfNeededAbove-$haveBelow;
						if($tooLess <= $possibleExtraAbove) {
							$start = $playerHasRecord->rank-$halfNeededAbove-$tooLess;
						} else {
							$start = $playerHasRecord->rank-$halfNeededAbove-$possibleExtraAbove;
						}
					}
				}

				$end = ($numberOfRecords < $needRecords) ? $numberOfRecords : ($start + $needRecords);
			}
		}

		if(isset($start) && isset($end)) {
			for($i = $start; $i < $end; $i++) {
				$y = -8. - $index * $lineHeight;

				if(isset($records[$i])) {
					$recordFrame = $this->fillRecordLine($records[$i], $y, $width, $lineHeight, $player, $currentPlayerLogins, $leftSide);
					$frame->add($recordFrame);
					$index++;
				} else {
					break;
				}
			}
		}

		if(isset($addEmptyLine) && $addEmptyLine) {
			$y = -8. - $index * $lineHeight;
			$recordFrame = $this->fillRecordLine(null, $y, $width, $lineHeight, $player, $currentPlayerLogins, $leftSide);
			$frame->add($recordFrame);

			$index++;
		}

		$this->maniaControl->manialinkManager->sendManialink($manialink, $player->login);
	}

	private function fillRecordLine($record, $y, $width, $lineHeight, $currentPlayer, $currentPlayerLogins, $leftSide) {
		$recordFrame = new Frame();
		$recordFrame->setPosition(0, $y);

		$rankLabel = new Label();
		$recordFrame->add($rankLabel);
		$rankLabel->setHAlign(Control::RIGHT);
		$rankLabel->setX($width * -0.39);
		$rankLabel->setSize($width * 0.1, $lineHeight);
		$rankLabel->setTextSize(1);
		if($record == null) {
			$rankLabel->setText('');
		} else {
			$rankLabel->setText($record->rank.'.');
		}
		$rankLabel->setStyle('TextCardSmallScores2');

		$nameLabel = new Label();
		$recordFrame->add($nameLabel);
		$nameLabel->setHAlign(Control::LEFT);
		$nameLabel->setX($width * -0.38);
		$nameLabel->setSize($width * 0.6, $lineHeight);
		$nameLabel->setTextSize(1);
		if($record == null) {
			$nameLabel->setText($currentPlayer->nickname);
		} else {
			if(!isset($record->nickname)) {
                $nameLabel->setText($record->nickName);
            }
            else{
                $nameLabel->setText($record->nickname);
            }
		}
		$nameLabel->setTextEmboss(true);

		$timeLabel = new Label();
		$recordFrame->add($timeLabel);
		$timeLabel->setHAlign(Control::RIGHT);
		$timeLabel->setX($width * 0.49);
		$timeLabel->setSize($width * 0.25, $lineHeight);
		$timeLabel->setTextSize(1);
		if($record == null) {
			$timeLabel->setX($width * 0.45);
			$text = '$fff-:--:---';
		} else {
			$text = '$fff';
			if($record->login == $currentPlayer->login) {
				$text = '$090';
			} elseif(in_array($record->login, $currentPlayerLogins)) {
				$text = '$09f';
			}
			if(!isset($record->time)) {
                $text .= Formatter::formatTime($record->best);
            } else {
                $text .= Formatter::formatTime($record->time);
            }
		}
		$timeLabel->setText($text);

		if($record != null) {
			if(isset($record->time) && $this->blinkRecord != null && $this->blinkRecord->playerIndex == $record->playerIndex) {
				$timeLabel->setStyle('TextTitle2Blink');
			} elseif(isset($record->best) && $this->blinkDedi != null && $this->blinkDedi->nickName == $record->nickName) {
				$timeLabel->setStyle('TextTitle2Blink');
			} else {
				$timeLabel->setStyle('TextTitle2');
			}
		}

		if($record != null) {
			$positionX = $width * -0.53;
			if($leftSide) $positionX = $width * 0.535;

			if($record->login == $currentPlayer->login) {
				$showOwnIcon = new Quad_Icons128x128_1();
				$recordFrame->add($showOwnIcon);
				$showOwnIcon->setSize(3, 3);
				$showOwnIcon->setY(-0.1);
				$showOwnIcon->setX($positionX);
				$showOwnIcon->setZ(2);
				$showOwnIcon->setSubStyle($showOwnIcon::SUBSTYLE_ChallengeAuthor);
			} elseif(in_array($record->login, $currentPlayerLogins)) {
				$showOthersIcon = new Quad_Icons128x128_1();
				$recordFrame->add($showOthersIcon);
				$showOthersIcon->setSize(3, 3);
				$showOthersIcon->setY(-0.05);
				$showOthersIcon->setX($positionX);
				$showOthersIcon->setZ(2);
				$showOthersIcon->setSubStyle($showOthersIcon::SUBSTYLE_Solo);
			}
		}

		return $recordFrame;
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
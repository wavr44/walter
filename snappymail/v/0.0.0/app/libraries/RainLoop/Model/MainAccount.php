<?php

namespace RainLoop\Model;

use RainLoop\Utils;
use RainLoop\Exceptions\ClientException;
use RainLoop\Notifications;
use RainLoop\Providers\Storage\Enumerations\StorageType;
use SnappyMail\SensitiveString;

class MainAccount extends Account
{
	private ?SensitiveString $sCryptKey = null;

	public function resealCryptKey(SensitiveString $oOldPass) : bool
	{
		$oStorage = \RainLoop\Api::Actions()->StorageProvider();
		$sKey = $oStorage->Get($this, StorageType::ROOT, '.cryptkey');

		// Get the AppManager from the server container
		$appManager = \OC::$server->getAppManager();
		// Check if the OIDC Login app is enabled
		$isEnabled = $appManager->isEnabledForUser('oidc_login');
		// If OIDC Login is enabled then we will assign $pwd instead of $this->IncPassword() and $pwd value is taken by following similar approach as https://github.com/the-djmaze/snappymail/blob/0db6c6ab5f9e05c46b13ebbdaf351341710438ac/plugins/login-gmail/index.php#L125
		if ($isEnabled) {
			$sUID = \OC::$server->getUserSession()->getUser()->getUID();
			$pwd = new \SnappyMail\SensitiveString($sUID);
			if ($sKey) {
				$sKey = \SnappyMail\Crypt::DecryptFromJSON($sKey, $pwd);
				if (!$sKey) {
					throw new ClientException(Notifications::CryptKeyError);
				}
				$sKey = \SnappyMail\Crypt::EncryptToJSON($sKey, $pwd);
				if ($sKey) {
					$this->sCryptKey = null;
					if (\RainLoop\Api::Actions()->StorageProvider()->Put($this, StorageType::ROOT, '.cryptkey', $sKey)) {
						return true;
					}
				}
			}
		} else {
			if ($sKey) {
				$sKey = \SnappyMail\Crypt::DecryptFromJSON($sKey, $oOldPass);
				if (!$sKey) {
					throw new ClientException(Notifications::CryptKeyError);
				}
				$sKey = \SnappyMail\Crypt::EncryptToJSON($sKey, $this->IncPassword());
				if ($sKey) {
					$this->sCryptKey = null;
					if (\RainLoop\Api::Actions()->StorageProvider()->Put($this, StorageType::ROOT, '.cryptkey', $sKey)) {
						return true;
					}
				}
			}
		}	
		return false;
	}

	public function CryptKey() : string
	{
		if (!$this->sCryptKey) {
			// Seal the cryptkey so that people who change their login password
			// can use the old password to re-seal the cryptkey
			$oStorage = \RainLoop\Api::Actions()->StorageProvider();
			$sKey = $oStorage->Get($this, StorageType::ROOT, '.cryptkey');

			// Get the AppManager from the server container
			$appManager = \OC::$server->getAppManager();
			// Check if the OIDC Login app is enabled
			$isEnabled = $appManager->isEnabledForUser('oidc_login');
			// If OIDC Login app is enabled then we will assign $pwd instead of $this->IncPassword() and $pwd value is taken by following similar approach as https://github.com/the-djmaze/snappymail/blob/0db6c6ab5f9e05c46b13ebbdaf351341710438ac/plugins/login-gmail/index.php#L125
			if ($isEnabled) {
				$sUID = \OC::$server->getUserSession()->getUser()->getUID();
				$pwd = new \SnappyMail\SensitiveString($sUID);
				if (!$sKey) {
					$sKey = \SnappyMail\Crypt::EncryptToJSON(
						\sha1($pwd . APP_SALT),
						$pwd
					);
					$oStorage->Put($this, StorageType::ROOT, '.cryptkey', $sKey);
				}
				$sKey = \SnappyMail\Crypt::DecryptFromJSON($sKey, $pwd);
				if (!$sKey) {
					throw new ClientException(Notifications::CryptKeyError);
				}
				$this->sCryptKey = new SensitiveString(\hex2bin($sKey));

			} else {
				if (!$sKey) {
					$sKey = \SnappyMail\Crypt::EncryptToJSON(
						\sha1($this->IncPassword() . APP_SALT),
						$this->IncPassword()
					);
					$oStorage->Put($this, StorageType::ROOT, '.cryptkey', $sKey);
				}
				$sKey = \SnappyMail\Crypt::DecryptFromJSON($sKey, $this->IncPassword());
				if (!$sKey) {
					throw new ClientException(Notifications::CryptKeyError);
				}
				$this->sCryptKey = new SensitiveString(\hex2bin($sKey));
			}	

		}
		return $this->sCryptKey;
	}

/*
	// Stores settings in MainAccount
	public function settings() : \RainLoop\Settings
	{
		return \RainLoop\Api::Actions()->SettingsProvider()->Load($this);
	}
*/
}

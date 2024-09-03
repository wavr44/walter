<?php

use RainLoop\Providers\AddressBook\Classes\Contact;

class NextcloudAddressBook implements \RainLoop\Providers\AddressBook\AddressBookInterface
{
	use RainLoop\Providers\AddressBook\CardDAV;
	private $contactsManager;

	private const SETTINGS_KEY = 'nextCloudAddressBookUri';

	private string $defaultUri = 'webmail';
	private string $defaultName = 'WebMail';
	private string $defaultDescription = 'Recipients from snappymail';
	private bool $ignoreSystemAddressBook = true;


	function __construct(string $defaultUri = 'webmail', string $defaultName = 'WebMail', string $defaultDescription = 'Recipients from snappymail', bool $ignoreSystemAddressBook = true)
	{
		$this->defaultUri = $defaultUri;
		$this->defaultName = $defaultName;
		$this->defaultDescription = $defaultDescription;
		$this->ignoreSystemAddressBook = $ignoreSystemAddressBook;

		$this->GetSavedAddressBookKey();
	}

	private function GetSavedAddressBookKey(): string
	{
		$this->contactsManager = \OC::$server->getContactsManager();

		if ($this->ignoreSystemAddressBook) {
			foreach ($this->contactsManager->getUserAddressBooks() as $addressBook) {
				if ($addressBook->isSystemAddressBook()) {
					$this->contactsManager->unregisterAddressBook($addressBook);
				}
			}
		}

		$uid = \OC::$server->getUserSession()->getUser()->getUID();
		$cardDavBackend = \OC::$server->get(\OCA\DAV\CardDAV\CardDavBackend::class);
		$principalUri = 'principals/users/' . $uid;
		$uri = $this->GetSavedUri();
		$addressBookId = $cardDavBackend->getAddressBooksByUri($principalUri, $uri);

		if ($addressBookId === null) {
			return $cardDavBackend->createAddressBook($principalUri, $uri, array_filter([
				'{DAV:}displayname' => $this->defaultName,
				'{urn:ietf:params:xml:ns:carddav}addressbook-description' => $this->defaultDescription,
			]));
		}

		return $addressBookId['id'];
	}

	public function IsSupported(): bool
	{
		// Maybe just return true, contacts app is just a frontend
		//return \OC::$server->getAppManager()->isEnabledForUser('contacts');
		return true;
	}

	public function SetEmail(string $sEmail) : bool {
		return true;
	}

	public function Sync(): bool
	{
		return false;
	}

	public function Export(string $sType = 'vcf'): bool
	{
		return false;
	}

	public function ContactSave(Contact $oContact): bool
	{
		return false;
	}

	public function DeleteContacts(array $aContactIds): bool
	{
		return false;
	}

	public function DeleteAllContacts(string $sEmail): bool
	{
		return false;
	}

	public function GetContacts(int $iOffset = 0, int $iLimit = 20, string $sSearch = '', int &$iResultCount = 0): array
	{
		return [];
	}

	public function GetContactByEmail(string $sEmail): ?Contact
	{
		return null;
	}

	public function GetContactByID($mID, bool $bIsStrID = false): ?Contact
	{
		return null;
	}

	public function GetSuggestions(string $sSearch, int $iLimit = 20): array
	{
		return [];
	}

	private function GetEmailObjects(array $aEmails): array
	{
		$aEmailsObjects = \array_map(function ($mItem) {
			$oResult = null;
			try {
				$oResult = \MailSo\Mime\Email::Parse(\trim($mItem));
			} catch (\Throwable $oException) {
				unset($oException);
			}
			return $oResult;
		}, $aEmails);

		$aEmailsObjects = \array_filter($aEmailsObjects, function ($oItem) {
			return !!$oItem;
		});
		return $aEmailsObjects;
	}

	/**
	 * Add/increment email address usage
	 * Handy for "most used" sorting suggestions in PdoAddressBook
	 */
	public function IncFrec(array $aEmails, bool $bCreateAuto = true): bool
	{
		if ($bCreateAuto) {
			$aEmailsObjects = $this->GetEmailObjects($aEmails);

			if (!count($aEmailsObjects)) {
				return false;
			}
			foreach ($aEmailsObjects as $oEmail) {
				if ('' === \trim($oEmail->GetEmail())) {
					continue;
				}
				$sEmail = \trim($oEmail->GetEmail(true));
				$existingResults = $this->contactsManager->search($sEmail, ['EMAIL'], ['strict_search' => true]);

				if (!empty($existingResults)) {
					continue;
				}

				$properties = [
					'EMAIL' => $sEmail,
					'FN' => $sEmail
				];

				if ('' !== \trim($oEmail->GetDisplayName())) {
					$properties['FN'] = $oEmail->GetDisplayName();
				}
				$this->contactsManager->createOrUpdate($properties, $this->GetSavedAddressBookKey());
			}
			return true;
		}
		return false;
	}

	public function Test(): string
	{
		return '';
	}

	private function Account(): \RainLoop\Model\Account
	{
		return \RainLoop\Api::Actions()->getAccountFromToken();
	}

	private function SettingsProvider(): \RainLoop\Providers\Settings
	{
		return \RainLoop\Api::Actions()->SettingsProvider(true);
	}

	private function Settings(): \RainLoop\Settings
	{
		return $this->SettingsProvider()->Load($this->Account());
	}

	private function GetSavedUri(): string
	{
		return $this->Settings()->GetConf(self::SETTINGS_KEY, $this->defaultUri);
	}
}

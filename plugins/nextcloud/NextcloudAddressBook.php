<?php
use Sabre\VObject\Reader;
use OCA\DAV\CardDAV\CardDavBackend;
use RainLoop\Providers\AddressBook\Classes\Contact;



class NextcloudAddressBook implements \RainLoop\Providers\AddressBook\AddressBookInterface
{
	private const URI = 'webmail';
	private $contactsManager;

	function __construct()
	{
		$this->contactsManager = \OC::$server->getContactsManager();
		foreach ($this->contactsManager->getAddressBooks() as $addressbook) {
			if ($addressbook->getKey() !== self::URI) {
				$this->contactsManager->unregisterAddressBook($addressbook);
			}
		}
	}

	public function IsSupported() : bool {
		// Maybe just return true, contacts app is just a frontend
		//return \OC::$server->getAppManager()->isEnabledForUser('contacts');
		return true;
	}

	public function SetEmail(string $sEmail) : bool {
		return true;
	}

	public function Sync() : bool {
		return false;
	}

	public function Export(string $sType = 'vcf') : bool {
		return false;
	}

	public function ContactSave(Contact $oContact) : bool {
		if ($this->contactsManager->createOrUpdate($oContact->vCard->jsonSerialize(), self::URI)) {
			return true;
		}
		return false;
	}

	public function DeleteContacts(array $aContactIds) : bool {
		return false;
	}

	public function DeleteAllContacts(string $sEmail) : bool {
		return false;
	}

	public function GetContacts(int $iOffset = 0, int $iLimit = 20, string $sSearch = '', int &$iResultCount = 0) : array {
		$options = [
			'offset' => $iOffset,
			'limit' => $iLimit
		];
		$results = $this->contactsManager->search($sSearch, [], $options);
		$iResultCount = count($results);
		return $results;

	}

	public function GetContactByEmail(string $sEmail) : ?Contact {
		return null;
	}

	public function GetContactByID($mID, bool $bIsStrID = false) : ?Contact {
		return null;
	}

	public function GetSuggestions(string $sSearch, int $iLimit = 20) : array {
		$options = [
			'limit' => $iLimit
		];
		return $this->contactsManager->search($sSearch, [], $options);
	}

	/**
	 * Add/increment email address usage
	 * Handy for "most used" sorting suggestions in PdoAddressBook
	 */
	public function IncFrec(array $aEmails, bool $bCreateAuto = true) : bool {
		return false;
	}

	public function Test() : string {
		return '';
	}
}

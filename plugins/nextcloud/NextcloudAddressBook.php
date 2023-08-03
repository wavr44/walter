<?php
use RainLoop\Providers\AddressBook\Classes\Contact;
use Sabre\VObject\Component\VCard;

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
		return $this->convertResultsToSnappymailContacts($results);

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
		$results = $this->contactsManager->search($sSearch, [], $options);
		return $this->convertResultsToSnappymailContacts($results);
	}

	/**
	 * Add/increment email address usage
	 * Handy for "most used" sorting suggestions in PdoAddressBook
	 */
	public function IncFrec(array $aEmails, bool $bCreateAuto = true) : bool {
		if ($bCreateAuto) {
			foreach ($aEmails as $sEmail => $sAddress) {
				$oVCard = new VCard;
				$oVCard->add('EMAIL', $sEmail);
				$sFullName = \trim(\MailSo\Mime\Email::Parse(\trim($sAddress))->GetDisplayName());
				if ('' !== $sFullName) {
					$sFirst = $sLast = '';
					if (false !== \strpos($sFullName, ' ')) {
						$aNames = \explode(' ', $sFullName, 2);
						$sFirst = isset($aNames[0]) ? $aNames[0] : '';
						$sLast = isset($aNames[1]) ? $aNames[1] : '';
					} else {
						$sFirst = $sFullName;
					}
					if (\strlen($sFirst) || \strlen($sLast)) {
						$oVCard->N = array($sLast, $sFirst, '', '', '');
					}
				}
				$oContact = new Contact();
				$oContact->setVCard($oVCard);
				$this->ContactSave($oContact);
			}
			return true;
		}
		return false;
	}

	public function Test() : string {
		return '';
	}

	private function convertResultsToSnappymailContacts(array $results = []) {
		$contacts = [];
		foreach($results as $result) {
			$vCard = \Sabre\VObject\Reader::readJson($result);
			$contact = new Contact();
			$contact->setVCard($vCard);
			$contacts[] = $contact;
		}

		return $contacts;
	}
}

https://www.iana.org/assignments/sieve-extensions/sieve-extensions.xhtml

- [ ] RFC2852 envelope-deliverby / redirect-deliverby
- [ ] RFC3461 envelope-dsn / redirect-dsn
- [x] RFC3894 copy
- [ ] RFC4790 comparator-*
- [x] RFC5173 body
- [x] RFC5183 environment
- [x] RFC5228 encoded-character / envelope / fileinto
- [x] RFC5229 variables
- [x] RFC5230 vacation
- [x] RFC5231 relational
- [x] RFC5232 imap4flags
- [x] RFC5233 subaddress
- [x] RFC5235 spamtest / spamtestplus / virustest
- [x] RFC5260 date / index
- [x] RFC5293 editheader
- [x] RFC5429 ereject / reject
- [x] RFC5435 enotify
- [x] RFC5463 ihave
- [x] RFC5490 mailbox / mboxmetadata / servermetadata
- [x] RFC5703 enclose / extracttext / foreverypart / mime / replace
- [x] RFC6131 vacation-seconds
- [ ] RFC6134 extlists
- [ ] RFC6558 convert
- [x] RFC6609 include
- [ ] RFC6785 imapsieve
- [ ] RFC7352 duplicate
- [ ] RFC8579 special-use
- [ ] RFC8580 fcc
- [ ] RFC     regex                https://tools.ietf.org/html/draft-ietf-sieve-regex-01
- [ ]         vnd.cyrus.*
- [ ]         vnd.dovecot.*

# Classes

## abstract GrammarCommand
	identifier = identifier || constructor.name.toLowerCase().replace(/(test|command|action)$/, '');
	arguments = []; only initialized at pushArguments()

### abstract ControlCommand extends GrammarCommand

#### IncludeCommand extends ControlCommand
	global = false; // ':personal' / ':global';
	once = false;
	optional = false;
	value = new GrammarQuotedString;

#### ReturnCommand extends ControlCommand
	no properties

#### GlobalCommand extends ControlCommand
	value = new GrammarStringList;

#### ForEveryPartCommand extends ControlCommand
	_name = new GrammarString;
	commands = new GrammarCommands;

#### ErrorCommand extends ControlCommand
	message = new GrammarQuotedString;

#### RequireCommand extends ControlCommand
	capabilities = new GrammarStringList();

#### StopCommand extends ControlCommand
	no properties

#### abstract ConditionalCommand extends ControlCommand
	no properties

#### IfCommand extends ConditionalCommand
	test = null; // must be descendent instanceof TestCommand
	commands = new GrammarCommands;

#### ElsIfCommand extends IfCommand
	test = null; // must be descendent instanceof TestCommand
	commands = new GrammarCommands;

#### ElseCommand extends ConditionalCommand
	no properties
	commands = new GrammarCommands;

### abstract ActionCommand extends GrammarCommand

#### FileIntoCommand extends ActionCommand
	_mailbox = new GrammarQuotedString();
	copy = false;
	create = false;

#### RedirectCommand extends ActionCommand
	_address = new GrammarQuotedString();
	copy = false;
	list = null;

#### KeepCommand extends ActionCommand
	no properties

#### DiscardCommand extends ActionCommand
	no properties

#### SetCommand extends ActionCommand
	modifiers = [];
	_name    = new GrammarQuotedString;
	_value   = new GrammarQuotedString;

#### VacationCommand extends ActionCommand
	_days      = new GrammarNumber;
	_seconds   = new GrammarNumber;
	_subject   = new GrammarQuotedString;
	_from      = new GrammarQuotedString;
	addresses  = new GrammarStringList;
	mime       = false;
	_handle    = new GrammarQuotedString;
	_reason    = new GrammarQuotedString; // QuotedString / MultiLine

#### SetFlagCommand extends ActionCommand
	_variablename = new GrammarQuotedString;
	list_of_flags = new GrammarStringList;

#### AddFlagCommand extends ActionCommand
	_variablename = new GrammarQuotedString;
	list_of_flags = new GrammarStringList;

#### RemoveFlagCommand extends ActionCommand
	_variablename = new GrammarQuotedString;
	list_of_flags = new GrammarStringList;

#### AddHeaderCommand extends ActionCommand
	last       = false;
	field_name = new GrammarQuotedString;
	value      = new GrammarQuotedString;

#### DeleteHeaderCommand extends ActionCommand
	index          = new GrammarNumber;
	last           = false;
	comparator     = '',
	match_type     = ':is',
	field_name     = new GrammarQuotedString;
	value_patterns = new GrammarStringList;

#### ErejectCommand extends ActionCommand
	_reason = new GrammarQuotedString;

#### RejectCommand extends ActionCommand
	_reason = new GrammarQuotedString;

#### NotifyCommand extends ActionCommand
	_method = new GrammarQuotedString;
	_from = new GrammarQuotedString;
	_importance = new GrammarNumber;
	options = new GrammarStringList;
	_message = new GrammarQuotedString;

#### ReplaceCommand extends ActionCommand
	mime        = false;
	_subject    = new GrammarQuotedString;
	_from       = new GrammarQuotedString;
	replacement = new GrammarQuotedString;

#### EncloseCommand extends ActionCommand
	_subject    = new GrammarQuotedString;
	headers     = new GrammarStringList;

#### ExtractTextCommand extends ActionCommand
	modifiers = [];
	_first    = new GrammarNumber;
	varname   = new GrammarQuotedString;

### abstract TestCommand extends GrammarCommand

#### AddressTest extends TestCommand
	address_part = ':all';
	header_list  = new GrammarStringList;
	key_list     = new GrammarStringList;
	index        = new GrammarNumber;
	last         = false;
//	mime
//	anychild

#### AllOfTest extends TestCommand
	tests = new GrammarTestList;

#### AnyOfTest extends TestCommand
	tests = new GrammarTestList;

#### EnvelopeTest extends TestCommand
	address_part = ':all';
	envelope_part = new GrammarStringList;
	key_list      = new GrammarStringList;

#### ExistsTest extends TestCommand
	header_names = new GrammarStringList;
//	mime
//	anychild

#### FalseTest extends TestCommand

#### HeaderTest extends TestCommand
	address_part = ':all';
	header_names = new GrammarStringList;
	key_list     = new GrammarStringList;
	index        = new GrammarNumber;
	last         = false;
	mime         = false;
	anychild     = false;
	// when ":mime" is used:
	type         = false;
	subtype      = false;
	contenttype  = false;
	param        = new GrammarStringList;

#### NotTest extends TestCommand
	test = new TestCommand;

#### SizeTest extends TestCommand
	mode  = ':over'; // :under
	limit = 0;

#### TrueTest extends TestCommand

#### BodyTest extends TestCommand
	body_transform = ''; // :raw, :content <string-list>, :text
	key_list = new GrammarStringList;

#### EnvironmentTest extends TestCommand
	name     = new GrammarQuotedString;
	key_list = new GrammarStringList;

#### StringTest extends TestCommand
	source   = new GrammarStringList;
	key_list = new GrammarStringList;

#### HasFlagTest extends TestCommand
	variable_list = new GrammarStringList;
	list_of_flags = new GrammarStringList;

#### SpamTestTest extends TestCommand
	percent = false, // 0 - 100 else 0 - 10
	value = new GrammarQuotedString;

#### VirusTestTest extends TestCommand
	value = new GrammarQuotedString; // 1 - 5

#### DateTest extends TestCommand
	zone         = new GrammarQuotedString;
	originalzone = false;
	header_name  = new GrammarQuotedString;
	date_part    = new GrammarQuotedString;
	key_list     = new GrammarStringList;
	index        = new GrammarNumber;
	last         = false;

#### CurrentDateTest extends TestCommand
	zone       = new GrammarQuotedString;
	date_part  = new GrammarQuotedString;
	key_list   = new GrammarStringList;

#### ValidNotifyMethodTest extends TestCommand
	notification_uris = new GrammarStringList;

#### NotifyMethodCapabilityTest extends TestCommand
	notification_uri = new GrammarQuotedString;
	notification_capability = new GrammarQuotedString;
	key_list = new GrammarStringList;

#### IHaveTest extends TestCommand
	capabilities = new GrammarStringList;

#### MailboxExistsTest extends TestCommand
	mailbox_names = new GrammarStringList;

#### MetadataTest extends TestCommand
	mailbox = new GrammarQuotedString;
	annotation_name = new GrammarQuotedString;
	key_list = new GrammarStringList;

#### MetadataExistsTest extends TestCommand
	mailbox = new GrammarQuotedString;
	annotation_names = new GrammarStringList;

#### ServerMetadataTest extends TestCommand
	annotation_name = new GrammarQuotedString;
	key_list = new GrammarStringList;

#### ServerMetadataExistsTest extends TestCommand
	annotation_names = new GrammarStringList;

#### ValidExtListTest extends TestCommand
	ext_list_names = new GrammarStringList;

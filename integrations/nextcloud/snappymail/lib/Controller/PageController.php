<?php

namespace OCA\SnappyMail\Controller;

use OCA\SnappyMail\Util\SnappyMailHelper;
use OCA\SnappyMail\ContentSecurityPolicy;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IRequest;

class PageController extends Controller
{
	private IL10N $l;
	// Nextcloud lang to locale mapping array
	public const LANGUAGE_TO_LOCALE_MAP = [
		'af' => 'af-ZA', // Afrikaans (South Africa)
		'ar' => 'ar-SA', // Arabic (Saudi Arabia)
		'az' => 'az-AZ', // Azerbaijani (Azerbaijan)
		'be' => 'be-BY', // Belarusian (Belarus)
		'bg' => 'bg-BG', // Bulgarian (Bulgaria)
		'bn' => 'bn-BD', // Bengali (Bangladesh)
		'bs' => 'bs-BA', // Bosnian (Bosnia and Herzegovina)
		'ca' => 'ca-ES', // Catalan (Spain)
		'cs' => 'cs-CZ', // Czech (Czech Republic)
		'cy' => 'cy-GB', // Welsh (United Kingdom)
		'da' => 'da-DK', // Danish (Denmark)
		'de' => 'de-DE', // German (Germany)
		'el' => 'el-GR', // Greek (Greece)
		'en' => 'en-US', // English (United States)
		'es' => 'es-ES', // Spanish (Spain)
		'et' => 'et-EE', // Estonian (Estonia)
		'eu' => 'eu-ES', // Basque (Spain)
		'fa' => 'fa-IR', // Persian (Iran)
		'fi' => 'fi-FI', // Finnish (Finland)
		'fr' => 'fr-FR', // French (France)
		'gl' => 'gl-ES', // Galician (Spain)
		'he' => 'he-IL', // Hebrew (Israel)
		'hi' => 'hi-IN', // Hindi (India)
		'hr' => 'hr-HR', // Croatian (Croatia)
		'hu' => 'hu-HU', // Hungarian (Hungary)
		'id' => 'id-ID', // Indonesian (Indonesia)
		'is' => 'is-IS', // Icelandic (Iceland)
		'it' => 'it-IT', // Italian (Italy)
		'ja' => 'ja-JP', // Japanese (Japan)
		'ka' => 'ka-GE', // Georgian (Georgia)
		'km' => 'km-KH', // Khmer (Cambodia)
		'ko' => 'ko-KR', // Korean (South Korea)
		'lt' => 'lt-LT', // Lithuanian (Lithuania)
		'lv' => 'lv-LV', // Latvian (Latvia)
		'mk' => 'mk-MK', // Macedonian (North Macedonia)
		'mn' => 'mn-MN', // Mongolian (Mongolia)
		'ms' => 'ms-MY', // Malay (Malaysia)
		'nb' => 'nb-NO', // Norwegian Bokmål (Norway)
		'nl' => 'nl-NL', // Dutch (Netherlands)
		'nn' => 'nn-NO', // Norwegian Nynorsk (Norway)
		'pl' => 'pl-PL', // Polish (Poland)
		'pt' => 'pt-PT', // Portuguese (Portugal)
		'pt-br' => 'pt-BR', // Portuguese (Brazil)
		'ro' => 'ro-RO', // Romanian (Romania)
		'ru' => 'ru-RU', // Russian (Russia)
		'sk' => 'sk-SK', // Slovak (Slovakia)
		'sl' => 'sl-SI', // Slovenian (Slovenia)
		'sq' => 'sq-AL', // Albanian (Albania)
		'sr' => 'sr-RS', // Serbian (Serbia)
		'sv' => 'sv-SE', // Swedish (Sweden)
		'th' => 'th-TH', // Thai (Thailand)
		'tr' => 'tr-TR', // Turkish (Turkey)
		'uk' => 'uk-UA', // Ukrainian (Ukraine)
		'vi' => 'vi-VN', // Vietnamese (Vietnam)
		'zh-cn' => 'zh-CN', // Chinese (China)
		'zh-tw' => 'zh-TW'  // Chinese (Taiwan)
	];


	public function __construct(string $appName, IRequest $request, IL10N $l) {
		parent::__construct($appName, $request);
		$this->l = $l;
		$lang = \strtolower(\str_replace('_', '-', $l->getLocaleCode()));
		if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$_SERVER['HTTP_ACCEPT_LANGUAGE'] = $lang;
		} else {
			$_SERVER['HTTP_ACCEPT_LANGUAGE'] .= ",{$lang};q=2";
		}
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index()
	{
		$config = \OC::$server->getConfig();

		$bAdmin = false;
		if (!empty($_SERVER['QUERY_STRING'])) {
			SnappyMailHelper::loadApp();
			$bAdmin = \RainLoop\Api::Config()->Get('security', 'admin_panel_key', 'admin') == $_SERVER['QUERY_STRING'];
			if (!$bAdmin) {
				return SnappyMailHelper::startApp(true);
			}
		}

		if (!$bAdmin && $config->getAppValue('snappymail', 'snappymail-no-embed')) {
			\OC::$server->getNavigationManager()->setActiveEntry('snappymail');
			\OCP\Util::addScript('snappymail', 'snappymail');
			\OCP\Util::addStyle('snappymail', 'style');
			SnappyMailHelper::startApp();
			$response = new TemplateResponse('snappymail', 'index', [
				'snappymail-iframe-url' => SnappyMailHelper::normalizeUrl(SnappyMailHelper::getAppUrl())
					. (empty($_GET['target']) ? '' : "#{$_GET['target']}")
			]);
			$csp = new ContentSecurityPolicy();
			$csp->addAllowedFrameDomain("'self'");
//			$csp->addAllowedFrameAncestorDomain("'self'");
			$response->setContentSecurityPolicy($csp);
			return $response;
		}

		\OC::$server->getNavigationManager()->setActiveEntry('snappymail');

		\OCP\Util::addStyle('snappymail', 'embed');

		SnappyMailHelper::startApp();
		$oConfig = \RainLoop\Api::Config();
		$oActions = $bAdmin ? new \RainLoop\ActionsAdmin() : \RainLoop\Api::Actions();
		$oHttp = \MailSo\Base\Http::SingletonInstance();
		$oServiceActions = new \RainLoop\ServiceActions($oHttp, $oActions);
		$sAppJsMin = $oConfig->Get('debug', 'javascript', false) ? '' : '.min';
		$sAppCssMin = $oConfig->Get('debug', 'css', false) ? '' : '.min';
		$languageSetting = (bool) $oConfig->Get('webmail', 'allow_languages_on_settings', true);
		if (!$languageSetting) {
			$aResultLang = \json_decode(\file_get_contents(APP_VERSION_ROOT_PATH . 'app/localization/langs.json'), true);
			if ($aResultLang === null) {
				throw new \Exception('Error decoding JSON content.');
			}
			$user = \OC::$server->getUserSession()->getUser();
			$userId = $user->getUID();
			$langCode = $config->getUserValue($userId, 'core', 'lang');
			$userLang = \OC::$server->getConfig()->getUserValue($userId, 'core', 'lang');
			$localeCode = self::LANGUAGE_TO_LOCALE_MAP[$langCode];
			if (!strpos($localeCode, '_') && !strpos($localeCode, '-')) {
				$localeCode = $localeCode . '-' . strtoupper($localeCode);
			}
			$sLanguage = 'en';
			if(isset($aResultLang['LANGS_NAMES_EN'][$localeCode])) {
				$sLanguage = $localeCode;
			}
		}else {
			$sLanguage = $oActions->GetLanguage(false);
		}
		$csp = new ContentSecurityPolicy();
		$sNonce = $csp->getSnappyMailNonce();

		$params = [
			'Admin' => $bAdmin ? 1 : 0,
			'LoadingDescriptionEsc' => \htmlspecialchars($oConfig->Get('webmail', 'loading_description', 'SnappyMail'), ENT_QUOTES|ENT_IGNORE, 'UTF-8'),
			'BaseTemplates' => \RainLoop\Utils::ClearHtmlOutput($oServiceActions->compileTemplates($bAdmin)),
			'BaseAppBootScript' => \file_get_contents(APP_VERSION_ROOT_PATH.'static/js'.($sAppJsMin ? '/min' : '').'/boot'.$sAppJsMin.'.js'),
			'BaseAppBootScriptNonce' => $sNonce,
			'BaseLanguage' => $oActions->compileLanguage($sLanguage, $bAdmin),
			'BaseAppBootCss' => \file_get_contents(APP_VERSION_ROOT_PATH.'static/css/boot'.$sAppCssMin.'.css'),
			'BaseAppThemeCss' => \preg_replace(
				'/\\s*([:;{},]+)\\s*/s',
				'$1',
				$oActions->compileCss($oActions->GetTheme($bAdmin), $bAdmin)
			)
		];

//		\OCP\Util::addScript('snappymail', '../app/snappymail/v/'.APP_VERSION.'/static/js'.($sAppJsMin ? '/min' : '').'/boot'.$sAppJsMin);

		// Nextcloud html encodes, so addHeader('style') is not possible
//		\OCP\Util::addHeader('style', ['id'=>'app-boot-css'], \file_get_contents(APP_VERSION_ROOT_PATH.'static/css/boot'.$sAppCssMin.'.css'));
		\OCP\Util::addHeader('link', ['type'=>'text/css','rel'=>'stylesheet','href'=>\RainLoop\Utils::WebStaticPath('css/'.($bAdmin?'admin':'app').$sAppCssMin.'.css')], '');
//		\OCP\Util::addHeader('style', ['id'=>'app-theme-style','data-href'=>$params['BaseAppThemeCssLink']], $params['BaseAppThemeCss']);

		$response = new TemplateResponse('snappymail', 'index_embed', $params);

		$response->setContentSecurityPolicy($csp);

		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function appGet()
	{
		return SnappyMailHelper::startApp(true);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function appPost()
	{
		return SnappyMailHelper::startApp(true);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function indexPost()
	{
		return SnappyMailHelper::startApp(true);
	}
}
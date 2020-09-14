import { ajax } from 'Common/Links';
import { pInt, pString } from 'Common/Utils';
import { DEFAULT_AJAX_TIMEOUT, TOKEN_ERROR_LIMIT, AJAX_ERROR_LIMIT } from 'Common/Consts';
import { Notification } from 'Common/Enums';
import { data as GlobalsData } from 'Common/Globals';

import { AbstractBasicPromises } from 'Promises/AbstractBasic';

class AbstractAjaxPromises extends AbstractBasicPromises {
	oRequests = {};

	constructor() {
		super();

		this.clear();
	}

	clear() {
		this.oRequests = {};
	}

	abort(sAction, bClearOnly) {
		if (this.oRequests[sAction]) {
			if (!bClearOnly && this.oRequests[sAction].abort) {
//				this.oRequests[sAction].__aborted__ = true;
				this.oRequests[sAction].abort();
			}

			this.oRequests[sAction] = null;
			delete this.oRequests[sAction];
		}

		return this;
	}

	ajaxRequest(action, isPost, timeOut, params, additionalGetString, fTrigger) {

		additionalGetString = pString(additionalGetString);

		let init = {};

		this.setTrigger(fTrigger, true);

		if (window.AbortController) {
			this.abort(action);
			const controller = new AbortController();
			setTimeout(() => controller.abort(), pInt(timeOut, DEFAULT_AJAX_TIMEOUT));
			init.signal = controller.signal;
			this.oRequests[action] = controller;
		}

		return rl.fetchJSON(ajax(additionalGetString), init, pInt(timeOut, DEFAULT_AJAX_TIMEOUT), isPost ? params : null)
			.then(data => {
				this.abort(action, true);

				if (!data) {
					return Promise.reject(Notification.AjaxParse);
				}

				if (data.UpdateToken) {
					rl.hash.set();
					rl.settings.set('AuthAccountHash', data.UpdateToken);
				}

/*
				let isCached = false, type = '';
				if (data && data.Time) {
					isCached = pInt(data.Time) > microtime() - start;
				}
				// backward capability
				switch (true) {
					case 'success' === textStatus && data && data.Result && action === data.Action:
						type = StorageResultType.Success;
						break;
					case 'abort' === textStatus && (!data || !data.__aborted__):
						type = StorageResultType.Abort;
						break;
					default:
						type = StorageResultType.Error;
						break;
				}
*/
				this.setTrigger(fTrigger, false);

				if (!data.Result || action !== data.Action) {
					if ([
							Notification.AuthError,
							Notification.AccessError,
							Notification.ConnectionError,
							Notification.DomainNotAllowed,
							Notification.AccountNotAllowed,
							Notification.MailServerError,
							Notification.UnknownNotification,
							Notification.UnknownError
						].includes(data.ErrorCode)
					) {
						++GlobalsData.iAjaxErrorCount;
					}

					if (Notification.InvalidToken === data.ErrorCode) {
						++GlobalsData.iTokenErrorCount;
					}

					if (TOKEN_ERROR_LIMIT < GlobalsData.iTokenErrorCount) {
						rl.logoutReload();
					}

					if (data.ClearAuth || data.Logout || AJAX_ERROR_LIMIT < GlobalsData.iAjaxErrorCount) {
						if (GlobalsData.__APP__) {
							rl.hash.clear();

							if (!data.ClearAuth) {
								rl.logoutReload();
							}
						}
					}

					return Promise.reject(data.ErrorCode ? data.ErrorCode : Notification.AjaxFalse);
				}

				return data;
			}).catch(err => {
				if (err.name == 'AbortError') { // handle abort()
					return Promise.reject(Notification.AjaxAbort);
				}
				return Promise.reject(err);
			});
	}

	getRequest(sAction, fTrigger, sAdditionalGetString, iTimeOut) {
		sAdditionalGetString = undefined === sAdditionalGetString ? '' : pString(sAdditionalGetString);
		sAdditionalGetString = sAction + '/' + sAdditionalGetString;

		return this.ajaxRequest(sAction, false, iTimeOut, null, sAdditionalGetString, fTrigger);
	}

	postRequest(action, fTrigger, params, timeOut) {
		params = params || {};
		params.Action = action;

		return this.ajaxRequest(action, true, timeOut, params, '', fTrigger);
	}
}

export { AbstractAjaxPromises, AbstractAjaxPromises as default };

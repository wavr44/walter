(rl => {
	if (rl) {
		addEventListener('rl-view-model', e => {
			if ('SettingsContacts' === e.detail.viewModelTemplateID) {
				const container = e.detail.viewModelDom.querySelector('.form-horizontal');
				if (container) {
					rl.pluginRemoteRequest((iError, oData) => {
						if (!iError) {
							const mainDivElement = Element.fromHTML('<div class="control-group">'
								+ '<label>' + rl.i18n("NEXTCLOUD/ADDRESS_BOOK") + '</label>'
								+ '</div>');

							const selectElement = Element.fromHTML('<select></select>');

							const books = JSON.parse(oData.Result.addressbooks);
							books.forEach(book => {
								if (book.selected) {
									selectElement.append(Element.fromHTML('<option value="' + book.uri + '" selected="selected">' + book.name + '</option>'));
								} else {
									selectElement.append(Element.fromHTML('<option value="' + book.uri + '">' + book.name + '</option>'));
								}
							});

							selectElement.onchange = function() {
								rl.pluginRemoteRequest(() => { }, 'NextcloudUpdateAddressBook', {
									uri: selectElement.value
								});
							}

							mainDivElement.append(selectElement);
							container.append(mainDivElement);
						}
					}, "NextcloudGetAddressBooks");
				}
			}
		});
	}
})(window.rl);

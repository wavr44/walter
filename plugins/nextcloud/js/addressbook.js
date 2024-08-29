(rl => {
	if (rl) {
		class AddressBookSettings /* extends AbstractViewSettings */ {
			constructor() {
				this.addressBookList = ko.observableArray();
				this.selectedAddressBook = ko.observable();

				rl.pluginRemoteRequest((iError, oData) => {
					if (!iError) {
						const books = JSON.parse(oData.Result.addressbooks);
						books.forEach(book => {
							this.addressBookList.push(book);

							if (book.selected) {
								this.selectedAddressBook(book.uri);
							}
						});

						this.selectedAddressBook.subscribe(value => {
							rl.pluginRemoteRequest(() => { }, 'NextcloudUpdateAddressBook', {
								uri: value
							});
						});
					}
				}, "JsonGetAddressbooks");
			}
		}

		rl.addSettingsViewModel(
			AddressBookSettings,
			'AddressBookSettings',
			'NEXTCLOUD/ADDRESS_BOOK',
			'addressbook'
		);
	}
})(window.rl);

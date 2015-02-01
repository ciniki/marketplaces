//
// This app will handle the listing, additions and deletions of markets.  These are associated business.
//
function ciniki_marketplaces_main() {
	//
	// Panels
	//
	this.marketStatus = {
		'10':'Active',
		'50':'Archive',
		};
	this.sellerStatus = {
		'10':'Applied',
		'20':'Accepted',
		};
	this.sellerFlags = {
		'1':{'name':'Paid'},
		};
	this.init = function() {
		//
		// markets panel
		//
		this.menu = new M.panel('Market Places',
			'ciniki_marketplaces_main', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.marketplaces.main.menu');
        this.menu.sections = {
			'markets':{'label':'Markets', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':[''],
				'addTxt':'Add Market',
				'addFn':'M.ciniki_marketplaces_main.marketEdit(\'M.ciniki_marketplaces_main.showMenu();\',0);',
				},
			};
		this.menu.sectionData = function(s) { return this.data[s]; }
		this.menu.noData = function(s) { return this.sections[s].noData; }
		this.menu.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.market.name;
			}
		};
		this.menu.rowFn = function(s, i, d) {
			return 'M.ciniki_marketplaces_main.marketShow(\'M.ciniki_marketplaces_main.showMenu();\',\'' + d.market.id + '\');';
		};
		this.menu.addButton('add', 'Add', 'M.ciniki_marketplaces_main.marketEdit(\'M.ciniki_marketplaces_main.showMenu();\',0);');
		this.menu.addClose('Back');

		//
		// The market panel 
		//
		this.market = new M.panel('Market',
			'ciniki_marketplaces_main', 'market',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.marketplaces.main.market');
		this.market.data = {};
		this.market.market_id = 0;
		this.market.sections = {
			'info':{'label':'', 'aside':'yes', 'list':{
				'name':{'label':'Name'},
				'dates':{'label':'dates'},
				}},
			'menu':{'label':'', 'aside':'yes', 'list':{
				'inventoryexcel':{'label':'Download Inventory (Excel)', 'fn':'M.ciniki_marketplaces_main.downloadInventory(M.ciniki_marketplaces_main.market.market_id, \'excel\');'},
				'pricelistpdf':{'label':'Download Price List (PDF)', 'fn':'M.ciniki_marketplaces_main.downloadPriceList(M.ciniki_marketplaces_main.market.market_id, \'pdf\');'},
				'sellers':{'label':'Seller Reports (PDF)', 'fn':'M.ciniki_marketplaces_main.downloadSellerSummary(M.ciniki_marketplaces_main.market.market_id,\'pdf\',0);'},
				}},
			'sellers':{'label':'', 'type':'simplegrid', 'num_cols':5,
				'headerValues':['Seller', 'Status', 'Value', 'Fees', 'Net'],
				'cellClasses':['', ''],
				'sortable':'yes', 'sortTypes':['text', 'text', 'altnumber', 'altnumber', 'altnumber'],
				'noData':'No prices',
				'addTxt':'Add Seller',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_marketplaces_main.marketShow();\',\'mc\',{\'next\':\'M.ciniki_marketplaces_main.sellerAdd\',\'customer_id\':0});',
			},
			'_buttons':{'label':'', 'buttons':{
//'edit':{'label':'Edit', 'fn':'M.ciniki_marketplaces_main.marketEdit(\'M.ciniki_marketplaces_main.marketShow();\',M.ciniki_marketplaces_main.market.market_id);'},
				}},
		};
		this.market.sectionData = function(s) {
			if( s == 'info' ) { return this.sections[s].list; }
			if( s == 'menu' ) { return this.sections[s].list; }
			return this.data[s];
		};
		this.market.listLabel = function(s, i, d) { if( s == 'info' ) { return d.label;} return '';};
		this.market.listValue = function(s, i, d) {
			if( s == 'menu' ) { return d.label; }
			if( i == 'dates' ) {
				return this.data['start_date'] + ' - ' + this.data['end_date'];
			}
			if( i == 'name' ) {
				return this.data['name'] + ' <span class="subdue">[' + this.data['status_text'] + ']</span>';
			}
			return this.data[i];
		};
		this.market.fieldValue = function(s, i, d) {
			return this.data[i];
		};
		this.market.cellValue = function(s, i, j, d) {
			if( s == 'sellers' ) { 
				switch(j) {
					case 0: return d.seller.display_name;
					case 1: return d.seller.status_text;
					case 2: return d.seller.total_price;
					case 3: return d.seller.total_business_fee;
					case 4: return d.seller.total_seller_amount;
				}
			}
		};
		this.market.cellSortValue = function(s, i, j, d) {
			if( s == 'sellers' ) {
				switch(j) {
					case 0: return d.seller.display_name;
					case 1: return d.seller.status_text;
					case 2: return d.seller.total_price.replace(/\$/, '');
					case 3: return d.seller.total_business_fee.replace(/\$/, '');
					case 4: return d.seller.total_seller_amount.replace(/\$/, '');
				}
			}
		};
		this.market.rowFn = function(s, i, d) {
			return 'M.ciniki_marketplaces_main.sellerShow(\'M.ciniki_marketplaces_main.marketShow();\',\'' + d.seller.id + '\');';
		};
		this.market.addButton('add', 'Seller', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_marketplaces_main.marketShow();\',\'mc\',{\'next\':\'M.ciniki_marketplaces_main.sellerAdd\',\'customer_id\':0});');
		this.market.addButton('edit', 'Edit', 'M.ciniki_marketplaces_main.marketEdit(\'M.ciniki_marketplaces_main.marketShow();\',M.ciniki_marketplaces_main.market.market_id);');
		this.market.addClose('Back');

		//
		// The panel for a editing a market
		//
		this.marketedit = new M.panel('Market',
			'ciniki_marketplaces_main', 'marketedit',
			'mc', 'medium', 'sectioned', 'ciniki.marketplaces.main.marketedit');
		this.marketedit.data = null;
		this.marketedit.market_id = 0;
        this.marketedit.sections = { 
            'general':{'label':'General', 'fields':{
                'name':{'label':'Name', 'hint':'Market name', 'type':'text'},
                'status':{'label':'Status', 'hint':'', 'type':'toggle', 'toggles':this.marketStatus},
                'start_date':{'label':'Start', 'type':'date'},
                'end_date':{'label':'End', 'type':'date'},
                }}, 
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_marketplaces_main.marketSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_marketplaces_main.marketDelete();'},
				}},
            };  
		this.marketedit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.marketedit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.marketplaces.marketHistory', 'args':{'business_id':M.curBusinessID, 
				'market_id':this.market_id, 'field':i}};
		}
		this.marketedit.addButton('save', 'Save', 'M.ciniki_marketplaces_main.marketSave();');
		this.marketedit.addClose('Cancel');

		//
		// The panel to display seller info and items
		//
		this.seller = new M.panel('Seller',
			'ciniki_marketplaces_main', 'seller',
			'mc', 'medium mediumflex', 'sectioned', 'ciniki.marketplaces.main.seller');
		this.seller.data = {};
		this.seller.market_id = 0;
		this.seller.seller_id = 0;
		this.seller.sections = {
			'customer_details':{'label':'Seller', 'type':'simplegrid', 'num_cols':2,
				'cellClasses':['label',''],
				},
			'info':{'label':'', 'list':{
				'status_text':{'label':'Status'},
				'flags_text':{'label':'Options'},
				}},
			'reports':{'label':'', 'list':{
				'summarypdf':{'label':'Summary (PDF)', 'fn':'M.ciniki_marketplaces_main.downloadSellerSummary(M.ciniki_marketplaces_main.market.market_id,\'pdf\',M.ciniki_marketplaces_main.seller.seller_id);'},
				}},
			'items':{'label':'Items', 'type':'simplegrid', 'num_cols':7,
				'headerValues':['Code', 'Item', 'Price', 'Fee%', 'Sell Date', 'Fees', 'Sell Price'],
				'cellClasses':['', ''],
				'sortable':'yes', 'sortTypes':['text', 'text', 'altnumber', 'altnumber', 'altnumber'],
				'noData':'No items',
			},
			'_buttons':{'label':'', 'buttons':{
				'add':{'label':'Add Item', 'fn':'M.ciniki_marketplaces_main.itemEdit(\'M.ciniki_marketplaces_main.sellerShow();\',0,M.ciniki_marketplaces_main.seller.seller_id);'},
				'edit':{'label':'Edit Seller', 'fn':'M.ciniki_marketplaces_main.sellerEdit(\'M.ciniki_marketplaces_main.sellerShow();\',M.ciniki_marketplaces_main.seller.seller_id,M.ciniki_marketplaces_main.seller.market_id,0);'},
				}},
		};
		this.seller.sectionData = function(s) {
			if( s == 'info' || s == 'reports' ) { return this.sections[s].list; }
			return this.data[s];
		};
		this.seller.listLabel = function(s, i, d) { 
			if( s == 'info' ) { return d.label; }
			return '';
		};
		this.seller.listValue = function(s, i, d) {
			if( s == 'reports' ) { return d.label; }
			return this.data[i];
		};
		this.seller.listFn = function(s, i, d) {
			if( s == 'reports' ) { return d.fn; }
			return null;
		};
		this.seller.cellValue = function(s, i, j, d) {
			if( s == 'customer_details' ) {
				switch (j) {
					case 0: return d.detail.label;
					case 1: return (d.detail.label == 'Email'?M.linkEmail(d.detail.value):d.detail.value);
				}
			}
			else if( s == 'items' ) { 
				switch(j) {
					case 0: return d.item.code;
					case 1: return d.item.name;
					case 2: return d.item.price;
					case 3: return d.item.fee_percent;
					case 4: return d.item.sell_date;
					case 5: return d.item.business_fee;
					case 6: return d.item.seller_amount;
				}
			}
		};
		this.seller.cellSortValue = function(s, i, j, d) {
			if( s == 'items' ) {
				switch(j) {
					case 0: return d.item.code;
					case 2: return d.item.price.replace(/\$/, '');
					case 5: return d.item.business_fee.replace(/\$/, '');
					case 6: return d.item.seller_amount.replace(/\$/, '');
				}
			}
		};
		this.seller.rowFn = function(s, i, d) {
			if( s == 'items' ) {
				return 'M.ciniki_marketplaces_main.itemEdit(\'M.ciniki_marketplaces_main.sellerShow();\',\'' + d.item.id + '\',0);';
			}
			return null;
		};
		this.seller.footerValue = function(s, i, d) {
			if( s == 'items' && this.data.item_totals != null ) {
				switch(i) {
					case 0: return '';
					case 1: return '';
					case 2: return this.data.item_totals.price;
					case 3: return '';
					case 4: return '';
					case 5: return this.data.item_totals.business_fee;
					case 6: return this.data.item_totals.seller_amount;
				}
			}
		};
		this.seller.addButton('add', 'Item', 'M.ciniki_marketplaces_main.itemEdit(\'M.ciniki_marketplaces_main.sellerShow();\',0,M.ciniki_marketplaces_main.seller.seller_id);');
		this.seller.addButton('edit', 'Edit', 'M.ciniki_marketplaces_main.sellerEdit(\'M.ciniki_marketplaces_main.sellerShow();\',M.ciniki_marketplaces_main.seller.seller_id,M.ciniki_marketplaces_main.seller.market_id,0);');
		this.seller.addClose('Back');

		//
		// The panel to edit a seller
		//
		this.selleredit = new M.panel('Market',
			'ciniki_marketplaces_main', 'selleredit',
			'mc', 'medium', 'sectioned', 'ciniki.marketplaces.main.selleredit');
		this.selleredit.data = null;
		this.selleredit.market_id = 0;
		this.selleredit.seller_id = 0;
		this.selleredit.customer_id = 0;
        this.selleredit.sections = { 
			'customer_details':{'label':'Seller', 'type':'simplegrid', 'num_cols':2,
				'cellClasses':['label', ''],
				'addTxt':'Edit',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_marketplaces_main.selleredit.updateCustomer(null);\',\'mc\',{\'next\':\'M.ciniki_marketplaces_main.selleredit.updateCustomer\',\'customer_id\':M.ciniki_marketplaces_main.selleredit.customer_id});',
				'changeTxt':'Change customer',
				'changeFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_marketplaces_main.selleredit.updateCustomer(null);\',\'mc\',{\'next\':\'M.ciniki_marketplaces_main.selleredit.updateCustomer\',\'customer_id\':0});',
				},
            'general':{'label':'General', 'fields':{
                'status':{'label':'Status', 'hint':'', 'type':'toggle', 'toggles':this.sellerStatus},
                'flags':{'label':'Options', 'hint':'', 'type':'flags', 'flags':this.sellerFlags},
                }}, 
			'_notes':{'label':'Notes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_marketplaces_main.sellerSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_marketplaces_main.sellerDelete();'},
				}},
            };  
		this.selleredit.sectionData = function(s) { return this.data[s]; }
		this.selleredit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.selleredit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.marketplaces.marketSellerHistory', 'args':{'business_id':M.curBusinessID, 
				'seller_id':this.seller_id, 'field':i}};
		}
		this.selleredit.cellValue = function(s, i, j, d) {
			if( s == 'customer_details' ) {
				switch(j) {
					case 0: return d.detail.label;
					case 1: return d.detail.value.replace(/\n/, '<br/>');
				}
			}
		};
		this.selleredit.updateCustomer = function(cid) {
			if( cid != null && this.customer_id != cid ) { this.customer_id = cid; }
			if( this.customer_id > 0 ) {
				M.api.getJSONCb('ciniki.customers.customerDetails', {'business_id':M.curBusinessID,
					'customer_id':this.edit.customer_id}, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						var p = M.ciniki_marketplaces_main.selleredit;
						p.data.customer = rsp.details;
						p.refreshSection('customer_details');
						p.show();
					});
			}
		};
		this.selleredit.addButton('save', 'Save', 'M.ciniki_marketplaces_main.sellerSave();');
		this.selleredit.addClose('Cancel');

		//
		// The panel to edit an item
		//
		this.itemedit = new M.panel('Market',
			'ciniki_marketplaces_main', 'itemedit',
			'mc', 'medium', 'sectioned', 'ciniki.marketplaces.main.itemedit');
		this.itemedit.data = null;
		this.itemedit.seller_id = 0;
		this.itemedit.item_id = 0;
        this.itemedit.sections = { 
            'general':{'label':'General', 'fields':{
                'code':{'label':'Code', 'hint':'', 'type':'text', 'size':'small'},
                'name':{'label':'Name', 'hint':'', 'type':'text'},
                'type':{'label':'Type', 'hint':'', 'type':'text'},
                'category':{'label':'Category', 'hint':'', 'type':'text'},
                'price':{'label':'Price', 'hint':'', 'type':'text', 'size':'small'},
                'fee_percent':{'label':'Fee %', 'hint':'', 'type':'text', 'size':'small', 'onchangeFn':'M.ciniki_marketplaces_main.itemedit.calc'},
                'sell_date':{'label':'Sell Date', 'hint':'', 'type':'date', 'onchangeFn':'M.ciniki_marketplaces_main.itemedit.calc'},
                'sell_price':{'label':'Sell Price', 'hint':'', 'type':'text', 'size':'small', 'onchangeFn':'M.ciniki_marketplaces_main.itemedit.calc'},
                'business_fee':{'label':'Business Fee', 'hint':'', 'type':'text', 'size':'small'},
                'seller_amount':{'label':'Seller Amount', 'hint':'', 'type':'text', 'size':'small'},
                }}, 
			'_notes':{'label':'Notes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_marketplaces_main.itemSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_marketplaces_main.itemDelete();'},
				}},
            };  
		this.itemedit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.itemedit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.marketplaces.marketItemHistory', 'args':{'business_id':M.curBusinessID, 
				'item_id':this.item_id, 'field':i}};
		}
		this.itemedit.calc = function(s, i) {
			var sell_date = this.formFieldValue(this.sections[s].fields['sell_date'], 'sell_date');
			if( sell_date == '' ) { return true; }
			var price = this.formFieldValue(this.sections[s].fields['price'], 'price');
			var sell_price = this.formFieldValue(this.sections[s].fields['sell_price'], 'sell_price');
			if( sell_price == '' ) {
				this.setFieldValue('sell_price', price);
				sell_price = price;
			}
			var fee_percent = parseFloat(this.formFieldValue(this.sections[s].fields['fee_percent'], 'fee_percent'));
			var business_fee = this.formFieldValue(this.sections[s].fields['business_fee'], 'business_fee');
			var seller_amount = this.formFieldValue(this.sections[s].fields['seller_amount'], 'seller_amount');
			var sp = parseFloat(sell_price.replace(/\$/,''));
			if( fee_percent > 0 ) {
				bf = (sp * (fee_percent/100));
				bf = bf.toFixed(2);
			} 
			this.setFieldValue('business_fee', '$' + bf);
			this.setFieldValue('seller_amount', '$' + (sp - bf).toFixed(2));
		};
		this.itemedit.addButton('save', 'Save', 'M.ciniki_marketplaces_main.itemSave();');
		this.itemedit.addClose('Cancel');

	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_marketplaces_main', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		if( M.curBusiness.modules['ciniki.marketplaces'] != null 
			&& (M.curBusiness.modules['ciniki.marketplaces'].flags&0x01) ) {
			this.selleredit.sections.general.fields.flags.active = 'yes';
		} else {
			this.selleredit.sections.general.fields.flags.active = 'no';
		}

		this.showMenu(cb);
	}

	this.showMenu = function(cb) {
		this.menu.data = {};
		var rsp = M.api.getJSONCb('ciniki.marketplaces.marketList', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_marketplaces_main.menu;
				p.data = rsp;
				p.refresh();
				p.show(cb);
			});
	};

	//
	// Market functions
	//

	this.marketShow = function(cb, mid) {
		this.market.reset();
		if( mid != null ) { this.market.market_id = mid; }
		var rsp = M.api.getJSONCb('ciniki.marketplaces.marketGet', {'business_id':M.curBusinessID, 
			'market_id':this.market.market_id, 'sellers':'summary'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_marketplaces_main.market;
				p.data = rsp.market;
				p.refresh();
				p.show(cb);
			});
	};

	this.marketEdit = function(cb, mid) {
		this.marketedit.reset();
		if( mid != null ) { this.marketedit.market_id = mid; }
		this.marketedit.sections._buttons.buttons.delete.visible = 'no';

		if( this.marketedit.market_id > 0 ) {
			M.api.getJSONCb('ciniki.marketplaces.marketGet', {'business_id':M.curBusinessID, 
				'market_id':this.marketedit.market_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_marketplaces_main.marketedit;
					p.data = rsp.market;
					if( rsp.market.status >= 50 ) {
						p.sections._buttons.buttons.delete.visible = 'yes';
					}
					p.refresh();
					p.show(cb);
				});
		} else {
			this.marketedit.data = {'status':'10'};
			this.marketedit.show(cb);
		}
	};

	this.marketSave = function() {
		if( this.marketedit.market_id > 0 ) {
			var c = this.marketedit.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.marketplaces.marketUpdate', {'business_id':M.curBusinessID, 
					'market_id':M.ciniki_marketplaces_main.marketedit.market_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					M.ciniki_marketplaces_main.marketedit.close();
					});
			} else {
				this.marketedit.close();
			}
		} else {
			var c = this.marketedit.serializeForm('yes');
			M.api.postJSONCb('ciniki.marketplaces.marketAdd', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					if( rsp.id > 0 ) {
						var cb = M.ciniki_marketplaces_main.marketedit.cb;
						M.ciniki_marketplaces_main.marketedit.destroy();
						M.ciniki_marketplaces_main.marketShow(cb,rsp.id);
					} else {
						M.ciniki_marketplaces_main.marketedit.close();
					}
				});
		}
	};

	this.marketDelete = function() {
		if( confirm("Are you sure you want to remove this market, it's sellers and all items and sales data?") ) {
			var rsp = M.api.getJSONCb('ciniki.marketplaces.marketDelete', 
				{'business_id':M.curBusinessID, 'market_id':M.ciniki_marketplaces_main.market.market_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_marketplaces_main.market.close();
				});
		}
	}

	this.downloadInventory = function(mid, format) {
		var args = {'business_id':M.curBusinessID, 'market_id':mid, 'output':format};
		M.api.openPDF('ciniki.marketplaces.marketInventory', args);
	};

	this.downloadPriceList = function(mid, format) {
		var args = {'business_id':M.curBusinessID, 'market_id':mid, 'output':format};
		M.api.openPDF('ciniki.marketplaces.marketPriceList', args);
	};

	this.downloadSellerSummary = function(mid, format, sid) {
		var args = {'business_id':M.curBusinessID, 'market_id':mid, 'output':format};
		if( sid != null && sid > 0 ) {
			args.seller_id = sid;
		}
		M.api.openPDF('ciniki.marketplaces.marketSellerSummary', args);
	};

	//
	// Seller functions
	//

	this.sellerShow = function(cb, sid) {
		this.seller.reset();
		if( sid != null ) { this.seller.seller_id = sid; }
		var rsp = M.api.getJSONCb('ciniki.marketplaces.marketSellerGet', {'business_id':M.curBusinessID, 
			'seller_id':this.seller.seller_id, 'items':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_marketplaces_main.seller;
				p.data = rsp.seller;
				p.refresh();
				p.show(cb);
			});
	};

	this.sellerAdd = function(cid) {
		this.sellerEdit('M.ciniki_marketplaces_main.marketShow();', 0, M.ciniki_marketplaces_main.market.market_id, cid);		
	};

	this.sellerEdit = function(cb, sid, mid, cid) {
		this.selleredit.reset();
		if( sid != null ) { this.selleredit.seller_id = sid; }
		if( mid != null ) { this.selleredit.market_id = mid; }
		if( cid != null ) { this.selleredit.customer_id = cid; }
		this.selleredit.sections._buttons.buttons.delete.visible = 'no';

		if( this.selleredit.seller_id > 0 ) {
			this.selleredit.sections._buttons.buttons.delete.visible = 'yes';
			this.selleredit.sections.customer_details.addTxt = 'Edit';
			this.selleredit.sections.customer_details.changeTxt = 'Change Seller';
			M.api.getJSONCb('ciniki.marketplaces.marketSellerGet', {'business_id':M.curBusinessID, 
				'seller_id':this.selleredit.seller_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_marketplaces_main.selleredit;
					p.data = rsp.seller;
					p.refresh();
					p.show(cb);
				});
		} else if( this.selleredit.customer_id > 0 ) {
			M.api.getJSONCb('ciniki.customers.customerDetails', {'business_id':M.curBusinessID,
				'customer_id':this.selleredit.customer_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_marketplaces_main.selleredit;
					p.data = {'customer_details':rsp.details, 'status':'10'};
					p.refresh();
					p.show(cb);
				});
		}
	};

	this.sellerSave = function() {
		if( this.selleredit.seller_id > 0 ) {
			var c = this.selleredit.serializeForm('no');
			if( this.selleredit.customer_id != this.selleredit.data.seller_id ) {
				c += '&customer_id=' + this.selleredit.customer_id;
			}
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.marketplaces.marketSellerUpdate', {'business_id':M.curBusinessID, 
					'seller_id':M.ciniki_marketplaces_main.selleredit.seller_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					M.ciniki_marketplaces_main.selleredit.close();
					});
			} else {
				this.selleredit.close();
			}
		} else {
			var c = this.selleredit.serializeForm('yes');
			c += '&market_id=' + this.selleredit.market_id;
			c += '&customer_id=' + this.selleredit.customer_id;
			M.api.postJSONCb('ciniki.marketplaces.marketSellerAdd', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					if( rsp.id > 0 ) {
						var cb = M.ciniki_marketplaces_main.selleredit.cb;
						M.ciniki_marketplaces_main.selleredit.destroy();
						M.ciniki_marketplaces_main.sellerShow(cb,rsp.id);
					} else {
						M.ciniki_marketplaces_main.selleredit.close();
					}
				});
		}
	};

	this.sellerDelete = function() {
		if( confirm("Are you sure you want to remove this seller, it's sellers and all items and sales data?") ) {
			var rsp = M.api.getJSONCb('ciniki.marketplaces.marketSellerDelete', 
				{'business_id':M.curBusinessID, 'seller_id':M.ciniki_marketplaces_main.seller.seller_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_marketplaces_main.seller.close();
				});
		}
	}

	//
	// Item functions
	//
	this.itemEdit = function(cb, iid, sid) {
		this.itemedit.reset();
		if( iid != null ) { this.itemedit.item_id = iid; }
		if( sid != null ) { this.itemedit.seller_id = sid; }
		this.itemedit.sections._buttons.buttons.delete.visible = 'no';

		if( this.itemedit.item_id > 0 ) {
			this.itemedit.sections._buttons.buttons.delete.visible = 'yes';
			M.api.getJSONCb('ciniki.marketplaces.marketItemGet', {'business_id':M.curBusinessID, 
				'item_id':this.itemedit.item_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_marketplaces_main.itemedit;
					p.data = rsp.item;
					p.refresh();
					p.show(cb);
				});
		} else {
			this.itemedit.data = {};
			this.itemedit.show(cb);
		}
	};

	this.itemSave = function() {
		if( this.itemedit.item_id > 0 ) {
			var c = this.itemedit.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.marketplaces.marketItemUpdate', {'business_id':M.curBusinessID, 
					'item_id':M.ciniki_marketplaces_main.itemedit.item_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					M.ciniki_marketplaces_main.itemedit.close();
					});
			} else {
				this.itemedit.close();
			}
		} else {
			var c = this.itemedit.serializeForm('yes');
			c += '&market_id=' + this.seller.market_id;
			c += '&seller_id=' + this.itemedit.seller_id;
			M.api.postJSONCb('ciniki.marketplaces.marketItemAdd', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_marketplaces_main.itemedit.close();
				});
		}
	};

	this.itemDelete = function() {
		if( confirm("Are you sure you want to remove this item?") ) {
			var rsp = M.api.getJSONCb('ciniki.marketplaces.marketItemDelete', 
				{'business_id':M.curBusinessID, 'item_id':M.ciniki_marketplaces_main.item.item_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_marketplaces_main.item.close();
				});
		}
	}
};

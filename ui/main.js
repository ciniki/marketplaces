//
// This app will handle the listing, additions and deletions of markets.  These are associated tenant.
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
    //
    // markets panel
    //
    this.menu = new M.panel('Market Places', 'ciniki_marketplaces_main', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.marketplaces.main.menu');
    this.menu.sections = {
        'markets':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'headerValues':['Date', 'Market'],
            'cellClasses':['multiline', ''],
            'sortable':'yes',
            'sortTypes':['date', 'text'],
            'addTxt':'Add Market',
            'addFn':'M.ciniki_marketplaces_main.marketedit.open(\'M.ciniki_marketplaces_main.menu.open();\',0);',
            },
        };
    this.menu.sectionData = function(s) { return this.data[s]; }
    this.menu.noData = function(s) { return this.sections[s].noData; }
    this.menu.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return '<span class="maintext">' + d.start_date + '</span><span class="subtext">' + d.end_date + '</span>';
            case 1: return d.name;
        }
    };
    this.menu.rowFn = function(s, i, d) {
        return 'M.ciniki_marketplaces_main.market.open(\'M.ciniki_marketplaces_main.menu.open();\',\'' + d.id + '\');';
    };
    this.menu.open = function(cb) {
        this.data = {};
        M.api.getJSONCb('ciniki.marketplaces.marketList', {'tnid':M.curTenantID}, function(rsp) {
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
    this.menu.addButton('add', 'Add', 'M.ciniki_marketplaces_main.marketedit.open(\'M.ciniki_marketplaces_main.menu.open();\',0);');
    this.menu.addClose('Back');

    //
    // The market panel 
    //
    this.market = new M.panel('Market', 'ciniki_marketplaces_main', 'market', 'mc', 'large narrowaside', 'sectioned', 'ciniki.marketplaces.main.market');
    this.market.data = {};
    this.market.market_id = 0;
    this.market.sections = {
        'info':{'label':'', 'aside':'yes', 'list':{
            'name':{'label':'Name'},
            'dates':{'label':'dates'},
            }},
        'menu':{'label':'', 'aside':'yes', 'list':{
            'inventoryexcel':{'label':'Download Inventory (Excel)', 'fn':'M.ciniki_marketplaces_main.market.downloadInventory(M.ciniki_marketplaces_main.market.market_id, \'excel\');'},
            'pricelistpdf':{'label':'Download Price List (PDF)', 'fn':'M.ciniki_marketplaces_main.market.downloadPriceList(M.ciniki_marketplaces_main.market.market_id, \'pdf\');'},
            'sellers':{'label':'Seller Reports (PDF)', 'fn':'M.ciniki_marketplaces_main.market.downloadSellerSummaryy(M.ciniki_marketplaces_main.market.market_id,\'pdf\',0);'},
            }},
        'sellers':{'label':'', 'type':'simplegrid', 'num_cols':6,
            'headerValues':['Seller', 'Status', '#', 'Value', 'Fees', 'Net'],
            'cellClasses':['', ''],
            'sortable':'yes', 'sortTypes':['text', 'text', 'number', 'altnumber', 'altnumber', 'altnumber'],
            'noData':'No prices',
            'addTxt':'Add Seller',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_marketplaces_main.market.open();\',\'mc\',{\'next\':\'M.ciniki_marketplaces_main.sellerAdd\',\'customer_id\':0});',
        },
        '_buttons':{'label':'', 'buttons':{
//'edit':{'label':'Edit', 'fn':'M.ciniki_marketplaces_main.marketedit.open(\'M.ciniki_marketplaces_main.market.open();\',M.ciniki_marketplaces_main.market.market_id);'},
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
                case 2: return d.seller.num_items;
                case 3: return d.seller.total_price;
                case 4: return d.seller.total_tenant_fee;
                case 5: return d.seller.total_seller_amount;
            }
        }
    };
    this.market.cellSortValue = function(s, i, j, d) {
        if( s == 'sellers' ) {
            switch(j) {
                case 0: return d.seller.display_name;
                case 1: return d.seller.status_text;
                case 2: return d.seller.num_items;
                case 3: return d.seller.total_price.replace(/\$/, '');
                case 4: return d.seller.total_tenant_fee.replace(/\$/, '');
                case 5: return d.seller.total_seller_amount.replace(/\$/, '');
            }
        }
    };
    this.market.footerValue = function(s, i, d) {
        if( this.data.totals == null ) {
            return null;
        }
        switch(i) {
            case 0: return '';
            case 1: return '';
            case 2: return this.data.totals.items;
            case 3: return this.data.totals.value;
            case 4: return this.data.totals.fees;
            case 5: return this.data.totals.net;
        }
    }
    this.market.rowFn = function(s, i, d) {
        return 'M.ciniki_marketplaces_main.seller.open(\'M.ciniki_marketplaces_main.market.open();\',\'' + d.seller.id + '\');';
    };
    this.market.open = function(cb, mid) {
        this.reset();
        if( mid != null ) { this.market_id = mid; }
        var rsp = M.api.getJSONCb('ciniki.marketplaces.marketGet', {'tnid':M.curTenantID, 
            'market_id':this.market_id, 'sellers':'summary'}, function(rsp) {
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
    this.market.downloadInventory = function(mid, format) {
        var args = {'tnid':M.curTenantID, 'market_id':mid, 'output':format};
        M.api.openPDF('ciniki.marketplaces.marketInventory', args);
    };

    this.market.downloadPriceList = function(mid, format) {
        var args = {'tnid':M.curTenantID, 'market_id':mid, 'output':format};
        M.api.openPDF('ciniki.marketplaces.marketPriceList', args);
    };
    this.market.downloadSellerSummaryy = function(mid, format, sid) {
        var args = {'tnid':M.curTenantID, 'market_id':mid, 'output':format};
        if( sid != null && sid > 0 ) {
            args.seller_id = sid;
        }
        M.api.openPDF('ciniki.marketplaces.marketSellerSummary', args);
    };
    this.market.addButton('add', 'Seller', 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_marketplaces_main.market.open();\',\'mc\',{\'next\':\'M.ciniki_marketplaces_main.sellerAdd\',\'customer_id\':0});');
    this.market.addButton('edit', 'Edit', 'M.ciniki_marketplaces_main.marketedit.open(\'M.ciniki_marketplaces_main.market.open();\',M.ciniki_marketplaces_main.market.market_id);');
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
            'save':{'label':'Save', 'fn':'M.ciniki_marketplaces_main.marketedit.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_marketplaces_main.marketedit.remove();'},
            }},
        };  
    this.marketedit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.marketedit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.marketplaces.marketHistory', 'args':{'tnid':M.curTenantID, 
            'market_id':this.market_id, 'field':i}};
    }
    this.marketedit.open = function(cb, mid) {
        this.reset();
        if( mid != null ) { this.market_id = mid; }
        this.sections._buttons.buttons.delete.visible = 'no';

        if( this.market_id > 0 ) {
            M.api.getJSONCb('ciniki.marketplaces.marketGet', {'tnid':M.curTenantID, 
                'market_id':this.market_id}, function(rsp) {
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
            this.data = {'status':'10'};
            this.show(cb);
        }
    };
    this.marketedit.save = function() {
        if( this.market_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.marketplaces.marketUpdate', {'tnid':M.curTenantID, 'market_id':this.market_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_marketplaces_main.marketedit.close();
                });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.marketplaces.marketAdd', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    if( rsp.id > 0 ) {
                        var cb = M.ciniki_marketplaces_main.marketedit.cb;
                        M.ciniki_marketplaces_main.marketedit.destroy();
                        M.ciniki_marketplaces_main.market.open(cb,rsp.id);
                    } else {
                        M.ciniki_marketplaces_main.marketedit.close();
                    }
                });
        }
    };
    this.marketedit.remove = function() {
        if( confirm("Are you sure you want to remove this market, it's sellers and all items and sales data?") ) {
            M.api.getJSONCb('ciniki.marketplaces.marketDelete', {'tnid':M.curTenantID, 'market_id':this.market_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_marketplaces_main.market.close();
            });
        }
    }
    this.marketedit.addButton('save', 'Save', 'M.ciniki_marketplaces_main.marketedit.save();');
    this.marketedit.addClose('Cancel');

    //
    // The panel to display seller info and items
    //
    this.seller = new M.panel('Seller',
        'ciniki_marketplaces_main', 'seller',
        'mc', 'large narrowaside', 'sectioned', 'ciniki.marketplaces.main.seller');
    this.seller.data = {};
    this.seller.market_id = 0;
    this.seller.seller_id = 0;
    this.seller.sections = {
        'customer_details':{'label':'Seller', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label',''],
            },
        'info':{'label':'', 'aside':'yes', 'list':{
            'status_text':{'label':'Status'},
            'flags_text':{'label':'Options'},
            'num_items':{'label':'# Items'},
            }},
        'reports':{'label':'', 'aside':'yes', 'list':{
            'summarypdf':{'label':'Summary (PDF)', 'fn':'M.ciniki_marketplaces_main.market.downloadSellerSummaryy(M.ciniki_marketplaces_main.market.market_id,\'pdf\',M.ciniki_marketplaces_main.seller.seller_id);'},
            }},
        'items':{'label':'Items', 'type':'simplegrid', 'num_cols':7,
            'headerValues':['Code', 'Item', 'Price', 'Fee%', 'Sell Date', 'Fees', 'Sell Price'],
            'cellClasses':['', ''],
            'sortable':'yes', 'sortTypes':['text', 'text', 'altnumber', 'altnumber', 'altnumber'],
            'noData':'No items',
            'addTxt':'Add Item',
            'addFn':'M.ciniki_marketplaces_main.itemedit.open(\'M.ciniki_marketplaces_main.seller.open();\',0,M.ciniki_marketplaces_main.seller.seller_id);',
        },
        '_buttons':{'label':'', 'buttons':{
//              'add':{'label':'Add Item', 'fn':'M.ciniki_marketplaces_main.itemedit.open(\'M.ciniki_marketplaces_main.seller.open();\',0,M.ciniki_marketplaces_main.seller.seller_id);'},
            'edit':{'label':'Edit Seller', 'fn':'M.ciniki_marketplaces_main.selleredit.open(\'M.ciniki_marketplaces_main.seller.open();\',M.ciniki_marketplaces_main.seller.seller_id,M.ciniki_marketplaces_main.seller.market_id,0);'},
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
                case 5: return d.item.tenant_fee;
                case 6: return d.item.seller_amount;
            }
        }
    };
    this.seller.cellSortValue = function(s, i, j, d) {
        if( s == 'items' ) {
            switch(j) {
                case 0: return d.item.code;
                case 2: return d.item.price.replace(/\$/, '');
                case 5: return d.item.tenant_fee.replace(/\$/, '');
                case 6: return d.item.seller_amount.replace(/\$/, '');
            }
        }
    };
    this.seller.rowFn = function(s, i, d) {
        if( s == 'items' ) {
            return 'M.ciniki_marketplaces_main.itemedit.open(\'M.ciniki_marketplaces_main.seller.open();\',\'' + d.item.id + '\',0);';
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
                case 5: return this.data.item_totals.tenant_fee;
                case 6: return this.data.item_totals.seller_amount;
            }
        }
    };
    this.seller.open = function(cb, sid) {
        this.reset();
        if( sid != null ) { this.seller_id = sid; }
        var rsp = M.api.getJSONCb('ciniki.marketplaces.marketSellerGet', {'tnid':M.curTenantID, 
            'seller_id':this.seller_id, 'items':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_marketplaces_main.seller;
                p.data = rsp.seller;
                p.market_id = rsp.seller.market_id;
                p.refresh();
                p.show(cb);
            });
    };
    this.seller.addButton('add', 'Item', 'M.ciniki_marketplaces_main.itemedit.open(\'M.ciniki_marketplaces_main.seller.open();\',0,M.ciniki_marketplaces_main.seller.seller_id);');
    this.seller.addButton('edit', 'Edit', 'M.ciniki_marketplaces_main.selleredit.open(\'M.ciniki_marketplaces_main.seller.open();\',M.ciniki_marketplaces_main.seller.seller_id,M.ciniki_marketplaces_main.seller.market_id,0);');
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
            'num_items':{'label':'# Items', 'hint':'', 'type':'text', 'size':'small'},
            }}, 
        '_notes':{'label':'Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_marketplaces_main.selleredit.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_marketplaces_main.selleredit.remove();'},
            }},
        };  
    this.selleredit.sectionData = function(s) { return this.data[s]; }
    this.selleredit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.selleredit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.marketplaces.marketSellerHistory', 'args':{'tnid':M.curTenantID, 
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
            M.api.getJSONCb('ciniki.customers.customerDetails', {'tnid':M.curTenantID, 'customer_id':this.customer_id, 
                'phones':'yes', 'emails':'yes', 'addresses':'yes'}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_marketplaces_main.selleredit;
                    p.data.customer_details = rsp.details;
                    p.refreshSection('customer_details');
                    p.show();
                });
        }
    };
    this.selleredit.open = function(cb, sid, mid, cid) {
        this.reset();
        if( sid != null ) { this.seller_id = sid; }
        if( mid != null ) { this.market_id = mid; }
        if( cid != null ) { this.customer_id = cid; }
        this.sections._buttons.buttons.delete.visible = 'no';

        if( this.seller_id > 0 ) {
            this.sections._buttons.buttons.delete.visible = 'yes';
            this.sections.customer_details.addTxt = 'Edit';
            this.sections.customer_details.changeTxt = 'Change Seller';
            M.api.getJSONCb('ciniki.marketplaces.marketSellerGet', {'tnid':M.curTenantID, 
                'seller_id':this.seller_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_marketplaces_main.selleredit;
                    p.data = rsp.seller;
                    p.customer_id = rsp.seller.customer_id;
                    p.refresh();
                    p.show(cb);
                });
        } else if( this.customer_id > 0 ) {
            M.api.getJSONCb('ciniki.customers.customerDetails', {'tnid':M.curTenantID,
                'customer_id':this.customer_id}, function(rsp) {
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
    this.selleredit.save = function() {
        if( this.seller_id > 0 ) {
            var c = this.serializeForm('no');
            if( this.customer_id != this.data.customer_id ) {
                c += '&customer_id=' + this.customer_id;
            }
            if( c != '' ) {
                M.api.postJSONCb('ciniki.marketplaces.marketSellerUpdate', {'tnid':M.curTenantID, 
                    'seller_id':this.seller_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        if( rsp.new_seller_id != null && rsp.new_seller_id > 0 ) {
                            M.ciniki_marketplaces_main.seller.seller_id = rsp.new_seller_id;
                        } 
                        M.ciniki_marketplaces_main.selleredit.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            c += '&market_id=' + this.market_id;
            c += '&customer_id=' + this.customer_id;
            M.api.postJSONCb('ciniki.marketplaces.marketSellerAdd', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    if( rsp.id > 0 ) {
                        var cb = M.ciniki_marketplaces_main.selleredit.cb;
                        M.ciniki_marketplaces_main.selleredit.destroy();
                        M.ciniki_marketplaces_main.seller.open(cb,rsp.id);
                    } else {
                        M.ciniki_marketplaces_main.selleredit.close();
                    }
                });
        }
    };
    this.selleredit.remove = function() {
        if( confirm("Are you sure you want to remove this seller, it's sellers and all items and sales data?") ) {
            M.api.getJSONCb('ciniki.marketplaces.marketSellerDelete', 
                {'tnid':M.curTenantID, 'seller_id':M.ciniki_marketplaces_main.seller.seller_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_marketplaces_main.seller.close();
                });
        }
    }
    this.selleredit.addButton('save', 'Save', 'M.ciniki_marketplaces_main.selleredit.save();');
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
            'tenant_fee':{'label':'Tenant Fee', 'hint':'', 'type':'text', 'size':'small'},
            'seller_amount':{'label':'Seller Amount', 'hint':'', 'type':'text', 'size':'small'},
            }}, 
        '_notes':{'label':'Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_marketplaces_main.itemedit.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_marketplaces_main.itemedit.remove();'},
            }},
        };  
    this.itemedit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.itemedit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.marketplaces.marketItemHistory', 'args':{'tnid':M.curTenantID, 
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
        var tenant_fee = this.formFieldValue(this.sections[s].fields['tenant_fee'], 'tenant_fee');
        var seller_amount = this.formFieldValue(this.sections[s].fields['seller_amount'], 'seller_amount');
        var sp = parseFloat(sell_price.replace(/\$/,''));
        bf = 0;
        if( fee_percent > 0 ) {
            bf = (sp * (fee_percent/100));
            bf = bf.toFixed(2);
        } 
        this.setFieldValue('tenant_fee', '$' + bf);
        this.setFieldValue('seller_amount', '$' + (sp - bf).toFixed(2));
    };
    this.itemedit.open = function(cb, iid, sid) {
        this.reset();
        if( iid != null ) { this.item_id = iid; }
        if( sid != null ) { this.seller_id = sid; }
        this.sections._buttons.buttons.delete.visible = 'no';
        if( this.item_id > 0 ) {
            this.sections._buttons.buttons.delete.visible = 'yes';
            M.api.getJSONCb('ciniki.marketplaces.marketItemGet', {'tnid':M.curTenantID, 'item_id':this.item_id}, function(rsp) {
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
            this.data = {};
            this.show(cb);
        }
    };
    this.itemedit.save = function() {
        if( this.item_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.marketplaces.marketItemUpdate', {'tnid':M.curTenantID, 'item_id':this.item_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_marketplaces_main.itemedit.close();
                });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            c += '&market_id=' + M.ciniki_marketplaces_main.seller.market_id;
            c += '&seller_id=' + this.seller_id;
            M.api.postJSONCb('ciniki.marketplaces.marketItemAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_marketplaces_main.itemedit.close();
            });
        }
    };
    this.itemedit.remove = function() {
        if( confirm("Are you sure you want to remove this item?") ) {
            M.api.getJSONCb('ciniki.marketplaces.marketItemDelete', 
                {'tnid':M.curTenantID, 'item_id':M.ciniki_marketplaces_main.itemedit.item_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_marketplaces_main.itemedit.close();
                });
        }
    }
    this.itemedit.addButton('save', 'Save', 'M.ciniki_marketplaces_main.itemedit.save();');
    this.itemedit.addClose('Cancel');

    //
    // markets panel
    //
    this.tools = new M.panel('Market Place Tools', 'ciniki_marketplaces_main', 'tools', 'mc', 'medium', 'sectioned', 'ciniki.marketplaces.main.tools');
    this.tools.sections = {
        '_':{'label':'Reports', 'list':{
            'marketsummaries':{'label':'Market Summaries', 'fn':'M.ciniki_marketplaces_main.reportsummaries.open(\'M.ciniki_marketplaces_main.tools.open();\');'},
            }},
    };
    this.tools.open = function(cb) {
        this.show(cb);
    }
    this.tools.addClose('Back');

    //
    // markets panel
    //
    this.reportsummaries = new M.panel('Market Places', 'ciniki_marketplaces_main', 'reportsummaries', 'mc', 'large', 'sectioned', 'ciniki.marketplaces.main.reportsummaries');
    this.reportsummaries.sections = {
        'years':{'label':'', 'type':'paneltabs', 'selected':'', 'tabs':{
            }},
        'markets':{'label':'', 'type':'simplegrid', 'num_cols':7,
            'headerValues':['Date', 'Market', '# Items', 'Sold', 'Sales', 'Fees', 'Net'],
            'cellClasses':['multiline', '', '', '', '', '', ''],
            'sortable':'yes',
            'sortTypes':['date', 'text', 'number', 'number', 'number', 'number', 'number'],
            },
        };
    this.reportsummaries.sectionData = function(s) { return this.data[s]; }
    this.reportsummaries.noData = function(s) { return this.sections[s].noData; }
    this.reportsummaries.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return '<span class="maintext">' + d.start_date + '</span><span class="subtext">' + d.end_date + '</span>';
            case 1: return d.name;
            case 2: return d.num_items;
            case 3: return d.num_sold;
            case 4: return d.total_value;
            case 5: return d.total_fees;
            case 6: return d.total_net;
        }
    };
    this.reportsummaries.rowFn = function(s, i, d) {
        return 'M.ciniki_marketplaces_main.market.open(\'M.ciniki_marketplaces_main.reportsummaries.open();\',\'' + d.id + '\');';
    };
    this.reportsummaries.footerValue = function(s, i, d) {
        if( this.data.totals == null ) {
            return null;
        }
        switch(i) {
            case 0: return '';
            case 1: return '';
            case 2: return this.data.totals.items;
            case 3: return this.data.totals.sold;
            case 4: return this.data.totals.value;
            case 5: return this.data.totals.fees;
            case 6: return this.data.totals.net;
        }
    }
    this.reportsummaries.open = function(cb, year) {
        this.data = {};
        if( year != null ) { this.sections.years.selected = year; }
        M.api.getJSONCb('ciniki.marketplaces.marketSummaries', {'tnid':M.curTenantID, 'year':this.sections.years.selected}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_marketplaces_main.reportsummaries;
            p.data = rsp;
            p.sections.years.tabs = {};
            p.sections.years.tabs[''] = {'label':'All', 'fn':'M.ciniki_marketplaces_main.reportsummaries.open(null,\'\');'};
            for(var i in rsp.years) {
                p.sections.years.tabs[rsp.years[i]] = {'label':rsp.years[i], 'fn':'M.ciniki_marketplaces_main.reportsummaries.open(null,\'' + rsp.years[i] + '\');'};
            }
            p.refresh();
            p.show(cb);
        });
    };
    this.reportsummaries.addClose('Back');

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

        this.selleredit.sections.general.fields.flags.active = M.modFlagSet('ciniki.marketplaces', 0x01);

        this.menu.rightbuttons = {}; 
        this.menu.addButton('add', 'Add', 'M.ciniki_marketplaces_main.marketedit.open(\'M.ciniki_marketplaces_main.menu.open();\',0);');
        if( (M.userPerms&0x01) > 0 || M.curTenant.permissions.owners == 'yes' ) {
            this.menu.addButton('tools', 'Tools', 'M.ciniki_marketplaces_main.tools.open(\'M.ciniki_marketplaces_main.menu.open();\');');
        }
        this.menu.open(cb);
    }

    //
    // Seller functions
    //
    this.sellerAdd = function(cid) {
        this.selleredit.open('M.ciniki_marketplaces_main.market.open();', 0, M.ciniki_marketplaces_main.market.market_id, cid);       
    };
};

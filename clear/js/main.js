var C = {

    debug: window.location.hash.replace('#','') === 'debug',

    // dom elements
    $wrapper: $('#wrapper'),
    $log: $('#log'),

    // view states
    states: {
        LIST_COLLECTION_VIEW: 'lists',
        TODO_COLLECTION_VIEW: 'todos'
    },

    isEditing: false,

    ITEM_HEIGHT: 62,

    init: function () {

        C.start = Date.now();

        // init some components
        C.client.init();
        C.db.init(C.debug);
        C.touch.init();
        C.listCollection.init();

        // restore state
        var data = C.db.data,
            state = data.state,
            lists = data.items,
            i = lists.length;

        switch (state.view) {

            case C.states.LIST_COLLECTION_VIEW:
                C.log('App: init at ListCollection.');
                C.currentCollection = C.listCollection;
                break;

            case C.states.TODO_COLLECTION_VIEW:
                C.log('App: init at TodoCollection with order: ' + state.order);
                while (i--) {
                    if (lists[i].order === state.order) {
                        C.currentCollection = new C.TodoCollection(lists[i]);
                        break;
                    }
                }
                break;

            default:
                C.log('App: init at ListCollection.');
                C.currentCollection = C.listCollection;
                break;

        }

        C.currentCollection.load(0, true); // passing in (position:0) and (noAnimation:true)

        if (!C.listCollection.initiated) {
            // If we started with a TodoCollection, load ListCollection and position it for pulldown
            C.listCollection.positionForPulldown();
            C.listCollection.load();
        } else {
            // otherwise, load the last used todoCollection
            C.lastTodoCollection = new C.TodoCollection(lists[state.lastTodoCollection || 0]);
            C.lastTodoCollection.load(C.client.height + C.ITEM_HEIGHT, true);
            C.lastTodoCollection.positionForPullUp();
        }

    },

    setCurrentCollection: function (col) {

        var msg = 'Current collection set to: '
        C.log(msg + (col.data.title ? 'TodoCollection <' + col.data.title + '>' : 'ListCollection'));

        C.currentCollection = col;
        var state = C.db.data.state;
        state.view = col.stateType;
        state.order = col.data.order;
        C.db.save();

    },

    setLastTodoCollection: function (col) {

        C.lastTodoCollection = col;
        C.db.data.state.lastTodoCollection = col.data.order;
        C.db.save();

    },

    log: function (msg) {

        if (!this.debug) return;

        //$('#log').text(msg);

        var time = Date.now() - C.start;
        if (time < 1000) {
            time = '[' + time + 'ms] ';
        } else {
            time = '[' + (time / 1000).toFixed(2) + 's] ';
        }
        msg = time + msg;
        console.log(msg);

    },

    raf: window.requestAnimationFrame ||
        window.webkitRequestAnimationFrame ||
        window.mozRequestAnimationFrame ||
        function (callback) {
            window.setTimeout(callback, 16);
        }

};

function jmpToFinishPage() {
    window.location.href = '/clear/finish.html';
}

function ajaxSubmit(e, data) {

    e.el.find('.title').append('<span class="text loading"><img src="img/loading.gif" /></span>');
    var objloading = e.el.find('.loading');
    
    var jsonvalue = JSON.stringify(data);
   
    $.ajax({
        url: 'api.php?action=done',
        type: 'POST',
        data: { value: jsonvalue },
        //dataType: 'json',
        success: function (data) {
            objloading.remove();
            jmpToFinishPage();
        },
        error: function () {
            
            objloading.remove();
            alert('error');

            e.data.done = false;
            e.el.removeClass('green');
        }
    });
}

function checkSubmit(e) {
    var checkdone = true;
    var items = C.db.data.items;
    var donevalue = [];
    // ignore op & how to use
    for (var i = 0; i < items.length - 2; i++) {
        for (var j = 0; j < items[i].items.length; j++) {
            if (!items[i].items[j].done) {
                checkdone = false;
                //break;
            } else {
                donevalue.push(items[i].items[j].value);
            }
        }
    }

    if (!checkdone) {
        if (confirm("并没有进行全部标记，未标记的会被认为是不认识的人，是否确认提交？")) {
            checkdone = true;
        }
    }
    if (checkdone) {
        ajaxSubmit(e, donevalue);
    }

    return checkdone;
}

function resetItems() {
    C.db.clear();
    window.location.reload();
    return true;
}

// boot up on page load
$(function () {
    var howtouseitems = {
        title: "如何使用",
        order: 0,
        noDragRight: true,
        noDragLeft: true,
        items: [{
            title: "标记出认识的人",
            order: 0
        }, {
            title: "大概需要5分钟",
            order: 1
        }, {
            title: "向右划标记为认识",
            order: 2
        }, {
            title: "向左划标记为不认识",
            order: 3
        }, {
            title: "向下划返回上层",
            order: 4
        }, {
            title: "完成后左划提交并查看变化",
            order: 5
        }]
    };
	var opitems = {
		title: "操作",
		order: 7,
        noDragRight: true,
        noDragLeft: true,
		items: [{
			title: "提交",
			order: 0,
			op: true,
			noDragLeft: true,
			opfunc: 'checkSubmit(this)'
		}, {
			title: "重置",
			order: 1,
			op: true,
			noDragLeft: true,
			opfunc: 'resetItems()'
		}]
	};
	$.ajax({
		url: 'api.php?action=get',
		success: function (data) {
			var items = JSON.parse(data);
            if (items && items.length > 0) {
                items.push(opitems);
                items.push(howtouseitems);
            } else {
                jmpToFinishPage();
            }
			C.db.useDefaultData = function () {
				this.data = {
					state: {
						view: C.states.LIST_COLLECTION_VIEW,
						lastTodoCollection: 0
					},
					items: items
				};
			};
			C.init();
		}
	});
});
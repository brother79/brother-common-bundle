/* SVN: $Id: jquery.autotree.js 36 2011-03-13 18:12:16Z brother79 $*/
(function($) {
$.fn.extend({
	autotree: function(urlOrData, options) {
		var isUrl = typeof urlOrData == "string";
		options = $.extend({}, $.autotreer.defaults, {
			url: isUrl ? urlOrData : null,
			data: isUrl ? null : urlOrData,
			delay: isUrl ? $.autotreer.defaults.delay : 10,
			max: options && !options.scroll ? 10 : 150
		}, options);

		// if highlight is set to false, replace it with a do-nothing function
		options.highlight = options.highlight || function(value) { return value; };

		// if the formatMatch option is not specified, then use formatItem for backwards compatibility
		options.formatMatch = options.formatMatch || options.formatItem;
		return this.each(function() {
			new $.autotreer(this, options);
		});
	},
	flushCache: function() {
		return this.trigger("flushCache");
	},
	setOptions: function(options){
		return this.trigger("setOptions", [options]);
	},
	unautotree: function() {
		return this.trigger("unautotree");
	}
});

$.autotreer = function(input, options) {
	var KEY = {
		UP: 38,
		DOWN: 40,
		DEL: 46,
		TAB: 9,
		RETURN: 13,
		ESC: 27,
		COMMA: 188,
		PAGEUP: 33,
		PAGEDOWN: 34,
		BACKSPACE: 8
	};

	// Create $ object for input element
	var $input = $(input).attr("autotree", "off").addClass(options.inputClass);

	var timeout;
	var previousValue = "";
	var previousCode = "";
	var cache = $.autotreer.Cache(options);
	var lastKeyPressCode;
	var select = $.autotreer.Select(options, input, selectCurrent);

	var blockSubmit;

	// prevent form submit in opera when selecting with return key
	$.browser.opera && $(input.form).bind("submit.autotree", function() {
		if (blockSubmit) {
			blockSubmit = false;
			return false;
		}
	});

	// only opera doesn't trigger keydown multiple times while pressed, others don't work with keypress at all
	$input.add(options.tree).add(options.code).bind(($.browser.opera ? "keypress" : "keydown") + ".autotree", function(event) {
		// track last key pressed
		lastKeyPressCode = event.keyCode;

		lastEditName=(event.target==$input.get(0));
		switch(event.keyCode) {

			case KEY.UP:
				event.preventDefault();
				select.prev();
				break;

			case KEY.DOWN:
				event.preventDefault();
				select.next();
				break;

			case KEY.PAGEUP:
				event.preventDefault();
				select.pageUp();
				break;

			case KEY.PAGEDOWN:
				event.preventDefault();
				select.pageDown();
				break;

			// matches also semicolon
			case KEY.TAB:
			case KEY.RETURN:
				if( selectCurrent() ) {
					// stop default to prevent a form submit, Opera needs special handling
					event.preventDefault();
					blockSubmit = true;
					return false;
				}
				break;

			case KEY.ESC:
				break;

			default:
				clearTimeout(timeout);
				timeout = setTimeout(onChange, options.delay);
				break;
		}
	})
	.bind("flushCache", function() {
		cache.flush();
	}).bind("setOptions", function() {
		$.extend(options, arguments[1]);
	}).bind("unautotree", function() {
		select.unbind();
		$input.unbind();
		$(input.form).unbind(".autotree");
	});


	function selectCurrent() {
		var selected = select.selected();
		if( !selected )
			return false;

		previousCode = selected.attr('id');
		$(options.code).val(selected.attr('id'));
		var v = selected.find('> span').html();
		v = v.replace(/<[\/]?strong>/gi,'');
		previousValue = v;
		$input.val(v);
//		hideResultsNow();
		return true;
	}

	function onChange() {

		if (lastEditName) {

			var currentValue = $input.val();
	
	
			if (currentValue == previousValue )
				return;
	
			previousValue = currentValue;
	
			if ( currentValue.length >= options.minChars) {
				$input.addClass(options.loadingClass);
				if (!options.matchCase)
					currentValue = currentValue.toLowerCase();
				request(currentValue, receiveData, hideResultsNow);
			} else {
				stopLoading();
			}
		} else {
			r=$(options.code).val();
			var currentCode=$(options.code).val();
			if (currentCode == previousCode)
				return;
			previousCode = currentCode;
			$input.addClass(options.loadingClass);
			request(currentCode, receiveData, hideResultsNow);
		}

	};

	function hideResultsNow() {
		clearTimeout(timeout);
		stopLoading();
		// position cursor at end of input field
		//$.autotreer.Selection(input, input.value.length, input.value.length);
		select.emptyList();
	};

	function receiveData(q, data) {
	
		if ( data && data.length) {
			
			stopLoading();
			select.display(data, q);
			select.show();
		} else {
			hideResultsNow();
		}
	};

	function request(term, success, failure) {
		if (!options.matchCase)
			term = term.toLowerCase();
		var data = cache.load(term);
		// recieve the cached data
		if (data && data.length) {
			success(term, data);
		// if an AJAX url has been supplied, try loading the data now
		} else if( (typeof options.url == "string") && (options.url.length > 0) ){
			var extraParams = {
				timestamp: +new Date()
			};
			$.each(options.extraParams, function(key, param) {
				extraParams[key] = typeof param == "function" ? param() : param;
			});
			$.ajax({
				// try to leverage ajaxQueue plugin to abort previous requests
				mode: "abort",
				// limit abortion to this input
				port: "autotree" + input.name,
				dataType: 'json',
				url: options.url,
				data: $.extend({
					q: term,
					limit: options.max
				}, extraParams),
				success: function(data) {
					cache.add(term, data);
					success(term, data);
				}
			});
		} else {
			// if we have a failure, we need to empty the list -- this prevents the the [TAB] key from selecting the last successful match
			select.emptyList();
			failure(term);
		}
	};

	function stopLoading() {
		$input.removeClass(options.loadingClass);
	};

};

$.autotreer.defaults = {
	inputClass: "ac_input",
	loadingClass: "ac_loading",
	minChars: 1,
	delay: 400,
	matchCase: false,
	cacheLength: 10,
	max: 100,
	extraParams: {},
	selectFirst: true,
	formatItem: function(item) {
		
		var r = item.id ? (item.id+' ').split('_').pop() : 0;
		return r+'. <span>'+(item.text?item.text:'')+'</span>';
	},
	formatMatch: null,
	width: 0,
	highlight: function(value, term) {
		return value.replace(new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + term.replace(/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi, "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi"), "<strong>$1</strong>");
	},
    scroll: true,
    scrollHeight: 180
};

$.autotreer.Cache = function(options) {

	var data = {};
	var length = 0;

	function add(q, value) {
		if (length > options.cacheLength){
			flush();
		}
		if (!data[q]){
			length++;
		}
		data[q] = value;
	}

	function flush(){
		data = {};
		length = 0;
	}

	return {
		flush: flush,
		add: add,
		load: function(q) {
			if (!options.cacheLength || !length)
				return null;
			// if the exact item exists, use it
			if (data[q]){
				return data[q];
			}
			return null;
		}
	};
};

$.autotreer.Select = function (options, input, select) {
	var CLASSES = {
		ACTIVE: "hover"
	};

	var listItems,
		active = -1,
		data,
		term = "",
		needsInit = true,
		list;

	// Create results
	function init() {
		if (!needsInit)
			return;

		list = $(options.tree)
		.mouseover( function(event) {
			if(target(event).nodeName && target(event).nodeName.toUpperCase() == 'LI') {
	            active = $("li", list).removeClass(CLASSES.ACTIVE).index(target(event));
			    $(target(event)).addClass(CLASSES.ACTIVE);
	        }
		}).click(function(event) {

			$(target(event)).addClass(CLASSES.ACTIVE);
			select();
			// TODO provide option to avoid setting focus again after selection? useful for cleanup-on-focus
			input.focus();
			return false;
		})
		.treeview();


		if( options.width > 0 )
			list.css("width", options.width);

		needsInit = false;
	}

	function target(event) {
		var element = event.target;
		while(element && element.tagName != "LI")
			element = element.parentNode;
		// more fun with IE, sometimes event.target is empty, just ignore it then
		if(!element)
			return [];
		return element;
	}

	function moveSelect(step) {
		listItems.slice(active, active + 1).removeClass(CLASSES.ACTIVE);
		movePosition(step);
        var activeItem = listItems.slice(active, active + 1).addClass(CLASSES.ACTIVE);
        var h=activeItem.offset().top-list.offset().top;
        if(options.scroll) {
        	if (h<0) {
                list.scrollTop(list.scrollTop()+h);
        	}
        	if (h>list.height()) {
        		list.scrollTop(list.scrollTop()+h - list.height()+40);
        	}
        }
	};

	function movePosition(step) {
		active += step;
		if (active < 0) {
			active = listItems.size() - 1;
		} else if (active >= listItems.size()) {
			active = 0;
		}
	}

	function limitNumberOfItems(available) {
		return options.max && options.max < available
			? options.max
			: available;
	}

	function fillNodes(e, data) {
		
		$.each(data, function(i,le){
			
			var formatted = options.formatItem(le, term);
			var ee = $('<li id="'+le.id+'"/>').html(options.highlight(formatted, term)).appendTo(e);
			if (le.children && (le.children.length > 0 || le.has_children > 0)) {
				fillNodes($('<ul/>').appendTo(ee),le.children);
			}
		});
	}

	function fillList() {
		
		list.empty();
		fillNodes(list,data);
		list.find('> li').each(function(i,e){
			list.treeview({add:e});
		});

		listItems = list.find("li");
		if ( options.selectFirst ) {
			listItems.slice(0, 1).addClass(CLASSES.ACTIVE);
			active = 0;
		}

		// apply bgiframe if available
		if ( $.fn.bgiframe )
			list.bgiframe();
	}

	return {
		display: function(d, q) {
			
			init();
			data = d;
			term = q;
			fillList();
		},
		next: function() {
			moveSelect(1);
		},
		prev: function() {
			moveSelect(-1);
		},
		pageUp: function() {
			if (active != 0 && active - 8 < 0) {
				moveSelect( -active );
			} else {
				moveSelect(-8);
			}
		},
		pageDown: function() {
			if (active != listItems.size() - 1 && active + 8 > listItems.size()) {
				moveSelect( listItems.size() - 1 - active );
			} else {
				moveSelect(8);
			}
		},
		current: function() {
			return this.visible() && (listItems.filter("." + CLASSES.ACTIVE)[0] || options.selectFirst && listItems[0]);
		},
		show: function() {
            if(options.scroll) {
                list.scrollTop(0);
                list.css({
					maxHeight: options.scrollHeight,
					overflow: 'auto'
				});

                if($.browser.msie && typeof document.body.style.maxHeight === "undefined") {
					var listHeight = 0;
					listItems.each(function() {
						listHeight += this.offsetHeight;
					});
					var scrollbarsVisible = listHeight > options.scrollHeight;
                    list.css('height', scrollbarsVisible ? options.scrollHeight : listHeight );
					if (!scrollbarsVisible) {
						// IE doesn't recalculate width when scrollbar disappears
						listItems.width( list.width() - parseInt(listItems.css("padding-left")) - parseInt(listItems.css("padding-right")) );
					}
                }

            }
		},
		selected: function() {
			var selected = listItems && listItems.filter("." + CLASSES.ACTIVE).removeClass(CLASSES.ACTIVE);
			return selected;
		},
		emptyList: function (){
			list && list.empty();
		}
	};
};

$.autotreer.Selection = function(field, start, end) {
	if( field.createTextRange ){
		var selRange = field.createTextRange();
		selRange.collapse(true);
		selRange.moveStart("character", start);
		selRange.moveEnd("character", end);
		selRange.select();
	} else if( field.setSelectionRange ){
		field.setSelectionRange(start, end);
	} else {
		if( field.selectionStart ){
			field.selectionStart = start;
			field.selectionEnd = end;
		}
	}
	field.focus();
};

})(jQuery);
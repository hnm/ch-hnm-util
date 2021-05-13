(function($) {
	"use strict";
	if (!window.n2n) {
		window.n2n = new Object();
	}
	(function(n2n) {
		
		var AutoCompletionElement = function(jqElem, options) {
			this.jqElem = jqElem;
			
			this.availableOptions = new Array();
			this.customValues = new Array();
			this.availableValues = new Array();
			
			this.currentActive = null;
			this.lastFilter = null;
			
			this.options = new Object();
			this.options.showInput = jqElem.data('show-input') || false;
			this.options.source = jqElem.data('source') || null;
			this.options.dynamicLabelUrl = jqElem.data('dynamic-label-url') || null;
			this.options.allowCustom = jqElem.data('allow-custom') || false;
			this.options.maxHeight = jqElem.data('max-height') || 300;
			this.options.textNoResults = jqElem.data('text-no-results') || "No Results";
			this.options.textEmpty = jqElem.data('text-empty') || "Please select";
			this.options.minInputLength = jqElem.data('min-input-length') || 0;
			this.options.highlightText = jqElem.data("highlight-text") || false;
			this.options.filterStyle = jqElem.data("filter-style") || AutoCompletionElement.FILTER_STYLE_CONTENT;
			this.options.selectionType = AutoCompletionElement.SELECTION_TYPE_SINGLE;
			this.options.dynamic = jqElem.data("dynamic") || null !== this.options.dynamicLabelUrl;
			this.options.loadOnStart = jqElem.data("load-on-start") || false;
			this.options.initialValue = jqElem.data("initial-value") || false;
			
			if (jqElem.is("select") && jqElem.prop("multiple")) {
				this.options.selectionType = AutoCompletionElement.SELECTION_TYPE_MULTIPLE;
			}
			
			$.extend(this.options, options);
			
			this.filterFunction = null;
			this.lastHightlightValue = null;
			this.highLightRegExp = null;
			
			this.jqElemDivContainer = null;
			this.jqElemSelectedValues = null;
			this.jqElemDivResultsContainer = null;
			this.jqElemUlResults = null;
			this.jqElemInputFilter = null;
			this.jqElemOptionCustom = null;
			
			this.xhr = null;
			this.initialized = false;
			this.initialize();
			this.initialized = true;
			this.valueSet = false;
			this.hiding = false;
			
			this.lastSetValue = this.determineCurrentValues();
		};
		
		AutoCompletionElement.SELECTION_TYPE_MULTIPLE = "multiple";
		AutoCompletionElement.SELECTION_TYPE_SINGLE = "single";
		
		AutoCompletionElement.FILTER_STYLE_CONTENT = "content";
		AutoCompletionElement.FILTER_STYLE_START = "start"
		
		AutoCompletionElement.prototype.activeClassName = "active";
		AutoCompletionElement.prototype.selectedClassName = "selected";
		AutoCompletionElement.prototype.openClassName = "active";
		AutoCompletionElement.prototype.actionKeyCodes = [40, 38, 13, 27, 9];
		
		AutoCompletionElement.prototype.initialize = function() {
			var _obj = this,
					desiredWidth = this.jqElem.outerWidth(true);
			this.jqElemDivContainer = $("<div/>").addClass("util-jquery-autocomplete-container util-jquery-autocomplete-" + this.options.selectionType).css({
				width: desiredWidth + "px",
				position: "relative"
			}).insertAfter(this.jqElem);
			
			this.jqElemDivResultsContainer = $("<div/>", {"class": "util-jquery-autocomplete-results-container"}).css({
				width: "100%",
				position: "absolute",
				zIndex: 10000
			}).appendTo(this.jqElemDivContainer).hide().click(function(e) {e.stopPropagation()});
			
			this.jqElemUlResults = $("<ul/>", {"class": "util-jquery-autocomplete-results"}).css({
				margin: "0",
				padding: "0",
				width: "100%",
				position: "relative"
			}).appendTo(this.jqElemDivResultsContainer);
			
			if (!this.options.showInput) {
				this.jqElem.hide();
				this.jqElemInputFilter = $("<input/>", {type: "text", "class": "util-jquery-autocomplete-filter"})
				if (this.options.selectionType == AutoCompletionElement.SELECTION_TYPE_MULTIPLE) {
					this.jqElemSelectedValues = $("<ul/>", {"class": "util-jquery-autocomplete-choices"}).css({
						margin: "0",
						padding: "0",
						width: "100%"
					}).prependTo(this.jqElemDivContainer).click(function() {
						_obj.show();
						return false;
					});
					this.jqElemSelectedValues.append($("<li/>").css({
						display: "inline"
					}).append(this.jqElemInputFilter));
				} else {
					this.jqElemSelectedValues = $("<span/>", {"class": "util-jquery-autocomplete-choice", text: this.options.textEmpty})
							.prependTo($("<a/>", {"class": "util-jquery-autocomplete-single-value", "href": "#"}).focus(function() {
								_obj.show();
							}).append($("<span/>", {"class": "util-jquery-autocomplete-opener"})).click(function() {
								_obj.show();
								return false;
							}).mouseup(function() {
								_obj.jqElemInputFilter.focus();
							}).appendTo($("<div/>").prependTo(this.jqElemDivContainer)));
					this.jqElemInputFilter.appendTo($("<div/>").prependTo(this.jqElemDivResultsContainer)).css({
						width: "100%"
					});
				}
			} else {
				if (this.jqElem.is("select")) {
					this.jqElemInputFilter = $("<input/>", {"class": "form-control util-jquery-autocomplete-input-filter"})
							.addClass(this.jqElem.attr("class")).attr("placeholder", this.jqElem.attr("placeholder"));
				} else {
					this.jqElemInputFilter = this.jqElem.clone(true, true)
							.removeAttr("name").val('').addClass("util-jquery-autocomplete-input-filter");
					this.jqElem.removeAttr("id");
				}
				this.jqElemInputFilter.on('keydown.shown', function(e) {
					if (_obj.isOpen()) return;
					var keyCode = (window.event) ? e.keyCode : e.which;
					//don't open the resultlist on TAB or ENTER
					if (keyCode == 9 || keyCode == 13) return;
					_obj.show();
				})
				this.jqElem.hide().before(this.jqElemInputFilter);
			}
			
			this.jqElemInputFilter.focus(function(e) {
				e.stopPropagation();
				_obj.show();
			}).click(function(e) {
				e.stopPropagation();
			}).attr("autocomplete", "off");
			
			this.adjustResultsContainer();
			this.applyAvailableOptions();
			this.initializeValue();

			if (this.options.loadOnStart && this.isDynamic()) {
				this.fillCurrentSuggestions('');
			}
		};
		
		AutoCompletionElement.prototype.initializeValue = function() {
			var value = this.determineCurrentValues(), 
					singleValue, _obj = this;
			
			if (value.length > 0)  {
				if (this.options.selectionType == AutoCompletionElement.SELECTION_TYPE_MULTIPLE ) {
					for (var i in value) {
						this.setValue(value[i]);
					}
				} else {
					singleValue = value.pop();
					if (!singleValue) return;
					if (this.isDynamic() && !this.options.initialValue) {
						if (null !== this.options.dynamicLabelUrl) {
							$.ajax({
								"type": "GET",
								"url": this.options.dynamicLabelUrl + "/" + encodeURI(singleValue),
								"dataType": "json",
								"contentType": "application/json; charset=utf-8",
								"success": function(data) {
									if (data) {
										var options = {};
										options[singleValue] = data;
										_obj.setAvailableOptions(options);
									}
									_obj.lastFilter = singleValue;
									_obj.setValue(singleValue);
								},"error": function() {
									_obj.lastFilter = singleValue;
									_obj.setValue(singleValue);
								}
							});
						}
					} else {
						_obj.lastFilter = singleValue;
						this.setValue(singleValue);
					}
				}
			}
		};
		
		AutoCompletionElement.prototype.applyFilterFunction = function(preparedFilter) {
			var _obj = this;
			if (this.options.filterStyle === AutoCompletionElement.FILTER_STYLE_CONTENT) { 
				this.filterFunction = function(choice) {
					if (null == choice) return false;
					choice = _obj.prepareForComperation(choice);
					if (choice.indexOf(preparedFilter) != -1) return true;
					return false;
				}
			} else {
				this.filterFunction = function(choice) {
					if (null == choice) return false;
					choice = _obj.prepareForComperation(choice);
					if (choice.startsWith(preparedFilter)) return true;
					return false;
				}
			}
		}
		
		AutoCompletionElement.prototype.adjustResultsContainer = function() {
			if (!this.options.showInput) {
				this.jqElemDivResultsContainer.css({
					top: this.jqElemSelectedValues.outerHeight(true) + "px"
				});
				this.jqElemDivContainer.css({
					width: this.jqElem.outerWidth(true)
				});
			} else {
				this.jqElemDivContainer.css({
					width: this.jqElemInputFilter.outerWidth(true)
				});
			} 
		}
		
		AutoCompletionElement.prototype.isDynamic = function() {
			if (this.options.dynamic) return true;
			return null !== this.options.source && !($.isArray(this.options.source) 
					|| $.isPlainObject(this.options.source)) && this.options.source.startsWith("http");
		}
		
		AutoCompletionElement.prototype.setAvailableOptions = function(options) {
			this.availableOptions = new Array();
			this.availableValues = new Array();
			if ($.isArray(options)) {
				for (var i in options) {
					if (this.availableOptions.indexOf(options[i]) !== -1) continue;
					this.availableOptions.push(options[i]);
					this.availableValues.push(options[i]);
				}
			} else if ($.isPlainObject(options)) {
				for (var i in options) {
					if (this.availableOptions.indexOf(options[i]) !== -1) continue;
					this.availableOptions.push(options[i])
					this.availableValues.push(i);
				}
			}
		};
		 
		AutoCompletionElement.prototype.applyAvailableOptions = function() {
			var _obj = this;
			if (null === this.options.source) {
				if (this.jqElem.is("select")) {
					this.jqElem.children("option").each(function() {
						var jqElem = $(this);
						var text = jqElem.text();
						if (_obj.availableOptions.indexOf(text) !== -1) return;
						_obj.availableOptions.push(text);
						_obj.availableValues.push(jqElem.val());
					});
				}
			} else {
				if ($.isArray(this.options.source) || $.isPlainObject(this.options.source)) {
					this.setAvailableOptions(this.options.source);
				} else if (this.options.initialValue) {
					var options = {};
					options[this.jqElem.val()] = this.options.initialValue; 
					this.setAvailableOptions(options);
				}
			}
		};
		
		AutoCompletionElement.prototype.determineJqElemOptionWithValue = function(value) {
			if (!this.jqElem.is("select")) return null;
			var jqElemOption = null;
			this.jqElem.children("option").each(function() {
				if (null !== jqElemOption) return;
				if (value === $(this).val()) {
					jqElemOption = $(this);
					return;
				}
			});
			return jqElemOption;
		};
		
		AutoCompletionElement.prototype.getOptionForValue = function(value) {
			var option = this.availableOptions[this.availableValues.indexOf(value)];
			if (null == option && this.customValues.indexOf(value) !== -1) {
				option = value;
			}
			return option;
		};
		
		AutoCompletionElement.prototype.getValueForOption = function(option) {
			return this.availableValues[this.availableOptions.indexOf(option)];
		};
		
		AutoCompletionElement.prototype.setValue = function(value) {
			if (!value && !this.options.showInput) {
				this.jqElemSelectedValues.text(this.options.textEmpty);
				return;
			}
			this.valueSet = true;
			var _obj = this;
			if ((this.options.allowCustom || this.options.source !== null)) {
				if (null == this.getOptionForValue(value)) {
					this.customValues.push(value);
				}
				if (this.jqElem.is("select") && null === this.determineJqElemOptionWithValue(value)) {
					this.jqElem.append($("<option/>", {value: value, text: value}));
				}
			}
			
			if (this.options.selectionType == AutoCompletionElement.SELECTION_TYPE_MULTIPLE) {
				if (this.initialized && this.determineCurrentValues().indexOf(value) !== -1) return;
				this.jqElemInputFilter.parent("li").before($("<li/>", {"class": "util-jquery-autocomplete-choice"}).css({
					display: "inline"
				}).append($("<span/>", {text: _obj.getOptionForValue(value), value:value})).append($("<a/>", {
					"class": "util-jquery-autocomplete-choice-remove", 
					"href": "#"
				}).click(function(){
					$(this).parent("li.util-jquery-autocomplete-choice").remove();
					_obj.determineJqElemOptionWithValue(value).prop("selected", false);
				})));
				this.determineJqElemOptionWithValue(value).prop("selected", true);
				if (this.isOpen()) {
					this.fillCurrentSuggestions(this.lastFilter);
					this.jqElemInputFilter.val('');
				}
			} else {
				if (!this.options.showInput) {
					this.jqElemSelectedValues.text(this.getOptionForValue(value));
				} else {
					this.lastFilter = this.getOptionForValue(value);
					this.jqElemInputFilter.val(this.lastFilter);
				}
				this.jqElem.val(value);
				if (this.isOpen()) {
					this.hide();
				}
			}
			this.lastSetValue = this.determineCurrentValues();
		};
		
		AutoCompletionElement.prototype.isOpen = function() {
			return this.jqElemDivResultsContainer.is(":visible");
		};
		
		AutoCompletionElement.prototype.show = function() {
			
			if (this.isOpen()) return;
			var _obj = this;
			this.valueSet = false; 
			if (!this.options.showInput) {
				this.jqElemInputFilter.val('');
			} 
			this.checkResultsBoundaries();
			this.fillCurrentSuggestions(this.jqElemInputFilter.val());
			this.adjustResultsContainer();
			this.jqElemDivResultsContainer.slideDown(200);
			this.jqElemInputFilter.focus();
			
			$(window).trigger('autoCompletion.otherOpens', [_obj.jqElem]);
			
			$(window).on('click.autoCompletion', function() {
				_obj.hide();
			}).on('autoCompletion.otherOpens', function(e, jqElem) {
				if (_obj.jqElem.is(jqElem)) return;
				_obj.hide();
			});
			
			this.jqElemInputFilter.on('keydown.autoCompletion', function(e) {
				if (!_obj.isOpen()) return;
				var keyCode = (window.event) ? e.keyCode : e.which;
				if (_obj.actionKeyCodes.indexOf(keyCode) != -1) {
						switch (keyCode) {
						case 40:
							e.preventDefault();
							//arrow down
							if (!_obj.isOpen()) {
								_obj.show();
							} else {
								_obj.activateNext();
							}
							break;
						case 38:
							e.preventDefault();
							//arrow up
							if (!_obj.isOpen()) {
								_obj.show();
							}
							if (_obj.isOpen()) {
								_obj.activatePrevious();
							}
							break;
						case 9:
							//tabulator
							_obj.hide();
							break;
						case 13:
							//enter
							if (null != _obj.currentActive && _obj.currentActive.length != 0) {
								e.preventDefault();
								e.stopPropagation();
								_obj.setValue(_obj.currentActive.data("value"));
							} else {
								if (_obj.options.showInput) {
									return;
								}
								e.preventDefault();
								e.stopPropagation();
								if (_obj.options.allowCustom) {
									e.preventDefault();
									e.stopPropagation();
									_obj.setValue(_obj.lastFilter);
								}
							}
							break;
					}
				}
			}).on('keyup.autoCompletion', function(e) {
				var keyCode = (window.event) ? e.keyCode : e.which;
				if (_obj.actionKeyCodes.indexOf(keyCode) != -1) return;
				var filter = $(this).val();
				if (_obj.lastFilter != filter) {
					_obj.fillCurrentSuggestions(filter);
				}
				_obj.lastFilter = filter;
			}).on('blur.autoCompletion', function() {
				if (_obj.options.allowCustom && !_obj.valueSet) {
					_obj.setValue(_obj.lastFilter);
				}
			});
		}
		
		AutoCompletionElement.prototype.hide = function() {
			if (!this.isOpen() || this.hiding) return;
			this.hiding = true;
			var _obj = this;

			if (!this.options.allowCustom && this.options.showInput) {
				if (null != this.currentActive && this.currentActive.length != 0) {
					this.setValue(this.currentActive.data("value"));
				} else {
					this.setValue('');
				}
			} else if (this.jqElemUlResults.children().length === 1 
					&& !this.valueSet) {
				this.setValue(this.currentActive.data("value"));
			}
			
			this.jqElemDivResultsContainer.slideUp(200, function() {
				
				_obj.jqElemUlResults.empty();
				_obj.jqElemUlResults.css({
					height: "",
					overflowY: ""
				});
				_obj.currentActive = null;
				_obj.hiding = false;
			});

			$(window).off('click.autoCompletion').off('autoCompletion.otherOpens');
			this.jqElemInputFilter.off('keydown.autoCompletion').off('keyup.autoCompletion').off('blur.autoCompletion');
			
		};

		AutoCompletionElement.prototype.fillCurrentSuggestions = function(filter) {
			var _obj = this;
			filter = filter || '';
			if (!this.options.loadOnStart && 
					(filter.length < this.options.minInputLength)) {
				this.jqElemUlResults.empty();
				return;
			}
			this.currentActive = null;
			var preparedFilter = this.prepareForComperation(filter);
			this.applyFilterFunction(preparedFilter);
			if (this.isDynamic()) {
				if (null !== this.xhr) {
					if (this.options.loadOnStart) return;
					this.xhr.abort();
				}
				this.xhr = $.ajax({
					"type": "GET",
					"url": this.options.source + "/" + encodeURI(filter),
					"dataType": "json",
					"contentType": "application/json; charset=utf-8",
					"success": function(data) {
						_obj.xhr = null;
						if (!_obj.options.loadOnStart && !_obj.isOpen()) return;
						if (_obj.options.loadOnStart) {
							_obj.options.dynamic = false;
							_obj.options.source = null;
						}
						_obj.setAvailableOptions(data);
						_obj.jqElemUlResults.empty();
						_obj.appendOptions(_obj.availableOptions);
						if (_obj.options.loadOnStart) {
							_obj.initializeValue();
						}
					}
				});
			} else {
				var filteredOptions = this.availableOptions;
				if (filter.length > 0) {
					var filteredOptions = this.availableOptions.filter(this.filterFunction);
				}
				this.jqElemUlResults.empty();
				this.appendOptions(filteredOptions)
			}
		};
		
		AutoCompletionElement.prototype.highlightOccurances = function(text) {
			if (false === this.options.highlightText || !this.lastFilter) return text;
			if (this.lastHightlightValue != this.lastFilter) {
				this.highLightRegExp = new RegExp("(" + this.lastFilter + ")", "gi");
				this.lastHightlightValue = this.lastFilter;
			}
			return text.replace(this.highLightRegExp, "<mark>$1</mark>");
		};
		
		AutoCompletionElement.prototype.appendOptions = function(options) {
			var _obj = this;
			if (options.length !== 0) {
				
				var currentValues = this.determineCurrentValues() || [];
				for (var i in currentValues) {
					currentValues[i] = this.prepareForComperation(currentValues[i]);
				}
				for (var i in options) {
					var currentClassName = '';
					var filteredOption = options[i];
					var value = this.getValueForOption(filteredOption);
					if (currentValues.indexOf(this.prepareForComperation(value)) != -1) {
						currentClassName = ' ' + this.selectedClassName;
					}
					$("<li/>", {html: _obj.highlightOccurances(filteredOption), "class": "util-jquery-autocomplete-choice" + currentClassName}).hover(function() {
						_obj.activate($(this), false);
					}).click(function() {
						_obj.setValue($(this).data("value"));
						return false;
					}).appendTo(this.jqElemUlResults).data('value', value);
				};
				this.activateNext();
			} else {
				if (!this.options.allowCustom) {
					this.jqElemUlResults.append($("<li/>", {"class": "util-jquery-autocomplete-no-choice", text: this.options.textNoResults}));
				}
			}
			this.checkMaxHeight();
		};
		
		AutoCompletionElement.prototype.prepareForComperation = function(value) {
			if (null == value) return value;
			return value.replace(/^\s+|\s+$/g, "").toLowerCase();
		};
		
		AutoCompletionElement.prototype.getMaxHeight = function() {
			var maxPossibleHeight = 0, jqElemWindow = $(window), 
				bottomElemPosition = 0;
			if (!this.options.showInput) {
				bottomElemPosition = this.jqElemSelectedValues.offset().top 
						+ this.jqElemSelectedValues.outerHeight(true);
			} else {
				bottomElemPosition = (this.jqElemInputFilter.offset().top + 
								this.jqElemInputFilter.outerHeight(true));
			}
			maxPossibleHeight = jqElemWindow.scrollTop() + jqElemWindow.height() - bottomElemPosition;
			if (maxPossibleHeight > this.options.maxHeight) {
				return this.options.maxHeight;
			}
			return maxPossibleHeight;
		};
		
		AutoCompletionElement.prototype.checkMaxHeight = function() {
			var maxHeight = this.getMaxHeight();
			this.jqElemUlResults.css({
				height: "",
				overflowY: ""
			});
			if (maxHeight < this.jqElemDivResultsContainer.outerHeight(true)) {
				this.jqElemUlResults.height(maxHeight).css({
					overflowY: "scroll"
				});
			}
		};
		
		AutoCompletionElement.prototype.isScrollable = function() {
			return this.jqElemUlResults.css("overflow-y") == "scroll"; 
		}
		
		AutoCompletionElement.prototype.determineCurrentValues = function() {
			var value = this.jqElem.val();
			if (this.options.selectionType != AutoCompletionElement.SELECTION_TYPE_MULTIPLE) return new Array(value);
			if (null === value) return new Array();
			return value;
		};
		
		AutoCompletionElement.prototype.activatePrevious = function() {
			if (null == this.currentActive) {
				this.activate(this.jqElemUlResults.children(".util-jquery-autocomplete-choice:last"));
			} else {
				var jqElemPrev = this.currentActive.prev();
				if (jqElemPrev.length > 0) {
					this.activate(jqElemPrev);
				} else {
					this.activate(this.jqElemUlResults.children(".util-jquery-autocomplete-choice:last"));
				}
			}
		};
		
		AutoCompletionElement.prototype.activateNext = function() {
			if (null == this.currentActive) {
				this.activate(this.jqElemUlResults.children(".util-jquery-autocomplete-choice:first"));
			} else {
				var jqElemNext = this.currentActive.next();
				if (jqElemNext.length > 0) {
					this.activate(jqElemNext);
				} else {
					this.activate(this.jqElemUlResults.children(".util-jquery-autocomplete-choice:first"));
				}
			}
		};
		
		AutoCompletionElement.prototype.activate = function(jqElem, checkBoundaries) {
			checkBoundaries = (checkBoundaries == null) ? true : checkBoundaries;
			if (null != this.currentActive) {
				this.currentActive.removeClass(this.activeClassName);
			}
			this.currentActive = jqElem.addClass(this.activeClassName);
			if (checkBoundaries) {
				this.checkResultsBoundaries();
			}
		};
		
		AutoCompletionElement.prototype.checkResultsBoundaries = function() {
		if (!(this.isScrollable()) || (null === this.currentActive) || this.currentActive.length == 0) return
			var jqElemFirstResult = this.jqElemUlResults.children(":first");
			if (jqElemFirstResult.length == 0) return;
			this.jqElemUlResults.children(":first").position().top
			this.jqElemUlResults.scrollTop(Math.abs(this.jqElemUlResults.children(":first").position().top) 
					+ this.currentActive.position().top);
		};
		
		n2n.AutoCompletionElement = AutoCompletionElement;
	})(n2n);
	
	$(".util-jquery-autocomplete").each(function() {
		new n2n.AutoCompletionElement($(this));
	});
})(jQuery);

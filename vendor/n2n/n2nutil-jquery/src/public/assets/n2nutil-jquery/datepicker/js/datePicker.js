(function($) {
	if (!window.n2n) {
		window.n2n = new Object();
	}
	
	(function(n2n) {
		//////////////////
		// DateUtils
		//////////////////
		var DateUtils = new Object();
		DateUtils.getDaysInMonthForDate = function(date) {
			return new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
		};
		DateUtils.areDatesOnSameDay = function(date1, date2) {
			if (null == date1 || null == date2) return false;
			return date1.getDate() == date2.getDate() && date1.getMonth() == date2.getMonth()
					&& date1.getFullYear() == date2.getFullYear();
		};
		
		DateUtils.areDatesInSameMonth = function(date1, date2) {
			if (null == date1 || null == date2) return false;
			return date1.getMonth() == date2.getMonth() && date1.getFullYear() == date2.getFullYear();
		};
		
		DateUtils.isMonthBiggerOrEqual = function(date1, date2) {
			if (null == date1 || null == date2) return false;
			
			return (date1.getMonth() >= date2.getMonth() && date1.getFullYear() == date2.getFullYear()) 
					|| date1.getFullYear() > date2.getFullYear();
		};
		
		DateUtils.isYearBiggerOrEqual = function(date1, date2) {
			if (null == date1 || null == date2) return false;
			
			return date1.getFullYear() >= date2.getFullYear();
		};
		
		DateUtils.areDatesInSameYear = function(date1, date2) {
			if (null == date1 || null == date2) return false;
			return date1.getFullYear() == date2.getFullYear();
		};
		
		DateUtils.areDatesInSameDecade = function(date1, date2) {
			if (null == date1 || null == date2) return false;
			var fullYearDate1 = date1.getFullYear();
			var fullYearDate2 = date2.getFullYear();
			return ((fullYearDate1 - (fullYearDate1 % 10)) ==
					(fullYearDate2 - (fullYearDate2 % 10)));
			
		};
		DateUtils.isDecadeBiggerOrEqual = function(date1, date2) {
			if (null == date1 || null == date2) return false;
			var fullYearDate1 = date1.getFullYear();
			var fullYearDate2 = date2.getFullYear();
			return ((fullYearDate1 - (fullYearDate1 % 10)) >=
				(fullYearDate2 - (fullYearDate2 % 10)));
			
		};
		//////////////////
		// DateOptions
		//////////////////	
		var DateOptions = function() {
			this.pseudo = false;
			this.firstDayInWeek = 0;
			this.monthNames = ["January", "February", "March", "April", "May", "June", "July", 
					    	"August", "September", "October", "November", "December"];
			this.monthNamesShort = ["Jan", "Feb", "Mar", "Apr", "May", "June", "July", 
				    "Aug", "Sept", "Oct", "Nov", "Dec"];
			this.weekDays = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
			this.weekDaysShort = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
			this.amPm = ['AM', 'PM']
			this.timeZonePatterns = {
				z: 'GMT+01:00',
				zzzz: 'Central European Time'
			}
		};

		DateOptions.prototype.isPseudo = function() {
			if (this.pseudo === "0") {
				return false;
			}
			return this.pseudo;
		};
		
		DateOptions.prototype.getLocalizedDayOfWeek = function(date) {
			return (date.getDay() - this.firstDayInWeek + 7) % 7;
		};

		DateOptions.prototype.getWeekDay = function(date) {
			return this.weekDays[date.getDay()];
		};

		var SimpleDateFormatter = function(format, options) {
			this.dateOptions = options || new DateOptions();
			this.formatParts = null;
			this.formatFunctions = new Array();
			if (this.dateOptions.isPseudo()) {
				this.formatParts = format.match(this.formattingTokensDefault);
				this.initFormatFunctionsDefault();
			} else {
				this.formatParts = format.match(this.formattingTokensIcu);
				this.initFormatFunctionsIcu();
			}
		}
		
		SimpleDateFormatter.prototype.formattingTokensDefault = /(\\.|.)/g;
		
		SimpleDateFormatter.prototype.formattingTokensIcu = /('.|G{2,5}|yyyy|yy|M{2,5}|dd|hh|HH|mm|ss|(e|E){2,6}|\z{4}|.)/g;
		
		SimpleDateFormatter.prototype.initFormatFunctionsDefault = function() {
			for (var i in this.formatParts) {
				this.formatFunctions.push(this.getFormattingFunctionForPatternPartDefault(this.formatParts[i]));
			}
		}

		SimpleDateFormatter.prototype.getFormattingFunctionForPatternPartDefault = function(patternPart) {
			var _obj = this;
			switch (patternPart) {
				case 'Y':
					return function(date) {
						return date.getFullYear();
					}
				case 'y':
					return function(date) {
						return _obj.fillWithLeadingZeros(date.getFullYear() % 100, 2);
					}
				case 'n':
					return function(date) {
						return date.getMonth() + 1;
					}	
				case 'm':
					return function(date) {
						return _obj.fillWithLeadingZeros(date.getMonth() + 1, 2);
					}
				case 'M':
					return function(date) {
						return _obj.dateOptions.monthNamesShort[date.getMonth()];
					}
				case 'F':
					return function(date) {
						return _obj.dateOptions.monthNames[date.getMonth()];
					}
				case 'j':
					return function(date) {
						return date.getDate();
					}
				case 'd':
					return function(date) {
						return _obj.fillWithLeadingZeros(date.getDate(), 2);
					}
				case 'g':
					return function(date) {
						return (date.getHours() % 12) || 12;
					}
				case 'h':
					return function(date) {
						return _obj.fillWithLeadingZeros((date.getHours() % 12) || 12, 2);
					}
				case 'G':
					return function(date) {
						return date.getHours();
					}
				case 'H':
					return function(date) {
						return _obj.fillWithLeadingZeros(date.getHours(), 2);
					}
				case 'i':
					return function(date) {
						return _obj.fillWithLeadingZeros(date.getMinutes(), 2);
					}
				case 's':
					return function(date) {
						return _obj.fillWithLeadingZeros(date.getSeconds(), 2);
					}
				case 'a':
				case 'A':
					return function(date) {
						return _obj.dateOptions.amPm[Math.floor(date.getHours() / 12)];
					}
				case 'D':
					return function(date) {
						return _obj.dateOptions.weekDaysShort[date.getDay()];
					}
				case 'l':
					return function(date) {
						return _obj.dateOptions.getWeekDay(date);
					}
				case 'e':
				case 'O':
				case 'P':
				case 'T':
					return function(date) {
						return _obj.dateOptions.timeZonePatterns[patternPart];
					}
				default:
					if (patternPart.charAt(0) == "\\") {
						return function(date, patternPart) {
							return patternPart.charAt(1);
						}
					} else {
						return function(date, patternPart) {
							return patternPart;
						};
					}
			}
		}
		SimpleDateFormatter.prototype.initFormatFunctionsIcu = function() {
			for (var i in this.formatParts) {
				this.formatFunctions.push(this.getFormattingFunctionForPatternPartIcu(this.formatParts[i]));
			}
		}

		SimpleDateFormatter.prototype.getFormattingFunctionForPatternPartIcu = function(patternPart) {
			var _obj = this;
			switch (patternPart) {
				case 'G':
				case 'GG':
				case 'GGG':
					return function() {
						return 'AD';
					}
				case 'GGGG':
					return function() {
						return 'Anno Domini';
					}
				case 'GGGGG':
					return function() {
						return 'A';
					}
				case 'yy':
					return function(date) {
						return date.getFullYear() % 100;
					}
				case 'y':
				case 'yyyy':
					return function(date) {
						return date.getFullYear();
					}
				
				case 'M':
				case 'MM':
					return function(date) {
						return _obj.fillWithLeadingZeros(date.getMonth() + 1, 2);
					}
				case 'MMM':
					return function(date) {
						return _obj.dateOptions.monthNamesShort[date.getMonth()];
					}
				case 'MMMM':
					return function(date) {
						return _obj.dateOptions.monthNames[date.getMonth()];
					}
				case 'MMMMM':
					return function(date) {
						return _obj.dateOptions.monthNames[date.getMonth()].charAt(0);
					}
				case 'd':
					return function(date) {
						return date.getDate();
					}
				case 'dd':
					return function(date) {
						return _obj.fillWithLeadingZeros(date.getDate(), 2);
					}
				case 'h':
					return function(date) {
						return (date.getHours() % 12) || 12;
					}
				case 'hh':
					return function(date) {
						return _obj.fillWithLeadingZeros((date.getHours() % 12) || 12, 2);
					}
				case 'H':
					return function(date) {
						return date.getHours();
					}
				case 'HH':
					return function(date) {
						return _obj.fillWithLeadingZeros(date.getHours(), 2);
					}
				case 'm':
					return function(date) {
						return date.getMinutes();
					}
				case 'mm':
					return function(date) {
						return _obj.fillWithLeadingZeros(date.getMinutes(), 2);
					}
				case 's':
					return function(date) {
						return date.getSeconds();
					}
				case 'ss':
					return function(date) {
						return _obj.fillWithLeadingZeros(date.getSeconds(), 2);
					}
				case 'a':
					return function(date) {
						return _obj.dateOptions.amPm[Math.floor(date.getHours() / 12)];
					}
				case 'e':
				case 'ee':
					return function(date) {
						return _obj.dateOptions.getLocalizedDayOfWeek(date);
					}
				case 'E':
				case 'EE':
				case 'eee':
				case 'EEE':
					return function(date) {
						return _obj.dateOptions.weekDaysShort[date.getDay()];
					}
				case 'eeee':
				case 'EEEE':
					return function(date) {
						return _obj.dateOptions.getWeekDay(date);
					}
				case 'eeeee':
				case 'EEEEE':
					return function(date) {
						return _obj.dateOptions.getWeekDay(date).charAt(0);
					}
				case 'eeeeee':
				case 'EEEEEE':
					return function(date) {
						return _obj.dateOptions.getWeekDay(date).substr(0,2);
					}
				case 'z':
				case 'zzzz':
					return function(date) {
						return _obj.dateOptions.timeZonePatterns[patternPart];
					}
				default:
					if (patternPart.charAt(0) == "'") {
						return function(date, patternPart) {
							return patternPart.charAt(1);
						}
					} else {
						return function(date, patternPart) {
							return patternPart;
						};
					}
			}
		}

		SimpleDateFormatter.prototype.fillWithLeadingZeros = function(number, numDecimals) {
			return (new Array(numDecimals).join("0") + number).slice(-numDecimals);
		};

		SimpleDateFormatter.prototype.format = function(date) {
			var formatedDate = '';
			for (var i in this.formatFunctions) {
				formatedDate += this.formatFunctions[i](date, this.formatParts[i]);
			}
			return formatedDate;
		}

		var DateTimeInitialiser = function() {
			this.fullYear = null;
			this.month = null;
			this.day = null;
			this.hour = null;
			this.minute = null;
			this.seconds = null;
		};

		DateTimeInitialiser.prototype.setFullYear = function(fullYear) {
			if (null !== this.fullYear) return;
			this.fullYear = parseInt(fullYear);
		};

		DateTimeInitialiser.prototype.setMonth = function(month) {
			if (null !== this.month) return;
			this.month = parseInt(month);
		};

		DateTimeInitialiser.prototype.setDay = function(day) {
			if (null !== this.day) return;
			this.day = parseInt(day);
		};

		DateTimeInitialiser.prototype.setHours = function(hours) {
			if (null !== this.hours) return;
			this.hours = parseInt(hour);
		};

		DateTimeInitialiser.prototype.setMinutes = function(minutes) {
			if (null !== this.minutes) return;
			this.minutes = parseInt(minutes);
		};

		DateTimeInitialiser.prototype.setSeconds = function(seconds) {
			if (null !== this.seconds) return;
			this.seconds = parseInt(seconds);
		};

		var DateParser = function(format, options) {
			this.dateOptions = options || new DateOptions();
			this.formatPatterns = new Array();
			this.formatFunctions = new Array();
			if (this.dateOptions.isPseudo()) {
				this.generateFormatPatternAndFunctionsDefault(format.match(SimpleDateFormatter.prototype.formattingTokensDefault));
			} else {
				this.generateFormatPatternAndFunctionsIcu(format.match(SimpleDateFormatter.prototype.formattingTokensIcu));
			}
		}

		DateParser.prototype.FORMAT_PATTERN_AD = /^AD/;
		DateParser.prototype.FORMAT_PATTERN_ANNO_DOMINI = /^Anno Domini/;
		DateParser.prototype.FORMAT_PATTERN_A = /^A/;
		DateParser.prototype.FOMAT_PATTERN_FOUR_DIGITS = /^\d{4}/;
		DateParser.prototype.FOMAT_PATTERN_TWO_DIGITS = /^\d{2}/;
		DateParser.prototype.FOMAT_PATTERN_ONE_OR_TWO_DIGITS = /^\d\d?/;
		DateParser.prototype.FORMAT_PATTERN_ONE_DIGIT = /^\d/;

		DateParser.prototype.generateFormatPatternAndFunctionsDefault = function(formatParts) {
			for (var i in formatParts) {
				this.formatPatterns.push(this.getFormatPatternForFormatPartDefault(formatParts[i]));
				this.formatFunctions.push(this.getFormatFunctionForFormatPartDefault(formatParts[i]));
			}
		};
		
		DateParser.prototype.generateFormatPatternAndFunctionsIcu = function(formatParts) {
			for (var i in formatParts) {
				this.formatPatterns.push(this.getFormatPatternForFormatPartIcu(formatParts[i]));
				this.formatFunctions.push(this.getFormatFunctionForFormatPartIcu(formatParts[i]));
			}
		};

		DateParser.prototype.generateShortValueArrayForArray = function(array, numChars) {
			var shortValueArray = new Array();
			for(var i in array)  {
				shortValueArray.push(array[i].substr(0, numChars));
			}
			return shortValueArray;
		};

		DateParser.prototype.generatePatternForArray = function(array) {
			return new RegExp("^(" + array.join("|") + ")");
		};
		
		DateParser.prototype.getFormatPatternForFormatPartDefault = function(formatPart) {
			switch (formatPart) {
			
				case 'Y':
					return this.FOMAT_PATTERN_FOUR_DIGITS;
				case 'y':
				case 'm':
				case 'd':
				case 'h':
				case 'H':
				case 'i':
				case 's':
					return this.FOMAT_PATTERN_TWO_DIGITS;
				case 'M':
					return this.generatePatternForArray(this.dateOptions.monthNamesShort);
				case 'F':
					return this.generatePatternForArray(this.dateOptions.monthNames);
				case 'n':
				case 'j':
				case 'g':
				case 'G':
					return this.FOMAT_PATTERN_ONE_OR_TWO_DIGITS;
				case 'a':
				case 'A':
					return this.generatePatternForArray(this.dateOptions.amPm);
				case 'D':
					return this.generatePatternForArray(this.dateOptions.weekDaysShort);
				case 'l':
					return this.generatePatternForArray(this.dateOptions.weekDays);
				case 'e':
				case 'O':
				case 'P':
				case 'T':
					return new RegExp('^' + this.dateOptions.timeZonePatterns[formatPart]);
				default:
					return new RegExp('^' + formatPart);
			}
		}

		DateParser.prototype.getFormatFunctionForFormatPartDefault = function(formatPart) {
			var _obj = this;
			switch (formatPart) {
				case 'Y':
				case 'y':
					return function(dti, dateStringPart) {
						dti.setFullYear(dateStringPart);
					}
				case 'n':
				case 'm':
					return function(dti, dateStringPart) {
						dti.setMonth(parseInt(dateStringPart) - 1);
					}
				case 'M':
					return function(dti, dateStringPart) {
						dti.setMonth(_obj.dateOptions.monthNamesShort.indexOf(dateStringPart));
					}
				case 'F':
					return function(dti, dateStringPart) {
						dti.setMonth(_obj.dateOptions.monthNames.indexOf(dateStringPart));
					}
				case 'j':
				case 'd':
					return function(dti, dateStringPart) {
						dti.setDay(dateStringPart);
					}
				case 'g':
				case 'G':
				case 'h':
				case 'H':
					return function(dti, dateStringPart) {
						dti.setHours(dateStringPart);
					}
				case 'i':
					return function(dti, dateStringPart) {
						dti.setMinutes(dateStringPart);
					}
				case 's':
					return function(dti, dateStringPart) {
						dti.setSeconds(dateStringPart);
					}
				case 'a':
				case 'A':
					return function(dti, dateStringPart) {
						dti.hours += parseInt(_obj.dateOptions.amPm.indexOf(dateStringPart)) * 12;
					}
				default:
					return function() {}
			}
		}

		DateParser.prototype.getFormatPatternForFormatPartIcu = function(formatPart) {
			switch (formatPart) {
				case 'G':
				case 'GG':
				case 'GGG':
					return this.FORMAT_PATTERN_AD;
				case 'GGGG':
					return this.FORMAT_PATTERN_ANNO_DOMINI;
				case 'GGGGG':
					return this.FORMAT_PATTERN_A;
				case 'y':
				case 'yyyy':
					return this.FOMAT_PATTERN_FOUR_DIGITS;
				case 'yy':
				case 'MM':
				case 'dd':
				case 'hh':
				case 'HH':
				case 'mm':
				case 'ss':
					return this.FOMAT_PATTERN_TWO_DIGITS;
				case 'MMM':
					return this.generatePatternForArray(this.dateOptions.monthNamesShort);
				case 'MMMM':
					return this.generatePatternForArray(this.dateOptions.monthNames);
				case 'MMMMM':
					return this.generatePatternForArray(this.generateShortValueArrayForArray(this.dateOptions.monthNames, 1));
				case 'd':
				case 'h':
				case 'H':
				case 'm':
				case 's':
				case 'M':
					return this.FOMAT_PATTERN_ONE_OR_TWO_DIGITS;
				case 'a':
					return this.generatePatternForArray(this.dateOptions.amPm);
				case 'e':
				case 'ee':
				case 'E':
				case 'E':
					return this.FORMAT_PATTERN_ONE_DIGIT;
				case 'eee':
				case 'EEE':
					return this.generatePatternForArray(this.dateOptions.weekDaysShort);
				case 'eeee':
				case 'EEEE':
					return this.generatePatternForArray(this.dateOptions.weekDays);

				case 'eeeee':
				case 'EEEEE':
					return this.generatePatternForArray(this.generateShortValueArrayForArray(this.dateOptions.weekDays, 1));
				case 'eeeeee':
				case 'EEEEEE':
					return this.generatePatternForArray(this.generateShortValueArrayForArray(this.dateOptions.weekDays, 2));
				case 'z':
				case 'zzzz':
					return new RegExp('^' + this.dateOptions.timeZonePatterns[formatPart]);
				default:
					return new RegExp('^' + formatPart);
			}
		}

		DateParser.prototype.getFormatFunctionForFormatPartIcu = function(formatPart) {
			var _obj = this;
			switch (formatPart) {
				case 'y':
				case 'yyyy':
				case 'yy':
					return function(dti, dateStringPart) {
						dti.setFullYear(dateStringPart);
					}
				case 'M':
				case 'MM':
					return function(dti, dateStringPart) {
						dti.setMonth(parseInt(dateStringPart) - 1);
					}
				case 'MMM':
					return function(dti, dateStringPart) {
						dti.setMonth(_obj.dateOptions.monthNamesShort.indexOf(dateStringPart));
					}
				case 'MMMM':
					return function(dti, dateStringPart) {
						dti.setMonth(_obj.dateOptions.monthNames.indexOf(dateStringPart));
					}
				case 'MMMMM':
					return function(dti, dateStringPart) {
						dti.setMonth(_obj.generateShortValueArrayForArray(this.dateOptions.monthNames, 0)
								.indexOf(dateStringPart));
					}
				case 'd':
				case 'dd':
					return function(dti, dateStringPart) {
						dti.setDay(dateStringPart);
					}
				case 'h':
				case 'hh':
				case 'H':
				case 'HH':
					return function(dti, dateStringPart) {
						dti.setHours(dateStringPart);
					}
				case 'm':
				case 'mm':
					return function(dti, dateStringPart) {
						dti.setMinutes(dateStringPart);
					}
				case 's':
				case 'ss':
					return function(dti, dateStringPart) {
						dti.setSeconds(dateStringPart);
					}
				case 'a':
					return function(dti, dateStringPart) {
						dti.hours += parseInt(_obj.dateOptions.amPm.indexOf(dateStringPart)) * 12;
					}
				default:
					return function() {}
			}
		}

		DateParser.prototype.parse = function(dateString) {
			if (null == dateString) return null;
			var dti = new DateTimeInitialiser();
			for (var i in this.formatPatterns) {
				var matches = dateString.match(this.formatPatterns[i]);
				if (null == matches) return null;
				var nextStringPart = matches.pop();
				dateString = dateString.substr(nextStringPart.length);
				this.formatFunctions[i](dti, nextStringPart);
			};
			return new Date(dti.fullYear, dti.month, dti.day, dti.hour, dti.minute);
		};
		
		//////////////////////
		// SuperClass Pickable
		//////////////////////
		var Pickable = function() {
			this.date = null;
			this.jqElemTd = null;
			this.clickCallbacks = null;
			this.firstSelectableDate = null;
		};
		
		Pickable.prototype.selectedPickableClassName = "util-jquery-date-picker-pickable-selected";
		Pickable.prototype.currentPickableClassName = "util-jquery-date-picker-pickable-current";
		Pickable.prototype.disabledPickableClassName = "util-jquery-date-picker-pickable-disabled";
		
		Pickable.prototype.init = function() {
			var _obj = this;
			this.date = null;
			this.jqElemTd = $("<td/>").on('click.pickable', function() {
				if (null == _obj.date) return;
				if (null !== _obj.firstSelectableDate && !_obj.isSelectable(_obj.date)) return;
				
				for (var i in _obj.clickCallbacks) {
					_obj.clickCallbacks[i].call(_obj);
				}
			}).addClass("util-jquery-date-picker-pickable").removeClass(this.disabledPickableClassName);
			this.clickCallbacks = new Array();
		};
		
		Pickable.prototype.registerClickCallback = function(callBack) {
			this.clickCallbacks.push(callBack);
		};
		
		Pickable.prototype.setSelected = function(selected) {
			this.jqElemTd.toggleClass(this.selectedPickableClassName, selected);
		};
		
		Pickable.prototype.setFirstSelectableDate = function(firstSelectableDate) {
			this.firstSelectableDate = firstSelectableDate;
			if (!this.isSelectable(this.date)) {
				this.jqElemTd.addClass(this.disabledPickableClassName)
			}
		};

		//////////////////////
		// SuperClass Picker
		//////////////////////
		var Picker = function() {};
		
		Picker.prototype.initPicker = function() {
			this.labelClickCallbacks = new Array();
			this.selectedCallbacks = new Array();
		};
		
		Picker.prototype.registerLabelClickCallback = function(callback) {
			this.labelClickCallbacks.push(callback);
		};
		
		Picker.prototype.registerSelectedCallback = function(callback) {
			this.selectedCallbacks.push(callback);
		};
		
		Picker.prototype.hide = function() {
			if (null != this.jqElem) {
				this.jqElem.hide();
			}
		}
		//////////////////
		//
		// DatePickerNavigation
		//
		//////////////////
		var DatePickerNavigation = function(jqElem, datePicker) {
			this.datePicker = datePicker;
			this.jqElem = jqElem;
			
			this.jqElemPrev = null;
			this.jqElemLabel = null;
			this.jqElemNext = null;
			
			this.prevClickCallback = null;
			this.labelClickCallback = null;
			this.nextClickCallback = null;
			
			this.initializeUi();
		};
		
		DatePickerNavigation.prototype.initializeUi = function() {
			var _obj = this;
			this.jqElemPrev = $("<span/>").appendTo(this.jqElem).click(function(e) {
				e.preventDefault();
				if (!typeof _obj.prevClickCallback == 'function') return;
				_obj.prevClickCallback.call(_obj);
			}).addClass("util-jquery-date-picker-previous");
			this.jqElemLabel = $("<span/>").appendTo(this.jqElem).click(function(e) {
				e.preventDefault();
				if (!typeof _obj.labelClickCallback == 'function') return;
				_obj.labelClickCallback.call(_obj);
			}).addClass("util-jquery-date-picker-navigation-label");
			
			this.jqElemNext = $("<span/>").appendTo(this.jqElem).click(function(e) {
				e.preventDefault();
				if (!typeof _obj.nextClickCallback == 'function') return;
				_obj.nextClickCallback.call(_obj);
			}).addClass("util-jquery-date-picker-next");
			
			//next Text or Icon
			if (null !== this.datePicker.iconClassNameNext) {
				this.jqElemNext.append($("<i/>", {"class": this.datePicker.iconClassNameNext}));
			} else {
				this.jqElemNext.append(this.datePicker.textNext);
			}
			
			//next Text or Icon
			if (null !== this.datePicker.iconClassNamePrev) {
				this.jqElemPrev.append($("<i/>", {"class": this.datePicker.iconClassNamePrev}));
			} else {
				this.jqElemPrev.append(this.datePicker.textPrev);
			}
		};
		
		DatePickerNavigation.prototype.setLabelText = function(text) {
			this.jqElemLabel.text(text);
		}
		//////////////////
		// Datepicker
		//////////////////
		var DatePicker = function(jqElemInput) {
			if (jqElemInput.length === 0) return;
			
			this.jqElemInput = jqElemInput.attr("autocomplete", "off");
			
			this.textNext = jqElemInput.data("text-next") || ">>";
			this.textPrev = jqElemInput.data("text-prev") || "<<";
			
			this.iconClassNameNext = jqElemInput.data("icon-class-name-next") || null;
			this.iconClassNamePrev = jqElemInput.data("icon-class-name-prev") || null;
			this.iconClassNameOpen = jqElemInput.data("icon-class-name-open") || null;
			this.openerSelector = jqElemInput.data("selector-opener") || null;
			
			this.options = new DateOptions();
			
			this.options.pseudo = jqElemInput.data("pseudo") || this.options.pseudo;
			this.options.firstDayInWeek = jqElemInput.data("first-day-in-week") || this.options.firstDayInWeek;
			this.options.monthNames = jqElemInput.data("month-names") || this.options.monthNames;
			this.options.monthNamesShort = jqElemInput.data("month-names-short") || this.options.monthNamesShort;
			this.options.weekDays = jqElemInput.data("week-days") || this.options.weekDays;
			this.options.weekDaysShort = jqElemInput.data("week-days-short") || this.options.weekDaysShort;
			this.options.amPm = jqElemInput.data("am-pm") || this.options.amPm;
			this.options.timeZonePatterns = jqElemInput.data("time-zone-patterns") || this.options.timeZonePatterns;
			var pattern = jqElemInput.data('pattern') || 'MMM d, y';
			this.formatter = new SimpleDateFormatter(pattern, this.options);
			this.parser = new DateParser(pattern, this.options)
			this.placement = jqElemInput.data("placement") || "auto";
			
			this.currentMode = jqElemInput.data("mode") || this.MODE_DECADE;
			
			this.jqElem = null;
			this.jqElemDivNavigation = null;
			this.jqElemDivDayPicker = null;
			this.jqElemDivMonthPicker = null;
			this.jqElemDivYearPicker = null;
			this.jqElemDivDecadePicker = null;
			this.jqElemAOpener = null;
			
			this.dayPicker = null;
			this.monthPicker = null;
			this.yearPicker = null;
			this.decadePicker = null;
			
			this.currentPicker = null;
			
			this.modes = new Array(this.MODE_DAY, this.MODE_MONTH, this.MODE_DECADE, this.MODE_DECADE);
			
			this.navigation = null;
			this.date = null;
			this.selectedDate = null;
			this.firstSelectableDate = this.parser.parse(jqElemInput.data("first-selectable-date"));
			this.init();
			this.jqElemInput.data('datepicker', this);
		};
		
		DatePicker.prototype.MODE_DAY = 'day';
		DatePicker.prototype.MODE_MONTH = 'month';
		DatePicker.prototype.MODE_YEAR = 'year';
		DatePicker.prototype.MODE_DECADE = 'decade';
		DatePicker.prototype.modes = new Array(this.MODE_DAY, this.MODE_MONTH, this.MODE_DECADE, this.MODE_DECADE);
		
		DatePicker.prototype.isPickerAvailable = function(mode) {
			return this.modes.indexOf(this.currentMode) >= this.modes.indexOf(mode);
		};
 		
		DatePicker.prototype.parseInputVal = function() {
			this.date = this.parser.parse(this.jqElemInput.val());
			if (null == this.date) {
				this.date = new Date();
			} else {
				this.selectedDate = this.date;
			}
		};
		
		DatePicker.prototype.init = function() {
			var _obj = this;
			this.parseInputVal();
			
			if (null !== this.openerSelector) {
				var jqElemAOpener = this.jqElemInput.next(this.openerSelector);
				if (jqElemAOpener.length === 0) {
					var tmpElem = this.jqElemInput;
					while (tmpElem.parent().length > 0 && jqElemAOpener.length === 0) {
						tmpElem = tmpElem.parent();
						tmpElem.find(this.openerSelector).first();
					}
				}
				
				if (jqElemAOpener.length === 0) {
					throw "Invalid date picker opener selector: " + this.openerSelector;
				}
				
				this.jqElemAOpener = jqElemAOpener;
			} else if (null !== this.iconClassNameOpen) {
				this.jqElemAOpener = $("<a/>", {
					"class": "util-jquery-datepicker-opener rocket-control",
					"href": "#"
				}).append($("<i/>").addClass(this.iconClassNameOpen)).insertAfter(this.jqElemInput); 
			}
			
			if (null !== this.jqElemAOpener) {
				this.jqElemAOpener.click(function(e) {
					e.preventDefault();
					e.stopPropagation();
					//hide other Datepicker
					if (!_obj.jqElem.is(":visible")) {
						$(".util-jquery-date-picker").hide();
						_obj.show();
					} else {
						_obj.hide();
					}
				})
			}
			
			this.jqElem = $("<div/>").addClass("util-jquery-date-picker").hide().css({
				position: "absolute",
				zIndex: 200000001
			}).insertAfter(this.jqElemInput);
			
			this.jqElemInput.focus(function() {
				//hide other Datepicker
				$(".util-jquery-date-picker").hide();
				_obj.show();
			}).keyup(function() {
				var date = _obj.parser.parse(_obj.jqElemInput.val());
				if (null != date) {
					_obj.date = date;
					_obj.selectedDate = date;
					_obj.redrawCurrentPicker();
				}
			}).keydown(function(e) {
				var keyCode = (window.event) ? e.keyCode : e.which;
				if (keyCode === 9) {
					_obj.hide();
				}
			}).click(function(e) {
				e.stopPropagation();
			});
			
			this.jqElem.click(function(e) {
				e.stopPropagation();
			});
			
			$(window).click(function() {
				_obj.hide();
			});
			
			//DayPicker
			this.jqElemDivNavigation = $("<div/>").addClass("util-jquery-date-picker-navigation").appendTo(this.jqElem);
			this.navigation = new DatePickerNavigation(this.jqElemDivNavigation, this);
			
			this.jqElemDivDayPicker = $("<div/>").appendTo(this.jqElem).hide();
			this.dayPicker = new DayPicker(this);
			this.dayPicker.registerSelectedCallback(function() {
				_obj.setDate(_obj.date);
				_obj.hide();
			});
			
			this.showPicker(this.dayPicker)
			
			//MonthPicker
			if (this.isPickerAvailable(this.MODE_MONTH)) {
				this.dayPicker.registerLabelClickCallback(function() {
					_obj.showPicker(_obj.monthPicker);
				});
				this.jqElemDivMonthPicker = $("<div/>").appendTo(this.jqElem).hide();
				this.monthPicker = new MonthPicker(this);
				this.monthPicker.registerSelectedCallback(function() {
					_obj.showPicker(_obj.dayPicker);
				});
			}
			
			//YearPicker
			if (this.isPickerAvailable(this.MODE_YEAR)) {
				this.monthPicker.registerLabelClickCallback(function() {
					_obj.showPicker(_obj.yearPicker);
				});
				
				this.jqElemDivYearPicker = $("<div/>").appendTo(this.jqElem).hide();
				this.yearPicker = new YearPicker(this);
				this.yearPicker.registerSelectedCallback(function() {
					_obj.showPicker(_obj.monthPicker);
				});
			}
			
			//DecadePicker
			if (this.isPickerAvailable(this.MODE_DECADE)) {
				this.yearPicker.registerLabelClickCallback(function() {
					_obj.showPicker(_obj.decadePicker);
				});
				
				this.jqElemDivDecadePicker = $("<div/>").appendTo(this.jqElem).hide();
				this.decadePicker = new DecadePicker(this);
				this.decadePicker.registerSelectedCallback(function() {
					_obj.showPicker(_obj.yearPicker);
				});
			}
		};
		
		DatePicker.prototype.show = function() {
			if (null !== this.currentPicker) {
				this.currentPicker.show();
				this.currentPicker.assignNavigation(this.navigation);
			}
			//check Positions before - otherwise the document height may increase after showing the datepicker
			var onTop = false;
			if (this.placement === null || this.placement === 'auto') {
				onTop = $(document).height() < this.jqElem.outerHeight() + this.jqElemInput.offset().top;
			} else if (this.placement === 'top') {
				onTop = true;
			}
			this.jqElem.show();
			if (onTop) {
				//strange behaviour with position absolute -> the object gets 20px more height
				this.jqElem.css({
					top: this.jqElemInput.position().top - this.jqElem.outerHeight()
				});
			} else {
				this.jqElem.css({
					top: this.jqElemInput.position().top + this.jqElemInput.outerHeight()
				});
			}
		};
		
		DatePicker.prototype.hide = function() {
			this.jqElem.hide();
		};
		
		DatePicker.prototype.showPicker = function(picker) {
			if (!(picker instanceof Picker)) return;
			if (picker === this.currentPicker) {
                this.currentPicker.show();
                return;
            } 
			if (null !== this.currentPicker) {
				this.currentPicker.hide();
			}
			this.currentPicker = picker;
			this.currentPicker.assignNavigation(this.navigation);
			this.currentPicker.show();
		};
		
		DatePicker.prototype.redrawCurrentPicker = function() {
			if (null === this.currentPicker) return;
			this.currentPicker.assignNavigation(this.navigation);
			this.currentPicker.show();
		};
		
		DatePicker.prototype.setDate = function(date) {
			this.date = date;
			this.selectedDate = date;
			this.jqElemInput.val(this.formatter.format(date));
			this.jqElemInput.trigger('dateselected', [new Date(date.getTime())]);
		};
		
		DatePicker.prototype.setFirstSelectableDate = function(firstSelectableDate) {
			this.firstSelectableDate = firstSelectableDate;
			this.redrawCurrentPicker();
		};
		
		//////////////////
		// DayPicker
		//////////////////
		var Day = function() {
			this.init()
		};
		
		Day.prototype = new Pickable();
		
		Day.prototype.setDate = function(date, selectedDate) {
			this.date = date;
			this.jqElemTd.text(date.getDate()).addClass("util-jquery-date-picker-pickable");
			if (DateUtils.areDatesOnSameDay(date, new Date())) {
				this.jqElemTd.addClass(this.currentPickableClassName);
			} else {
				this.jqElemTd.removeClass(this.currentPickableClassName);
			}
			
			if (DateUtils.areDatesOnSameDay(date, selectedDate)) {
				this.jqElemTd.addClass(this.selectedPickableClassName);
			} else {
				this.jqElemTd.removeClass(this.selectedPickableClassName);
			}
		};
		
		Day.prototype.reset = function() {
			this.jqElemTd.empty();
			this.jqElemTd.removeClass();
			this.clickCallbacks = new Array();
		};
		
		Day.prototype.isSelectable = function(date) {
			return this.firstSelectableDate <= date;
		};
		
		var Week = function() {
			this.jqElemTr = $("<tr/>");
			this.days = new Array();
			for (var i = 0; i < 7; i++) {
				var day = new Day();
				this.days.push(day);
				this.jqElemTr.append(day.jqElemTd);
			}
		};
		
		Week.prototype.reset = function() {
			this.jqElemTr.remove();
			for (var i in this.days) {
				this.days[i].reset();
			}
		};
		
		Week.prototype.activate = function(startDate, dayClickCallback, dayPicker) {
			var tmpDate = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate() - 1);
			for (var i in this.days) {
				this.days[i].reset();
				if (i < (dayPicker.datePicker.options.getLocalizedDayOfWeek(startDate))) continue;
				var newDate = new Date(tmpDate.getFullYear(), tmpDate.getMonth(), tmpDate.getDate() + 1);
				if (newDate.getMonth() != startDate.getMonth()) continue;
				if (DateUtils.areDatesOnSameDay(dayPicker.datePicker.selectedDate, newDate)) {
					dayPicker.selectedDay = this.days[i];
					this.days[i].setSelected(true);
				}
				this.days[i].setDate(newDate, dayPicker.datePicker.selectedDate);
				this.days[i].setFirstSelectableDate(dayPicker.datePicker.firstSelectableDate);
				this.days[i].registerClickCallback(dayClickCallback);
				tmpDate = newDate;
			}
		};
		
		var WeekManager = function() {
			this.availableWeeks = new Array();
		};
		
		WeekManager.prototype.requestWeek = function() {
			if (this.availableWeeks.length > 0) {
				return this.availableWeeks.pop();
			}
			return new Week();
		};
		
		WeekManager.prototype.addWeek = function(week) {
			week.reset();
			this.availableWeeks.push(week);
		};
		
		var DayPicker = function(datePicker) {
			this.jqElem = datePicker.jqElemDivDayPicker;
			this.weekManager = new WeekManager();
			this.datePicker = datePicker;
			
			this.weeks = new Array();
			this.selectedDay = null;
			
			this.jqElemTableBody = null;
			this.jqElemNext = null;
			this.jqElemPrev = null;
			this.jqElemMonth = null;
			
			this.initPicker();
			this.init();
			this.drawCalender();
		};
		
		DayPicker.prototype = new Picker();
		
		DayPicker.prototype.init = function() {
			var jqElemHeader = $("<tr/>");
			for (var i = this.datePicker.options.firstDayInWeek; i < this.datePicker.options.firstDayInWeek + 7; i++) {
				$("<th/>", {
					text: this.datePicker.options.weekDaysShort[i % 7]
				}).appendTo(jqElemHeader);
			};
			this.jqElemTableBody = $("<tbody/>");
			$("<table/>").addClass("util-jquery-picker-calender").appendTo(this.jqElem).append($("<thead/>").addClass("util-jquery-date-picker-table-header")
					.append(jqElemHeader)).append(this.jqElemTableBody);
		};
		
		DayPicker.prototype.assignNavigation = function(navigation) {
			if (!(navigation instanceof DatePickerNavigation)) return;
			navigation.setLabelText(this.getSelectedMonthName()); 
			var _obj = this;
			navigation.prevClickCallback = function() {
				var date = _obj.datePicker.date;
				_obj.datePicker.date = new Date(date.getFullYear(), date.getMonth() - 1, date.getDate());
				navigation.setLabelText(_obj.getSelectedMonthName());
				_obj.drawCalender();
			};
			navigation.labelClickCallback = function() {
				for(var i in _obj.labelClickCallbacks) {
					_obj.labelClickCallbacks[i](_obj.datePicker.date);
				}
			};
			navigation.nextClickCallback = function() {
				var date = _obj.datePicker.date;
				_obj.datePicker.date = new Date(date.getFullYear(), date.getMonth() + 1, date.getDate());
				if (_obj.datePicker.date.getMonth() > date.getMonth() + 1) {
					_obj.datePicker.date = new Date(date.getFullYear(), date.getMonth() + 2, 0);
				}
				
				navigation.setLabelText(_obj.getSelectedMonthName());
				_obj.drawCalender();
			}
		};
		
		DayPicker.prototype.drawCalender = function() {
			var _obj = this;
			var date = new Date(this.datePicker.date.getFullYear(), this.datePicker.date.getMonth(), 1);
			var cumulatedDayOfWeekFirstDay = this.datePicker.options.getLocalizedDayOfWeek(date);
			var numWeeks = Math.ceil(((cumulatedDayOfWeekFirstDay) 
					+ DateUtils.getDaysInMonthForDate(date)) / 7);
			for (var i = numWeeks; i < this.weeks.length; i++) {
				this.weekManager.addWeek(this.weeks.pop());
			} 
			for (var i = 0; i < numWeeks; i++) {
				var newWeek;
				switch (i) {
					case 0:
						break;
					case 1:
						date.setDate(date.getDate() + 7 - cumulatedDayOfWeekFirstDay);
						break;
					default:
						date.setDate(date.getDate() + 7);
				}
				if (this.weeks.length > i) {
					newWeek = this.weeks[i];
				} else {
					newWeek = this.weekManager.requestWeek();
					this.weeks.push(newWeek);
					this.jqElemTableBody.append(newWeek.jqElemTr);
				}
				newWeek.activate(date, function() {
					//the callee is a Day Object -> we can access the properties via this;
					this.setSelected(true);
					if (null !== _obj.selectedDay) {
						_obj.selectedDay.setSelected(false);
					}
					_obj.selectedDay = this;
					_obj.datePicker.date = this.date;
					for (var i in _obj.selectedCallbacks) {
						_obj.selectedCallbacks[i](this.date);
					};
				}, this);
			}
		};
		
		DayPicker.prototype.getSelectedMonthName = function() {
			return this.datePicker.options.monthNames[this.datePicker.date.getMonth()] + " " + this.datePicker.date.getFullYear();
		};
		
		DayPicker.prototype.show = function() {
			this.jqElem.show();
			this.drawCalender();
		};
		
		/////////////////////////////////
		// Class MonthPicker
		/////////////////////////////////
		
		var Month = function(monthPicker) {
			this.monthPicker = monthPicker;
			this.init();
		};
		
		Month.prototype = new Pickable();
		Month.prototype.setDate = function(date, selectedDate) {
			if (this.date == null) {
				this.jqElemTd.text(this.monthPicker.datePicker.options.monthNamesShort[date.getMonth()]);
			}
			this.date = date;
			if (DateUtils.areDatesInSameMonth(date, new Date())) {
				this.jqElemTd.addClass(this.currentPickableClassName);
			} else {
				this.jqElemTd.removeClass(this.currentPickableClassName);
			}
			
			if (this.isSelectable(date)) {
				this.jqElemTd.removeClass(this.disabledPickableClassName);
			} else {
				this.jqElemTd.addClass(this.disabledPickableClassName);
			}
			
			if (DateUtils.areDatesInSameMonth(date, selectedDate)) {
				this.jqElemTd.addClass(this.selectedPickableClassName);
			} else {
				this.jqElemTd.removeClass(this.selectedPickableClassName);
			}
		};

		Month.prototype.isSelectable = function(date) {
			return DateUtils.isMonthBiggerOrEqual(date, this.firstSelectableDate);
		};
		
		var MonthPicker = function(datePicker) {
			this.datePicker = datePicker;
			this.jqElem = datePicker.jqElemDivMonthPicker;
			
			this.months = new Array();
			this.selectedMonth = null;
			this.jqElemTableBody = $("<tbody/>");
			this.jqElem.append($("<table/>").addClass("util-jquery-picker-calender").append(this.jqElemTableBody));
			this.initPicker();
			this.init();
		};
		
		MonthPicker.prototype = new Picker();
		MonthPicker.prototype.init = function() {
			var _obj  = this;
			var currentRow = null;
			for (var i = 0; i < 12; i++) {
				
				if (i % 4 == 0) {
					currentRow = $('<tr/>').appendTo(this.jqElemTableBody);
				}
				
				var date = new Date(this.datePicker.date.getFullYear(), i, 1, 0, 0, 0);
				var month = new Month(this);
				this.months.push(month);
				if (DateUtils.areDatesInSameMonth(date, this.datePicker.date)) {
					month.setSelected(true);
					this.selectedMonth = month;
				}
				month.setDate(date, this.datePicker.selectedDate);
				month.setFirstSelectableDate(this.datePicker.firstSelectableDate);
				currentRow.append(month.jqElemTd);
				month.registerClickCallback(function() {
					this.setSelected(true);
					_obj.selectedMonth.setSelected(false);
					_obj.selectedMonth = this;
					_obj.datePicker.date = this.date;
					for (var i in _obj.selectedCallbacks) {
						_obj.selectedCallbacks[i](this.date);
					};
				});
			}
		};
		
		MonthPicker.prototype.update = function() {
			for (var i in this.months) {
				var month = this.months[i];
				month.date.setFullYear(this.datePicker.date.getFullYear());
				month.setDate(month.date, this.datePicker.selectedDate);
			}
		};
		
		MonthPicker.prototype.assignNavigation = function(navigation) {
			if (!(navigation instanceof DatePickerNavigation)) return;
			navigation.setLabelText(this.datePicker.date.getFullYear()); 
			var _obj = this;
			navigation.prevClickCallback = function() {
				var date = _obj.datePicker.date;
				_obj.datePicker.date = new Date(date.getFullYear() - 1, date.getMonth(), date.getDate());
				navigation.setLabelText(_obj.datePicker.date.getFullYear());
				_obj.update();
			};
			navigation.labelClickCallback = function() {
				for(var i in _obj.labelClickCallbacks) {
					_obj.labelClickCallbacks[i](_obj.datePicker.date);
				}
			};
			navigation.nextClickCallback = function() {
				var date = _obj.datePicker.date;
				_obj.datePicker.date = new Date(date.getFullYear() + 1, date.getMonth() , date.getDate());
				navigation.setLabelText(_obj.datePicker.date.getFullYear());
				_obj.update();
			}
		};
		
		MonthPicker.prototype.show = function() {
			this.jqElem.show();
			this.update();
		};

		/////////////////////////////////
		// Class YearPicker
		/////////////////////////////////
		var Year = function() {
			this.init();
		};
		
		Year.prototype = new Pickable();
		Year.prototype.setDate = function(date, selectedDate) {
			this.date = date;
			this.jqElemTd.text(date.getFullYear());
			if (DateUtils.areDatesInSameYear(date, new Date())) {
				this.jqElemTd.addClass(this.currentPickableClassName);
			} else {
				this.jqElemTd.removeClass(this.currentPickableClassName);
			}
			
			if (this.isSelectable(date)) {
				this.jqElemTd.removeClass(this.disabledPickableClassName);
			} else {
				this.jqElemTd.addClass(this.disabledPickableClassName);
			}
			
			if (DateUtils.areDatesInSameYear(date, selectedDate)) {
				this.jqElemTd.addClass(this.selectedPickableClassName);
			} else {
				this.jqElemTd.removeClass(this.selectedPickableClassName);
			}
			
		};

		Year.prototype.isSelectable = function(date) {
			return DateUtils.isYearBiggerOrEqual(date, this.firstSelectableDate);
		};
		
		var YearPicker = function(datePicker) {
			this.datePicker = datePicker;
			this.jqElem = datePicker.jqElemDivYearPicker;
			
			this.years = new Array();
			this.selectedYear = null;
			this.jqElemTableBody = $("<tbody/>");
			this.jqElem.append($("<table/>").addClass("util-jquery-picker-calender").append(this.jqElemTableBody));
			this.initPicker();
			this.init();
		};
		
		YearPicker.prototype = new Picker();
		YearPicker.prototype.init = function() {
			var _obj  = this;
			var currentRow = null;
			var fullYear = this.datePicker.date.getFullYear();
			for (var i = 0; i < 12; i++) {
				if (i % 4 == 0) {
					currentRow = $("<tr/>").appendTo(this.jqElemTableBody);
				}
				var date = new Date(fullYear - (fullYear % 10) - 1 + i, this.datePicker.date.getMonth(), this.datePicker.date.getDate());
				var year = new Year();
				this.years.push(year);
				if (DateUtils.areDatesInSameYear(date, this.datePicker.date)) {
					year.setSelected(true);
					this.selectedYear = year;
				}
				year.setDate(date, this.datePicker.selectedDate);
				year.setFirstSelectableDate(this.datePicker.firstSelectableDate);
				currentRow.append(year.jqElemTd);
				year.registerClickCallback(function() {
					this.setSelected(true);
					_obj.selectedYear.setSelected(false);
					_obj.selectedYear = this;
					_obj.datePicker.date = this.date;
					for (var i in _obj.selectedCallbacks) {
						_obj.selectedCallbacks[i](this.date);
					};
				});
			}
		};
		
		YearPicker.prototype.update = function() {
			var fullYear = this.datePicker.date.getFullYear();
			for (var i in this.years) {
				var year = this.years[i];
				year.date.setFullYear(fullYear - (fullYear % 10) - 1 + parseInt(i));
				year.setDate(year.date, this.datePicker.selectedDate);
			}
		};
		
		YearPicker.prototype.assignNavigation = function(navigation) {
			if (!(navigation instanceof DatePickerNavigation)) return;
			navigation.setLabelText(this.getLabelText()); 
			var _obj = this;
			navigation.prevClickCallback = function() {
				var date = _obj.datePicker.date;
				_obj.datePicker.date = new Date(date.getFullYear() - 10, date.getMonth(), date.getDate());
				navigation.setLabelText(_obj.getLabelText());
				_obj.update();
			};
			navigation.labelClickCallback = function() {
				for(var i in _obj.labelClickCallbacks) {
					_obj.labelClickCallbacks[i](_obj.datePicker.date);
				}
			};
			navigation.nextClickCallback = function() {
				var date = _obj.datePicker.date;
				_obj.datePicker.date = new Date(date.getFullYear() + 10, date.getMonth() , date.getDate());
				navigation.setLabelText(_obj.getLabelText());
				_obj.update();
			}
		};
		
		YearPicker.prototype.getLabelText = function() {
			var fullYear = this.datePicker.date.getFullYear();
			var startYear = fullYear - (fullYear % 10);
			return startYear + " - " + (startYear + 9);
		};
		
		
		YearPicker.prototype.show = function() {
			this.jqElem.show();
			this.update();
		};
		
		/////////////////////////////////
		// Class DecadePicker
		/////////////////////////////////
		var Decade = function() {
			this.init();
		};
		
		Decade.prototype = new Pickable();
		Decade.prototype.setDate = function(date, selectedDate) {
			this.date = date;
			this.jqElemTd.text(this.getText());
			if (DateUtils.areDatesInSameDecade(date, new Date())) {
				this.jqElemTd.addClass(this.currentPickableClassName);
			} else {
				this.jqElemTd.removeClass(this.currentPickableClassName);
			}
			
			if (this.isSelectable(date)) {
				this.jqElemTd.removeClass(this.disabledPickableClassName);
			} else {
				this.jqElemTd.addClass(this.disabledPickableClassName);
			}
			
			if (DateUtils.areDatesInSameDecade(date, selectedDate)) {
				this.jqElemTd.addClass(this.selectedPickableClassName);
			} else {
				this.jqElemTd.removeClass(this.selectedPickableClassName);
			}
		};
		
		Decade.prototype.getText = function() {
			var fullYear = this.date.getFullYear();
			var startYear = fullYear - (fullYear % 10);
			return startYear + " - " + "\n" + (startYear + 9);
		};
		
		Decade.prototype.isSelectable = function(date) {
			return DateUtils.isDecadeBiggerOrEqual(date, this.firstSelectableDate);
		};
		
		var DecadePicker = function(datePicker) {
			this.datePicker = datePicker;
			this.jqElem = datePicker.jqElemDivDecadePicker;
			
			this.decades = new Array();
			this.selectedDecade = null;
			this.jqElemTableBody = $("<tbody/>");
			this.jqElem.append($("<table/>").addClass("util-jquery-picker-calender").append(this.jqElemTableBody));
			this.initPicker();
			this.init();
		};
		
		DecadePicker.prototype = new Picker();
		DecadePicker.prototype.init = function() {
			var _obj  = this;
			var currentRow = null;
			var fullYear = this.datePicker.date.getFullYear();
			for (var i = 0; i < 12; i++) {
				if (i % 4 == 0) {
					currentRow = $("<tr/>").appendTo(this.jqElemTableBody);
				}
				var date = new Date(fullYear - (fullYear % 100) - 10 + i * 10, this.datePicker.date.getMonth(), this.datePicker.date.getDate());
				var decade = new Decade();
				this.decades.push(decade);
				if (DateUtils.areDatesInSameDecade(date, this.datePicker.date)) {
					decade.setSelected(true);
					this.selectedDecade = decade;
				}
				decade.setDate(date, this.datePicker.selectedDate);
				decade.setFirstSelectableDate(this.datePicker.firstSelectableDate);
				currentRow.append(decade.jqElemTd);
				decade.registerClickCallback(function() {
					this.setSelected(true);
					_obj.selectedDecade.setSelected(false);
					_obj.selectedDecade = this;
					_obj.datePicker.date = this.date;
					for (var i in _obj.selectedCallbacks) {
						_obj.selectedCallbacks[i](this.date);
					};
				});
			}
		};
		
		DecadePicker.prototype.update = function() {
			var fullYear = this.datePicker.date.getFullYear();
			for (var i in this.decades) {
				var decade = this.decades[i];
				decade.date.setFullYear(fullYear - (fullYear % 100) - 10 + parseInt(i) * 10);
				decade.setDate(decade.date, this.datePicker.selectedDate);
			}
		};
		
		DecadePicker.prototype.assignNavigation = function(navigation) {
			if (!(navigation instanceof DatePickerNavigation)) return;
			navigation.setLabelText(this.getLabelText()); 
			var _obj = this;
			navigation.prevClickCallback = function() {
				var date = _obj.datePicker.date;
				_obj.datePicker.date = new Date(date.getFullYear() - 100, date.getMonth(), date.getDate());
				navigation.setLabelText(_obj.getLabelText());
				_obj.update();
			};
			navigation.labelClickCallback = function() {
				for(var i in _obj.labelClickCallbacks) {
					_obj.labelClickCallbacks[i](_obj.datePicker.date);
				}
			};
			navigation.nextClickCallback = function() {
				var date = _obj.datePicker.date;
				_obj.datePicker.date = new Date(date.getFullYear() + 100, date.getMonth() , date.getDate());
				navigation.setLabelText(_obj.getLabelText());
				_obj.update();
			}
		};
		
		DecadePicker.prototype.getLabelText = function() {
			var fullYear = this.datePicker.date.getFullYear();
			var startYear = fullYear - (fullYear % 100);
			return startYear + " - " + (startYear + 99);
		};
		
		DecadePicker.prototype.show = function() {
			this.jqElem.show();
			this.update();
		};
		
		n2n.DatePicker = DatePicker;
	})(window.n2n);
	
	var initFunction = function() {
		$(".util-jquery-datepicker").each(function() {
			var elem = $(this);
			if (elem.data("initialized-util-jquery-datepicker")) return;
			elem.data("initialized-util-jquery-datepicker", true);
			new window.n2n.DatePicker(elem);
		});
	};
	
	if (n2n != null) {
		n2n.dispatch.registerCallback(initFunction);
	}
	
	initFunction();
	
	if (typeof Jhtml !== 'undefined') {
		Jhtml.ready(function (elements) {
			$(elements).find(".util-jquery-datepicker").each(function () {
				var elem = $(this);
				if (elem.data("initialized-util-jquery-datepicker")) return;
				elem.data("initialized-util-jquery-datepicker", true);
				new window.n2n.DatePicker(elem);
			});
		});

		
	} else {
		$(document).ready(function() {
			$(".util-jquery-datepicker").each(function () {
				var elem = $(this);
				if (elem.data("initialized-util-jquery-datepicker")) return;
				elem.data("initialized-util-jquery-datepicker", true);
				new window.n2n.DatePicker(elem);
			});
		});
	}
})(jQuery);

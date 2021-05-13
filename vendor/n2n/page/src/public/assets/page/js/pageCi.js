//jQuery(document).ready(function($) {
//	return;
//	(function() {
//		var PageCi = function(jqElemSelect, jqElemContentItemOption) {
//			this.jqElemSelect = jqElemSelect;
//			this.initialValue = jqElemSelect.val();
//			this.jqElemContentItemOption = jqElemContentItemOption;
//			this.panelDataCollection = jqElemSelect.data("panels");
//			this.contentItemOption = null;
//			this.init();
//		};
//		
//		PageCi.prototype.init = function () {
//			var _obj = this;
//			this.jqElemSelect.change(function() {
//				var jqElem = $(this);
//				if (!jqElem.is(":visible")) return;
//				var contentItemOption = _obj.getContentItemOption();
//				if (null == this.cachedPanelsManager) {
//					this.cachedPanelsManager = new Object();
//					this.cachedPanelsManager[_obj.initialValue] = _obj.determinePanels(_obj.initialValue);
//				}
//				var ciSelector = jqElem.val();
//				if (null == this.cachedPanelsManager[ciSelector]) {
//					this.cachedPanelsManager[ciSelector] = _obj.determinePanels(ciSelector);
//				} else {
//					_obj.purifyPanels(this.cachedPanelsManager[ciSelector], _obj.panelDataCollection[ciSelector]);
//				}
//				contentItemOption.setPanels(this.cachedPanelsManager[ciSelector]);
//			});
//			$(window).load(function() {
//				rocket.core.unsavedFormManager.listening = false;
//				_obj.jqElemSelect.change();
//				rocket.core.unsavedFormManager.listening = true;
//			});
//		};
//			
//		PageCi.prototype.determinePanels = function(ciSelector) {
//			var panels = new Object();
//			var newPanelData = this.panelDataCollection[ciSelector];
//			var contentItemOption = this.getContentItemOption();
//			for (var key in newPanelData) {
//				var singlePanelData = newPanelData[key];
//				var oldPanel = contentItemOption.panels[key];
//				if (null != oldPanel) {
//					this.purifySinglePanel(oldPanel, singlePanelData);
//					panels[key] = oldPanel;
//				} else {
//					var cio = this.getContentItemOption();
//					var contentItemTypes = singlePanelData.allowedContentItemIds; 
//					if (contentItemTypes.length === 0) {
//						contentItemTypes = cio.availableContentItemTypes;
//					}
//					panels[key] = new rocket.state.ContentItemPanel(key, singlePanelData["label"], contentItemTypes, cio); 
//				}
//			}
//			return panels;
//		};
//		
//		PageCi.prototype.purifyPanels = function(panels, panelData) {
//			for (var i in panels) {
//				this.purifySinglePanel(panels[i], panelData[i]);
//			}
//		}
//		
//		PageCi.prototype.purifySinglePanel = function(panel, singlePanelData) {
//			var contentItemTypes = singlePanelData.allowedContentItemIds; 
//			if (contentItemTypes.length === 0) {
//				contentItemTypes = this.getContentItemOption().availableContentItemTypes;
//			}
//			panel.jqElemUl.children("li.rocket-controls").each(function() {
//				var jqElemLi = $(this);
//				if (jqElemLi.hasClass("rocket-current")) return;
//				var type = jqElemLi.find(".rocket-script-type-selection:first").val();
//				var allowed = false;
//				for (var i in contentItemTypes) {
//					if (contentItemTypes[i] === type) {
//						allowed = true;
//						break;
//					}
//				}
//				if (!allowed) {
//					//remove ContentItem
//					jqElemLi.find(".rocket-content-item-remove:first").click();
//				}
//			});
//
//			panel.contentItemTypes = contentItemTypes;
//			panel.jqElemLabel.text(singlePanelData["label"]);
//		};
//			
//		PageCi.prototype.getContentItemOption = function() {
//			if (null === this.contentItemOption) {
//				for(var i in rocket.state.contentItemOptions) {
//			
//					var contentItemOption = rocket.state.contentItemOptions[i];
//					if (contentItemOption.jqElemLi.is(this.jqElemContentItemOption)) {
//						this.contentItemOption = contentItemOption; 
//					}
//				}
//			}
//			return this.contentItemOption;
//		}
//		
//		var initPagCi = function(jqElem) {
//			jqElem.find("select.page-content-item-refresh").each(function() {
//				var jqElem = $(this), jqElemContentItemOption;
//				if (jqElem.data('init-page-ci')) return;
//				jqElem.data('init-page-ci', true);
//				var entryForm = jqElem.parents(".rocket-type-dependent-entry-form:first");
//				new PageCi(jqElem, jqElem.parents(".rocket-type-dependent-entry-form:first")
//						.find("> .rocket-script-type-page-display-page li.rocket-content-item-option:first"));
//			});
//		};
//		
//		initPagCi($("body"));
//		
//		if (typeof rocket !== 'undefined') {
//			rocket.core.contentInitializer.registerInitFunction(function(jqElem) {
//				initPagCi(jqElem);
//			});
//		}
//		
//		$("#rocket-form-entryForm-selectedTypeId").change(function() {
//			$("select.page-content-item-refresh").change();
//		});
//	})();
//});
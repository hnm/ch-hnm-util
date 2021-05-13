jQuery(document).ready(function($) {
//	var PageContent = function(jqElem) {
//		this.jqElemSsl = jqElem.siblings(".rocket-gui-field-pageContent-ssl:first");
//		this.jqElemSubsystemName = jqElem.siblings(".rocket-gui-field-pageContent-subsystemName:first");
//		this.jqElemPage = jqElem.siblings(".rocket-gui-field-pageContent-page:first");
//		this.jqElemSeTitle = jqElem.siblings(".rocket-gui-field-pageContent-pageContentTs-seTitle:first");
//		this.jqElemSeDescription = jqElem.siblings(".rocket-gui-field-pageContent-pageContentTs-seDescription:first");
//		this.jqElemSeKeywords = jqElem.siblings(".rocket-gui-field-pageContent-pageContentTs-seKeywords:first");
//	};
//	
//	PageContent.prototype.isComplete = function() {
//		return this.jqElemPage.length > 0 && this.jqElemSeTitle.length > 0 &&
//				this.jqElemSeDescription.length > 0 && this.jqElemSeKeywords.length > 0;
//	};
//	
//	PageContent.prototype.show = function() {
//		this.jqElemSsl.show();
//		this.jqElemSubsystemName.show();
//		this.jqElemPage.show();
//		this.jqElemSeTitle.show();
//		this.jqElemSeDescription.show();
//		this.jqElemSeKeywords.show();
//	};
//	
//	PageContent.prototype.hide = function() {
//		this.jqElemSsl.hide();
//		this.jqElemSubsystemName.hide();
//		this.jqElemPage.hide();
//		this.jqElemSeTitle.hide();
//		this.jqElemSeDescription.hide();
//		this.jqElemSeKeywords.hide();
//	};
	
	var PageConfigField = function(jqElem) {
		
		this.pageTypes = jqElem.data('page-types') || null;
		this.viewEiSpecId = jqElem.data("view-ei-spec-id");
		this.controllerEiSpecId = jqElem.data("controller-ei-spec-id");
		this.ciControllerEiSpecId = jqElem.data("ci-controller-ei-spec-id");
		
		this.jqElem = jqElem;
		this.jqElemExternalUrl = jqElem.siblings(".rocket-gui-field-externalUrl:first");
		this.jqElemInternalPage = jqElem.siblings(".rocket-gui-field-internalPage:first");
		this.jqElemSelect = jqElem.find("select:first");
		this.jqElemPageContent = jqElem.siblings(".rocket-gui-field-pageContent:first");
		//this.pageContent = new PageContent(jqElem);
		this.pageType = null;
		
		if (this.jqElemExternalUrl.length === 0 
				|| this.jqElemInternalPage.length === 0
				|| this.jqElemPageContent.length === 0
				|| this.jqElemSelect.length === 0) return;
		
		(function(that) {
			this.jqElemSelect.change(function() {
				that.jqElemExternalUrl.hide();
				that.jqElemInternalPage.hide();
				that.jqElemPageContent.hide();
				
				switch ($(this).val()) {
					case 'display':
						that.jqElemPageContent.show();
						return;
					case 'internal':
						that.jqElemInternalPage.show();
						return;
					case 'external':
						that.jqElemExternalUrl.show();
						return;
				}
			}).change();
			
			var jqElemPageContentToOne = this.jqElemPageContent.find(".rocket-to-one:first"),
				pageContentToOne = jqElemPageContentToOne.data('rocket-to-one') || null;
			if (null !== pageContentToOne) {
				that.initPageContentToOne(pageContentToOne);
			} else {
				jqElemPageContentToOne.on('initialized.toOne', function() {
					pageContentToOne = jqElemPageContentToOne.data('rocket-to-one') || null;
					if (null === pageContentToOne) return;
					
					that.initPageContentToOne(jqElemPageContentToOne.data('rocket-to-one'));
				});
			}
		}).call(this, this);
	};
	
	PageConfigField.prototype.initPageContentToOne = function(pageContentToOne) {
		pageContentToOne.setMandatory(true);
		var that = this, initPageType = function() {
			var elemGuiFieldPage = that.jqElemPageContent.find(".rocket-gui-field-page:first");
			if (null !== that.pageType) return;
			
			var elemToOne = elemGuiFieldPage.find("> .rocket-controls > .rocket-to-one");
			if (elemToOne.length === 0) return;
			
			that.pageType = new PageType(that, elemToOne);
		};
		
		initPageType();
		n2n.dispatch.registerCallback(initPageType);	
	};
	
	var PageType = function(pageConfigField, jqElemToOne) {
		this.pageConfigField = pageConfigField;
		this.jqElemToOne = jqElemToOne;
		this.toOne = this.jqElemToOne.data('rocket-to-one') || null;
		
		(function(that) {
			if (null === this.toOne) {
				this.jqElemToOne.on('initialized.toOne', function() {
					if (null !== that.toOne) return;
					
					that.toOne = that.jqElemToOne.data('rocket-to-one') || null;
					if (null === that.toOne) return;
					
					that.initTypes();
				});
			} else {
				this.initTypes();
			}
		}).call(this, this);
	};
	
	PageType.prototype.initTypes = function() {
		var toOneCurrent = this.toOne.getToOneCurrent();
		if (null !== toOneCurrent) {
			this.initCurrent(toOneCurrent);
		}
		
		this.initOptions("viewOptions", this.pageConfigField.viewEiSpecId, "rocket-gui-field-viewName");
		this.initOptions("controllerOptions", this.pageConfigField.controllerEiSpecId, "rocket-gui-field-controllerClassName");
		this.initOptions("ciControllerOptions", this.pageConfigField.ciControllerEiSpecId, "rocket-gui-field-controllerClassName");
	};
	
	PageType.prototype.initCurrent = function(toOneCurrent) {
		var eiSpecId = toOneCurrent.getEiSpecId();
		if (eiSpecId === this.pageConfigField.viewEiSpecId) {
			this.initLabel(toOneCurrent, "viewOptions", "rocket-gui-field-viewName");
			return;
		}
		
		if (eiSpecId === this.pageConfigField.controllerEiSpecId) {
			this.initLabel(toOneCurrent, "controllerOptions", "rocket-gui-field-controllerClassName");
			return;
		} 

		if (eiSpecId === this.pageConfigField.ciControllerEiSpecId) {
			this.initLabel(toOneCurrent, "ciControllerOptions", "rocket-gui-field-controllerClassName");
			return;
		}
	};
	
	PageType.prototype.initLabel = function(toOneCurrent, optionKey, 
			targetElementClassName) {
		var elemItemContainer = toOneCurrent.getElemContent().find("." + targetElementClassName).hide(),
			elemItem = elemItemContainer.find("input[type=text]:first"),
			optionIndex = elemItem.val(),
			label = optionIndex;
		if (null !== this.pageConfigField.pageTypes && 
				this.pageConfigField.pageTypes.hasOwnProperty(optionKey) &&
				this.pageConfigField.pageTypes[optionKey].hasOwnProperty(optionIndex)) {
			label = this.pageConfigField.pageTypes[optionKey][optionIndex];
		}
		this.toOne.setTypeSpecLabel(label);
	};
	
	PageType.prototype.initOptions = function(optionKey, 
			eiSpecId, targetElementClassName) {
		var eiSpecConfig = this.toOne.getOrCreateEiSpecConfig(eiSpecId);
		
		if (null === this.pageConfigField.pageTypes || !this.pageConfigField.pageTypes.hasOwnProperty(optionKey)) return;
		
		this.applyOptions(eiSpecConfig, optionKey, eiSpecId, targetElementClassName);
	};
	
	PageType.prototype.applyOptions = function(eiSpecConfig, optionKey, 
			eiSpecId, targetElementClassName, excludeElementName) {
		var that = this;
		excludeElementName = excludeElementName || null;
		eiSpecConfig.reset();
		
		$.each(this.pageConfigField.pageTypes[optionKey], function(elementName, label) {
			if (null !== excludeElementName && excludeElementName === elementName) return;
			
			eiSpecConfig.registerTypeConfig(label, function(toOne) {
				var toOneNew = toOne.getToOneNew(), 
					elemToOneNew = toOne.getToOneNew().getElem();
				elemToOneNew.off("applyEiSpecId.toOne").on("applyEiSpecId.toOne", function(e, selectedEiSpecId) {
					that.initTypes();
					if (eiSpecId === selectedEiSpecId) {
						var elemTargetContainer = elemToOneNew.find("." + targetElementClassName + ":first").hide();
						elemTargetContainer.find("input[type=text]:first").val(elementName);
						that.applyOptions(eiSpecConfig, optionKey, eiSpecId, targetElementClassName, elementName);
						that.toOne.setTypeSpecLabel(label);
					}
				});
			});
		});
	}
	
	var initialize = function() {
		$(".rocket-gui-field-type").each(function() {
			var jqElem = $(this);
			if (jqElem.data("initialized-page-type-config")) return;
			jqElem.data("initialized-page-type-config", true);
			
			new PageConfigField(jqElem);
		});
	};
	
	initialize();
	n2n.dispatch.registerCallback(initialize);	
});
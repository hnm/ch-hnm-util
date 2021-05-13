;jQuery(document).ready(function($) {
	"use strict";
	(function() {
		var initializeLinkType = function(jqElem) {
			var jqElemsLinkType = jqElem.find(".rocket-link-type-selector");
			if (jqElemsLinkType.length === 0) return;
			
			jqElemsLinkType.each(function() {
				var jqElemLinkType = $(this);
				if (jqElemLinkType.data("init.linkType")) return;
				var jqElemsHideIfNone = [],  
					jqElemRocketProperties = jqElemLinkType.parents(".rocket-properties:first"),
					jqElemInternal = jqElemRocketProperties.find(".rocket-field-" + jqElemLinkType.data("internal-field-id")),
					jqElemExternal = jqElemRocketProperties.find(".rocket-field-" + jqElemLinkType.data("external-field-id"));
				$.each(jqElemLinkType.data("hide-additional-field-ids"), function(index, value) {
					jqElemsHideIfNone.push(jqElemRocketProperties.find(".rocket-field-" + value));
				});
				jqElemLinkType.change(function() {
					var value = jqElemLinkType.val();
					switch (value)  {
						case 'internal':
							jqElemExternal.hide();
							jqElemInternal.show();
							$.each(jqElemsHideIfNone, function(index, jqElem) {
								jqElem.show();
							});
							break;
						case 'external':
							jqElemInternal.hide();
							jqElemExternal.show();
							$.each(jqElemsHideIfNone, function(index, jqElem) {
								jqElem.show();
							});
							break;
						default:
							jqElemInternal.hide();
							jqElemExternal.hide();
							$.each(jqElemsHideIfNone, function(index, jqElem) {
								jqElem.hide();
							});
					}
				}).change();
				jqElemLinkType.data("init.linkType", true);
			});
		}
		rocket.core.contentInitializer.registerInitFunction(function(jqElem) {
			initializeLinkType(jqElem);
		});
		initializeLinkType($("body"));
	})();
	
	(function() {
		var jqElemsLinkTypeDescriber = $(".rocket-link-type-describer");
		
		var LinkTypeDescriber = function (jqElem) {
			if (!jqElem.data("hide-elements")) return;
			(function(_obj) {
				var jqElemRocketProperties = jqElem.parents(".rocket-properties:first"),
					jqElemInternal = jqElemRocketProperties.find(".rocket-field-" + jqElem.data("internal-field-id")),
					jqElemExternal = jqElemRocketProperties.find(".rocket-field-" + jqElem.data("external-field-id")),
					type = jqElem.data('type');
				jqElemInternal.hide();
				jqElemExternal.hide();
				if (type === 'internal') {
					jqElemInternal.show();
				} else if (type === 'external') {
					jqElemExternal.show();
				} else {
					$.each(jqElem.data("hide-additional-field-ids"), function(index, value) {
						jqElemRocketProperties.find(".rocket-field-" + value).hide();
					});
				}
				if (jqElem.data('hide')) {
					jqElem.parents(".rocket-field-link:first").hide();
				}
			}).call(this, this);
		};
		
		jqElemsLinkTypeDescriber.each(function() {
			new LinkTypeDescriber($(this));
		});
	})();
});
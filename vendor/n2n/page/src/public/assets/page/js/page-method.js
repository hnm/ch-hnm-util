/**
 * 
 */

Jhtml.ready(function (elements) {
	$(elements).find("select.page-method").each(function() {
		var jqSelect = $(this);
		var methodPanelNames = jqSelect.data('panel-names');
		var jqCiContainer = jqSelect.parent().parent().parent().find("div.rocket-field.rocket-gui-field-pageControllerTs-contentItems");
		var jqCiDivs = jqCiContainer.find("div.rocket-impl-content-items > div.rocket-impl-content-item-panel");
		
		var restrictCiPanels = function () {
			var methodName = jqSelect.val();
			var panelNames = methodPanelNames[methodName];
			var display = panelNames.length > 0;
			jqCiDivs.each(function () {
				var jqCiDiv = $(this);
				
				//exclude content items in content items - only regard content items in page content item panels
				if (jqCiDiv.parents("div.rocket-impl-content-item-panel").length > 0) return;
				
				var panelName = jqCiDiv.data("name");
				if (0 <= panelNames.indexOf(panelName)) {
					jqCiDiv.show();
					display = true;
				} else {
					jqCiDiv.hide();
				}
			});
			
			if (display) {
				jqCiContainer.show();
			} else {
				jqCiContainer.hide();
			}
		};
		
		restrictCiPanels();
		
		jqSelect.off("change");
		jqSelect.on("change", restrictCiPanels);
		
		if (Object.keys(methodPanelNames).length == 1) {
			jqSelect.parent().parent().hide();
		}
	});
});

//$("select.page-method").parent().parent().parent().children("div.rocket-property").children("div").children("div.rocket-properties").children("div.rocket-property").children("div").children("div.rocket-content-items").children("div.rocket-content-item-panel").size()
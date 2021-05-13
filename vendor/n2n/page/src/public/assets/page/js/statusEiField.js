/**
 * 
 */

jQuery(document).ready(function ($) {
	
	var PageStatusField = function(jqElemA) {
		this.jqElemA = jqElemA;
		this.jqElemIcon = jqElemA.children("i:first");
		this.jqElemSpan = jqElemA.children("span:first");
		
		this.status = jqElemA.data("status");
		
		
		this.onlineLabel = jqElemA.data("online-label"); 
		this.onlineTooltip = jqElemA.data("online-tooltip");
		this.offlineLabel = jqElemA.data("offline-label"); 
		this.offlineTooltip = jqElemA.data("offline-tooltip");
		this.visibleLabel = jqElemA.data("visible-label"); 
		this.visibleTooltip = jqElemA.data("visible-tooltip");
		
		this.onlineUrl = jqElemA.data("online-url");
		this.offlineUrl = jqElemA.data("offline-url");
		this.visibleUrl = jqElemA.data("visible-url");
		
		(function(that) {
			that.jqElemA.click(function() {
				that.changeStatus();
			});
		}).call(this, this);
	};
	
	PageStatusField.prototype.changeStatus = function() {
		if (this.jqElemA.hasClass("rocket-loading")) return;
		
		this.jqElemIcon.removeClass().addClass("fa fa-spinner fa-spin");
		this.jqElemA.removeClass("rocket-control-success rocket-control-danger").addClass("rocket-loading");
		
		var that = this;
		switch (this.status) {
			case 'visible':
				$.get(this.offlineUrl, function() {
					that.jqElemIcon.removeClass().addClass("fa fa-times");
					that.jqElemA.removeClass("rocket-loading").addClass("rocket-control-danger")
							.attr("title", that.offlineTooltip);
					that.jqElemSpan.text(that.offlineLabel);
					that.status = 'offline';
				});
				break;
			case 'online':
				$.get(this.visibleUrl, function() {
					that.jqElemIcon.removeClass().addClass("fa fa-eye");
					that.jqElemA.removeClass("rocket-loading").addClass("rocket-control-success")
							.attr("title", that.visibleTooltip);
					that.jqElemSpan.text(that.visibleLabel);
					that.status = 'visible';
				});
				break;
			case 'offline':
				$.get(this.onlineUrl, function() {
					that.jqElemIcon.removeClass().addClass("fa fa-check-circle");
					that.jqElemA.removeClass("rocket-loading").addClass("rocket-control-success")
							.attr("title", that.onlineToolTip);
					that.jqElemSpan.text(that.onlineLabel);
					that.status = 'online';
				});
				break;
		}
	};
	
	rocketTs.ready(function() {
		rocketTs.registerUiInitFunction("a.page-status-cmd", function(jqElemA) {
			new PageStatusField(jqElemA);
		});
	});
});
(function () {
	var boolCallback = function () {
		boolUpdateToggler(this);
	};

	var boolToggler = function () {
		var togglerElems = document.getElementsByClassName("n2n-impl-web-dispatch-toggler");
		
		for (var i = 0, ii = togglerElems.length; i < ii; i++) {
			togglerElems[i].removeEventListener("click", boolCallback);
			togglerElems[i].addEventListener("click", boolCallback);
			
			boolUpdateToggler(togglerElems[i]);
		}
	};

	function boolUpdateToggler(elem) {
		var showGroupElems = null;
		var hideGroupElems = null;
		var onGroupElems = document.getElementsByClassName(elem.getAttribute("data-n2n-impl-web-dispatch-toggler-on-class"))
		var offGroupElems = document.getElementsByClassName(elem.getAttribute("data-n2n-impl-web-dispatch-toggler-off-class"))
		
		if (elem.checked) {
			showGroupElems = onGroupElems;
			hideGroupElems = offGroupElems;
		} else {
			showGroupElems = offGroupElems;
			hideGroupElems = onGroupElems;
		}
		
		for (var i = 0, ii = hideGroupElems.length; i < ii; i++) {
			hideGroupElems[i].style.display = "none";
		}
		
		for (var i = 0, ii = showGroupElems.length; i < ii; i++) {
			showGroupElems[i].style.display = null;
		}
	}
	
	if (document.readyState === "complete" || document.readyState === "interactive") {
		boolToggler();
	} else {
		document.addEventListener("DOMContentLoaded", boolToggler);
	}
	
	if (n2n.dispatch) {
		n2n.dispatch.registerCallback(boolToggler);
	}
	
	if (window.Jhtml) {
		Jhtml.ready(boolToggler);
	}

	var enumCallback = function () {
		enumUpdateToggler(this);
	};

	var enumTogglerFunc = function () {
		var togglerElems = document.getElementsByClassName("n2n-impl-web-dispatch-enum-toggler");
		
		for (var i = 0, ii = togglerElems.length; i < ii; i++) {
			togglerElems[i].removeEventListener("change", enumCallback);
			togglerElems[i].addEventListener("change", enumCallback);

			enumUpdateToggler(togglerElems[i]);
		}
	};

	function enumUpdateToggler(elem) {
		var value = elem.value;
		var groupClassName = elem.getAttribute("data-n2n-impl-web-dispatch-toggler-class")

		var groupElems = document.getElementsByClassName(groupClassName);
		for (var i = 0, ii = groupElems.length; i < ii; i++) {
			groupElems[i].style.display = "none"
		}

		var groupElems = document.getElementsByClassName(groupClassName + "-" + elem.value);
		for (var i = 0, ii = groupElems.length; i < ii; i++) {
			groupElems[i].style.display = null
		}
	}

	if (document.readyState === "complete" || document.readyState === "interactive") {
		enumTogglerFunc();
	} else {
		document.addEventListener("DOMContentLoaded", enumTogglerFunc);
	}

	if (n2n.dispatch) {
		n2n.dispatch.registerCallback(enumTogglerFunc);
	}

	if (window.Jhtml) {
		Jhtml.ready(enumTogglerFunc);
	}
})();
(function () {
	function MagCollection(elem, adderClassName, removerClassName, showCount = 0) {
		this.shownElems = [];
		this.hiddenElems = [];
		this.removerClassName = removerClassName;
		this.adderBtn = elem.getElementsByClassName(adderClassName)[0];
		this.hiddenElemTemplate = document.createElement("template");
		this.collectionItemContainer = elem.getElementsByClassName("n2n-impl-web-dispatch-mag-collection-items")[0];

		this.init(showCount);
	}

	MagCollection.prototype.createRemoveBtnClosure = function(item) {
		var that = this;
		return function(e) {
			that.hiddenElems.push(item);
			that.update();
			e.stopPropagation();
			return false;
		};
	}

	MagCollection.prototype.init = function(showCount) {
		var that = this;
		this.adderBtn.onclick = function (e) {
			var elem = that.hiddenElems.pop();
			that.shownElems.push(elem);
			that.update();
			e.stopPropagation();
			return false;
		};


		var collectionItemElems = [].slice.call(this.collectionItemContainer
			.getElementsByClassName("n2n-impl-web-dispatch-mag-collection-item"));

		for (var i in collectionItemElems) {
			var item = collectionItemElems[i];
			var removerBtn = item.getElementsByClassName(this.removerClassName)[0];
			removerBtn.onclick = this.createRemoveBtnClosure(item);

			if (showCount <= 0) {
				this.hiddenElems.push(item);
			} else {
				showCount--;
				this.shownElems.push(item);
			}
		}

		this.update();
	}

	MagCollection.prototype.update = function() {
		if (this.hiddenElems.length === 0) {
			this.adderBtn.style.display = "none";
		}

		if (this.hiddenElems.length > 0) {
			this.adderBtn.style.display = "block";
		}

		for (var i in this.shownElems) {
			var item = this.shownElems[i];
			if (-1 < [].slice.call(this.collectionItemContainer.children).indexOf(item)) continue;
			this.collectionItemContainer.append(item);
		}

		for (var i in this.hiddenElems) {
			var item = this.hiddenElems[i];
			if (-1 < [].slice.call(this.collectionItemContainer.children).indexOf(item)) {
				this.hiddenElemTemplate.append(item);
			}
		}
	}

	var init = function (elements) {
		var collectionElements = [],
			i;
		for (i in elements) {
			var elem = elements[i],
				collectionElemsArr = [].slice.call(elem.getElementsByClassName("n2n-impl-web-dispatch-mag-collection"));
			collectionElements = collectionElements.concat(collectionElemsArr);
		}

		for (i in collectionElements) {
			var collectionElem = collectionElements[i],
				adderClass = collectionElem.dataset.magCollectionItemAdderClass,
				removerClass = collectionElem.dataset.magCollectionItemRemoverClass,
				showCount = collectionElem.dataset.magCollectionShowCount;

			new MagCollection(collectionElem, adderClass, removerClass, showCount);
		};
	}

	// setTimeout is used to make sure the function is placed at the end of the javascript execution queue.
	if (window.Jhtml) {
		Jhtml.ready(function (elements) {
			setTimeout(function () { init(elements); });
		});
	} else if (document.readyState === "complete" || document.readyState === "interactive") {
		setTimeout(function () { init([document.documentElement]); });
	} else {
		document.addEventListener("DOMContentLoaded", function () {
			setTimeout(function () { init([document.documentElement]); });
		});
	}
})();

var enumEnablerFunc = function () {
	var enablerElems = document.getElementsByClassName("n2n-impl-web-dispatch-enum-enabler");

	for (var i = 0, ii = enablerElems.length; i < ii; i++) {
		enablerElems[i].removeEventListener("change", enumCallback);
		enablerElems[i].addEventListener("change", enumCallback);

		enumUpdateEnabler(enablerElems[i]);
	}
};

function enumUpdateEnabler(elem) {
	var value = elem.value;
	var groupClassName = elem.getAttribute("data-n2n-impl-web-dispatch-enabler-class")

	var groupElems = document.getElementsByClassName(groupClassName);
	for (var i = 0, ii = groupElems.length; i < ii; i++) {
		groupElems[i].style.display = "none"
	}

	var groupElems = document.getElementsByClassName(groupClassName + "-" + elem.value);
	for (var i = 0, ii = groupElems.length; i < ii; i++) {
		groupElems[i].style.display = "block"
	}
}
(function () {
	if (window.Jhtml) {
		Jhtml.ready(function (elements) {
			enumEnablerFunc(elements);
		});
	} else if (document.readyState === "complete" || document.readyState === "interactive") {
		enumEnablerFunc([document.documentElement]);
	} else {
		document.addEventListener("DOMContentLoaded", enumEnablerFunc([document.documentElement]));
	}
})();
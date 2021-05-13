var __extends = (this && this.__extends) || (function () {
    var extendStatics = Object.setPrototypeOf ||
        ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
        function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var Jhtml;
(function (Jhtml) {
    function ready(callback, document) {
        return getOrCreateContext(document).onReady(callback);
    }
    Jhtml.ready = ready;
    var browser = null;
    var monitor = null;
    function getOrCreateBrowser() {
        if (browser)
            return browser;
        var context = getOrCreateContext();
        if (!context.isBrowsable())
            return null;
        var history = new Jhtml.History();
        browser = new Jhtml.Browser(window, history);
        monitor = Jhtml.Monitor.create(context.document.documentElement, history, false);
        return browser;
    }
    Jhtml.getOrCreateBrowser = getOrCreateBrowser;
    function getOrCreateMonitor() {
        getOrCreateBrowser();
        return monitor;
    }
    Jhtml.getOrCreateMonitor = getOrCreateMonitor;
    function getOrCreateContext(document) {
        return Jhtml.Context.from(document || window.document);
    }
    Jhtml.getOrCreateContext = getOrCreateContext;
    function lookupModel(url) {
        getOrCreateBrowser();
        if (monitor) {
            return monitor.lookupModel(Jhtml.Url.create(url));
        }
        return new Promise(function (resolve) {
            getOrCreateContext().requestor.exec("GET", Jhtml.Url.create(url)).send().then(function (response) {
                resolve({ model: response.model, response: response });
            });
        });
    }
    Jhtml.lookupModel = lookupModel;
    function request(method, url) {
        return getOrCreateContext().requestor.exec(method, Jhtml.Url.create(url));
    }
    Jhtml.request = request;
    window.document.addEventListener("DOMContentLoaded", function () {
        getOrCreateBrowser();
    }, false);
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Browser = (function () {
        function Browser(window, _history) {
            var _this = this;
            this.window = window;
            this._history = _history;
            this.poping = false;
            var entry = _history.push(new Jhtml.Page(Jhtml.Url.create(window.location.href), null));
            _history.onPush(function (entry) {
                _this.onPush(entry);
            });
            _history.onChanged(function (evt) {
                _this.onChanged(evt);
            });
            this.window.history.replaceState(this.buildStateObj(entry), "Page", entry.page.url.toString());
            this.window.onpopstate = function (evt) {
                _this.onPopstate(evt);
            };
        }
        Object.defineProperty(Browser.prototype, "history", {
            get: function () {
                return this._history;
            },
            enumerable: true,
            configurable: true
        });
        Browser.prototype.onPopstate = function (evt) {
            var url = Jhtml.Url.create(this.window.location.toString());
            var index = 0;
            if (evt.state && evt.state.jhtmlHistoryIndex !== undefined) {
                index = evt.state.jhtmlHistoryIndex;
            }
            try {
                this.poping = true;
                this.history.go(index, url);
                this.poping = false;
            }
            catch (e) {
                this.window.location.href = url.toString();
            }
        };
        Browser.prototype.onChanged = function (evt) {
            if (this.poping || evt.pushed)
                return;
            this.window.history.go(evt.indexDelta);
        };
        Browser.prototype.onPush = function (entry) {
            this.window.history.pushState(this.buildStateObj(entry), "Page", entry.page.url.toString());
        };
        Browser.prototype.buildStateObj = function (entry) {
            return {
                "jhtmlUrl": entry.page.url.toString(),
                "jhtmlHistoryIndex": entry.index
            };
        };
        return Browser;
    }());
    Jhtml.Browser = Browser;
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var History = (function () {
        function History() {
            this._currentIndex = null;
            this._entries = [];
            this.changeCbr = new Jhtml.Util.CallbackRegistry();
            this.changedCbr = new Jhtml.Util.CallbackRegistry();
            this.pushCbr = new Jhtml.Util.CallbackRegistry();
        }
        Object.defineProperty(History.prototype, "currentIndex", {
            get: function () {
                return this._currentIndex;
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(History.prototype, "currentEntry", {
            get: function () {
                if (this._entries[this._currentIndex]) {
                    return this._entries[this._currentIndex];
                }
                return null;
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(History.prototype, "currentPage", {
            get: function () {
                var entry;
                if (entry = this.currentEntry) {
                    return entry.page;
                }
                return null;
            },
            enumerable: true,
            configurable: true
        });
        History.prototype.getEntryByIndex = function (index) {
            if (this._entries[index]) {
                return this._entries[index];
            }
            return null;
        };
        History.prototype.getPageByUrl = function (url) {
            for (var _i = 0, _a = this._entries; _i < _a.length; _i++) {
                var entry = _a[_i];
                if (!entry.page.url.equals(url))
                    continue;
                return entry.page;
            }
            return null;
        };
        History.prototype.onChange = function (callback) {
            this.changeCbr.on(callback);
        };
        History.prototype.offChange = function (callback) {
            this.changeCbr.off(callback);
        };
        History.prototype.onChanged = function (callback) {
            this.changedCbr.on(callback);
        };
        History.prototype.offChanged = function (callback) {
            this.changedCbr.off(callback);
        };
        History.prototype.onPush = function (callback) {
            this.pushCbr.on(callback);
        };
        History.prototype.offPush = function (callback) {
            this.pushCbr.off(callback);
        };
        History.prototype.go = function (index, checkUrl) {
            if (!this._entries[index]) {
                throw new Error("Unknown history entry index " + index + ". Check url: " + checkUrl);
            }
            if (checkUrl && !this._entries[index].page.url.equals(checkUrl)) {
                throw new Error("Check url does not match with page of history entry index " + index + " dow: "
                    + checkUrl + " != " + this._entries[index].page.url);
            }
            if (this._currentIndex == index)
                return;
            var evt = { pushed: false, indexDelta: (index - this._currentIndex) };
            this.changeCbr.fire(evt);
            this._currentIndex = index;
            this.changedCbr.fire(evt);
        };
        History.prototype.getFirstIndexOfPage = function (page) {
            return this._entries.findIndex(function (entry) {
                return entry.page === page;
            });
        };
        History.prototype.push = function (page) {
            var sPage = this.getPageByUrl(page.url);
            if (sPage && sPage !== page) {
                throw new Error("Page with same url already registered.");
            }
            var evt = { pushed: true, indexDelta: 1 };
            this.changeCbr.fire(evt);
            var nextI = (this._currentIndex === null ? 0 : this._currentIndex + 1);
            for (var i = nextI; i < this._entries.length; i++) {
                var iPage = this._entries[i].page;
                if (nextI >= this.getFirstIndexOfPage(iPage))
                    continue;
                iPage.dispose();
            }
            this._entries.splice(nextI);
            this._currentIndex = nextI;
            var entry = new History.Entry(this._currentIndex, page);
            this._entries.push(entry);
            this.pushCbr.fire(entry);
            this.changedCbr.fire(evt);
            return entry;
        };
        return History;
    }());
    Jhtml.History = History;
    (function (History) {
        var Entry = (function () {
            function Entry(_index, _page) {
                this._index = _index;
                this._page = _page;
                this.scrollPos = 0;
            }
            Object.defineProperty(Entry.prototype, "index", {
                get: function () {
                    return this._index;
                },
                enumerable: true,
                configurable: true
            });
            Object.defineProperty(Entry.prototype, "page", {
                get: function () {
                    return this._page;
                },
                enumerable: true,
                configurable: true
            });
            return Entry;
        }());
        History.Entry = Entry;
    })(History = Jhtml.History || (Jhtml.History = {}));
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Page = (function () {
        function Page(_url, promise) {
            this._url = _url;
            this._promise = null;
            this._loaded = false;
            this._config = new Page.Config();
            this.cbr = new Jhtml.Util.CallbackRegistry();
            this.loadUrl = null;
            this.promise = promise;
        }
        Object.defineProperty(Page.prototype, "config", {
            get: function () {
                return this._config;
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(Page.prototype, "loaded", {
            get: function () {
                return this._loaded;
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(Page.prototype, "url", {
            get: function () {
                return this._url;
            },
            enumerable: true,
            configurable: true
        });
        Page.prototype.dispose = function () {
            this.promise = null;
        };
        Page.prototype.fire = function (eventType) {
            this.cbr.fireType(eventType);
        };
        Page.prototype.on = function (eventType, callback) {
            this.cbr.onType(eventType, callback);
        };
        Page.prototype.off = function (eventType, callback) {
            this.cbr.offType(eventType, callback);
        };
        Object.defineProperty(Page.prototype, "promise", {
            get: function () {
                return this._promise;
            },
            set: function (promise) {
                var _this = this;
                if (this._promise === promise)
                    return;
                if (this._promise) {
                    this._promise.then(function (directive) {
                        directive.destroy();
                    });
                }
                this._promise = promise;
                if (!promise) {
                    this.fire("disposed");
                    return;
                }
                this._loaded = false;
                promise.then(function () {
                    _this._loaded = true;
                });
                this.fire("promiseAssigned");
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(Page.prototype, "disposed", {
            get: function () {
                return this.promise ? false : true;
            },
            enumerable: true,
            configurable: true
        });
        return Page;
    }());
    Jhtml.Page = Page;
    (function (Page) {
        var Config = (function () {
            function Config() {
                this.frozen = false;
                this.keep = false;
                this.scrollPos = 0;
            }
            return Config;
        }());
        Page.Config = Config;
    })(Page = Jhtml.Page || (Jhtml.Page = {}));
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Context = (function () {
        function Context(_document) {
            var _this = this;
            this._document = _document;
            this.compHandlers = {};
            this.readyCbr = new Jhtml.Util.CallbackRegistry();
            this.readyBound = false;
            this._requestor = new Jhtml.Requestor(this);
            this._document.addEventListener("DOMContentLoaded", function () {
                _this.readyCbr.fire([_this.document.documentElement], {});
            }, false);
        }
        Object.defineProperty(Context.prototype, "requestor", {
            get: function () {
                return this._requestor;
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(Context.prototype, "document", {
            get: function () {
                return this._document;
            },
            enumerable: true,
            configurable: true
        });
        Context.prototype.isJhtml = function () {
            return this.getModelState(false) ? true : false;
        };
        Context.prototype.isBrowsable = function () {
            if (!this.isJhtml())
                return false;
            return this.getModelState(false).metaState.browsable;
        };
        Context.prototype.getModelState = function (required) {
            if (this.modelState) {
                return this.modelState;
            }
            try {
                this.modelState = Jhtml.ModelFactory.createStateFromDocument(this.document);
                if (this.modelState.metaState.browsable) {
                    Jhtml.Ui.Scanner.scan(this.document.documentElement);
                }
            }
            catch (e) {
                if (e instanceof Jhtml.ParseError)
                    return null;
                throw e;
            }
            if (!this.modelState && required) {
                throw new Error("No jhtml context");
            }
            return this.modelState || null;
        };
        Context.prototype.replaceModel = function (newModel, montiorCompHandlers) {
            var _this = this;
            if (montiorCompHandlers === void 0) { montiorCompHandlers = {}; }
            var boundModelState = this.getModelState(true);
            var mergeObserver = boundModelState.metaState.replaceWith(newModel.meta);
            mergeObserver.done(function () {
                for (var name_1 in boundModelState.comps) {
                    var comp = boundModelState.comps[name_1];
                    if (!(montiorCompHandlers[name_1] && montiorCompHandlers[name_1].detachComp(comp))
                        && !(_this.compHandlers[name_1] && _this.compHandlers[name_1].detachComp(comp))) {
                        comp.detach();
                    }
                }
                if (!boundModelState.container.matches(newModel.container)) {
                    boundModelState.container.detach();
                    boundModelState.container = newModel.container;
                    boundModelState.container.attachTo(boundModelState.metaState.containerElement);
                }
                for (var name_2 in newModel.comps) {
                    var comp = boundModelState.comps[name_2] = newModel.comps[name_2];
                    if (!(montiorCompHandlers[name_2] && montiorCompHandlers[name_2].attachComp(comp))
                        && !(_this.compHandlers[name_2] && _this.compHandlers[name_2].attachComp(comp))) {
                        comp.attachTo(boundModelState.container.compElements[name_2]);
                    }
                }
            });
        };
        Context.prototype.importMeta = function (meta) {
            var boundModelState = this.getModelState(true);
            var loadObserver = boundModelState.metaState.import(meta, true);
            return loadObserver;
        };
        Context.prototype.registerNewModel = function (model) {
            var _this = this;
            var container = model.container;
            if (container) {
                var containerReadyCallback_1 = function () {
                    container.off("attached", containerReadyCallback_1);
                    console.log("container attached");
                    _this.readyCbr.fire(container.elements, { container: container });
                    _this.triggerAndScan(container.elements);
                };
                container.on("attached", containerReadyCallback_1);
            }
            var _loop_1 = function (comp) {
                var compReadyCallback = function () {
                    comp.off("attached", compReadyCallback);
                    _this.readyCbr.fire(comp.elements, { comp: Jhtml.Comp });
                    _this.triggerAndScan(comp.elements);
                };
                comp.on("attached", compReadyCallback);
            };
            for (var _i = 0, _a = Object.values(model.comps); _i < _a.length; _i++) {
                var comp = _a[_i];
                _loop_1(comp);
            }
            var snippet = model.snippet;
            if (snippet) {
                var snippetReadyCallback_1 = function () {
                    snippet.off("attached", snippetReadyCallback_1);
                    _this.importMeta(model.meta).whenLoaded(function () {
                        _this.readyCbr.fire(snippet.elements, { snippet: snippet });
                        _this.triggerAndScan(snippet.elements);
                    });
                };
                snippet.on("attached", snippetReadyCallback_1);
            }
        };
        Context.prototype.triggerAndScan = function (elements) {
            var w = window;
            if (w.n2n && w.n2n.dispatch) {
                w.n2n.dispatch.update();
            }
            Jhtml.Ui.Scanner.scanArray(elements);
        };
        Context.prototype.replace = function (text, mimeType, replace) {
            this.document.open(mimeType, replace ? "replace" : null);
            this.document.write(text);
            this.document.close();
        };
        Context.prototype.registerCompHandler = function (compName, compHandler) {
            this.compHandlers[compName] = compHandler;
        };
        Context.prototype.unregisterCompHandler = function (compName) {
            delete this.compHandlers[compName];
        };
        Context.prototype.onReady = function (readyCallback) {
            this.readyCbr.on(readyCallback);
            if ((this._document.readyState === "complete" || this._document.readyState === "interactive")
                && (!this.modelState || !this.modelState.metaState.busy)) {
                readyCallback([this.document.documentElement], {});
            }
        };
        Context.prototype.offReady = function (readyCallback) {
            this.readyCbr.off(readyCallback);
        };
        Context.test = function (document) {
            var context = Jhtml.Util.getElemData(document.documentElement, Context.KEY);
            if (context instanceof Context) {
                return context;
            }
            return null;
        };
        Context.from = function (document) {
            var context = Context.test(document);
            if (context)
                return context;
            Jhtml.Util.bindElemData(document.documentElement, Context.KEY, context = new Context(document));
            return context;
        };
        Context.KEY = "data-jhtml-context";
        return Context;
    }());
    Jhtml.Context = Context;
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Merger = (function () {
        function Merger(rootElem, headElem, bodyElem, currentContainerElem, newContainerElem) {
            this.rootElem = rootElem;
            this.headElem = headElem;
            this.bodyElem = bodyElem;
            this.currentContainerElem = currentContainerElem;
            this.newContainerElem = newContainerElem;
            this._loadObserver = new Jhtml.LoadObserver();
            this._processedElements = [];
            this._blockedElements = [];
            this.removableElements = [];
            this.removableAttrs = [];
        }
        Object.defineProperty(Merger.prototype, "loadObserver", {
            get: function () {
                return this._loadObserver;
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(Merger.prototype, "processedElements", {
            get: function () {
                return this._processedElements;
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(Merger.prototype, "remainingElements", {
            get: function () {
                var _this = this;
                return this.removableElements.filter(function (removableElement) { return !_this.containsProcessed(removableElement); });
            },
            enumerable: true,
            configurable: true
        });
        Merger.prototype.importInto = function (newElems, parentElem, target) {
            var importedElems = [];
            var curElems = Jhtml.Util.array(parentElem.children);
            for (var i in newElems) {
                var newElem = newElems[i];
                var importedElem = this.mergeElem(curElems, newElem, target);
                if (importedElem === this.currentContainerElem)
                    continue;
                this.importInto(Jhtml.Util.array(newElem.children), importedElem, target);
                importedElems.push(importedElem);
            }
            for (var i = 0; i < importedElems.length; i++) {
                var importedElem = importedElems[i];
                if (-1 < curElems.indexOf(importedElem)) {
                    continue;
                }
                this.loadObserver.addElement(importedElem);
                parentElem.appendChild(importedElem);
            }
        };
        Merger.prototype.mergeInto = function (newElems, parentElem, target) {
            var mergedElems = [];
            var curElems = Jhtml.Util.array(parentElem.children);
            for (var i in newElems) {
                var newElem = newElems[i];
                var mergedElem = this.mergeElem(curElems, newElem, target);
                if (mergedElem === this.currentContainerElem)
                    continue;
                this.mergeInto(Jhtml.Util.array(newElem.children), mergedElem, target);
                mergedElems.push(mergedElem);
            }
            for (var i = 0; i < curElems.length; i++) {
                if (-1 < mergedElems.indexOf(curElems[i]))
                    continue;
                if (!Merger.checkIfUnremovable(curElems[i])) {
                    this.removableElements.push(curElems[i]);
                }
                curElems.splice(i, 1);
                i--;
            }
            var curElem = curElems.shift();
            for (var i = 0; i < mergedElems.length; i++) {
                var mergedElem = mergedElems[i];
                if (mergedElem === curElem) {
                    curElem = curElems.shift();
                    continue;
                }
                this.loadObserver.addElement(mergedElem);
                if (!curElem) {
                    parentElem.appendChild(mergedElem);
                    continue;
                }
                parentElem.insertBefore(mergedElem, curElem);
                var j = void 0;
                if (-1 < (j = curElems.indexOf(mergedElem))) {
                    curElems.splice(j, 1);
                }
            }
        };
        Merger.prototype.mergeElem = function (preferedElems, newElem, target) {
            if (newElem === this.newContainerElem) {
                if (!this.compareExact(this.currentContainerElem, newElem, false)) {
                    var mergedElem_1 = newElem.cloneNode(false);
                    this.processedElements.push(mergedElem_1);
                    return mergedElem_1;
                }
                this.processedElements.push(this.currentContainerElem);
                return this.currentContainerElem;
            }
            if (this.newContainerElem && newElem.contains(this.newContainerElem)) {
                var mergedElem_2;
                if (mergedElem_2 = this.filterExact(preferedElems, newElem, false)) {
                    this.processedElements.push(mergedElem_2);
                    return mergedElem_2;
                }
                return this.cloneNewElem(newElem, false);
            }
            var mergedElem;
            switch (newElem.tagName) {
                case "SCRIPT":
                    if ((mergedElem = this.filter(preferedElems, newElem, ["src", "type"], true, false))
                        || (mergedElem = this.find(newElem, ["src", "type"], true, false))) {
                        this.processedElements.push(mergedElem);
                        return mergedElem;
                    }
                    return this.cloneNewElem(newElem, true);
                case "STYLE":
                case "LINK":
                    if ((mergedElem = this.filterExact(preferedElems, newElem, true))
                        || (mergedElem = this.findExact(newElem, true))) {
                        this.processedElements.push(mergedElem);
                        return mergedElem;
                    }
                    return this.cloneNewElem(newElem, true);
                default:
                    if ((mergedElem = this.filterExact(preferedElems, newElem, true))
                        || (mergedElem = this.findExact(newElem, true, target))) {
                        this.processedElements.push(mergedElem);
                        return mergedElem;
                    }
                    return this.cloneNewElem(newElem, false);
            }
        };
        Merger.checkIfUnremovable = function (elem) {
            return elem.tagName == "SCRIPT";
        };
        Merger.prototype.cloneNewElem = function (newElem, deep) {
            var mergedElem = this.rootElem.ownerDocument.createElement(newElem.tagName);
            for (var _i = 0, _a = this.attrNames(newElem); _i < _a.length; _i++) {
                var name_3 = _a[_i];
                mergedElem.setAttribute(name_3, newElem.getAttribute(name_3));
            }
            if (deep) {
                mergedElem.innerHTML = newElem.innerHTML;
            }
            this.processedElements.push(mergedElem);
            return mergedElem;
        };
        Merger.prototype.attrNames = function (elem) {
            var attrNames = [];
            var attrs = elem.attributes;
            for (var i = 0; i < attrs.length; i++) {
                attrNames.push(attrs[i].nodeName);
            }
            return attrNames;
        };
        Merger.prototype.findExact = function (matchingElem, checkInner, target) {
            if (target === void 0) { target = Jhtml.Meta.Target.HEAD | Jhtml.Meta.Target.BODY; }
            return this.find(matchingElem, this.attrNames(matchingElem), checkInner, true, target);
        };
        Merger.prototype.find = function (matchingElem, matchingAttrNames, checkInner, checkAttrNum, target) {
            if (target === void 0) { target = Jhtml.Meta.Target.HEAD | Jhtml.Meta.Target.BODY; }
            var foundElem = null;
            if ((target & Jhtml.Meta.Target.HEAD)
                && (foundElem = this.findIn(this.headElem, matchingElem, matchingAttrNames, checkInner, checkAttrNum))) {
                return foundElem;
            }
            if ((target & Jhtml.Meta.Target.BODY)
                && (foundElem = this.findIn(this.bodyElem, matchingElem, matchingAttrNames, checkInner, checkAttrNum))) {
                return foundElem;
            }
            return null;
        };
        Merger.prototype.findIn = function (nodeSelector, matchingElem, matchingAttrNames, checkInner, chekAttrNum) {
            for (var _i = 0, _a = Jhtml.Util.find(nodeSelector, matchingElem.tagName); _i < _a.length; _i++) {
                var tagElem = _a[_i];
                if (tagElem === this.currentContainerElem || tagElem.contains(this.currentContainerElem)
                    || this.currentContainerElem.contains(tagElem) || this.containsProcessed(tagElem)) {
                    continue;
                }
                if (this.compare(tagElem, matchingElem, matchingAttrNames, checkInner, chekAttrNum)) {
                    return tagElem;
                }
            }
            return null;
        };
        Merger.prototype.filterExact = function (elems, matchingElem, checkInner) {
            return this.filter(elems, matchingElem, this.attrNames(matchingElem), checkInner, true);
        };
        Merger.prototype.containsProcessed = function (elem) {
            return -1 < this.processedElements.indexOf(elem);
        };
        Merger.prototype.filter = function (elems, matchingElem, attrNames, checkInner, checkAttrNum) {
            for (var _i = 0, elems_1 = elems; _i < elems_1.length; _i++) {
                var elem = elems_1[_i];
                if (!this.containsProcessed(elem)
                    && this.compare(elem, matchingElem, attrNames, checkInner, checkAttrNum)) {
                    return elem;
                }
            }
        };
        Merger.prototype.compareExact = function (elem1, elem2, checkInner) {
            return this.compare(elem1, elem2, this.attrNames(elem1), checkInner, true);
        };
        Merger.prototype.compare = function (elem1, elem2, attrNames, checkInner, checkAttrNum) {
            if (elem1.tagName !== elem2.tagName)
                return false;
            for (var _i = 0, attrNames_1 = attrNames; _i < attrNames_1.length; _i++) {
                var attrName = attrNames_1[_i];
                if (elem1.getAttribute(attrName) !== elem2.getAttribute(attrName)) {
                    return false;
                }
            }
            if (checkInner && elem1.innerHTML.trim() !== elem2.innerHTML.trim()) {
                return false;
            }
            if (checkAttrNum && elem1.attributes.length != elem2.attributes.length) {
                return false;
            }
            return true;
        };
        Merger.prototype.mergeAttrsInto = function (newElem, elem) {
        };
        return Merger;
    }());
    Jhtml.Merger = Merger;
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Meta = (function () {
        function Meta() {
            this.headElements = [];
            this.bodyElements = [];
            this.bodyElement = null;
            this.containerElement = null;
        }
        return Meta;
    }());
    Jhtml.Meta = Meta;
    var MetaState = (function () {
        function MetaState(rootElem, headElem, bodyElem, containerElem) {
            this.rootElem = rootElem;
            this.headElem = headElem;
            this.bodyElem = bodyElem;
            this.containerElem = containerElem;
            this._browsable = false;
            this.loadObservers = [];
            this.mergeQueue = new ElementMergeQueue();
            this.markAsUsed(this.headElements);
            this.markAsUsed(this.bodyElements);
            var reader = new Jhtml.Util.ElemConfigReader(containerElem);
            this._browsable = reader.readBoolean("browsable", false);
        }
        Object.defineProperty(MetaState.prototype, "browsable", {
            get: function () {
                return this._browsable;
            },
            enumerable: true,
            configurable: true
        });
        MetaState.prototype.markAsUsed = function (elements) {
            for (var _i = 0, elements_1 = elements; _i < elements_1.length; _i++) {
                var element = elements_1[_i];
                if (element === this.containerElement)
                    continue;
                this.mergeQueue.addUsed(element);
                this.markAsUsed(Jhtml.Util.array(element.children));
            }
        };
        Object.defineProperty(MetaState.prototype, "headElements", {
            get: function () {
                return Jhtml.Util.array(this.headElem.children);
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(MetaState.prototype, "bodyElements", {
            get: function () {
                return Jhtml.Util.array(this.bodyElem.children);
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(MetaState.prototype, "containerElement", {
            get: function () {
                return this.containerElem;
            },
            enumerable: true,
            configurable: true
        });
        MetaState.prototype.registerLoadObserver = function (loadObserver) {
            var _this = this;
            this.loadObservers.push(loadObserver);
            loadObserver.whenLoaded(function () {
                _this.loadObservers.splice(_this.loadObservers.indexOf(loadObserver), 1);
            });
        };
        Object.defineProperty(MetaState.prototype, "busy", {
            get: function () {
                return this.loadObservers.length > 0;
            },
            enumerable: true,
            configurable: true
        });
        MetaState.prototype.import = function (newMeta, curModelDependent) {
            var merger = new Jhtml.Merger(this.rootElem, this.headElem, this.bodyElem, this.containerElem, newMeta.containerElement);
            merger.importInto(newMeta.headElements, this.headElem, Meta.Target.HEAD);
            merger.importInto(newMeta.bodyElements, this.bodyElem, Meta.Target.BODY);
            this.registerLoadObserver(merger.loadObserver);
            if (!curModelDependent) {
                return merger.loadObserver;
            }
            this.mergeQueue.finalizeImport(merger);
            return merger.loadObserver;
        };
        MetaState.prototype.replaceWith = function (newMeta) {
            var merger = new Jhtml.Merger(this.rootElem, this.headElem, this.bodyElem, this.containerElem, newMeta.containerElement);
            merger.mergeInto(newMeta.headElements, this.headElem, Meta.Target.HEAD);
            merger.mergeInto(newMeta.bodyElements, this.bodyElem, Meta.Target.BODY);
            if (newMeta.bodyElement) {
                merger.mergeAttrsInto(newMeta.bodyElement, this.bodyElem);
            }
            this.registerLoadObserver(merger.loadObserver);
            return this.mergeQueue.finalizeMerge(merger);
        };
        return MetaState;
    }());
    Jhtml.MetaState = MetaState;
    var ElementMergeQueue = (function () {
        function ElementMergeQueue() {
            this.usedElements = [];
            this.unnecessaryElements = [];
            this.blockedElements = [];
            this.curObserver = null;
        }
        ElementMergeQueue.prototype.addUsed = function (usedElement) {
            if (this.containsUsed(usedElement))
                return;
            this.usedElements.push(usedElement);
        };
        ElementMergeQueue.prototype.containsUsed = function (element) {
            return -1 < this.usedElements.indexOf(element);
        };
        ElementMergeQueue.prototype.addBlocked = function (blockedElement) {
            if (this.containsBlocked(blockedElement))
                return;
            this.blockedElements.push(blockedElement);
        };
        ElementMergeQueue.prototype.containsBlocked = function (element) {
            return -1 < this.blockedElements.indexOf(element);
        };
        ElementMergeQueue.prototype.addUnnecessary = function (unnecessaryElement) {
            if (this.containsUnnecessary(unnecessaryElement))
                return;
            this.unnecessaryElements.push(unnecessaryElement);
        };
        ElementMergeQueue.prototype.containsUnnecessary = function (element) {
            return -1 < this.unnecessaryElements.indexOf(element);
        };
        ElementMergeQueue.prototype.removeUnnecessary = function (element) {
            this.unnecessaryElements.splice(this.unnecessaryElements.indexOf(element), 1);
        };
        ElementMergeQueue.prototype.approveRemove = function () {
            var removeElement;
            while (removeElement = this.unnecessaryElements.pop()) {
                removeElement.remove();
            }
        };
        ElementMergeQueue.prototype.finalizeImport = function (merger) {
            for (var _i = 0, _a = merger.processedElements; _i < _a.length; _i++) {
                var element = _a[_i];
                this.removeUnnecessary(element);
                this.addUsed(element);
            }
        };
        ElementMergeQueue.prototype.finalizeMerge = function (merger) {
            var _this = this;
            var removableElements = [];
            var remainingElements = merger.remainingElements;
            var remainingElement;
            while (remainingElement = remainingElements.pop()) {
                if (this.containsBlocked(remainingElement))
                    continue;
                if (!this.containsUsed(remainingElement)
                    && !this.containsUnnecessary(remainingElement)) {
                    this.addBlocked(remainingElement);
                    continue;
                }
                this.addUnnecessary(remainingElement);
            }
            for (var _i = 0, _a = merger.processedElements; _i < _a.length; _i++) {
                var processedElement = _a[_i];
                this.removeUnnecessary(processedElement);
                this.addUsed(processedElement);
            }
            if (this.curObserver !== null) {
                this.curObserver.abort();
            }
            var observer = this.curObserver = new MergeObserverImpl();
            merger.loadObserver.whenLoaded(function () {
                if (_this.curObserver !== observer) {
                    return;
                }
                _this.curObserver = null;
                observer.complete();
                _this.approveRemove();
            });
            return observer;
        };
        return ElementMergeQueue;
    }());
    var MergeObserverImpl = (function () {
        function MergeObserverImpl() {
            this.success = null;
            this.successCallbacks = [];
            this.abortedCallbacks = [];
        }
        MergeObserverImpl.prototype.ensure = function () {
            if (this.success === null)
                return;
            throw new Error("already finished");
        };
        MergeObserverImpl.prototype.complete = function () {
            this.ensure();
            this.success = true;
            var successCallback;
            while (successCallback = this.successCallbacks.pop()) {
                successCallback();
            }
            this.reset();
        };
        MergeObserverImpl.prototype.abort = function () {
            this.ensure();
            this.success = false;
            var abortedCallback;
            while (abortedCallback = this.abortedCallbacks.pop()) {
                abortedCallback();
            }
            this.reset();
        };
        MergeObserverImpl.prototype.reset = function () {
            this.successCallbacks = [];
            this.abortedCallbacks = [];
        };
        MergeObserverImpl.prototype.done = function (callback) {
            if (this.success === null) {
                this.successCallbacks.push(callback);
            }
            else if (this.success) {
                callback();
            }
            return this;
        };
        MergeObserverImpl.prototype.aborted = function (callback) {
            if (this.success === null) {
                this.abortedCallbacks.push(callback);
            }
            else if (!this.success) {
                callback();
            }
            return this;
        };
        return MergeObserverImpl;
    }());
    (function (Meta) {
        var Target;
        (function (Target) {
            Target[Target["HEAD"] = 1] = "HEAD";
            Target[Target["BODY"] = 2] = "BODY";
        })(Target = Meta.Target || (Meta.Target = {}));
    })(Meta = Jhtml.Meta || (Jhtml.Meta = {}));
    var LoadObserver = (function () {
        function LoadObserver() {
            this.loadCallbacks = [];
            this.readyCallback = [];
        }
        LoadObserver.prototype.addElement = function (elem) {
            var _this = this;
            var tn;
            var loadCallback = function () {
                elem.removeEventListener("load", loadCallback);
                clearTimeout(tn);
                _this.unregisterLoadCallback(loadCallback);
            };
            this.loadCallbacks.push(loadCallback);
            elem.addEventListener("load", loadCallback, false);
            tn = setTimeout(function () {
                console.warn("Jhtml continues; following resource could not be loaded in time: "
                    + elem.outerHTML);
                loadCallback();
            }, 2000);
        };
        LoadObserver.prototype.unregisterLoadCallback = function (callback) {
            this.loadCallbacks.splice(this.loadCallbacks.indexOf(callback), 1);
            this.checkFire();
        };
        LoadObserver.prototype.whenLoaded = function (callback) {
            this.readyCallback.push(callback);
            this.checkFire();
        };
        LoadObserver.prototype.checkFire = function () {
            if (this.loadCallbacks.length > 0)
                return;
            var callback;
            while (callback = this.readyCallback.shift()) {
                callback();
            }
        };
        return LoadObserver;
    }());
    Jhtml.LoadObserver = LoadObserver;
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Model = (function () {
        function Model(meta) {
            this.meta = meta;
            this.comps = {};
        }
        Model.prototype.isFull = function () {
            return !!this.container;
        };
        Model.prototype.abadone = function () {
            if (this.container) {
                this.container.abadone();
            }
            for (var name_4 in this.comps) {
                this.comps[name_4].abadone();
            }
            if (this.snippet) {
                this.snippet.abadone();
            }
        };
        return Model;
    }());
    Jhtml.Model = Model;
    var ModelState = (function () {
        function ModelState(metaState, container, comps) {
            this.metaState = metaState;
            this.container = container;
            this.comps = comps;
        }
        return ModelState;
    }());
    Jhtml.ModelState = ModelState;
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var ModelFactory = (function () {
        function ModelFactory() {
        }
        ModelFactory.createFromJsonObj = function (jsonObj) {
            if (typeof jsonObj.content != "string") {
                throw new Jhtml.ParseError("Missing or invalid property 'content'.");
            }
            var rootElem = document.createElement("html");
            rootElem.innerHTML = jsonObj.content;
            var meta = ModelFactory.buildMeta(rootElem, false);
            ModelFactory.compileMetaElements(meta.headElements, "head", jsonObj);
            ModelFactory.compileMetaElements(meta.bodyElements, "bodyStart", jsonObj);
            ModelFactory.compileMetaElements(meta.bodyElements, "bodyEnd", jsonObj);
            var model = new Jhtml.Model(meta);
            if (meta.containerElement) {
                model.container = ModelFactory.compileContainer(meta.containerElement, model);
                model.comps = ModelFactory.compileComps(model.container, meta.containerElement, model);
                model.container.detach();
                for (var _i = 0, _a = Object.values(model.comps); _i < _a.length; _i++) {
                    var comp = _a[_i];
                    comp.detach();
                }
            }
            else if (jsonObj.content) {
                rootElem = document.createElement("div");
                rootElem.innerHTML = jsonObj.content;
                model.snippet = new Jhtml.Snippet(Jhtml.Util.array(rootElem.children), model, document.createElement("template"));
            }
            return model;
        };
        ModelFactory.createStateFromDocument = function (document) {
            var metaState = new Jhtml.MetaState(document.documentElement, document.head, document.body, ModelFactory.extractContainerElem(document.body, true));
            var container = ModelFactory.compileContainer(metaState.containerElement, null);
            var comps = ModelFactory.compileComps(container, metaState.containerElement, null);
            return new Jhtml.ModelState(metaState, container, comps);
        };
        ModelFactory.createFromHtml = function (htmlStr, full) {
            var templateElem = document.createElement("html");
            templateElem.innerHTML = htmlStr;
            var model = new Jhtml.Model(ModelFactory.buildMeta(templateElem, true));
            model.container = ModelFactory.compileContainer(model.meta.containerElement, model);
            model.comps = ModelFactory.compileComps(model.container, templateElem, model);
            model.container.detach();
            for (var _i = 0, _a = Object.values(model.comps); _i < _a.length; _i++) {
                var comp = _a[_i];
                comp.detach();
            }
            return model;
        };
        ModelFactory.extractHeadElem = function (rootElem, required) {
            var headElem = rootElem.querySelector("head");
            if (headElem || !required) {
                return headElem;
            }
            throw new Jhtml.ParseError("head element missing.");
        };
        ModelFactory.extractBodyElem = function (rootElem, required) {
            var bodyElem = rootElem.querySelector("body");
            if (bodyElem || !required) {
                return bodyElem;
            }
            throw new Jhtml.ParseError("body element missing.");
        };
        ModelFactory.buildMeta = function (rootElem, full) {
            var meta = new Jhtml.Meta();
            var elem;
            if ((elem = ModelFactory.extractContainerElem(rootElem, full))) {
                meta.containerElement = elem;
            }
            else {
                return meta;
            }
            if (elem = ModelFactory.extractBodyElem(rootElem, true)) {
                meta.bodyElements = Jhtml.Util.array(elem.children);
            }
            if (elem = ModelFactory.extractHeadElem(rootElem, false)) {
                meta.headElements = Jhtml.Util.array(elem.children);
            }
            return meta;
        };
        ModelFactory.extractContainerElem = function (rootElem, required) {
            var containerList = Jhtml.Util.find(rootElem, ModelFactory.CONTAINER_SELECTOR);
            if (containerList.length == 0) {
                if (!required)
                    return null;
                throw new Jhtml.ParseError("Jhtml container missing.");
            }
            if (containerList.length > 1) {
                if (!required)
                    return null;
                throw new Jhtml.ParseError("Multiple jhtml container detected.");
            }
            return containerList[0];
        };
        ModelFactory.compileContainer = function (containerElem, model) {
            return new Jhtml.Container(containerElem.getAttribute(ModelFactory.CONTAINER_ATTR), containerElem, model);
        };
        ModelFactory.compileComps = function (container, containerElem, model) {
            var comps = {};
            for (var _i = 0, _a = Jhtml.Util.find(containerElem, ModelFactory.COMP_SELECTOR); _i < _a.length; _i++) {
                var compElem = _a[_i];
                var name_5 = compElem.getAttribute(ModelFactory.COMP_ATTR);
                if (comps[name_5]) {
                    throw new Jhtml.ParseError("Duplicated comp name: " + name_5);
                }
                container.compElements[name_5] = compElem;
                comps[name_5] = new Jhtml.Comp(name_5, compElem, model);
            }
            return comps;
        };
        ModelFactory.compileMetaElements = function (elements, name, jsonObj) {
            if (!(jsonObj[name] instanceof Array)) {
                throw new Jhtml.ParseError("Missing or invalid property '" + name + "'.");
            }
            for (var _i = 0, _a = jsonObj[name]; _i < _a.length; _i++) {
                var elemHtml = _a[_i];
                elements.push(ModelFactory.createElement(elemHtml));
            }
        };
        ModelFactory.createElement = function (elemHtml) {
            var templateElem = document.createElement("template");
            templateElem.innerHTML = elemHtml;
            return templateElem.content.firstChild;
        };
        ModelFactory.CONTAINER_ATTR = "data-jhtml-container";
        ModelFactory.COMP_ATTR = "data-jhtml-comp";
        ModelFactory.CONTAINER_SELECTOR = "[" + ModelFactory.CONTAINER_ATTR + "]";
        ModelFactory.COMP_SELECTOR = "[" + ModelFactory.COMP_ATTR + "]";
        return ModelFactory;
    }());
    Jhtml.ModelFactory = ModelFactory;
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Monitor = (function () {
        function Monitor(container, history, _pseudo) {
            var _this = this;
            this.container = container;
            this._pseudo = _pseudo;
            this.active = true;
            this.compHandlers = {};
            this.directiveCbr = new Jhtml.Util.CallbackRegistry();
            this.directiveExecutedCbr = new Jhtml.Util.CallbackRegistry();
            this.pushing = false;
            this.pendingPromise = null;
            this.context = Jhtml.Context.from(container.ownerDocument);
            this.history = history;
            this.history.onChanged(function () {
                _this.historyChanged();
            });
        }
        Object.defineProperty(Monitor.prototype, "compHandlerReg", {
            get: function () {
                return this.compHandlers;
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(Monitor.prototype, "pseudo", {
            get: function () {
                return this._pseudo;
            },
            enumerable: true,
            configurable: true
        });
        Monitor.prototype.registerCompHandler = function (compName, compHandler) {
            this.compHandlers[compName] = compHandler;
        };
        Monitor.prototype.unregisterCompHandler = function (compName) {
            delete this.compHandlers[compName];
        };
        Monitor.prototype.exec = function (urlExpr, requestConfig) {
            var _this = this;
            if (this.history.currentEntry) {
                this.history.currentEntry.scrollPos = this.history.currentPage.config.scrollPos = window.pageYOffset;
            }
            var url = Jhtml.Url.create(urlExpr);
            var config = Jhtml.FullRequestConfig.from(requestConfig);
            var page = this.history.getPageByUrl(url);
            if (!page) {
                page = new Jhtml.Page(url, this.context.requestor.lookupDirective(url));
            }
            else if (page.config.frozen) {
                if (page.disposed) {
                    throw new Error("Target page is frozen and disposed.");
                }
            }
            else if (page.disposed || config.forceReload || !page.config.keep) {
                page.promise = this.context.requestor.lookupDirective(page.loadUrl || url);
            }
            var promise = this.pendingPromise = page.promise;
            if (config.pushToHistory && page !== this.history.currentPage) {
                this.pushing = true;
                this.history.push(page);
                this.pushing = false;
            }
            promise.then(function (directive) {
                if (promise !== _this.pendingPromise)
                    return;
                _this.handleDirective(directive, true, config.usePageScrollPos);
            });
            return promise;
        };
        Monitor.prototype.handleDirective = function (directive, fresh, usePageScrollPos) {
            if (fresh === void 0) { fresh = true; }
            if (usePageScrollPos === void 0) { usePageScrollPos = false; }
            this.triggerDirectiveCallbacks({ directive: directive, new: fresh });
            directive.exec(this);
            if (this.history.currentEntry && this.active) {
                window.scroll(0, (usePageScrollPos ? this.history.currentPage.config.scrollPos
                    : this.history.currentEntry.scrollPos));
            }
            this.triggerDirectiveExecutedCallbacks({ directive: directive, new: fresh });
        };
        Monitor.prototype.triggerDirectiveCallbacks = function (evt) {
            this.directiveCbr.fire(evt);
        };
        Monitor.prototype.onDirective = function (callback) {
            this.directiveCbr.on(callback);
        };
        Monitor.prototype.offDirective = function (callback) {
            this.directiveCbr.off(callback);
        };
        Monitor.prototype.triggerDirectiveExecutedCallbacks = function (evt) {
            this.directiveExecutedCbr.fire(evt);
        };
        Monitor.prototype.onDirectiveExecuted = function (callback) {
            this.directiveExecutedCbr.on(callback);
        };
        Monitor.prototype.offDirectiveExecuted = function (callback) {
            this.directiveExecutedCbr.off(callback);
        };
        Monitor.prototype.lookupModel = function (url) {
            var _this = this;
            return new Promise(function (resolve) {
                _this.context.requestor.exec("GET", url).send().then(function (response) {
                    if (response.model) {
                        resolve({ model: response.model, response: response });
                    }
                    else {
                        _this.handleDirective(response.directive);
                    }
                });
            });
        };
        Monitor.prototype.historyChanged = function () {
            var _this = this;
            if (this.pushing)
                return;
            var currentPage = this.history.currentPage;
            if (!currentPage.promise) {
                currentPage.promise = this.context.requestor.lookupDirective(currentPage.url);
            }
            currentPage.promise.then(function (directive) {
                _this.handleDirective(directive);
            });
        };
        Monitor.of = function (element, selfIncluded) {
            if (selfIncluded === void 0) { selfIncluded = true; }
            if (selfIncluded && element.matches("." + Monitor.CSS_CLASS)) {
                return Monitor.test(element);
            }
            if (element = element.closest("." + Monitor.CSS_CLASS)) {
                return Monitor.test(element);
            }
            return null;
        };
        Monitor.test = function (element) {
            var monitor = Jhtml.Util.getElemData(element, Monitor.KEY);
            if (element.classList.contains(Monitor.CSS_CLASS) && monitor instanceof Monitor) {
                return monitor;
            }
            return null;
        };
        Monitor.create = function (container, history, pseudo) {
            var monitor = Monitor.test(container);
            if (monitor) {
                throw new Error("Element is already monitored.");
            }
            container.classList.add(Monitor.CSS_CLASS);
            monitor = new Monitor(container, history, pseudo);
            Jhtml.Util.bindElemData(container, Monitor.KEY, monitor);
            return monitor;
        };
        Monitor.KEY = "jhtml-monitor";
        Monitor.CSS_CLASS = "jhtml-selfmonitored";
        return Monitor;
    }());
    Jhtml.Monitor = Monitor;
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Content = (function () {
        function Content(elements, _model, detachedElem) {
            this.elements = elements;
            this._model = _model;
            this.detachedElem = detachedElem;
            this.cbr = new Jhtml.Util.CallbackRegistry();
            this.attached = false;
        }
        Object.defineProperty(Content.prototype, "model", {
            get: function () {
                return this._model;
            },
            enumerable: true,
            configurable: true
        });
        Content.prototype.fire = function (eventType) {
            this.cbr.fireType(eventType);
        };
        Content.prototype.on = function (eventType, callback) {
            this.eunsureNotDisposed();
            this.cbr.onType(eventType, callback);
        };
        Content.prototype.off = function (eventType, callback) {
            this.cbr.offType(eventType, callback);
        };
        Object.defineProperty(Content.prototype, "isAttached", {
            get: function () {
                return this.attached;
            },
            enumerable: true,
            configurable: true
        });
        Content.prototype.ensureDetached = function () {
            if (this.attached) {
                throw new Error("Content already attached.");
            }
        };
        Content.prototype.eunsureNotDisposed = function () {
            if (!this.disposed)
                return;
            throw new Error("Content disposed.");
        };
        Content.prototype.attach = function (element) {
            this.eunsureNotDisposed();
            this.ensureDetached();
            for (var _i = 0, _a = this.elements; _i < _a.length; _i++) {
                var childElem = _a[_i];
                element.appendChild(childElem);
            }
            this.attached = true;
            this.fire("attached");
        };
        Content.prototype.detach = function () {
            if (!this.attached)
                return;
            this.cbr.fireType("detach");
            for (var _i = 0, _a = this.elements; _i < _a.length; _i++) {
                var childElem = _a[_i];
                if (this.abadoned) {
                    childElem.remove();
                }
                else {
                    this.detachedElem.appendChild(childElem);
                }
            }
            this.attached = false;
            this.cbr.fireType("detached");
        };
        Object.defineProperty(Content.prototype, "abadoned", {
            get: function () {
                return !this.detachedElem;
            },
            enumerable: true,
            configurable: true
        });
        Content.prototype.abadone = function () {
            if (this.abadoned)
                return;
            this.detachedElem.remove();
            this.detachedElem = null;
            if (!this.attached) {
                this.dispose();
            }
        };
        Object.defineProperty(Content.prototype, "disposed", {
            get: function () {
                return this.elements === null;
            },
            enumerable: true,
            configurable: true
        });
        Content.prototype.dispose = function () {
            this.fire("dispose");
            this.abadone();
            if (this.attached) {
                this.detach();
            }
            this.elements = null;
            this.fire("disposed");
            this.cbr = null;
        };
        return Content;
    }());
    Jhtml.Content = Content;
    var Panel = (function (_super) {
        __extends(Panel, _super);
        function Panel(_name, attachedElem, model) {
            var _this = _super.call(this, Jhtml.Util.array(attachedElem.children), model, attachedElem.ownerDocument.createElement("template")) || this;
            _this._name = _name;
            _this.attached = true;
            return _this;
        }
        Object.defineProperty(Panel.prototype, "name", {
            get: function () {
                return this._name;
            },
            enumerable: true,
            configurable: true
        });
        Panel.prototype.attachTo = function (element) {
            this.attach(element);
        };
        Panel.prototype.detach = function () {
            _super.prototype.detach.call(this);
        };
        return Panel;
    }(Content));
    Jhtml.Panel = Panel;
    var Container = (function (_super) {
        __extends(Container, _super);
        function Container() {
            var _this = _super !== null && _super.apply(this, arguments) || this;
            _this.compElements = {};
            return _this;
        }
        Container.prototype.matches = function (container) {
            return this.name == container.name
                && JSON.stringify(Object.keys(this.compElements)) == JSON.stringify(Object.keys(container.compElements));
        };
        return Container;
    }(Panel));
    Jhtml.Container = Container;
    var Comp = (function (_super) {
        __extends(Comp, _super);
        function Comp() {
            return _super !== null && _super.apply(this, arguments) || this;
        }
        return Comp;
    }(Panel));
    Jhtml.Comp = Comp;
    var Snippet = (function (_super) {
        __extends(Snippet, _super);
        function Snippet() {
            return _super !== null && _super.apply(this, arguments) || this;
        }
        Snippet.prototype.markAttached = function () {
            this.ensureDetached();
            this.attached = true;
            this.cbr.fireType("attached");
        };
        Snippet.prototype.attachTo = function (element) {
            this.attach(element);
        };
        return Snippet;
    }(Content));
    Jhtml.Snippet = Snippet;
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var ParseError = (function (_super) {
        __extends(ParseError, _super);
        function ParseError() {
            return _super !== null && _super.apply(this, arguments) || this;
        }
        return ParseError;
    }(Error));
    Jhtml.ParseError = ParseError;
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var DocumentManager = (function () {
        function DocumentManager() {
        }
        return DocumentManager;
    }());
    Jhtml.DocumentManager = DocumentManager;
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var FullModelDirective = (function () {
        function FullModelDirective(model, additionalData) {
            this.model = model;
            this.additionalData = additionalData;
            if (!model.isFull()) {
                throw new Error("Invalid argument. Full model required.");
            }
        }
        FullModelDirective.prototype.getAdditionalData = function () {
            return this.additionalData;
        };
        FullModelDirective.prototype.exec = function (monitor) {
            if (!monitor.pseudo) {
                monitor.context.replaceModel(this.model, monitor.compHandlerReg);
                return;
            }
            var loadObserver = monitor.context.importMeta(this.model.meta);
            for (var name_6 in this.model.comps) {
                if (!monitor.compHandlerReg[name_6])
                    continue;
                monitor.compHandlerReg[name_6].attachComp(this.model.comps[name_6]);
            }
        };
        FullModelDirective.prototype.destroy = function () {
            this.model.abadone();
        };
        return FullModelDirective;
    }());
    Jhtml.FullModelDirective = FullModelDirective;
    var ReplaceDirective = (function () {
        function ReplaceDirective(status, responseText, mimeType, url) {
            this.status = status;
            this.responseText = responseText;
            this.mimeType = mimeType;
            this.url = url;
        }
        ReplaceDirective.prototype.getAdditionalData = function () {
            return null;
        };
        ReplaceDirective.prototype.exec = function (monitor) {
            monitor.context.replace(this.responseText, this.mimeType, monitor.history.currentPage.url.equals(this.url));
        };
        ReplaceDirective.prototype.destroy = function () {
        };
        return ReplaceDirective;
    }());
    Jhtml.ReplaceDirective = ReplaceDirective;
    var RedirectDirective = (function () {
        function RedirectDirective(srcUrl, back, targetUrl, requestConfig, additionalData) {
            this.srcUrl = srcUrl;
            this.back = back;
            this.targetUrl = targetUrl;
            this.requestConfig = requestConfig;
            this.additionalData = additionalData;
        }
        RedirectDirective.prototype.getAdditionalData = function () {
            return this.additionalData;
        };
        RedirectDirective.prototype.exec = function (monitor) {
            switch (this.back) {
                case RedirectDirective.Type.REFERER:
                    var currentPage = monitor.history.currentPage;
                    if (!currentPage.url.equals(this.srcUrl)) {
                        if (currentPage.disposed) {
                            monitor.exec(currentPage.url, { pushToHistory: false });
                        }
                        return;
                    }
                case RedirectDirective.Type.BACK:
                    if (monitor.history.currentEntry.index > 0) {
                        var entry = monitor.history.getEntryByIndex(monitor.history.currentEntry.index - 1);
                        monitor.exec(entry.page.url, this.requestConfig);
                        monitor.history.currentEntry.scrollPos = entry.scrollPos;
                        return;
                    }
                default:
                    monitor.exec(this.targetUrl, this.requestConfig);
            }
        };
        RedirectDirective.prototype.destroy = function () {
        };
        return RedirectDirective;
    }());
    Jhtml.RedirectDirective = RedirectDirective;
    (function (RedirectDirective) {
        var Type;
        (function (Type) {
            Type[Type["TARGET"] = 0] = "TARGET";
            Type[Type["REFERER"] = 1] = "REFERER";
            Type[Type["BACK"] = 2] = "BACK";
        })(Type = RedirectDirective.Type || (RedirectDirective.Type = {}));
    })(RedirectDirective = Jhtml.RedirectDirective || (Jhtml.RedirectDirective = {}));
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Request = (function () {
        function Request(requestor, _xhr, _url) {
            this.requestor = requestor;
            this._xhr = _xhr;
            this._url = _url;
        }
        Object.defineProperty(Request.prototype, "xhr", {
            get: function () {
                return this._xhr;
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(Request.prototype, "url", {
            get: function () {
                return this._url;
            },
            enumerable: true,
            configurable: true
        });
        Request.prototype.abort = function () {
            this.xhr.abort();
        };
        Request.prototype.send = function (data) {
            this.xhr.send(data);
            return this.buildPromise();
        };
        Request.prototype.buildPromise = function () {
            var _this = this;
            return new Promise(function (resolve) {
                _this.xhr.onreadystatechange = function () {
                    if (_this.xhr.readyState != 4)
                        return;
                    switch (_this.xhr.status) {
                        case 200:
                            var model = void 0;
                            var directive = void 0;
                            var additionalData = void 0;
                            if (!_this.xhr.getResponseHeader("Content-Type").match(/json/)) {
                                model = _this.createModelFromHtml(_this.xhr.responseText);
                            }
                            else {
                                var jsonObj = _this.createJsonObj(_this.url, _this.xhr.responseText);
                                additionalData = jsonObj.additional;
                                directive = _this.scanForDirective(_this.url, jsonObj);
                                if (!directive && (additionalData === undefined || jsonObj.content)) {
                                    model = _this.createModelFromJson(_this.url, jsonObj);
                                }
                            }
                            if (model) {
                                directive = model.isFull() ? new Jhtml.FullModelDirective(model, additionalData) : null;
                            }
                            var response = { status: 200, request: _this, model: model, directive: directive, additionalData: additionalData };
                            resolve(response);
                            break;
                        default:
                            resolve({
                                status: _this.xhr.status,
                                request: _this,
                                directive: new Jhtml.ReplaceDirective(_this.xhr.status, _this.xhr.responseText, _this.xhr.getResponseHeader("Content-Type"), _this.url)
                            });
                    }
                };
                _this.xhr.onerror = function () {
                    throw new Error("Could not request " + _this.url.toString());
                };
            });
        };
        Request.prototype.createJsonObj = function (url, jsonText) {
            try {
                return JSON.parse(jsonText);
            }
            catch (e) {
                throw new Error(url + "; invalid json response: " + e.message);
            }
        };
        Request.prototype.scanForDirective = function (url, jsonObj) {
            switch (jsonObj.directive) {
                case "redirect":
                    return new Jhtml.RedirectDirective(url, Jhtml.RedirectDirective.Type.TARGET, Jhtml.Url.create(jsonObj.location), Jhtml.FullRequestConfig.from(jsonObj.requestConfig), jsonObj.additional);
                case "redirectToReferer":
                    return new Jhtml.RedirectDirective(url, Jhtml.RedirectDirective.Type.REFERER, Jhtml.Url.create(jsonObj.location), Jhtml.FullRequestConfig.from(jsonObj.requestConfig), jsonObj.additional);
                case "redirectBack":
                    return new Jhtml.RedirectDirective(url, Jhtml.RedirectDirective.Type.BACK, Jhtml.Url.create(jsonObj.location), Jhtml.FullRequestConfig.from(jsonObj.requestConfig), jsonObj.additional);
                default:
                    return null;
            }
        };
        Request.prototype.createModelFromJson = function (url, jsonObj) {
            try {
                var model = Jhtml.ModelFactory.createFromJsonObj(jsonObj);
                this.requestor.context.registerNewModel(model);
                return model;
            }
            catch (e) {
                if (e instanceof Jhtml.ParseError || e instanceof SyntaxError) {
                    throw new Error(url + "; no or invalid json: " + e.message);
                }
                throw e;
            }
        };
        Request.prototype.createModelFromHtml = function (html) {
            try {
                var model = Jhtml.ModelFactory.createFromHtml(html, true);
                this.requestor.context.registerNewModel(model);
                return model;
            }
            catch (e) {
                throw new Error(this.url + "; invalid jhtml response: " + e.message);
            }
        };
        return Request;
    }());
    Jhtml.Request = Request;
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var FullRequestConfig = (function () {
        function FullRequestConfig() {
            this.forceReload = false;
            this.pushToHistory = true;
            this.usePageScrollPos = false;
        }
        FullRequestConfig.from = function (requestConfig) {
            if (requestConfig instanceof FullRequestConfig) {
                return requestConfig;
            }
            var config = new FullRequestConfig();
            if (!requestConfig)
                return config;
            if (requestConfig.forceReload !== undefined) {
                config.forceReload = requestConfig.forceReload;
            }
            if (requestConfig.pushToHistory !== undefined) {
                config.pushToHistory = requestConfig.pushToHistory;
            }
            if (requestConfig.usePageScrollPos !== undefined) {
                config.usePageScrollPos = requestConfig.usePageScrollPos;
            }
            return config;
        };
        FullRequestConfig.fromElement = function (element) {
            var reader = new Jhtml.Util.ElemConfigReader(element);
            var config = new FullRequestConfig();
            config.forceReload = reader.readBoolean("force-reload", config.forceReload);
            config.pushToHistory = reader.readBoolean("push-to-history", config.pushToHistory);
            config.usePageScrollPos = reader.readBoolean("use-page-scroll-pos", config.usePageScrollPos);
            return config;
        };
        return FullRequestConfig;
    }());
    Jhtml.FullRequestConfig = FullRequestConfig;
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Requestor = (function () {
        function Requestor(_context) {
            this._context = _context;
        }
        Object.defineProperty(Requestor.prototype, "context", {
            get: function () {
                return this._context;
            },
            enumerable: true,
            configurable: true
        });
        Requestor.prototype.lookupDirective = function (url) {
            var _this = this;
            return new Promise(function (resolve) {
                _this.exec("GET", url).send().then(function (result) {
                    if (result.directive) {
                        resolve(result.directive);
                        return;
                    }
                    throw new Error(url + " provides no jhtml directive.");
                });
            });
        };
        Requestor.prototype.lookupModel = function (url) {
            var _this = this;
            return new Promise(function (resolve) {
                _this.exec("GET", url).send().then(function (result) {
                    if (result.directive) {
                        resolve(result.model);
                        return;
                    }
                    throw new Error(url + " provides no jhtml model.");
                });
            });
        };
        Requestor.prototype.exec = function (method, url) {
            var xhr = new XMLHttpRequest();
            xhr.open(method, url.toString(), true);
            xhr.setRequestHeader("Accept", "application/json,text/html");
            return new Jhtml.Request(this, xhr, url);
        };
        return Requestor;
    }());
    Jhtml.Requestor = Requestor;
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Url = (function () {
        function Url(urlStr) {
            this.urlStr = urlStr;
        }
        Url.prototype.toString = function () {
            return this.urlStr;
        };
        Url.prototype.equals = function (url) {
            return this.urlStr == url.urlStr;
        };
        Url.prototype.extR = function (pathExt, queryExt) {
            if (pathExt === void 0) { pathExt = null; }
            if (queryExt === void 0) { queryExt = null; }
            var urlParts = this.urlStr.split("?");
            var newUrlStr = urlParts[0];
            if (pathExt !== null && pathExt !== undefined) {
                newUrlStr = newUrlStr.replace(/\/+$/, "") + "/" + encodeURI(pathExt);
            }
            if (urlParts[1]) {
                newUrlStr += "?" + urlParts[1];
            }
            if (queryExt !== null || queryExt !== undefined) {
                var parts = [];
                this.compileQueryParts(parts, queryExt, null);
                var queryExtStr = parts.join("&");
                if (newUrlStr.match(/\?/)) {
                    newUrlStr += "&" + queryExtStr;
                }
                else {
                    newUrlStr += "?" + queryExtStr;
                }
            }
            return new Url(newUrlStr);
        };
        Url.prototype.compileQueryParts = function (parts, queryExt, prefix) {
            for (var key in queryExt) {
                var name_7 = null;
                if (prefix) {
                    name_7 = prefix + "[" + key + "]";
                }
                else {
                    name_7 = key;
                }
                var value = queryExt[key];
                if (value === null || value === undefined) {
                    continue;
                }
                if (value instanceof Array || value instanceof Object) {
                    this.compileQueryParts(parts, value, name_7);
                }
                else {
                    parts.push(encodeURIComponent(name_7) + "=" + encodeURIComponent(value));
                }
            }
        };
        Url.build = function (urlExpression) {
            if (urlExpression === null || urlExpression === undefined)
                return null;
            return Url.create(urlExpression);
        };
        Url.create = function (urlExpression) {
            if (urlExpression instanceof Url) {
                return urlExpression;
            }
            return new Url(Url.absoluteStr(urlExpression));
        };
        Url.absoluteStr = function (urlExpression) {
            if (urlExpression instanceof Url) {
                return urlExpression.toString();
            }
            var urlStr = urlExpression;
            if (!/^(?:\/|[a-z]+:\/\/)/.test(urlStr)) {
                return window.location.toString().replace(/(\/+)?((\?|#).*)?$/, "") + "/" + urlStr;
            }
            if (!/^(?:[a-z]+:)?\/\//.test(urlStr)) {
                return window.location.protocol + "//" + window.location.host + urlStr;
            }
            return urlStr;
        };
        return Url;
    }());
    Jhtml.Url = Url;
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Ui;
    (function (Ui) {
        var Form = (function () {
            function Form(_element) {
                this._element = _element;
                this._observing = false;
                this._config = new Form.Config();
                this.callbackRegistery = new Jhtml.Util.CallbackRegistry();
                this.curRequest = null;
                this.tmpSubmitDirective = null;
                this.controlLockAutoReleaseable = true;
            }
            Object.defineProperty(Form.prototype, "element", {
                get: function () {
                    return this._element;
                },
                enumerable: true,
                configurable: true
            });
            Object.defineProperty(Form.prototype, "observing", {
                get: function () {
                    return this._observing;
                },
                enumerable: true,
                configurable: true
            });
            Object.defineProperty(Form.prototype, "config", {
                get: function () {
                    return this._config;
                },
                enumerable: true,
                configurable: true
            });
            Form.prototype.reset = function () {
                this.element.reset();
            };
            Form.prototype.fire = function (eventType) {
                this.callbackRegistery.fireType(eventType.toString(), this);
            };
            Form.prototype.on = function (eventType, callback) {
                this.callbackRegistery.onType(eventType.toString(), callback);
            };
            Form.prototype.off = function (eventType, callback) {
                this.callbackRegistery.offType(eventType.toString(), callback);
            };
            Form.prototype.observe = function () {
                var _this = this;
                if (this._observing)
                    return;
                this._observing = true;
                this.element.addEventListener("submit", function (evt) {
                    evt.preventDefault();
                    if (_this.config.autoSubmitAllowed) {
                        var submitDirective_1 = _this.tmpSubmitDirective;
                        setTimeout(function () {
                            _this.submit(submitDirective_1);
                        });
                    }
                    _this.tmpSubmitDirective = null;
                }, false);
                Jhtml.Util.find(this.element, "input[type=submit], button[type=submit]").forEach(function (elem) {
                    elem.addEventListener("click", function (evt) {
                        _this.tmpSubmitDirective = { button: elem };
                    }, false);
                });
            };
            Form.prototype.buildFormData = function (submitConfig) {
                var formData = new FormData(this.element);
                if (submitConfig && submitConfig.button) {
                    formData.append(submitConfig.button.getAttribute("name"), submitConfig.button.getAttribute("value"));
                }
                return formData;
            };
            Form.prototype.block = function () {
                if (!this.controlLock && this.config.disableControls) {
                    this.disableControls();
                }
            };
            Form.prototype.unblock = function () {
                if (this.controlLock && this.controlLockAutoReleaseable) {
                    this.controlLock.release();
                }
            };
            Form.prototype.disableControls = function (autoReleaseable) {
                if (autoReleaseable === void 0) { autoReleaseable = true; }
                this.controlLockAutoReleaseable = autoReleaseable;
                if (this.controlLock)
                    return;
                this.controlLock = new ControlLock(this.element);
            };
            Form.prototype.enableControls = function () {
                if (this.controlLock) {
                    this.controlLock.release();
                    this.controlLock = null;
                    this.controlLockAutoReleaseable = true;
                }
            };
            Form.prototype.abortSubmit = function () {
                if (this.curRequest) {
                    var curXhr = this.curRequest;
                    this.curRequest = null;
                    curXhr.abort();
                    this.unblock();
                }
            };
            Form.prototype.submit = function (submitConfig) {
                var _this = this;
                this.abortSubmit();
                this.fire("submit");
                var url = Jhtml.Url.build(this.config.actionUrl || this.element.getAttribute("action"));
                var formData = this.buildFormData(submitConfig);
                var monitor = Jhtml.Monitor.of(this.element);
                var request = this.curRequest = Jhtml.getOrCreateContext(this.element.ownerDocument).requestor
                    .exec("POST", url);
                request.send(formData).then(function (response) {
                    if (_this.curRequest !== request)
                        return;
                    if ((!_this.config.successResponseHandler || !_this.config.successResponseHandler(response))
                        && monitor) {
                        monitor.handleDirective(response.directive);
                    }
                    if (submitConfig && submitConfig.success) {
                        submitConfig.success();
                    }
                    _this.unblock();
                    _this.fire("submitted");
                }).catch(function (e) {
                    if (_this.curRequest !== request)
                        return;
                    if (submitConfig && submitConfig.error) {
                        submitConfig.error();
                    }
                    _this.unblock();
                    _this.fire("submitted");
                });
                this.block();
            };
            Form.from = function (element) {
                var form = Jhtml.Util.getElemData(element, Form.KEY);
                if (form instanceof Form) {
                    return form;
                }
                form = new Form(element);
                Jhtml.Util.bindElemData(element, Form.KEY, form);
                form.observe();
                return form;
            };
            Form.KEY = "jhtml-form";
            return Form;
        }());
        Ui.Form = Form;
        var ControlLock = (function () {
            function ControlLock(containerElem) {
                this.containerElem = containerElem;
                this.lock();
            }
            ControlLock.prototype.lock = function () {
                if (this.controls)
                    return;
                this.controls = Jhtml.Util.find(this.containerElem, "input:not([disabled]), textarea:not([disabled]), button:not([disabled]), select:not([disabled])");
                for (var _i = 0, _a = this.controls; _i < _a.length; _i++) {
                    var control = _a[_i];
                    control.setAttribute("disabled", "disabled");
                }
            };
            ControlLock.prototype.release = function () {
                if (!this.controls)
                    return;
                for (var _i = 0, _a = this.controls; _i < _a.length; _i++) {
                    var control = _a[_i];
                    control.removeAttribute("disabled");
                }
                this.controls = null;
            };
            return ControlLock;
        }());
        (function (Form) {
            var Config = (function () {
                function Config() {
                    this.disableControls = true;
                    this.autoSubmitAllowed = true;
                    this.actionUrl = null;
                }
                return Config;
            }());
            Form.Config = Config;
        })(Form = Ui.Form || (Ui.Form = {}));
    })(Ui = Jhtml.Ui || (Jhtml.Ui = {}));
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Ui;
    (function (Ui) {
        var Link = (function () {
            function Link(elem) {
                var _this = this;
                this.elem = elem;
                this.ecr = new Jhtml.Util.CallbackRegistry();
                this.dcr = new Jhtml.Util.CallbackRegistry();
                this.disabled = false;
                this.requestConfig = Jhtml.FullRequestConfig.fromElement(this.elem);
                elem.addEventListener("click", function (evt) {
                    evt.preventDefault();
                    _this.handle();
                    return false;
                });
            }
            Link.prototype.handle = function () {
                if (this.disabled)
                    return;
                var event = new Link.Event();
                this.ecr.fire(event);
                if (!event.execPrevented) {
                    this.exec();
                }
            };
            Link.prototype.exec = function () {
                this.dcr.fire(Jhtml.Monitor.of(this.elem).exec(this.elem.href, this.requestConfig));
            };
            Object.defineProperty(Link.prototype, "element", {
                get: function () {
                    return this.elem;
                },
                enumerable: true,
                configurable: true
            });
            Link.prototype.dispose = function () {
                this.elem.remove();
                this.elem = null;
                this.dcr.clear();
            };
            Link.prototype.onEvent = function (callback) {
                this.ecr.on(callback);
            };
            Link.prototype.offEvent = function (callback) {
                this.ecr.off(callback);
            };
            Link.prototype.onDirective = function (callback) {
                this.dcr.on(callback);
            };
            Link.prototype.offDirective = function (callback) {
                this.dcr.off(callback);
            };
            Link.from = function (element) {
                var link = Jhtml.Util.getElemData(element, Link.KEY);
                if (link instanceof Link) {
                    return link;
                }
                link = new Link(element);
                Jhtml.Util.bindElemData(element, Link.KEY, link);
                return link;
            };
            Link.KEY = "jhtml-link";
            return Link;
        }());
        Ui.Link = Link;
        (function (Link) {
            var Event = (function () {
                function Event() {
                    this._execPrevented = false;
                }
                Object.defineProperty(Event.prototype, "execPrevented", {
                    get: function () {
                        return this._execPrevented;
                    },
                    enumerable: true,
                    configurable: true
                });
                Event.prototype.preventExec = function () {
                    this._execPrevented = true;
                };
                return Event;
            }());
            Link.Event = Event;
        })(Link = Ui.Link || (Ui.Link = {}));
    })(Ui = Jhtml.Ui || (Jhtml.Ui = {}));
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Ui;
    (function (Ui) {
        var Scanner = (function () {
            function Scanner() {
            }
            Scanner.scan = function (elem) {
                for (var _i = 0, _a = Jhtml.Util.findAndSelf(elem, Scanner.A_SELECTOR); _i < _a.length; _i++) {
                    var linkElem = _a[_i];
                    Ui.Link.from(linkElem);
                }
                for (var _b = 0, _c = Jhtml.Util.findAndSelf(elem, Scanner.FORM_SELECTOR); _b < _c.length; _b++) {
                    var fromElem = _c[_b];
                    Ui.Form.from(fromElem);
                }
            };
            Scanner.scanArray = function (elems) {
                for (var _i = 0, elems_2 = elems; _i < elems_2.length; _i++) {
                    var elem = elems_2[_i];
                    Scanner.scan(elem);
                }
            };
            Scanner.A_ATTR = "data-jhtml";
            Scanner.A_SELECTOR = "a[" + Scanner.A_ATTR + "]";
            Scanner.FORM_ATTR = "data-jhtml";
            Scanner.FORM_SELECTOR = "form[" + Scanner.FORM_ATTR + "]";
            return Scanner;
        }());
        Ui.Scanner = Scanner;
    })(Ui = Jhtml.Ui || (Jhtml.Ui = {}));
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Util;
    (function (Util) {
        var CallbackRegistry = (function () {
            function CallbackRegistry() {
                this.callbacks = {};
            }
            CallbackRegistry.prototype.on = function (callback) {
                this.onType("", callback);
            };
            CallbackRegistry.prototype.onType = function (type, callback) {
                if (type === void 0) { type = ""; }
                if (!this.callbacks[type]) {
                    this.callbacks[type] = [];
                }
                if (-1 == this.callbacks[type].indexOf(callback)) {
                    this.callbacks[type].push(callback);
                }
            };
            CallbackRegistry.prototype.off = function (callback) {
                this.offType("", callback);
            };
            CallbackRegistry.prototype.offType = function (type, callback) {
                if (type === void 0) { type = ""; }
                if (!this.callbacks[type])
                    return;
                var i = this.callbacks[type].indexOf(callback);
                if (i > -1) {
                    this.callbacks[type].splice(i, 1);
                }
            };
            CallbackRegistry.prototype.fire = function () {
                var args = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    args[_i] = arguments[_i];
                }
                this.fireType.apply(this, [""].concat(args));
            };
            CallbackRegistry.prototype.fireType = function (type) {
                var args = [];
                for (var _i = 1; _i < arguments.length; _i++) {
                    args[_i - 1] = arguments[_i];
                }
                if (!this.callbacks[type])
                    return;
                for (var _a = 0, _b = this.callbacks[type]; _a < _b.length; _a++) {
                    var callback = _b[_a];
                    callback.apply(void 0, args);
                }
            };
            CallbackRegistry.prototype.clear = function () {
                this.callbacks = {};
            };
            return CallbackRegistry;
        }());
        Util.CallbackRegistry = CallbackRegistry;
    })(Util = Jhtml.Util || (Jhtml.Util = {}));
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Util;
    (function (Util) {
        function closest(element, selector, selfIncluded) {
            var elem = element;
            do {
                if (elem.matches(selector)) {
                    return elem;
                }
            } while (elem = elem.parentElement);
            return null;
        }
        Util.closest = closest;
        function getElemData(elem, key) {
            return elem["data-" + key];
        }
        Util.getElemData = getElemData;
        function bindElemData(elem, key, data) {
            elem["data-" + key] = data;
        }
        Util.bindElemData = bindElemData;
        function findAndSelf(element, selector) {
            var foundElems = find(element, selector);
            if (element.matches(selector)) {
                foundElems.unshift(element);
            }
            return foundElems;
        }
        Util.findAndSelf = findAndSelf;
        function find(nodeSelector, selector) {
            var foundElems = [];
            var nodeList = nodeSelector.querySelectorAll(selector);
            for (var i = 0; i < nodeList.length; i++) {
                foundElems.push(nodeList.item(i));
            }
            return foundElems;
        }
        Util.find = find;
        function array(nodeList) {
            var elems = [];
            for (var i = 0; i < nodeList.length; i++) {
                elems.push(nodeList.item(i));
            }
            return elems;
        }
        Util.array = array;
    })(Util = Jhtml.Util || (Jhtml.Util = {}));
})(Jhtml || (Jhtml = {}));
var Jhtml;
(function (Jhtml) {
    var Util;
    (function (Util) {
        var ElemConfigReader = (function () {
            function ElemConfigReader(element) {
                this.element = element;
            }
            ElemConfigReader.prototype.buildName = function (key) {
                return "data-jhtml-" + key;
            };
            ElemConfigReader.prototype.readBoolean = function (key, fallback) {
                var value = this.element.getAttribute(this.buildName(key));
                if (value === null) {
                    return fallback;
                }
                switch (value) {
                    case "true":
                    case "TRUE:":
                        return true;
                    case "false":
                    case "FALSE":
                        return false;
                    default:
                        throw new Error("Attribute '" + this.buildName(key) + " of Element " + this.element.tagName
                            + "  must contain a boolean value 'true|false'.");
                }
            };
            return ElemConfigReader;
        }());
        Util.ElemConfigReader = ElemConfigReader;
    })(Util = Jhtml.Util || (Jhtml.Util = {}));
})(Jhtml || (Jhtml = {}));
//# sourceMappingURL=jhtml.js.map
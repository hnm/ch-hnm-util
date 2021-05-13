namespace Jhtml {
	
	export class Monitor {
		public context: Context;
		public history: History;
		public active: boolean = true;
		private compHandlers: CompHandlerReg = {};
		private directiveCbr = new Util.CallbackRegistry<(evt: DirectiveEvent) => any>();
		private directiveExecutedCbr = new Util.CallbackRegistry<(evt: DirectiveEvent) => any>();
		
		constructor(private container: Element, history: History, private _pseudo: boolean) {
			this.context = Context.from(container.ownerDocument);
			this.history = history;
			this.history.onChanged(() => {
			   this.historyChanged(); 
			});
		}
		
		get compHandlerReg(): CompHandlerReg {
			return this.compHandlers;
		}
		
		get pseudo(): boolean {
			return this._pseudo;
		}
		
		registerCompHandler(compName: string, compHandler: CompHandler) {
			this.compHandlers[compName] = compHandler;
		}
		
		unregisterCompHandler(compName: string) {
			delete this.compHandlers[compName];
		}
		
		private pushing: boolean = false;
		private pendingPromise: Promise<Directive> = null;
		
		exec(urlExpr: Url|string, requestConfig?: RequestConfig): Promise<Directive> {
			if (this.history.currentEntry) {
				this.history.currentEntry.scrollPos = this.history.currentPage.config.scrollPos = window.pageYOffset;
			}
			
			let url = Url.create(urlExpr);
			
			let config = FullRequestConfig.from(requestConfig);
			
			let page = this.history.getPageByUrl(url);
			
			if (!page) {
				page = new Page(url, this.context.requestor.lookupDirective(url));
			} else if (page.config.frozen) {
				if (page.disposed) {
					throw new Error("Target page is frozen and disposed.");
				}
			} else if (page.disposed || config.forceReload || !page.config.keep) {
				page.promise = this.context.requestor.lookupDirective(page.loadUrl || url);
			}
			
			// page.promise could be changed by history.push callback
			let promise = this.pendingPromise = page.promise;
			
			if (config.pushToHistory && page !== this.history.currentPage) {
			    this.pushing = true;
				this.history.push(page);
				this.pushing = false;
			}

			promise.then((directive: Directive) => {
				if (promise !== this.pendingPromise) return;
				
				this.handleDirective(directive, true, config.usePageScrollPos);	
			});
			
			return promise;
		}
		
		public handleDirective(directive: Directive, fresh: boolean = true, usePageScrollPos: boolean = false) {
			this.triggerDirectiveCallbacks({ directive: directive, new: fresh });

			directive.exec(this);

			if (this.history.currentEntry && this.active) {
				window.scroll(0, (usePageScrollPos ? this.history.currentPage.config.scrollPos 
						: this.history.currentEntry.scrollPos));
			}
			this.triggerDirectiveExecutedCallbacks({ directive: directive, new: fresh });
		}
		
		private triggerDirectiveCallbacks(evt: DirectiveEvent) {
			this.directiveCbr.fire(evt);
		}
		
		public onDirective(callback: (evt: DirectiveEvent) => any) {
			this.directiveCbr.on(callback);
		}
		
		public offDirective(callback: (evt: DirectiveEvent) => any) {
			this.directiveCbr.off(callback);
		}
		
		private triggerDirectiveExecutedCallbacks(evt: DirectiveEvent) {
			this.directiveExecutedCbr.fire(evt);
		}
		
		public onDirectiveExecuted(callback: (evt: DirectiveEvent) => any) {
			this.directiveExecutedCbr.on(callback);
		}
		
		public offDirectiveExecuted(callback: (evt: DirectiveEvent) => any) {
			this.directiveExecutedCbr.off(callback);
		}
		
		public lookupModel(url: Url): Promise<ModelResult> {
			return new Promise(resolve => {
				this.context.requestor.exec("GET", url).send().then((response: Response) => {
					if (response.model) {
						resolve({ model: response.model, response: response });
					} else {
						this.handleDirective(response.directive);
					}
				});
			});
		}
		
		private historyChanged() {
		    if (this.pushing/* || !this.active*/) return;
		    
		    let currentPage = this.history.currentPage;
		    
		    if (!currentPage.promise) {
		        currentPage.promise = this.context.requestor.lookupDirective(currentPage.url);
		    }
		    
		    currentPage.promise.then(directive => {
                this.handleDirective(directive);
            });
		}
		
		private static readonly KEY: string = "jhtml-monitor";
		private static readonly CSS_CLASS: string = "jhtml-selfmonitored";
		
		static of(element: Element, selfIncluded: boolean = true): Monitor|null {
			if (selfIncluded && element.matches("." + Monitor.CSS_CLASS)) {
				return Monitor.test(element);
			}

			if (element = element.closest("." + Monitor.CSS_CLASS)) {
				return Monitor.test(element);
			}
			
			return null;
		}
		
		static test(element: Element): Monitor|null {
			let monitor = Util.getElemData(element, Monitor.KEY);
			if (element.classList.contains(Monitor.CSS_CLASS) && monitor instanceof Monitor) {
				return monitor;
			}
			return null;
		}
		
		static create(container: Element, history: History, pseudo: boolean): Monitor {
			let monitor = Monitor.test(container);
			
			if (monitor) {
				throw new Error("Element is already monitored.");
			}
			
			container.classList.add(Monitor.CSS_CLASS);
			
			monitor = new Monitor(container, history, pseudo);
			Util.bindElemData(container, Monitor.KEY, monitor);
			
			return monitor;
		}
	}
	
	export interface DirectiveEvent {
		directive: Directive;
		new: boolean;
	}
	
	export interface ModelResult {
		model: Model; 
		response: Response;
	}
} 
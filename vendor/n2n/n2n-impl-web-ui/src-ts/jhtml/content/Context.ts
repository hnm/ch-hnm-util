namespace Jhtml {
	
	export class Context {
		private _requestor: Requestor;
		private modelState: ModelState;
	
		private compHandlers: { [compName: string]: CompHandler } = {};
		private readyCbr: Util.CallbackRegistry<ReadyCallback> = new Util.CallbackRegistry<ReadyCallback>();
		
		constructor(private _document: Document) {
			this._requestor = new Requestor(this);
			
			this._document.addEventListener("DOMContentLoaded", () => {
				this.readyCbr.fire([this.document.documentElement], {});
			}, false);
		}
		
		private readyBound: boolean = false;
		
		get requestor(): Requestor {
			return this._requestor;
		}
		
		get document(): Document {
			return this._document;
		}
		
		isJhtml(): boolean {
			return this.getModelState(false) ? true : false;
		}
		
		isBrowsable(): boolean {
			if (!this.isJhtml()) return false;
			
			return this.getModelState(false).metaState.browsable;
		}
		
		private getModelState(required: boolean): ModelState {
			if (this.modelState) {
				return this.modelState;
			}
			
			try {
				this.modelState = ModelFactory.createStateFromDocument(this.document);
				if (this.modelState.metaState.browsable) {
					Ui.Scanner.scan(this.document.documentElement);
				}
			} catch (e) { 
				if (e instanceof ParseError) return null;
				
				throw e;
			}
			
			if (!this.modelState && required) {
				throw new Error("No jhtml context");
			}
			
			return this.modelState || null;
		}
		
		replaceModel(newModel: Model, montiorCompHandlers: { [compName: string]: CompHandler } = {}): void {
			let boundModelState: ModelState = this.getModelState(true);
			
			let mergeObserver = boundModelState.metaState.replaceWith(newModel.meta);
			
			mergeObserver.done(() => {
				for (let name in boundModelState.comps) {
					let comp = boundModelState.comps[name];
					
					if (!(montiorCompHandlers[name] && montiorCompHandlers[name].detachComp(comp))
							&& !(this.compHandlers[name] && this.compHandlers[name].detachComp(comp))) {
						comp.detach();
					}
				}
				
				if (!boundModelState.container.matches(newModel.container)) {
					boundModelState.container.detach();
					boundModelState.container = newModel.container;
					boundModelState.container.attachTo(boundModelState.metaState.containerElement);
				}
				
				for (let name in newModel.comps) {
					let comp = boundModelState.comps[name] = newModel.comps[name];
					
					if (!(montiorCompHandlers[name] && montiorCompHandlers[name].attachComp(comp))
							&& !(this.compHandlers[name] && this.compHandlers[name].attachComp(comp))) {
						comp.attachTo(boundModelState.container.compElements[name]);
					}
				}
			});
		}
		
		importMeta(meta: Meta): LoadObserver {
			let boundModelState = this.getModelState(true);
			
			let loadObserver = boundModelState.metaState.import(meta, true);
//			this.registerLoadObserver(loadObserver);
			return loadObserver;
		}
		
		registerNewModel(model: Model) {
			let container = model.container;
			if (container) {
				let containerReadyCallback = () => {
					container.off("attached", containerReadyCallback)
					console.log("container attached");
//					container.loadObserver.whenLoaded(() => {
						this.readyCbr.fire(container.elements, { container: container });
						this.triggerAndScan(container.elements);
//					});
				};
				container.on("attached", containerReadyCallback);
			}
			
			for (let comp of Object.values(model.comps)) {
				let compReadyCallback = () => {
					comp.off("attached", compReadyCallback);
//					comp.loadObserver.whenLoaded(() => {
						this.readyCbr.fire(comp.elements, { comp: Comp });
						this.triggerAndScan(comp.elements);
//					});
				};
				comp.on("attached", compReadyCallback);
			}
			
			let snippet = model.snippet;
			if (snippet) {
				let snippetReadyCallback = () => {
					snippet.off("attached", snippetReadyCallback)
					this.importMeta(model.meta).whenLoaded(() => {
						this.readyCbr.fire(snippet.elements, { snippet: snippet });
						this.triggerAndScan(snippet.elements);
					});
				};
				snippet.on("attached", snippetReadyCallback);
			}
		}
		
		private triggerAndScan(elements: Element[]) {
			let w: any = window;
			if (w.n2n && w.n2n.dispatch) {
				w.n2n.dispatch.update();
			}
			Ui.Scanner.scanArray(elements);
		}
		
		replace(text: string, mimeType: string, replace: boolean) {
			this.document.open(mimeType, replace? "replace" : null);
			this.document.write(text);
			this.document.close();
		}
		
		registerCompHandler(compName: string, compHandler: CompHandler) {
			this.compHandlers[compName] = compHandler;
		}
		
		unregisterCompHandler(compName: string) {
			delete this.compHandlers[compName];
		}
		
		onReady(readyCallback: ReadyCallback) {
			this.readyCbr.on(readyCallback);

			if ((this._document.readyState === "complete" || this._document.readyState === "interactive") 
					 && (!this.modelState || !this.modelState.metaState.busy)) {
				readyCallback([this.document.documentElement], {});	
			}
		}
		
		offReady(readyCallback: ReadyCallback) {
			this.readyCbr.off(readyCallback);
		}
		
		private static KEY: string = "data-jhtml-context";
		
		static test(document: Document): Context|null {
			let context: any = Util.getElemData(document.documentElement, Context.KEY);
			if (context instanceof Context) {
				return context;
			}
			return null;
		}
		
		static from(document: Document): Context {
			let context = Context.test(document)
			if (context) return context;
			
			Util.bindElemData(document.documentElement, Context.KEY, context = new Context(document));
			return context;
		}
	}
	
	export interface CompHandler {
		attachComp(comp: Comp): boolean;
		
		detachComp(comp: Comp): boolean;
	}
	
	export interface CompHandlerReg {
		[compName: string]: CompHandler
	}
	
	export interface ReadyCallback {
		(elements: Array<Element>, event: ReadyEvent ): any;
	}
	
	export interface ReadyEvent {
		container?: Container;
		comp?: Comp;
		snippet?: Snippet;
	}
}
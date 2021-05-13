namespace Jhtml.Ui {
	export class Link {
		private requestConfig: RequestConfig;
		private ecr: Util.CallbackRegistry<Link.EventCallback> = new Util.CallbackRegistry();
		private dcr: Util.CallbackRegistry<Link.DirectiveCallback> = new Util.CallbackRegistry();

		disabled: boolean = false;
		
		constructor(private elem: HTMLAnchorElement) {
			this.requestConfig = FullRequestConfig.fromElement(this.elem);
			
			elem.addEventListener("click", (evt) => {
				evt.preventDefault();
				
				this.handle();

				return false;
			});
		}
		
		private handle() {
			if (this.disabled) return;
			
			let event = new Link.Event();
			this.ecr.fire(event);
			
			if (!event.execPrevented) {
				this.exec();
			}
		}
		
		exec() {
			this.dcr.fire(Monitor.of(this.elem).exec(this.elem.href, this.requestConfig));
		}
		
		get element(): HTMLAnchorElement {
			return this.elem;
		}
		
		dispose() {
			this.elem.remove();
			this.elem = null;
			this.dcr.clear();
		}
		
		onEvent(callback: Link.EventCallback) {
			this.ecr.on(callback);
		}
		
		offEvent(callback: Link.EventCallback) {
			this.ecr.off(callback);
		}
		
		onDirective(callback: Link.DirectiveCallback) {
			this.dcr.on(callback);
		}
		
		offDirective(callback: Link.DirectiveCallback) {
			this.dcr.off(callback);
		}
		
		private static readonly KEY: string = "jhtml-link";
		
		public static from(element: HTMLAnchorElement): Link {
			let link = Util.getElemData(element, Link.KEY);
			if (link instanceof Link) {
				return link;
			}
			
			link = new Link(element);
			Util.bindElemData(element, Link.KEY, link);
			return link;
		}
	}
	
	export namespace Link {
		export class Event {
			private _execPrevented: boolean = false;
		
			get execPrevented(): boolean {
				return this._execPrevented;
			}
			
			preventExec() {
				this._execPrevented = true;
			}
		}
		
		export interface EventCallback {
			(evt: Event): any;
		}
		
		export interface DirectiveCallback {
			(directivePromise: Promise<Directive>): any;
		}
	}
}
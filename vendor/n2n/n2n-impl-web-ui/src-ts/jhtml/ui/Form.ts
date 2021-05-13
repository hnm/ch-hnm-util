namespace Jhtml.Ui {
	
	export class Form {
		private _observing = false;
		private _config: Form.Config = new Form.Config();
		private callbackRegistery: Util.CallbackRegistry<FormCallback> = new Util.CallbackRegistry<FormCallback>();
		private curRequest: Request = null;
		
		constructor(private _element: HTMLFormElement) {
		}
		
		get element(): HTMLFormElement {
			return this._element;
		}
		
		get observing(): boolean {
			return this._observing;
		}
		
		get config(): Form.Config {
			return this._config;
		}
		
		reset() {
			this.element.reset();
		}
		
		private fire(eventType: Form.EventType) {
			this.callbackRegistery.fireType(eventType.toString(), this);
		}
		
		public on(eventType: Form.EventType, callback: FormCallback) {
			this.callbackRegistery.onType(eventType.toString(), callback);
		}
		
		public off(eventType: Form.EventType, callback: FormCallback) {
			this.callbackRegistery.offType(eventType.toString(), callback);
		}
		
		
		private tmpSubmitDirective: Form.SubmitDirective = null;
		
		public observe() {
			if (this._observing) return;
			
			this._observing = true;
			
			this.element.addEventListener("submit", (evt) => {
				evt.preventDefault();
				if (this.config.autoSubmitAllowed) {
					let submitDirective = this.tmpSubmitDirective;
					setTimeout(() => {
						this.submit(submitDirective);
					});
				}
				this.tmpSubmitDirective = null;
			}, false);
			
			
//			this.element.addEventListener("submit", (evt) => {
//				console.log("on submit");
//			}, true);
			
			Util.find(this.element, "input[type=submit], button[type=submit]").forEach((elem: Element) => {
//				elem.addEventListener("click", (evt) => {
//					
//					return false;
//				}, true);
				elem.addEventListener("click", (evt) => {
					this.tmpSubmitDirective = { button: elem };
				}, false);
			});
		}
		
		private buildFormData(submitConfig?: Form.SubmitDirective): FormData {
			var formData = new FormData(this.element);
			
			if (submitConfig && submitConfig.button) {
				formData.append(submitConfig.button.getAttribute("name"), submitConfig.button.getAttribute("value"));
			}
			
			return formData;
		}
		
		private controlLock: ControlLock;
		private controlLockAutoReleaseable = true;
		
		private block() {
			if (!this.controlLock && this.config.disableControls) {
				this.disableControls();
			}	
		}
		
		private unblock() {
			if (this.controlLock && this.controlLockAutoReleaseable) {
				this.controlLock.release();
			}
		}
		
		public disableControls(autoReleaseable: boolean = true) {
			this.controlLockAutoReleaseable = autoReleaseable;
			
			if (this.controlLock) return;
			
			this.controlLock = new ControlLock(this.element);
		}
		
		public enableControls() {
			if (this.controlLock) {
				this.controlLock.release();
				this.controlLock = null;
				this.controlLockAutoReleaseable = true;
			}
		}
		
		public abortSubmit() {
			if (this.curRequest) {
				var curXhr = this.curRequest;
				this.curRequest = null;
				curXhr.abort();
				this.unblock();
			}
		}
		
		public submit(submitConfig?: Form.SubmitDirective) {
			this.abortSubmit();
			
			this.fire("submit");
			
			var url = Url.build(this.config.actionUrl || this.element.getAttribute("action"));
			var formData = this.buildFormData(submitConfig);

			let monitor = Monitor.of(this.element);
			let request = this.curRequest = Jhtml.getOrCreateContext(this.element.ownerDocument).requestor
					.exec("POST", url);

			request.send(formData).then((response: Response) => {
				if (this.curRequest !== request) return;

				if ((!this.config.successResponseHandler || !this.config.successResponseHandler(response)) 
						&& monitor) {
					monitor.handleDirective(response.directive);
				}
				
				if (submitConfig && submitConfig.success) {
					submitConfig.success();
				}
				
				this.unblock();
				this.fire("submitted");
			}).catch((e) => {
				if (this.curRequest !== request) return;
				
				if (submitConfig && submitConfig.error) {
					submitConfig.error();
				}
				
				this.unblock();
				this.fire("submitted");
			});
			
			this.block();
		}
		
		private static readonly KEY: string = "jhtml-form";
		
		public static from(element: HTMLFormElement): Form {
			let form = Util.getElemData(element, Form.KEY);
			if (form instanceof Form) {
				return form;
			}
			
			form = new Form(element);
			Util.bindElemData(element, Form.KEY, form);
			form.observe();
			return form;
		}
	}
	
	
	class ControlLock {
		private controls: Array<Element>;
		
		constructor(private containerElem: Element) {
			this.lock();
		}
		
		public lock() {
			if (this.controls) return;
			
			this.controls = Util.find(this.containerElem, "input:not([disabled]), textarea:not([disabled]), button:not([disabled]), select:not([disabled])");
			for (let control of this.controls) {
				control.setAttribute("disabled", "disabled");
			}
		}
		
		public release() {
			if (!this.controls) return;
			
			for (let control of this.controls) {
				control.removeAttribute("disabled");
			}
			this.controls = null;
		}
	}
	
	export namespace Form {
		export class Config {
			public disableControls = true;
			public successResponseHandler: (response: Response) => boolean;
			public autoSubmitAllowed: boolean = true;
			public actionUrl: Url|string = null;
		}
		
		export type EventType = "submit" | "submitted";
		
		export interface SubmitDirective {
			success?: () => any,
			error?: () => any,
			button?: Element
			
		}
	}
	
	export interface FormCallback {
		(form: Form): any
	}
}
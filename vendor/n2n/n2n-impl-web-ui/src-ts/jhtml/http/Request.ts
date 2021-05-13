namespace Jhtml {
	
	export class Request  {
		constructor (private requestor: Requestor, private _xhr: XMLHttpRequest, private _url: Url) {
		}
		
		get xhr(): XMLHttpRequest {
			return this._xhr;
		}
		
		get url(): Url {
			return this._url;
		}
		
		abort() {
			this.xhr.abort();
		}
		
		send(data?: FormData): Promise<Response> {
			this.xhr.send(data);
			
			return this.buildPromise();
		}
		
		private buildPromise(): Promise<Response> {
			return new Promise((resolve) =>  {
				this.xhr.onreadystatechange = () => {
					if (this.xhr.readyState != 4) return;
					
					switch (this.xhr.status) {
						case 200:
							let model: Model;
							let directive: Directive; 
							let additionalData: any;
							if (!this.xhr.getResponseHeader("Content-Type").match(/json/)) {
								model = this.createModelFromHtml(this.xhr.responseText);
							} else {
								let jsonObj: any =  this.createJsonObj(this.url, this.xhr.responseText);
								additionalData = jsonObj.additional;
								directive = this.scanForDirective(this.url, jsonObj);
								if (!directive && (additionalData === undefined || jsonObj.content)) {
									model = this.createModelFromJson(this.url, jsonObj);
								}
							}
							
							if (model) {
								directive = model.isFull() ? new FullModelDirective(model, additionalData) : null;
							}
							
							let response: Response = { status: 200, request: this, model: model, directive: directive, additionalData: additionalData };
//							if (model) {
//								model.response = response;
//							}
							resolve(response);
							break;
						default:
							resolve({
								status: this.xhr.status,
								request: this,
								directive: new ReplaceDirective(this.xhr.status, this.xhr.responseText, 
										this.xhr.getResponseHeader("Content-Type"), this.url)
							});
					}
				};
				
				this.xhr.onerror = () => {
					throw new Error("Could not request " + this.url.toString());
				};
			});
		}
		
		private createJsonObj(url: Url, jsonText: string): any {
			try {
				return JSON.parse(jsonText)
			} catch (e) {
				throw new Error(url + "; invalid json response: " + e.message);
			}
		}
		
		private scanForDirective(url: Url, jsonObj: any): Directive|null {
			switch(jsonObj.directive) {
			case "redirect":
				return new RedirectDirective(url, RedirectDirective.Type.TARGET, Jhtml.Url.create(jsonObj.location),
						FullRequestConfig.from(jsonObj.requestConfig), jsonObj.additional);
			case "redirectToReferer":
				return new RedirectDirective(url, RedirectDirective.Type.REFERER, Jhtml.Url.create(jsonObj.location),
                        FullRequestConfig.from(jsonObj.requestConfig), jsonObj.additional);
			case "redirectBack": 
				return new RedirectDirective(url, RedirectDirective.Type.BACK, Jhtml.Url.create(jsonObj.location),
						FullRequestConfig.from(jsonObj.requestConfig), jsonObj.additional);
			default:
//				if (/*jsonObj.additional !== undefined && */!jsonObj.content) {
//					return new DataDirective(url, jsonObj.additional);
//				}
			
				return null;
			}
			
		}
		
		private createModelFromJson(url: Url, jsonObj: any): Model {
			try {
				let model = ModelFactory.createFromJsonObj(jsonObj);
				this.requestor.context.registerNewModel(model);
				return model;
			} catch (e) {
				if (e instanceof ParseError || e instanceof SyntaxError) {
			        throw new Error(url + "; no or invalid json: " + e.message);
			    }
				
				throw e;
			}
		}
		
		private createModelFromHtml(html: string): Model {
			try {
				let model = ModelFactory.createFromHtml(html, true);
				this.requestor.context.registerNewModel(model);
				return model;
			} catch (e) {
				throw new Error(this.url + "; invalid jhtml response: " + e.message);
			}
		}
	}
}
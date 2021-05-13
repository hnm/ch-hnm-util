namespace Jhtml {
    export class Page {
    	private _promise: Promise<Directive>|null = null;
    	private _loaded: boolean = false;
    	private _config = new Page.Config();
    	private cbr = new Util.CallbackRegistry<() => any>();
    	public loadUrl: Url = null;
    
    	constructor(private _url: Url, promise: Promise<Directive>|null) {
    		this.promise = promise;
    	}
    	
    	get config(): Page.Config {
    		return this._config;
    	}
    	
    	get loaded(): boolean {
    		return this._loaded;
    	}
    	
    	get url(): Url {
    		return this._url;
    	}
    	
    	dispose() {
    		this.promise = null;
    	}
    	
    	private fire(eventType: Page.EventType) {
    		this.cbr.fireType(eventType);
    	}
    	
    	on(eventType: Page.EventType, callback: () => any) {
    		this.cbr.onType(eventType, callback);
    	}
    	
    	off(eventType: Page.EventType, callback: () => any) {
    		this.cbr.offType(eventType, callback);
    	}
    	
    	get promise() {
    		return this._promise;
    	}
    	
    	set promise(promise: Promise<Directive>|null) {
    		if (this._promise === promise) return;
    		
    		if (this._promise) {
    			this._promise.then((directive: Directive) => {
    				directive.destroy();
    			});
    		}
    		
    		this._promise = promise;
    		
    		if (!promise) {
    			this.fire("disposed");
    			return;
    		}
    		
    		this._loaded = false;
    		promise.then(() => {
    			this._loaded = true;
    		});
    		this.fire("promiseAssigned");
    	}
    	
    	get disposed(): boolean {
    		return this.promise ? false : true;
    	}
    }
    
    export namespace Page {
    	export class Config {
    		frozen: boolean = false;
    		keep: boolean = false;
    		scrollPos: number = 0;
    	}
    	
    	export type EventType = "disposed" | "promiseAssigned";
    }
}
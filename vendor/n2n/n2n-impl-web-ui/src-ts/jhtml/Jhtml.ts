namespace Jhtml {
	export function ready(callback: ReadyCallback, document?: Document) {
		 return getOrCreateContext(document).onReady(callback);
	}
	
	let browser: Browser|null = null;
	let monitor: Monitor|null = null;
	
	export function getOrCreateBrowser(): Browser|null {
		if (browser) return browser;
		
		let context: Context = getOrCreateContext();
	
		if (!context.isBrowsable()) return null;
		
		let history = new History();
		browser = new Browser(window, history);
		monitor = Monitor.create(context.document.documentElement, history, false);

		return browser;
	}
	
	export function getOrCreateMonitor(): Monitor|null {
		getOrCreateBrowser();
		return monitor;
	}
	
	export function getOrCreateContext(document?: Document): Context {
		return Context.from(document || window.document);
	}
	
	export function lookupModel(url: Url|string): Promise<ModelResult> {
		getOrCreateBrowser();
		if (monitor) {
			return monitor.lookupModel(Url.create(url))
		}
		
		return new Promise(resolve => {
			getOrCreateContext().requestor.exec("GET", Url.create(url)).send().then((response: Response) => {
				resolve({ model: response.model, response: response });
			});
		});
	}
	
	export function request(method: Requestor.Method, url: Url|string): Request {
		return getOrCreateContext().requestor.exec(method, Url.create(url));
	}
	
	
	window.document.addEventListener("DOMContentLoaded", () => {
		getOrCreateBrowser();
	}, false);
}
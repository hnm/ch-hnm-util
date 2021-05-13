namespace Jhtml {
	
	export interface Directive {
		
		getAdditionalData(): any;
		
		exec(monitor: Monitor);
		
		destroy();
	}
    
    export class FullModelDirective implements Directive {
    	constructor(private model: Model, public additionalData: any) {
    		if (!model.isFull()) {
    			throw new Error("Invalid argument. Full model required.")
    		}
    	}
    	
    	getAdditionalData(): any {
    		return this.additionalData;
    	}
    	
    	exec(monitor: Monitor) {
    		if (!monitor.pseudo) {
    			monitor.context.replaceModel(this.model, monitor.compHandlerReg);
    			return;
    		}
    		
    		let loadObserver = monitor.context.importMeta(this.model.meta);
    		
    		for (let name in this.model.comps) {
    			if (!monitor.compHandlerReg[name]) continue;
    			
    			monitor.compHandlerReg[name].attachComp(this.model.comps[name]);
			}
    		
    	}
    	
    	destroy() {
    		this.model.abadone();
    	}
    }
    
    export class ReplaceDirective implements Directive {
        constructor(public status: number, public responseText: string, public mimeType: string, public url: Url) {
        }
    	
        getAdditionalData(): any {
        	return null;
        }
        
        exec(monitor: Monitor) {
        	monitor.context.replace(this.responseText, this.mimeType, 
        			monitor.history.currentPage.url.equals(this.url));
        }
        
        destroy() {
        }
    }
    
    export class RedirectDirective implements Directive {
    	constructor(public srcUrl: Url, public back: RedirectDirective.Type, public targetUrl: Url, 
    			public requestConfig?: RequestConfig, public additionalData?: any) {
    	}
    	
    	getAdditionalData(): any {
        	return this.additionalData;
        }
        
    	exec(monitor: Monitor) {
            switch (this.back) {
            case RedirectDirective.Type.REFERER:
            	let currentPage = monitor.history.currentPage;
                if (!currentPage.url.equals(this.srcUrl)) {
                	if (currentPage.disposed) {
                		monitor.exec(currentPage.url, { pushToHistory: false })
                	}
                	return;
                }
            case RedirectDirective.Type.BACK:
                if (monitor.history.currentEntry.index > 0) {
                	let entry = monitor.history.getEntryByIndex(monitor.history.currentEntry.index - 1);
                	monitor.exec(entry.page.url, this.requestConfig);
                	monitor.history.currentEntry.scrollPos = entry.scrollPos;
                	return;
                } 
            default:
                monitor.exec(this.targetUrl, this.requestConfig);
            }
        }
    	
    	destroy() {
        }
    }
    
    export namespace RedirectDirective {
        export enum Type {
            TARGET, REFERER, BACK 
        }
    }
    
//    export class SnippetDirective implements Directive {
//    	constructor(public srcUrl: Url, public model: Model) {
//    	}
//    	
//    	getAdditionalData(): any {
//        	return this.model.additionalData;
//        }
//    	
//    	exec(monitor: Monitor) {
//    		throw new Error(this.srcUrl + "; can not exec snippet only directive.");
//        }
//    }
//    
//    export class DataDirective implements Directive {
//    	constructor(public srcUrl: Url, public additionalData: any) {
//    	}
//    	
//    	getAdditionalData(): any {
//        	return this.additionalData;
//        }
//    	
//    	exec(monitor: Monitor) {
//    		throw new Error(this.srcUrl + "; can not exec data only directive.");
//        }
//    }
    
}
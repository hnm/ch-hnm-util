namespace Jhtml {
	
	export class Meta {
		public headElements: Array<Element> = []; 
		public bodyElements: Array<Element> = []; 
		public bodyElement: Element|null = null;
		public containerElement: Element|null = null;
	}
	
    export class MetaState {
    	private _browsable: boolean = false;
    	private mergeQueue: ElementMergeQueue;
    	
    	constructor(private rootElem: Element, private headElem: Element, private bodyElem: Element,
    			private containerElem: Element) {
    		this.mergeQueue = new ElementMergeQueue();
    		this.markAsUsed(this.headElements);
    		this.markAsUsed(this.bodyElements);
    		
    		let reader = new Util.ElemConfigReader(containerElem);
    		this._browsable = reader.readBoolean("browsable", false);
    	}
    	
    	get browsable(): boolean {
    		return this._browsable;
    	}
    	
    	private markAsUsed(elements: Element[]) {
    		for (let element of elements) {
    			if (element === this.containerElement) continue;
    			
    			this.mergeQueue.addUsed(element);
    			
    			this.markAsUsed(Util.array(element.children));
    		}
    	}
    	
    	get headElements(): Array<Element> {
    		return Util.array(this.headElem.children);
    	}
    	
    	get bodyElements(): Array<Element> {
    		return Util.array(this.bodyElem.children);
    	}
    	
    	get containerElement(): Element {
    		return this.containerElem;
    	}

    	private loadObservers: Array<LoadObserver> = [];
		
		private registerLoadObserver(loadObserver: LoadObserver) {
			this.loadObservers.push(loadObserver);
			loadObserver.whenLoaded(() => {
				this.loadObservers.splice(this.loadObservers.indexOf(loadObserver), 1);
			});
		}
		
		get busy(): boolean {
			return this.loadObservers.length > 0;
		}
    	
    	public import(newMeta: Meta, curModelDependent: boolean): LoadObserver {
    		let merger = new Merger(this.rootElem, this.headElem, this.bodyElem,
    				this.containerElem, newMeta.containerElement);
			
    		merger.importInto(newMeta.headElements, this.headElem, Meta.Target.HEAD);
    		merger.importInto(newMeta.bodyElements, this.bodyElem, Meta.Target.BODY);
			
    		this.registerLoadObserver(merger.loadObserver);
    		
    		if (!curModelDependent) {
    			return merger.loadObserver;
    		}
    		
    		this.mergeQueue.finalizeImport(merger);
    		
			return merger.loadObserver;
    	}
    	
    	public replaceWith(newMeta: Meta): MergeObserver {
    		let merger = new Merger(this.rootElem, this.headElem, this.bodyElem,
    				this.containerElem, newMeta.containerElement);
    		
			merger.mergeInto(newMeta.headElements, this.headElem, Meta.Target.HEAD);
			merger.mergeInto(newMeta.bodyElements, this.bodyElem, Meta.Target.BODY);
			
			if (newMeta.bodyElement) {
				merger.mergeAttrsInto(newMeta.bodyElement, this.bodyElem);
			}
			
			this.registerLoadObserver(merger.loadObserver);
			
			return this.mergeQueue.finalizeMerge(merger);
		}
    }
    
    class ElementMergeQueue {
		private usedElements: Array<Element> = [];
    	private unnecessaryElements: Array<Element> = [];
    	private blockedElements: Array<Element> = [];
    	private curObserver: MergeObserverImpl|null = null;
    
    	addUsed(usedElement: Element) {
    		if (this.containsUsed(usedElement)) return;
    		
    		this.usedElements.push(usedElement);
    	}
    	
    	containsUsed(element: Element) {
    		return -1 < this.usedElements.indexOf(element);
    	}
    	
    	/**
    	 * Blocked elements are added outside of the 
    	 * @param blockedElement
    	 */
    	private addBlocked(blockedElement: Element) {
    		if (this.containsBlocked(blockedElement)) return;
    		
    		this.blockedElements.push(blockedElement);
    	}
    	
    	containsBlocked(element: Element) {
    		return -1 < this.blockedElements.indexOf(element);
    	}
    	
    	private addUnnecessary(unnecessaryElement: Element) {
    		if (this.containsUnnecessary(unnecessaryElement)) return;
    		
    		this.unnecessaryElements.push(unnecessaryElement);
    	}
    	
//    	private clearUnnecessaries() {
//    		this.unnecessaryElements.splice(0);
//    	}
    	
    	containsUnnecessary(element: Element) {
    		return -1 < this.unnecessaryElements.indexOf(element);
    	}
    	
    	private removeUnnecessary(element: Element) {
    		this.unnecessaryElements.splice(
    				this.unnecessaryElements.indexOf(element), 1);
    	}
    	
    	private approveRemove() {
    		let removeElement;
    		while (removeElement = this.unnecessaryElements.pop()) {
    			removeElement.remove();
    		}
    	}
    	
    	finalizeImport(merger: Merger) {
    		for (let element of merger.processedElements) {
				this.removeUnnecessary(element);
    			this.addUsed(element);
    		}
    	}
    	
    	finalizeMerge(merger: Merger): MergeObserver {
    		let removableElements: Element[] = [];
    		
    		let remainingElements = merger.remainingElements;
			let remainingElement;
			while (remainingElement = remainingElements.pop()) {
				if (this.containsBlocked(remainingElement)) continue;
				
				if (!this.containsUsed(remainingElement)
						&& !this.containsUnnecessary(remainingElement)) {
					this.addBlocked(remainingElement);
					continue;
				}
				
				this.addUnnecessary(remainingElement);
			}
			
			for (let processedElement of merger.processedElements) {
				this.removeUnnecessary(processedElement);
				this.addUsed(processedElement);
			}
			
			if (this.curObserver !== null) {
				this.curObserver.abort();
			}
			
			let observer = this.curObserver = new MergeObserverImpl();
			
			merger.loadObserver.whenLoaded(() => {
				if (this.curObserver !== observer) {
					return;
				}
				
				this.curObserver = null;
				observer.complete();
				
				this.approveRemove();
			});
			
			return observer;
    	}
    }
    
    export interface MergeObserver {
    	done(callback: () => any): MergeObserver;
    	aborted(callback: () => any): MergeObserver;
    }
    
    class MergeObserverImpl implements MergeObserver {
    	private success: boolean = null;
    	private successCallbacks: Array<() => any> = [];
    	private abortedCallbacks: Array<() => any> = [];
    	
    	private ensure() {
    		if (this.success === null) return;
    		
    		throw new Error("already finished");
    	}
    	
    	complete() {
    		this.ensure();
    		this.success = true;
    		
    		let successCallback;
    		while (successCallback = this.successCallbacks.pop()) {
    			successCallback();
    		}
    		
    		this.reset();
    	}
    	
    	abort() {
    		this.ensure();
    		this.success = false;
    		
    		let abortedCallback;
    		while (abortedCallback = this.abortedCallbacks.pop()) {
    			abortedCallback();
    		}
    		
    		this.reset();
    	}
    	
    	private reset() {
    		this.successCallbacks = [];
    		this.abortedCallbacks = [];
    	}
    	
    	done(callback: () => any): MergeObserver {
    		if (this.success === null) {
    			this.successCallbacks.push(callback);
    		} else if (this.success) {
    			callback();
    		}
    		return this;
    	}
    	
    	aborted(callback: () => any): MergeObserver {
    		if (this.success === null) {
    			this.abortedCallbacks.push(callback);
    		} else if (!this.success) {
    			callback();
    		}
    		return this;
    	}
    }
    
    export namespace Meta {
    	export enum Target {
    		HEAD = 1,
    		BODY = 2
    	}
    }  
    
    export class LoadObserver {
    	private loadCallbacks: Array<() => any> = [];
    	private readyCallback: Array<() => any> = [];
    	
    	constructor() {
    	}
    	
    	public addElement(elem: Element) {
    		let tn: number;
    		let loadCallback = () => {
    			elem.removeEventListener("load", loadCallback);
    			clearTimeout(tn);
				this.unregisterLoadCallback(loadCallback);
			}
    		this.loadCallbacks.push(loadCallback)
			elem.addEventListener("load", loadCallback, false);
    		tn = setTimeout(() => {
    			console.warn("Jhtml continues; following resource could not be loaded in time: " 
    					+ elem.outerHTML);
    			loadCallback();
    		}, 2000);
    	}
    	
    	private unregisterLoadCallback(callback: () => any) {
    		this.loadCallbacks.splice(this.loadCallbacks.indexOf(callback), 1);
    		
    		this.checkFire();
    	}
    	
    	public whenLoaded(callback: () => any) {
    		this.readyCallback.push(callback);
    		
    		this.checkFire();
    	}
    	
    	private checkFire() {
    		if (this.loadCallbacks.length > 0) return;
    		
    		let callback: () => any;
    		while(callback = this.readyCallback.shift()) {
    			callback();
    		}
    	}
    }
}
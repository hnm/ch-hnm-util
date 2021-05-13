 namespace Jhtml {
    
    export class Merger {
    	private _loadObserver = new LoadObserver();
    	private _processedElements: Element[] = [];
    	private _blockedElements: Element[] = [];
    	private removableElements: Element[] = [];
    	
    	constructor(private rootElem, private headElem, private bodyElem, 
    			private currentContainerElem: Element, private newContainerElem: Element|null) {
    	}
    	
    	get loadObserver(): LoadObserver {
    		return this._loadObserver;
    	}
    	
    	get processedElements(): Element[] {
    		return this._processedElements;	
    	}
    	
    	get remainingElements(): Element[] {
    		return this.removableElements.filter(removableElement => !this.containsProcessed(removableElement));
    	}
    	
    	importInto(newElems: Array<Element>, parentElem: Element, target: Meta.Target) {
			let importedElems: Array<Element> = [];
			let curElems = Util.array(parentElem.children);
			
			for (let i in newElems) {
				let newElem = newElems[i];
								
				let importedElem = this.mergeElem(curElems, newElem, target);
				
				if (importedElem === this.currentContainerElem) continue;
				
				this.importInto(Util.array(newElem.children), importedElem, target);
				
				importedElems.push(importedElem);
			}
			
			for (let i = 0; i < importedElems.length; i++) {
				let importedElem = importedElems[i];
				
				if (-1 < curElems.indexOf(importedElem)) {
					continue;
				}
				
				this.loadObserver.addElement(importedElem);
				parentElem.appendChild(importedElem);
			}
		}
    	
		mergeInto(newElems: Array<Element>, parentElem: Element, target: Meta.Target) {
			let mergedElems: Array<Element> = [];
			let curElems = Util.array(parentElem.children);
			
			for (let i in newElems) {
				let newElem = newElems[i];
								
				let mergedElem = this.mergeElem(curElems, newElem, target);
				
				if (mergedElem === this.currentContainerElem) continue;
				
				this.mergeInto(Util.array(newElem.children), mergedElem, target);
				
				mergedElems.push(mergedElem);
			}
			
			for (let i = 0; i < curElems.length; i++) {
				if (-1 < mergedElems.indexOf(curElems[i])) continue;
						
				if (!Merger.checkIfUnremovable(curElems[i])) {
					this.removableElements.push(curElems[i]);
				}
				
				curElems.splice(i, 1);
				i--;
			}
			
			let curElem = curElems.shift();
			for (let i = 0; i < mergedElems.length; i++) {
				let mergedElem = mergedElems[i];
				
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
				
				let j;
				if (-1 < (j = curElems.indexOf(mergedElem))) {
					curElems.splice(j, 1);
				}
			}
		}
		
		private mergeElem(preferedElems: Array<Element>, newElem: Element, target: Meta.Target): Element {
			if (newElem === this.newContainerElem) {
				if (!this.compareExact(this.currentContainerElem, newElem, false)) {
					let mergedElem =  <Element> newElem.cloneNode(false);
					this.processedElements.push(mergedElem);
					return mergedElem;
				}
				
				this.processedElements.push(this.currentContainerElem);
				return this.currentContainerElem;
			}
			
			if (this.newContainerElem && newElem.contains(this.newContainerElem)) {
				let mergedElem;
				if (mergedElem = this.filterExact(preferedElems, newElem, false)) {
					this.processedElements.push(mergedElem);
					return mergedElem;
				}
				
				return this.cloneNewElem(newElem, false);
			}
			
			let mergedElem: Element;
			
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
		}
		
		private static checkIfUnremovable(elem: Element) {
			return elem.tagName == "SCRIPT";
		}
		
		private cloneNewElem(newElem: Element, deep: boolean): Element {
			let mergedElem = this.rootElem.ownerDocument.createElement(newElem.tagName);
			
			for (let name of this.attrNames(newElem)) {
				mergedElem.setAttribute(name, newElem.getAttribute(name));
			}
			
			if (deep) {
				mergedElem.innerHTML = newElem.innerHTML;
			}
			
//			let mergedElem = <Element> newElem.cloneNode(deep);
			this.processedElements.push(mergedElem);
			return mergedElem;
		}
		
		private attrNames(elem: Element): Array<string> {
			let attrNames: Array<string> = [];
			let attrs = elem.attributes;
			for (let i = 0; i < attrs.length; i++) {
				attrNames.push(attrs[i].nodeName);
			}
			return attrNames;
		}
    	
		private findExact(matchingElem: Element, checkInner: boolean,
				target: Meta.Target = Meta.Target.HEAD|Meta.Target.BODY): Element {
			
			return this.find(matchingElem, this.attrNames(matchingElem), checkInner, true, target);
		}
		
		private find(matchingElem: Element, matchingAttrNames: Array<string>, checkInner: boolean, 
				checkAttrNum: boolean, target: Meta.Target = Meta.Target.HEAD|Meta.Target.BODY): Element {
			let foundElem = null;
			
			if ((target & Meta.Target.HEAD) 
					&& (foundElem = this.findIn(this.headElem, matchingElem, matchingAttrNames, checkInner, checkAttrNum))) {
				return foundElem;
			}
			
			if ((target & Meta.Target.BODY) 
					&& (foundElem = this.findIn(this.bodyElem, matchingElem, matchingAttrNames, checkInner, checkAttrNum))) {
				return foundElem;
			}
			
			return null;
		}
    	
		private findIn(nodeSelector: NodeSelector, matchingElem: Element, matchingAttrNames: Array<string>,
    			checkInner: boolean, chekAttrNum: boolean): Element {
    		for (let tagElem of Util.find(nodeSelector, matchingElem.tagName)) {
    			if (tagElem === this.currentContainerElem  || tagElem.contains(this.currentContainerElem)
    					|| this.currentContainerElem.contains(tagElem) || this.containsProcessed(tagElem)) {
    				continue;
    			}
    			
    			if (this.compare(tagElem, matchingElem, matchingAttrNames, checkInner, chekAttrNum)) {
					return tagElem;
				}
    		}
    			
    		return null;
		}
    	
		private filterExact(elems: Array<Element>, matchingElem: Element, checkInner: boolean): Element {
			return this.filter(elems, matchingElem, this.attrNames(matchingElem), checkInner, true);
		}
		
		private containsProcessed(elem: Element): boolean {
			return -1 < this.processedElements.indexOf(elem);
		}
		
		private filter(elems: Array<Element>, matchingElem: Element, attrNames: Array<string>, checkInner: boolean, 
				checkAttrNum: boolean): Element {
			for (let elem of elems) {
				if (!this.containsProcessed(elem)
						&& this.compare(elem, matchingElem, attrNames, checkInner, checkAttrNum)) {
					return elem;
				}
			}
		}
		
    	private compareExact(elem1: Element, elem2: Element, checkInner: boolean): boolean {
    		return this.compare(elem1, elem2, this.attrNames(elem1), checkInner, true);
    	}
		
		private compare(elem1: Element, elem2: Element, attrNames: Array<string>, checkInner: boolean, 
				checkAttrNum: boolean): boolean {
			if (elem1.tagName !== elem2.tagName) return false;
			
			for (let attrName of attrNames) {
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
		}
		
		private removableAttrs: Attr[] = [];
		
		mergeAttrsInto(newElem: Element, elem: Element) {
//			let attrs: Attr[] = [];
//			for (let i = 0; i < elem.attributes.length; i++) {
//				attrs.push(elem.attributes.item(i));
//			}
//			
//			for (let i = 0; i < newElem.attributes.length; i++) {
//				let newAttr = newElem.attributes.getNamedItem(name)
//				newElem.getAttribute(name)
//			}
		}
    }  
 }
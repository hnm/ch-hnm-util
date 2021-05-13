namespace Jhtml {
    
    export class ModelFactory {
    	public static readonly CONTAINER_ATTR: string = "data-jhtml-container";
    	public static readonly COMP_ATTR: string = "data-jhtml-comp";

    	private static readonly CONTAINER_SELECTOR: string =  "[" + ModelFactory.CONTAINER_ATTR + "]";
    	private static readonly COMP_SELECTOR: string =  "[" + ModelFactory.COMP_ATTR + "]";
    	
    	
    	public static createFromJsonObj(jsonObj: any): Model {
    		if (typeof jsonObj.content != "string") {
				throw new ParseError("Missing or invalid property 'content'.");
			}
    		
    		let rootElem: Element = document.createElement("html");
    		rootElem.innerHTML = jsonObj.content;
    		let meta: Meta = ModelFactory.buildMeta(rootElem, false);
    		
    		ModelFactory.compileMetaElements(meta.headElements, "head", jsonObj);
    		ModelFactory.compileMetaElements(meta.bodyElements, "bodyStart", jsonObj);
    		ModelFactory.compileMetaElements(meta.bodyElements, "bodyEnd", jsonObj);
    		
    		let model = new Model(meta);
    		
    		if (meta.containerElement) {
    			model.container = ModelFactory.compileContainer(meta.containerElement, model);
    			model.comps = ModelFactory.compileComps(model.container, meta.containerElement, model);
    			
    			model.container.detach();
    		    for (let comp of Object.values(model.comps)) {
    		    	comp.detach();
    		    }
    		} else if (jsonObj.content) {
    			rootElem = document.createElement("div");
    			rootElem.innerHTML = jsonObj.content;
    			model.snippet = new Snippet(Util.array(rootElem.children), model, document.createElement("template"));
    		}

    		return model;
    	}
    	    	
    	public static createStateFromDocument(document: Document): ModelState {
    		let metaState = new MetaState(document.documentElement, document.head, document.body,
    				ModelFactory.extractContainerElem(document.body, true));
    		let container = ModelFactory.compileContainer(metaState.containerElement, null);
    		let comps = ModelFactory.compileComps(container, metaState.containerElement, null);
    		
    		return new ModelState(metaState, container, comps);
    	}
    	
    	public static createFromHtml(htmlStr: string, full: boolean): Model {
    		let templateElem = document.createElement("html");
		    templateElem.innerHTML = htmlStr;
		    
		    let model = new Model(ModelFactory.buildMeta(templateElem, true));
		    model.container = ModelFactory.compileContainer(model.meta.containerElement, model);
		    model.comps = ModelFactory.compileComps(model.container, templateElem, model);
		    
		    model.container.detach();
		    for (let comp of Object.values(model.comps)) {
		    	comp.detach();
		    }
		    
    		return model;
    	}
    	
    	private static extractHeadElem(rootElem: Element, required: boolean): Element {
    		let headElem = rootElem.querySelector("head");
    		
    		if (headElem || !required) {
    			return headElem;
		    }
    		
    		throw new ParseError("head element missing.");
    	}
    	
    	
    	private static extractBodyElem(rootElem: Element, required: boolean): Element {
    		let bodyElem = rootElem.querySelector("body");
    		
    		if (bodyElem || !required) {
    			return bodyElem;
		    }
    		
    		throw new ParseError("body element missing.");
    	}
    	
    	public static buildMeta(rootElem: Element, full: boolean): Meta {
		    let meta = new Meta();
		    
		    let elem;
		    
		    if ((elem = ModelFactory.extractContainerElem(rootElem, full))) {
		    	meta.containerElement = elem;
		    } else {
		    	return meta;
		    }
		    
		    if (elem = ModelFactory.extractBodyElem(rootElem, true)) {
		    	meta.bodyElements = Util.array(elem.children);
		    }
		    
		    if (elem = ModelFactory.extractHeadElem(rootElem, false)) {
		    	meta.headElements = Util.array(elem.children);
		    }
		    
		    return meta;
    	}
    	
    	private static extractContainerElem(rootElem: Element, required: boolean): Element {
		    let containerList = Util.find(rootElem, ModelFactory.CONTAINER_SELECTOR);
		    
		    if (containerList.length == 0) {
		    	if (!required) return null;
		    	throw new ParseError("Jhtml container missing.");
		    }
		    
		    if (containerList.length > 1) {
		    	if (!required) return null;
		    	throw new ParseError("Multiple jhtml container detected.");
		    }
		    
		    return containerList[0];
    	}
    	
    	private static compileContainer(containerElem: Element, model: Model): Container  {
    		return new Container(containerElem.getAttribute(ModelFactory.CONTAINER_ATTR), 
		    		containerElem, model);
    		
    	} 
    	
    	private static compileComps(container: Container, containerElem: Element, model: Model): { [name: string]: Comp } {
    		let comps: { [name: string]: Comp } = {}
    	
    		for (let compElem of Util.find(containerElem, ModelFactory.COMP_SELECTOR)) {
		    	let name: string = compElem.getAttribute(ModelFactory.COMP_ATTR);
		    	
		    	if (comps[name]) {
		    		throw new ParseError("Duplicated comp name: " + name);
		    	}
		    	
		    	container.compElements[name] = compElem;
		    	comps[name] = new Comp(name, compElem, model);
		    }
    		
    		return comps;
    	}

    	private static compileMetaElements(elements: Array<Element>, name: string, jsonObj: any) {
    		if (!(jsonObj[name] instanceof Array)) {
				throw new ParseError("Missing or invalid property '" + name + "'.");
			}
    		
    		for (let elemHtml of jsonObj[name]) {
    			elements.push(ModelFactory.createElement(elemHtml));
    		}
    	}

    	private static createElement(elemHtml: string): Element {
    		let templateElem = document.createElement("template");
    		templateElem.innerHTML = elemHtml;
    		return <Element> templateElem.content.firstChild;
    	}
    }
}
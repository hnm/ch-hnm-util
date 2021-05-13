namespace Jhtml {
    export class Url {
        protected urlStr: string;
        
        constructor(urlStr: string) {
            this.urlStr = urlStr;
        }
        
        public toString(): string {
            return this.urlStr;
        }
        
        public equals(url: Url): boolean {
            return this.urlStr == url.urlStr;
        }
        
        public extR(pathExt: string = null, queryExt: { [key: string]: any } = null): Url {
        	let urlParts = this.urlStr.split("?");
        	let newUrlStr = urlParts[0];
        	
            if (pathExt !== null && pathExt !== undefined) {
            	newUrlStr = newUrlStr.replace(/\/+$/, "") + "/" + encodeURI(pathExt);
            }
            
            if (urlParts[1]) {
            	newUrlStr += "?" + urlParts[1];
            }
            
            if (queryExt !== null || queryExt !== undefined) {
            	let parts = [];
            	this.compileQueryParts(parts, queryExt, null);
            	let queryExtStr = parts.join("&");

            	if (newUrlStr.match(/\?/)) {
            		newUrlStr += "&" + queryExtStr;
            	} else {
            		newUrlStr += "?" + queryExtStr;
            	}
            }
            
            return new Url(newUrlStr);
        }
        
        private compileQueryParts(parts: Array<string>, queryExt: { [key: string]: any }, prefix: string|null) {
        	for (let key in queryExt) {
        		let name = null;
        		if (prefix) {
        			name = prefix + "[" + key + "]"; 
        		} else {
        			name = key
        		}
        		
        		let value = queryExt[key];
        		if (value === null || value === undefined) {
        			continue;
        		}
        		
        		if (value instanceof Array || value instanceof Object) {
        			this.compileQueryParts(parts, value, name)
        		} else {
        			parts.push(encodeURIComponent(name) + "=" + encodeURIComponent(value));
        		}
        	}
        }
        
        public static build(urlExpression: string|Url): Url|null {
        	if (urlExpression === null || urlExpression === undefined) return null;
        	
        	return Url.create(urlExpression);
        }
        
        public static create(urlExpression: string|Url): Url {
            if (urlExpression instanceof Url) {
                return urlExpression;
            }
            
            return new Url(Url.absoluteStr(urlExpression));
        }
        
        public static absoluteStr(urlExpression: string|Url): string {
            if (urlExpression instanceof Url) {
                return urlExpression.toString();
            }
            
            var urlStr = <string> urlExpression;
            
            if (!/^(?:\/|[a-z]+:\/\/)/.test(urlStr)) {
            	return window.location.toString().replace(/(\/+)?((\?|#).*)?$/, "") + "/" + urlStr;
            } 
            
            if (!/^(?:[a-z]+:)?\/\//.test(urlStr)) {
                return window.location.protocol + "//" + window.location.host + urlStr;             
            }
            
            return urlStr;
        }
    }
}
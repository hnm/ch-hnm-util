namespace Jhtml.Util {
	
	export class ElemConfigReader {
		
		constructor(private element: Element) {
		}
		
		private buildName(key: string): string {
			return "data-jhtml-" + key;
		}
		
		readBoolean(key: string, fallback: boolean): boolean {
			let value = this.element.getAttribute(this.buildName(key));
			
			if (value === null) {
				return fallback;
			}
			
			switch (value) {
			case "true":
			case "TRUE:":
				return true;
			case "false":
			case "FALSE":
				return false;
			default:
				throw new Error("Attribute '" + this.buildName(key) + " of Element " + this.element.tagName 
						+ "  must contain a boolean value 'true|false'.");
			}
		}
	}
}
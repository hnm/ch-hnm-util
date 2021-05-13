namespace Jhtml.Ui {
	export class Scanner {
		public static readonly A_ATTR = "data-jhtml";
		private static readonly A_SELECTOR = "a[" + Scanner.A_ATTR + "]";
		
		public static readonly FORM_ATTR = "data-jhtml";
		private static readonly FORM_SELECTOR = "form[" + Scanner.FORM_ATTR + "]";
		
		static scan(elem: Element) {
			for (let linkElem of Util.findAndSelf(elem, Scanner.A_SELECTOR)) {
				Link.from(<HTMLAnchorElement> linkElem);
			}
			
			for (let fromElem of Util.findAndSelf(elem, Scanner.FORM_SELECTOR)) {
				Form.from(<HTMLFormElement> fromElem);
			}
		}
		
		static scanArray(elems: Array<Element>) {
			for (let elem of elems) {
				Scanner.scan(elem);
			}
		}
	}
}
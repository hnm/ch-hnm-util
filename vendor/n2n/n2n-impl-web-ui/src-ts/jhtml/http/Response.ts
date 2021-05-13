namespace Jhtml {
	
	export interface Response {
		status: number;
		request: Request;
		model?: Model;
		directive?: Directive;
		additionalData?: any;
	}
}
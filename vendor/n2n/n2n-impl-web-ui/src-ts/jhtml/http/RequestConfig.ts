namespace Jhtml {
	
	export interface RequestConfig {
		forceReload?: boolean; 
		pushToHistory?: boolean;
		usePageScrollPos?: boolean;
	}
	
	export class FullRequestConfig implements RequestConfig {
		forceReload: boolean = false;
		pushToHistory: boolean = true;
		usePageScrollPos: boolean = false;
	
		static from(requestConfig: RequestConfig): FullRequestConfig {
			if (requestConfig instanceof FullRequestConfig) {
				return requestConfig;
			}
			
			let config = new FullRequestConfig();
			
			if (!requestConfig) return config;
				
			if (requestConfig.forceReload !== undefined) {
				config.forceReload = requestConfig.forceReload;
			}
			
			if (requestConfig.pushToHistory !== undefined) {
				config.pushToHistory = requestConfig.pushToHistory;
			}
			
			if (requestConfig.usePageScrollPos !== undefined) {
				config.usePageScrollPos = requestConfig.usePageScrollPos;
			}
			
			return config;
		}
	
		static fromElement(element: Element): FullRequestConfig {
			let reader = new Util.ElemConfigReader(element);
			
			let config = new FullRequestConfig();
			config.forceReload = reader.readBoolean("force-reload", config.forceReload);
			config.pushToHistory = reader.readBoolean("push-to-history", config.pushToHistory);
			config.usePageScrollPos = reader.readBoolean("use-page-scroll-pos", config.usePageScrollPos);
			return config;
		}
	}
}
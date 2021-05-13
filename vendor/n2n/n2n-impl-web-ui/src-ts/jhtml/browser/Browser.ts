namespace Jhtml {
	export class Browser {
        constructor(private window: Window, private _history: History) {
	        let entry = _history.push(new Page(Url.create(window.location.href), null));
	        
	        _history.onPush((entry: History.Entry) => {
	        	this.onPush(entry);
	        });
	        
	        _history.onChanged((evt) => {
	        	this.onChanged(evt);
	        });

//	        this.window.addEventListener("popstate", (evt) => {
//        	    this.onPopstate(evt)
//        	});

        	this.window.history.replaceState(this.buildStateObj(entry), "Page", entry.page.url.toString());
	        
	        this.window.onpopstate = (evt) => {
                this.onPopstate(evt)
            };
		}
        
        get history(): History {
        	return this._history;
        }
        
        private poping: boolean = false;
        
        private onPopstate(evt) {
        	let url: Url = Url.create(this.window.location.toString());
        	let index: number = 0;
        	
        	if (evt.state && evt.state.jhtmlHistoryIndex !== undefined) {
            	 index = evt.state.jhtmlHistoryIndex;
            }
            
            try {
                this.poping = true;
        		this.history.go(index, url);
        		this.poping = false;
        	} catch (e) {
//        	    alert("err " + e.message);
        	    this.window.location.href = url.toString();
        	}
        }
        
        private onChanged(evt: ChangeEvent) {
            if (this.poping || evt.pushed) return;
           
            this.window.history.go(evt.indexDelta);
            
//        	this.window.location.href = this.history.currentEntry.page.url.toString();
        }
        
        private onPush(entry: History.Entry) {
        	this.window.history.pushState(this.buildStateObj(entry), "Page", entry.page.url.toString());
        }
        
        private buildStateObj(entry: History.Entry) {
        	return {
        		"jhtmlUrl": entry.page.url.toString(),
				"jhtmlHistoryIndex": entry.index
        	};
        }
	}
}
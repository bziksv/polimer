// @flow

import {Junction} from "./junction";

export class JunctionCollection {
	
	constructor() {
		this._collection = [];
	}
	
	add(junction: Junction) : void {
		this._collection.push(junction);
		junction.setCollection(this);
	}
	
	remove(junction: Junction) : void {
		const index = this.index(junction);
		
		this._collection.splice(index, 1);
	}
	
	previous(junction: Junction) : ?Junction {
		const index = this.index(junction);
		const targetIndex = index - 1;
		
		return this._collection[targetIndex] ?? null;
	}
	
	next(junction: Junction) : ?Junction {
		const index = this.index(junction);
		const targetIndex = index + 1;
		
		return this._collection[targetIndex] ?? null;
	}

	at(index: number) : ?Junction {
		return this._collection[index] ?? null;
	}
	
	index(junction: Junction) : number {
		const index = this._collection.indexOf(junction);

		if (index === -1) {
			throw new Error('cant find junction at collection');
		}
		
		return index;
	}
}
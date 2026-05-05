// noinspection JSUnresolvedReference

import {Skeleton as PluginSkeleton} from "../../../plugin/skeleton";
import type {View} from "./view/view";

export class Skeleton extends PluginSkeleton {

	static defaults = {
		url: null,
		langPrefix: 'AVITO_EXPORT_TRADING_ACTIVITY_',
	}

	constructor(view: View, options: Object = {}) {
		super(null, options);
		this.view = view;
		this.view.boot(this);
	}

	activate() : void {
		throw new Error('not implemented');
	}
}
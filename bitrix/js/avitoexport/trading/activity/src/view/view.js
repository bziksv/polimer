import {Skeleton as PluginSkeleton} from "../../../../plugin/skeleton";
import type {Skeleton} from "../skeleton";

export class View extends PluginSkeleton {

	static defaults = {
		langPrefix: 'AVITO_EXPORT_TRADING_ACTIVITY_',
	}

	activity : Skeleton;

	boot(activity: Skeleton) : void {
		this.activity = activity;
	}

	bind() : void {}

	unbind() : void {}

	reload() : void {}

	showLoading() : void {}

	hideLoading() : void {}

}

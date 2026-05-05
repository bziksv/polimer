import Factory from "./factory";
import Basket from "./basket/field";
import Property from "./property/field";
import Attention from "./attention/field";
import './common.css';

// factory

const factory = new Factory({
	map: {
		attention: Attention,
		basket: Basket,
		property: Property,
	},
});

factory.register();

export {
	Attention,
	Basket,
	Property,
};
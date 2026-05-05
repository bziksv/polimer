import {Element} from "./element";
import {Section} from "./section";
import {ProductElement} from "./productelement";
import {ProductSection} from "./productsection";
import {SectionSelf} from "./sectionself";
import {MassiveEdit} from "./massiveedit";
import {Behavior} from "./behavior";

export class Factory {

	static make(type: string, element: HTMLElement, options: Object) : Behavior {
		if (type === 'element') {
			return new Element(element, options);
		} else if (type === 'section') {
			return new Section(element, options);
		} else if (type === 'productElement') {
			return new ProductElement(element, options);
		} else if (type === 'productSection') {
			return new ProductSection(element, options);
		} else if (type === 'sectionSelf') {
			return new SectionSelf(element, options);
		} else if (type === 'massiveEdit') {
			return new MassiveEdit(element, options);
		} else {
			throw new Error('unknown form category type');
		}
	}

}
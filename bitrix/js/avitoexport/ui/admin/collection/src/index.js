import {Skeleton} from "../../../../plugin/skeleton";


export class Collection extends Skeleton {

    static defaults = {
        inputName: null,
        itemElement: '.js-input-collection__item',
        selectElement: '.js-input-collection__select',
        deleteElement: '.js-input-collection__delete',
        addElement: '.js-input-collection__add',
    }

    constructor(element: HTMLElement, options: Object = {}) {
        super(element, options);
        this.bind();
    }

    bind() : void {
        this.handleDeleteClick(true);
        this.handleAddClick(true)
    }

    handleDeleteClick(dir) : void {
        const button = this.getElement('delete');

        button[dir ? 'on' : 'off']('click', this.delete);
    }

    handleAddClick(dir) : void {
        const button = this.getElement('add');

        button[dir ? 'on' : 'off']('click', this.add);
    }

    delete = (event) : void => {
        event.preventDefault();

        const currentButton = $(event.target);
        const item = this.getElement('item', currentButton, 'closest');
        const addButton = this.getElement('add', item);
        const prevItem = this.getElement('item', item, 'prev');

        if (prevItem.length === 0 && addButton.length !== 0) {
            const select = this.getElement('select', item);
            select.prop('selectedIndex', 0);

            return false;
        }

        if (addButton.length !== 0) {
            addButton.clone(true).appendTo(prevItem.find('td'));
        }

        item.remove();

        this.recalculateIndexes();
    }

    add = (event) : void => {
        event.preventDefault();

        const currentButton = $(event.target);
        const item = this.getElement('item', currentButton, 'closest');

        const newItem = item.clone(true, true).insertAfter(item);
        currentButton.remove();

        const newSelect = this.getElement('select', newItem);

        const index = parseInt(newSelect.data('index')) + 1;

        this.changeNameIndex(newSelect, index);
    }

    recalculateIndexes() : void {
        const items = this.getElement('item');

        items.each((index, item) => {
            const select = this.getElement('select', $(item));

            this.changeNameIndex(select, index);
        });
    }

    changeNameIndex(select, index) : void {
        select.prop('name', `${this.options.inputName}[${index}]`);
        select.data('index', index);
        select.attr('data-index', index);
    }
}
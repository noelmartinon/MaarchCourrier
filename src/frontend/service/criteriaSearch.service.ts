import { Injectable } from '@angular/core';

interface ListProperties {
    'page': number;
    'pageSize': number;
    'criteria': any[];
    'filters': any[];
    'order': string;
    'orderDir': string;
}

@Injectable()
export class CriteriaSearchService {

    listsProperties: ListProperties = {
        page : 0,
        pageSize : 0,
        order: 'creation_date',
        orderDir: 'DESC',
        criteria: [],
        filters: []
    };

    constructor() { }

    initListsProperties(userId: number) {

        const crit = JSON.parse(sessionStorage.getItem('criteriaSearch_' + userId));

        if (crit !== null) {
            this.listsProperties = JSON.parse(sessionStorage.getItem('criteriaSearch_' + userId));
        }

        return this.listsProperties;
    }

    updateListsPropertiesPage(page: number) {
        this.listsProperties.page = page;
        this.saveListsProperties();
    }

    updateListsPropertiesPageSize(pageSize: number) {
        this.listsProperties.pageSize = pageSize;
        this.saveListsProperties();
    }

    updateListsPropertiesCriteria(criteria: any) {
        this.listsProperties.criteria = criteria;
        this.saveListsProperties();
    }

    updateListsProperties(listProperties: ListProperties) {
        this.listsProperties = listProperties;
        this.saveListsProperties();
    }

    saveListsProperties() {
        sessionStorage.setItem('criteriaSearch', JSON.stringify(this.listsProperties));
    }

    getCriteria() {
        return this.listsProperties.criteria;
    }

}

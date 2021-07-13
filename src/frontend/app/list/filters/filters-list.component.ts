import { Component, OnInit, Input, ViewChild, EventEmitter, Output, ViewEncapsulation } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { FiltersListService } from '../../../service/filtersList.service';
import { MatSelectionList, MatExpansionPanel, MatInput } from '@angular/material';

declare function $j(selector: any): any;

@Component({
    selector: 'app-filters-list',
    templateUrl: 'filters-list.component.html',
    styleUrls: ['filters-list.component.scss'],
    providers: [NotificationService],
    encapsulation: ViewEncapsulation.None
})
export class FiltersListComponent implements OnInit {

    lang: any = LANG;
    priorities: any[] = [];
    categories: any[] = [];
    entitiesList: any[] = [];
    statuses: any[] = [];
    doctypes: any[] = [];

    loading: boolean = false;

    @Input('listProperties') listProperties: any;

    @Output('refreshEvent') refreshEvent = new EventEmitter<string>();


    constructor(public http: HttpClient, private filtersListService: FiltersListService) { }

    ngOnInit(): void {
        this.loading = true;
        this.http.get("../../rest/priorities")
            .subscribe((data: any) => {
                this.priorities = data.priorities;
                this.priorities.forEach((element) => {
                    element.selected = false;
                    this.listProperties.priorities.forEach((listPropertyPrio: any) => {
                        if (element.id === listPropertyPrio.id) {
                            element.selected = true;
                        }
                    });
                });

                this.http.get("../../rest/categories")
                    .subscribe((data: any) => {
                        this.categories = data.categories;
                        this.categories.forEach(element => {
                            element.selected = false;
                            this.listProperties.categories.forEach((listPropertyCat: any) => {
                                if (element.id === listPropertyCat.id) {
                                    element.selected = true;
                                }
                            });
                        });

                        this.http.get("../../rest/statuses")
                            .subscribe((data: any) => {
                                this.statuses = data.statuses;
                                this.statuses.forEach(element => {
                                    element.selected = false;
                                    this.listProperties.statuses.forEach((listPropertyStatus: any) => {
                                        if (element.id === listPropertyStatus.id) {
                                            element.selected = true;
                                        }
                                    });
                                });
                                this.http.get("../../rest/doctypes/types")
                                    .subscribe((data: any) => {
                                        this.doctypes = data.doctypes;
                                        this.doctypes.forEach(element => {
                                            element.selected = false;
                                            this.listProperties.doctypes.forEach((listPropertyDoctype: any) => {
                                                if (element.type_id === listPropertyDoctype.id) {
                                                    element.selected = true;
                                                }
                                            });
                                        });

                                        this.loading = false;

                                    });

                            });
                    });
            });
    }

    setFilters(e: any, i: number, id: string) {
        this[id][i].selected = e.source.checked;
        this.listProperties[id] = [];
        this[id].forEach((element: any) => {
            if (element.selected === true) {
                this.listProperties[id].push({
                    'id': element.id,
                    'label': element.label
                });
            }
            
        });
        this.updateFilters();
    }

    /*setFilters(e: any, elem: any, id: string) {
        elem.selected = e.source.checked;
        this.listProperties[id] = [];
        elem.forEach(element => {
            this.listProperties[id].push({
                'id': element.value,
                'label': element._text.nativeElement.innerText
            });
        });
        this.updateFilters();
    }*/

    updateFilters() {
        this.listProperties.page = 0;

        this.filtersListService.updateListsProperties(this.listProperties);

        this.refreshEvent.emit();
    }

    setFocus(elem: MatInput) {
        setTimeout(() => {
            elem.focus();
        }, 200);
    }
}

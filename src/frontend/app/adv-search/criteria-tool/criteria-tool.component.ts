import { Component, OnInit, ViewChild, ElementRef, EventEmitter, Output, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { AppService } from '../../../service/app.service';
import { FunctionsService } from '../../../service/functions.service';
import { Observable, of } from 'rxjs';
import { FormControl } from '@angular/forms';
import { startWith, map, tap, filter, exhaustMap, catchError } from 'rxjs/operators';
import { LatinisePipe } from 'ngx-pipes';
import { MatExpansionPanel } from '@angular/material/expansion';
import { IndexingFieldsService } from '../../../service/indexing-fields.service';
import { ActivatedRoute } from '@angular/router';
import { MatDialog } from '@angular/material/dialog';
import { ConfirmComponent } from '../../../plugins/modal/confirm.component';
import { NotificationService } from '../../../service/notification/notification.service';
import { AddSearchTemplateModalComponent } from './search-template/search-template-modal.component';

@Component({
    selector: 'app-criteria-tool',
    templateUrl: 'criteria-tool.component.html',
    styleUrls: ['criteria-tool.component.scss', '../../indexation/indexing-form/indexing-form.component.scss']
})
export class CriteriaToolComponent implements OnInit {

    loading: boolean = true;
    criteria: any = [];
    searchTemplates: any;

    currentCriteria: any = [];

    filteredCriteria: Observable<string[]>;

    searchTermControl = new FormControl();
    searchCriteria = new FormControl();

    hideCriteriaList: boolean = true;

    @Input() searchTerm: string = 'Foo';
    @Input() defaultCriteria: any = [];

    @Output() searchUrlGenerated = new EventEmitter<any>();

    @ViewChild('criteriaTool', { static: false }) criteriaTool: MatExpansionPanel;
    @ViewChild('searchCriteriaInput', { static: false }) searchCriteriaInput: ElementRef;

    constructor(
        private _activatedRoute: ActivatedRoute,
        public translate: TranslateService,
        public http: HttpClient,
        public appService: AppService,
        public functions: FunctionsService,
        public indexingFields: IndexingFieldsService,
        private dialog: MatDialog,
        private notify: NotificationService,
        private latinisePipe: LatinisePipe) {
            _activatedRoute.queryParams.subscribe(
                params => {
                    this.searchTerm = params.value;
                }
            );
        }

    async ngOnInit(): Promise<void> {
        // console.log('getAllFields()', await this.indexingFields.getAllFields());

        this.searchTermControl.setValue(this.searchTerm);

        this.criteria = await this.indexingFields.getAllFields();

        this.criteria.forEach((element: any) => {
            if (this.defaultCriteria.indexOf(element.identifier) > -1) {
                element.control = new FormControl('');
                this.addCriteria(element);
            }
        });

        this.filteredCriteria = this.searchCriteria.valueChanges
            .pipe(
                startWith(''),
                map(value => this._filter(value))
            );
        this.loading = false;
        setTimeout(() => {
            this.searchTermControl.valueChanges
            .pipe(
                startWith(''),
                map(value => {
                    if (typeof value === 'string' && !this.functions.empty(value)) {
                        this.searchTerm = value;
                    }
                })
            ).subscribe();
            this.criteriaTool.open();
        }, 500);
        this.getSearchTemplates();
    }

    private _filter(value: string): string[] {
        if (typeof value === 'string') {
            const filterValue = this.latinisePipe.transform(value.toLowerCase());
            return this.criteria.filter((option: any) => this.latinisePipe.transform(option['label'].toLowerCase()).includes(filterValue));
        } else {
            return this.criteria;
        }
    }

    isCurrentCriteria(criteriaId: string) {
        return this.currentCriteria.filter((currCrit: any) => currCrit.identifier === criteriaId).length > 0;
    }

    async addCriteria(criteria: any) {
        if (this.functions.empty(criteria.control.value)) {
            criteria.control = criteria.type === 'date' ? new FormControl({}) : new FormControl('');
        }
        this.initField(criteria);
        this.currentCriteria.push(criteria);
        this.searchTermControl.setValue(this.searchTerm);
        this.searchCriteria.reset();
        // this.searchCriteriaInput.nativeElement.blur();
        setTimeout(() => {
            this.criteriaTool.open();
        }, 0);
    }

    initField(field: any) {
        try {
            const regex = /role_[.]*/g;
            if (field.identifier.match(regex) !== null) {
                this['set_role_field'](field);
            } else {
                this['set_' + field.identifier + '_field'](field);

            }
        } catch (error) {
            // console.log(error);
        }
    }

    removeCriteria(index: number) {
        this.currentCriteria.splice(index, 1);
        if (this.currentCriteria.length === 0) {
            this.criteriaTool.close();
        }
    }

    getFilterControl() {
        return this.searchCriteria;
    }

    getCriterias() {
        return this.criteria;
    }

    getFilteredCriterias() {
        return this.filteredCriteria;
    }

    focusFilter() {
        this.hideCriteriaList = false;
        setTimeout(() => {
            this.searchCriteriaInput.nativeElement.focus();
        }, 100);
    }

    getCurrentCriteriaValues() {
        const objCriteria = {};
        if (!this.functions.empty(this.searchTermControl.value)) {
            objCriteria['quickSearch'] = {
                values: this.searchTermControl.value
            };
        }
        this.currentCriteria.forEach((field: any) => {
            if (!this.functions.empty(field.control.value)) {
                objCriteria[field.identifier] = {
                    values: field.control.value
                };
            }
        });
        this.searchUrlGenerated.emit(objCriteria);
        this.criteriaTool.close();
    }


    getLabelValue(identifier: string, value: string) {
        if (this.functions.empty(value)) {
            return this.translate.instant('lang.undefined');
        } else  if (['doctype', 'destination'].indexOf(identifier) > -1) {
            return this.criteria.filter((field: any) => field.identifier === identifier)[0].values.filter((val: any) => val.id === value)[0].title;
        } else {
            return this.criteria.filter((field: any) => field.identifier === identifier)[0].values.filter((val: any) => val.id === value)[0].label;

        }
    }

    getLabelValues(identifier: string, values: string[]) {
        if (values.length === 0) {
            return this.translate.instant('lang.undefined');
        } else  if (['doctype', 'destination'].indexOf(identifier) > -1) {
            return this.criteria.filter((field: any) => field.identifier === identifier)[0].values.filter((val: any) => values.indexOf(val.id) > -1).map((val: any) => val.title);
        } else {
            return this.criteria.filter((field: any) => field.identifier === identifier)[0].values.filter((val: any) => values.indexOf(val.id) > -1).map((val: any) => val.label);
        }
    }

    refreshCriteria(criteria: any) {
        this.currentCriteria.forEach((field: any, index: number) => {
            if (criteria[field.identifier] !== undefined) {
                field.control.setValue(criteria[field.identifier].values);
            }
        });

        this.getCurrentCriteriaValues();
    }

    set_doctype_field(elem: any) {
        return new Promise((resolve, reject) => {
            this.http.get(`../rest/doctypes`).pipe(
                tap((data: any) => {
                    let arrValues: any[] = [];
                    data.structure.forEach((doctype: any) => {
                        if (doctype['doctypes_second_level_id'] === undefined) {
                            arrValues.push({
                                id: doctype.doctypes_first_level_id,
                                label: doctype.doctypes_first_level_label,
                                title: doctype.doctypes_first_level_label,
                                disabled: true,
                                isTitle: true,
                                color: doctype.css_style
                            });
                            data.structure.filter((info: any) => info.doctypes_first_level_id === doctype.doctypes_first_level_id && info.doctypes_second_level_id !== undefined && info.description === undefined).forEach((secondDoctype: any) => {
                                arrValues.push({
                                    id: secondDoctype.doctypes_second_level_id,
                                    label: '&nbsp;&nbsp;&nbsp;&nbsp;' + secondDoctype.doctypes_second_level_label,
                                    title: secondDoctype.doctypes_second_level_label,
                                    disabled: true,
                                    isTitle: true,
                                    color: secondDoctype.css_style
                                });
                                arrValues = arrValues.concat(data.structure.filter((infoDoctype: any) => infoDoctype.doctypes_second_level_id === secondDoctype.doctypes_second_level_id && infoDoctype.description !== undefined).map((infoType: any) => {
                                    return {
                                        id: infoType.type_id,
                                        label: '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' + infoType.description,
                                        title: infoType.description,
                                        disabled: false,
                                        isTitle: false,
                                    };
                                }));
                            });
                        }
                    });
                    elem.values = arrValues;
                    elem.event = 'calcLimitDate';
                    resolve(true);
                })
            ).subscribe();
        });
    }

    set_priority_field(elem: any) {
        return new Promise((resolve, reject) => {
            this.http.get(`../rest/priorities`).pipe(
                tap((data: any) => {
                    elem.values = data.priorities;
                    resolve(true);
                })
            ).subscribe();
        });
    }

    set_destination_field(elem: any) {
        return new Promise((resolve, reject) => {
            this.http.get(`../rest/indexingModels/entities`).pipe(
                tap((data: any) => {
                        let title = '';
                        elem.values = elem.values.concat(data.entities.map((entity: any) => {
                            title = entity.entity_label;

                            for (let index = 0; index < entity.level; index++) {
                                entity.entity_label = '&nbsp;&nbsp;&nbsp;&nbsp;' + entity.entity_label;
                            }
                            return {
                                id: entity.id,
                                title: title,
                                label: entity.entity_label,
                                disabled: false
                            };
                        }));
                    resolve(true);
                })
            ).subscribe();
        });
    }

    set_role_field(elem: any) {
        return new Promise((resolve, reject) => {
            this.http.get(`../rest/users`).pipe(
                tap((data: any) => {
                    const arrValues: any[] = [];
                    data.users.forEach((user: any) => {
                        arrValues.push({
                            id: user.id,
                            label: `${user.firstname} ${user.lastname}`,
                            title: `${user.firstname} ${user.lastname}`,
                        });
                    });
                    elem.values = arrValues;
                    resolve(true);
                })
            ).subscribe();
        });
    }

    getSearchTemplates() {
        this.http.get(`../rest/searchTemplates`).pipe(
            tap((data: any) => {
                this.searchTemplates = data.searchTemplates;
            })
        ).subscribe();
    }

    saveSearchTemplate() {
        let query: any = [];
        this.currentCriteria.forEach((field: any, index: number) => {
            query.push({'identifier': field.identifier, 'values': field.control.value});
        });

        const dialogRef = this.dialog.open(
            AddSearchTemplateModalComponent,
            {
                panelClass: 'maarch-modal',
                autoFocus: true,
                disableClose: true,
                data: {
                    searchTemplate: {query: query}
                }
            }
        );

        dialogRef.afterClosed().pipe(
            filter((data: any) => data !== undefined),
            tap((data) => {
                this.searchTemplates.push(data.searchTemplate);
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    deleteSearchTemplate(id: number, index: number) {
        const dialogRef = this.dialog.open(
            ConfirmComponent,
            {
                panelClass: 'maarch-modal',
                autoFocus: false,
                disableClose: true,
                data: {
                    title: this.translate.instant('lang.delete'),
                    msg: this.translate.instant('lang.confirmAction')
                }
            }
        );

        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.delete(`../rest/searchTemplates/${id}`)),
            tap(() => {
                this.searchTemplates.splice(index, 1);
                this.notify.success(this.translate.instant('lang.searchTemplateDeleted'));
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    selectSearchTemplate(searchTemplate: any) {
        this.currentCriteria = [];
        this.criteria.forEach((element: any) => {
            let index = searchTemplate.query.map((field: any) => field.identifier).indexOf(element.identifier);
            if (index > -1) {
                element.control = new FormControl({ value: searchTemplate.query[index].values, disabled: false });
                element.control.value = searchTemplate.query[index].values;
                this.addCriteria(element);
            }
        });
    }
}

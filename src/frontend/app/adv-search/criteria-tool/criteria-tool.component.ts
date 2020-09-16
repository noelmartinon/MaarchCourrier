import { Component, OnInit, ViewChild, ElementRef, EventEmitter, Output, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { AppService } from '../../../service/app.service';
import { FunctionsService } from '../../../service/functions.service';
import { Observable } from 'rxjs';
import { FormControl } from '@angular/forms';
import { startWith, map, tap } from 'rxjs/operators';
import { LatinisePipe } from 'ngx-pipes';
import { MatExpansionPanel } from '@angular/material/expansion';
import { IndexingFieldsService } from '../../../service/indexing-fields.service';
import { ActivatedRoute } from '@angular/router';

@Component({
    selector: 'app-criteria-tool',
    templateUrl: 'criteria-tool.component.html',
    styleUrls: ['criteria-tool.component.scss', '../../indexation/indexing-form/indexing-form.component.scss']
})
export class CriteriaToolComponent implements OnInit {

    loading: boolean = true;
    criteria: any = [];

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
        criteria.control = new FormControl('');
        await this.initField(criteria);
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
            this['set_' + field.identifier + '_field'](field);
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

    getSearchUrl() {
        let arrUrl: any[] = [];
        this.currentCriteria.forEach((crit: any) => {
            if (!this.functions.empty(crit.control.value)) {
                arrUrl.push(`${crit.id}=${crit.control.value}`);
            }
        });
        this.criteriaTool.close();
        this.searchUrlGenerated.emit('&' + arrUrl.join('&'));
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
            objCriteria[field.identifier] = {
                values: field.control.value
            };
        });
        this.searchUrlGenerated.emit(objCriteria);
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
}

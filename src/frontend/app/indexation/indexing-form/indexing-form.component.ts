import { Component, OnInit, Input, ViewChild, EventEmitter, Output } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { HeaderService } from '../../../service/header.service';
import { MatDialog } from '@angular/material/dialog';
import { AppService } from '../../../service/app.service';
import { tap, catchError, exhaustMap, filter } from 'rxjs/operators';
import { of } from 'rxjs';
import { SortPipe } from '../../../plugins/sorting.pipe';
import { CdkDragDrop, moveItemInArray, transferArrayItem } from '@angular/cdk/drag-drop';
import { FormControl, Validators, FormGroup, ValidationErrors, ValidatorFn, AbstractControl } from '@angular/forms';
import { DiffusionsListComponent } from '../../diffusions/diffusions-list.component';
import { FunctionsService } from '../../../service/functions.service';

@Component({
    selector: 'app-indexing-form',
    templateUrl: "indexing-form.component.html",
    styleUrls: ['indexing-form.component.scss'],
    providers: [NotificationService, AppService, SortPipe]
})

export class IndexingFormComponent implements OnInit {

    lang: any = LANG;

    loading: boolean = true;

    @Input('indexingFormId') indexingFormId: number;
    @Input('resId') resId: number = null;
    @Input('groupId') groupId: number;
    @Input('admin') adminMode: boolean;
    @Input('canEdit') canEdit: boolean = true;
    @Input('mode') mode: string = 'indexation';

    @Input('hideDiffusionList') hideDiffusionList: boolean = false;

    @Output() retrieveDocumentEvent = new EventEmitter<string>();
    @Output() loadingFormEndEvent = new EventEmitter<string>();

    @ViewChild('appDiffusionsList', { static: false }) appDiffusionsList: DiffusionsListComponent;

    fieldCategories: any[] = ['mail', 'contact', 'process', 'classifying'];

    indexingModelsCore: any[] = [
        {
            identifier: 'doctype',
            label: this.lang.doctype,
            unit: 'mail',
            type: 'select',
            system: true,
            mandatory: true,
            default_value: '',
            values: []
        },
        {
            identifier: 'subject',
            label: this.lang.subject,
            unit: 'mail',
            type: 'string',
            system: true,
            mandatory: true,
            default_value: '',
            values: []
        },
    ];

    indexingModels_mail: any[] = [];
    indexingModels_contact: any[] = [];
    indexingModels_process: any[] = [];
    indexingModels_classement: any[] = [];

    indexingModels_mailClone: any[] = [];
    indexingModels_contactClone: any[] = [];
    indexingModels_processClone: any[] = [];
    indexingModels_classementClone: any[] = [];

    indexingModelsCustomFields: any[] = [];

    availableFields: any[] = [
        {
            identifier: 'recipients',
            label: this.lang.getRecipients,
            type: 'autocomplete',
            default_value: null,
            values: []
        },
        {
            identifier: 'priority',
            label: this.lang.priority,
            type: 'select',
            default_value: null,
            values: []
        },
        {
            identifier: 'confidentiality',
            label: this.lang.confidential,
            type: 'radio',
            default_value: null,
            values: [{ 'id': true, 'label': this.lang.yes }, { 'id': false, 'label': this.lang.no }]
        },
        {
            identifier: 'initiator',
            label: this.lang.initiatorEntityAlt,
            type: 'select',
            default_value: null,
            values: []
        },
        {
            identifier: 'departureDate',
            label: this.lang.departureDate,
            type: 'date',
            default_value: null,
            values: []
        },
        {
            identifier: 'processLimitDate',
            label: this.lang.processLimitDate,
            type: 'date',
            default_value: null,
            values: []
        },
        {
            identifier: 'tags',
            label: this.lang.tags,
            type: 'autocomplete',
            default_value: null,
            values: ['/rest/autocomplete/tags', '/rest/tags']
        },
        {
            identifier: 'senders',
            label: this.lang.getSenders,
            type: 'autocomplete',
            default_value: null,
            values: ['/rest/autocomplete/correspondents']
        },
        {
            identifier: 'destination',
            label: this.lang.destination,
            type: 'select',
            default_value: null,
            values: []
        },
        {
            identifier: 'folders',
            label: this.lang.folders,
            type: 'autocomplete',
            default_value: null,
            values: ['/rest/autocomplete/folders', '/rest/folders']
        },
        {
            identifier: 'documentDate',
            label: this.lang.docDate,
            unit: 'mail',
            type: 'date',
            default_value: null,
            values: []
        },
        {
            identifier: 'arrivalDate',
            label: this.lang.arrivalDate,
            unit: 'mail',
            type: 'date',
            default_value: null,
            values: []
        },
    ];
    availableFieldsClone: any[] = [];

    availableCustomFields: any[] = [];
    availableCustomFieldsClone: any[] = null;

    indexingFormGroup: FormGroup;

    arrFormControl: any = {};

    currentCategory: string = '';
    currentPriorityColor: string = '';

    currentResourceValues: any = null;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        public dialog: MatDialog,
        private headerService: HeaderService,
        public appService: AppService,
        public functions: FunctionsService
    ) {

    }

    async ngOnInit(): Promise<void> {
        this.adminMode === undefined ? this.adminMode = false : this.adminMode = true;

        this.availableFieldsClone = JSON.parse(JSON.stringify(this.availableFields));

        this.fieldCategories.forEach(category => {
            this['indexingModels_' + category] = [];
        });

        if (this.indexingFormId <= 0 || this.indexingFormId === undefined) {

            await this.initFields();
            await this.initCustomFields();

            this.initElemForm();
        } else {
            this.loadForm(this.indexingFormId);
        }
    }

    initFields() {
        return new Promise((resolve, reject) => {
            this.fieldCategories.forEach(element => {
                this['indexingModels_' + element] = this.indexingModelsCore.filter((x: any, i: any, a: any) => x.unit === element);
                this['indexingModels_' + element].forEach((field: any) => {
                    this.initValidator(field);
                });
            });
            resolve(true);
        });
    }

    initCustomFields() {
        return new Promise((resolve, reject) => {

            this.http.get("../../rest/customFields").pipe(
                tap((data: any) => {
                    this.availableCustomFields = data.customFields.map((info: any) => {
                        info.identifier = 'indexingCustomField_' + info.id;
                        info.system = false;
                        info.default_value = null;
                        info.values = info.values.length > 0 ? info.values.map((custVal: any) => {
                            return {
                                id: custVal,
                                label: custVal
                            }
                        }) : info.values;
                        return info;
                    });
                    this.availableCustomFieldsClone = JSON.parse(JSON.stringify(this.availableCustomFields));
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    drop(event: CdkDragDrop<string[]>) {
        event.item.data.unit = event.container.id.split('_')[1];

        if (event.previousContainer === event.container) {
            moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
        } else {
            this.initValidator(event.item.data);
            transferArrayItem(event.previousContainer.data,
                event.container.data,
                event.previousIndex,
                event.currentIndex);
            if (['destination', 'priority'].indexOf(event.item.data.identifier) > -1) {
                this.initElemForm();
            }

        }
    }

    onSubmit() {
        let arrIndexingModels: any[] = [];
        this.fieldCategories.forEach(category => {
            arrIndexingModels = arrIndexingModels.concat(this['indexingModels_' + category]);
        });
    }

    removeItem(arrTarget: string, item: any, index: number) {
        item.mandatory = false;
        if (item.identifier.indexOf('indexingCustomField') > -1) {
            this.availableCustomFields.push(item);
            this[arrTarget].splice(index, 1);
        } else {
            this.availableFields.push(item);
            this[arrTarget].splice(index, 1);
        }
    }

    getDatas(withDiffusionList = true) {
        let arrIndexingModels: any[] = [];
        this.fieldCategories.forEach(category => {
            arrIndexingModels = arrIndexingModels.concat(this['indexingModels_' + category]);
        });
        arrIndexingModels.forEach(element => {
            if (element.type === 'date' && !this.functions.empty(this.arrFormControl[element.identifier].value)) {
                if (element.today === true) {
                    if (this.adminMode) {
                        element.default_value = '_TODAY';
                    } else {
                        element.default_value = this.functions.formatDateObjectToDateString(this.arrFormControl[element.identifier].value, false);
                    }
                } else {
                    if (element.identifier === 'processLimitDate') {
                        element.default_value = this.functions.formatDateObjectToDateString(this.arrFormControl[element.identifier].value, true);
                    } else {
                        element.default_value = this.functions.formatDateObjectToDateString(this.arrFormControl[element.identifier].value, false);
                    }
                }
            } else {
                element.default_value = this.arrFormControl[element.identifier].value === '' ? null : this.arrFormControl[element.identifier].value;
            }

            if (element.identifier === "destination" && !this.adminMode && withDiffusionList) {
                arrIndexingModels.push({
                    identifier: 'diffusionList',
                    default_value: this.arrFormControl['diffusionList'].value
                });
            }

        });

        if (!this.adminMode) {
            arrIndexingModels.push({
                identifier: 'modelId',
                default_value: this.indexingFormId
            });

            if (this.mode === 'indexation') {
                arrIndexingModels.push({
                    identifier: 'followed',
                    default_value: this.arrFormControl['mail­tracking'].value
                });
            }
        }

        return arrIndexingModels;
    }

    saveData() {
        return new Promise((resolve, reject) => {
            if (this.isValidForm()) {
                const formatdatas = this.formatDatas(this.getDatas());

                this.http.put(`../../rest/resources/${this.resId}`, formatdatas).pipe(
                    tap(() => {
                        this.currentResourceValues = JSON.parse(JSON.stringify(this.getDatas(false)));
                        this.notify.success(this.lang.dataUpdated);
                        resolve(true);
                    }),
                    catchError((err: any) => {
                        this.notify.handleErrors(err);
                        return of(false);
                    })
                ).subscribe();
                return true;
            } else {
                this.notify.error(this.lang.mustFixErrors);
                return false;
            }
        });

    }

    formatDatas(datas: any) {
        let formatData: any = {};
        const regex = /indexingCustomField_[.]*/g;

        formatData['customFields'] = {};

        datas.forEach((element: any) => {

            if (element.identifier.match(regex) !== null) {

                formatData['customFields'][element.identifier.split('_')[1]] = element.default_value;

            } else {
                formatData[element.identifier] = element.default_value;
            }
        });
        return formatData;
    }

    getCategory() {
        return this.currentCategory;
    }

    getAvailableFields() {
        return this.availableFields;
    }

    getAvailableCustomFields() {
        return this.availableCustomFields;
    }

    isModified() {
        let state = false;
        let compare: string = '';
        let compareClone: string = '';

        this.fieldCategories.forEach(category => {

            compare = JSON.stringify((this['indexingModels_' + category]));
            compareClone = JSON.stringify((this['indexingModels_' + category + 'Clone']));

            if (compare !== compareClone) {
                state = true;
            }
        });
        return state;
    }

    isResourceModified() {
        if (this.loading || JSON.stringify(this.currentResourceValues) === JSON.stringify(this.getDatas(false))) {
            return false;
        } else {
            return true;
        }
    }

    setModification() {
        this.fieldCategories.forEach(element => {
            this['indexingModels_' + element + 'Clone'] = JSON.parse(JSON.stringify(this['indexingModels_' + element]));
        });
    }

    cancelModification() {
        this.fieldCategories.forEach(element => {
            this['indexingModels_' + element] = JSON.parse(JSON.stringify(this['indexingModels_' + element + 'Clone']));
        });
    }

    setDocumentDateField(elem: any) {
        elem.startDate = '';
        elem.endDate = '_TODAY';

        this.fieldCategories.forEach(element => {
            if (this['indexingModels_' + element].filter((field: any) => field.identifier === 'arrivalDate').length > 0) {
                elem.endDate = 'arrivalDate';
            } else if (this['indexingModels_' + element].filter((field: any) => field.identifier === 'departureDate').length > 0) {
                elem.endDate = 'departureDate';
            }
        });
    }

    setDestinationField(elem: any) {
        let route = this.adminMode || this.mode !== 'indexation' ? `../../rest/indexingModels/entities` : `../../rest/indexing/groups/${this.groupId}/entities`;

        return new Promise((resolve, reject) => {
            this.http.get(route).pipe(
                tap((data: any) => {
                    if (this.adminMode) {
                        let title = '';
                        elem.values = data.entities.map((entity: any) => {
                            title = entity.entity_label;

                            for (let index = 0; index < entity.level; index++) {
                                entity.entity_label = '&nbsp;&nbsp;&nbsp;&nbsp;' + entity.entity_label;
                            }
                            return {
                                id: entity.id,
                                title: title,
                                label: entity.entity_label,
                                disabled: false
                            }
                        });

                    } else {
                        let title = '';

                        let defaultVal = data.entities.filter((entity: any) => entity.enabled === true && entity.id === elem.default_value);
                        elem.default_value = defaultVal.length > 0 ? defaultVal[0].id : null;
                        this.arrFormControl[elem.identifier].setValue(defaultVal.length > 0 ? defaultVal[0].id : '');
                        elem.values = data.entities.map((entity: any) => {
                            title = entity.entity_label;

                            for (let index = 0; index < entity.level; index++) {
                                entity.entity_label = '&nbsp;&nbsp;&nbsp;&nbsp;' + entity.entity_label;
                            }
                            return {
                                id: entity.id,
                                title: title,
                                label: entity.entity_label,
                                disabled: !entity.enabled
                            }
                        });
                        elem.event = 'loadDiffusionList';
                        elem.allowedEntities = elem.values.filter((val: any) => val.disabled === false).map((entities: any) => entities.id);
                    }
                    resolve(true);
                })
            ).subscribe();
        });

    }

    setInitiatorField(elem: any) {
        elem.values = this.headerService.user.entities.map((entity: any) => {
            return {
                id: entity.id,
                label: entity.entity_label
            };
        });
    }

    setCategoryField(elem: any) {
        return new Promise((resolve, reject) => {
            this.http.get(`../../rest/categories`).pipe(
                tap((data: any) => {
                    elem.values = data.categories;
                    resolve(true);
                })
            ).subscribe();
        });
    }

    setPriorityField(elem: any) {
        return new Promise((resolve, reject) => {
            this.http.get(`../../rest/priorities`).pipe(
                tap((data: any) => {
                    elem.values = data.priorities;
                    elem.event = 'calcLimitDateByPriority';
                    if (elem.default_value !== null) {
                        this.calcLimitDateByPriority(elem, elem.default_value);
                    }
                    resolve(true);
                })
            ).subscribe();
        });
    }

    setDoctypeField(elem: any) {
        return new Promise((resolve, reject) => {
            this.http.get(`../../rest/doctypes`).pipe(
                tap((data: any) => {
                    let title = '';
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
                        } else if (doctype['description'] === undefined) {
                            arrValues.push({
                                id: doctype.doctypes_second_level_id,
                                label: '&nbsp;&nbsp;&nbsp;&nbsp;' + doctype.doctypes_second_level_label,
                                title: doctype.doctypes_second_level_label,
                                disabled: true,
                                isTitle: true,
                                color: doctype.css_style
                            });

                            arrValues = arrValues.concat(data.structure.filter((info: any) => info.doctypes_second_level_id === doctype.doctypes_second_level_id && info.description !== undefined).map((info: any) => {
                                return {
                                    id: info.type_id,
                                    label: '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' + info.description,
                                    title: info.description,
                                    disabled: false,
                                    isTitle: false,
                                }
                            }));
                        }
                    });
                    elem.values = arrValues;
                    elem.event = 'calcLimitDate';
                    if (elem.default_value !== null && !this.adminMode) {
                        this.calcLimitDate(elem, elem.default_value);
                    }
                    resolve(true);
                })
            ).subscribe();
        });
    }

    async initElemForm(saveResourceState: boolean = true) {
        this.loading = true;

        if (!this.adminMode) {
            this.arrFormControl['mail­tracking'].setValue(false);
        }
        this.currentPriorityColor = '';


        await Promise.all(this.fieldCategories.map(async (element) => {
            await Promise.all(this['indexingModels_' + element].map(async (elem: any) => {
                if (elem.identifier === 'documentDate') {
                    this.setDocumentDateField(elem);

                } else if (elem.identifier === 'destination') {
                    await this.setDestinationField(elem);

                } else if (elem.identifier === 'arrivalDate') {
                    elem.startDate = 'documentDate';
                    elem.endDate = '_TODAY';

                } else if (elem.identifier === 'initiator' && !this.adminMode) {
                    this.setInitiatorField(elem);

                } else if (elem.identifier === 'processLimitDate') {
                    elem.startDate = '_TODAY';
                    elem.endDate = '';
                    elem.event = 'setPriorityColorByLimitDate';

                } else if (elem.identifier === 'departureDate') {
                    elem.startDate = 'documentDate';
                    elem.endDate = '';

                } else if (elem.identifier === 'folders') {
                    elem.values = null;

                } else if (elem.identifier === 'category_id') {
                    await this.setCategoryField(elem);

                } else if (elem.identifier === 'priority') {
                    await this.setPriorityField(elem);

                } else if (elem.identifier === 'doctype') {
                    await this.setDoctypeField(elem);
                }
            }));
        }));

        if (this.resId !== null) {

            await this.setResource(saveResourceState);
        }

        this.loading = false;
    }

    setResource(saveResourceState: boolean = true) {
        return new Promise((resolve, reject) => {
            this.http.get(`../../rest/resources/${this.resId}`).pipe(
                tap(async (data: any) => {
                    await Promise.all(this.fieldCategories.map(async (element: any) => {

                        // this.fieldCategories.forEach(async element => {
                        await Promise.all(this['indexingModels_' + element].map(async (elem: any) => {
                            // this['indexingModels_' + element].forEach((elem: any) => {
                            const customId: any = Object.keys(data.customFields).filter(index => index === elem.identifier.split('indexingCustomField_')[1])[0];

                            if (Object.keys(data).indexOf(elem.identifier) > -1 || customId !== undefined) {
                                let fieldValue: any = '';
                                if (customId !== undefined) {
                                    fieldValue = data.customFields[customId];
                                } else {
                                    fieldValue = data[elem.identifier];
                                }

                                if (elem.identifier === 'priority') {
                                    this.setPriorityColor(null, fieldValue);
                                } else if (elem.identifier === 'processLimitDate' && !this.functions.empty(fieldValue)) {
                                    elem.startDate = '';
                                } else if (elem.identifier === 'destination') {
                                    if (this.mode === 'process') {
                                        this.arrFormControl[elem.identifier].disable();
                                    }
                                    this.arrFormControl['diffusionList'].disable();
                                } else if (elem.identifier === 'initiator' && elem.values.filter((val: any) => val.id === fieldValue).length === 0 && !this.functions.empty(fieldValue)) {
                                    await this.getCurrentInitiator(elem, fieldValue);
                                }

                                if (elem.type === 'date' && !this.functions.empty(fieldValue)) {
                                    fieldValue = new Date(fieldValue);
                                }
                                this.arrFormControl[elem.identifier].setValue(fieldValue);
                            } else if (!saveResourceState && elem.identifier === 'destination') {
                                this.arrFormControl[elem.identifier].disable();
                                this.arrFormControl[elem.identifier].setValidators([]);
                                this.arrFormControl['diffusionList'].disable();
                            }

                            if (!this.canEdit) {
                                this.arrFormControl[elem.identifier].disable();
                            }
                        }));
                    }));
                    this.arrFormControl['mail­tracking'].setValue(data.followed);
                    if (saveResourceState) {
                        this.currentResourceValues = JSON.parse(JSON.stringify(this.getDatas(false)));
                    }
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    getCurrentInitiator(field: any, initiatorId: number) {
        return new Promise((resolve, reject) => {
            this.http.get(`../../rest/entities/${initiatorId}`).pipe(
                tap((data: any) => {
                    field.values.unshift({
                        id: data.id,
                        label: data.entity_label
                    });
                    resolve(true);
                })
            ).subscribe();
        });
    }

    createForm() {
        this.indexingFormGroup = new FormGroup(this.arrFormControl);
        this.loadingFormEndEvent.emit();
    }

    async resetForm() {
        Object.keys(this.arrFormControl).forEach(element => {
            delete this.arrFormControl[element];
        });

        this.availableFields = JSON.parse(JSON.stringify(this.availableFieldsClone));
        this.fieldCategories.forEach(category => {
            this['indexingModels_' + category] = [];
        });

        if (this.availableCustomFieldsClone === null) {
            await this.initCustomFields();
        } else {
            this.availableCustomFields = JSON.parse(JSON.stringify(this.availableCustomFieldsClone));
        }
    }

    async loadForm(indexModelId: number, saveResourceState: boolean = true) {
        this.loading = true;

        this.indexingFormId = indexModelId;

        await this.resetForm();

        if (!this.adminMode) {
            this.arrFormControl['mail­tracking'] = new FormControl({ value: '', disabled: this.adminMode ? true : false });
        }

        this.http.get(`../../rest/indexingModels/${indexModelId}`).pipe(
            tap(async (data: any) => {
                this.currentCategory = data.indexingModel.category;
                let fieldExist: boolean;
                if (data.indexingModel.fields.length === 0) {
                    this.initFields();
                    this.notify.error(this.lang.noFieldInModelMsg);
                } else {
                    data.indexingModel.fields.forEach((field: any) => {
                        fieldExist = false;
                        field.system = false;
                        field.values = [];

                        let indexFound = this.availableFields.map(avField => avField.identifier).indexOf(field.identifier);

                        if (indexFound > -1) {
                            field.label = this.availableFields[indexFound].label;
                            field.values = this.availableFields[indexFound].values;
                            field.type = this.availableFields[indexFound].type;
                            this.availableFields.splice(indexFound, 1);
                            fieldExist = true;
                        }

                        indexFound = this.availableCustomFields.map(avField => avField.identifier).indexOf(field.identifier);

                        if (indexFound > -1) {
                            field.label = this.availableCustomFields[indexFound].label;
                            field.values = this.availableCustomFields[indexFound].values;
                            field.type = this.availableCustomFields[indexFound].type;
                            this.availableCustomFields.splice(indexFound, 1);
                            fieldExist = true;
                        }

                        indexFound = this.indexingModelsCore.map(info => info.identifier).indexOf(field.identifier);

                        if (indexFound > -1) {
                            field.label = this.indexingModelsCore[indexFound].label;
                            field.values = this.indexingModelsCore[indexFound].values;
                            field.type = this.indexingModelsCore[indexFound].type;
                            fieldExist = true;
                            field.system = true;
                        }

                        if (field.type === 'date' && field.default_value === '_TODAY') {
                            field.today = true;
                            field.default_value = new Date();
                        }

                        if (field.identifier == 'initiator' && this.mode == 'indexation' && this.functions.empty(field.default_value)) {
                            if (this.headerService.user.entities[0]) {
                                field.default_value = this.headerService.user.entities.filter((entity: any) => entity.primary_entity == 'Y')[0].id;
                            }
                        }

                        if (fieldExist) {
                            this['indexingModels_' + field.unit].push(field);
                            this.initValidator(field);
                        } else {
                            this.notify.error(this.lang.fieldNotExist + ': ' + field.identifier);
                        }

                    });
                }

                await this.initElemForm(saveResourceState);
                this.createForm();

            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    initValidator(field: any) {
        let valArr: ValidatorFn[] = [];
        this.arrFormControl[field.identifier] = new FormControl({ value: field.default_value, disabled: (field.today && this.adminMode) ? true : false });

        if (field.type === 'integer') {
            valArr.push(this.regexValidator(new RegExp('[+-]?([0-9]*[.])?[0-9]+'), { 'floatNumber': '' }));
        } else if (field.type === 'date' && !this.functions.empty(field.default_value)) {
            this.arrFormControl[field.identifier].setValue(new Date(field.default_value));
        }

        if (field.mandatory && !this.adminMode) {
            valArr.push(Validators.required);
        }

        this.arrFormControl[field.identifier].setValidators(valArr);

        if (field.identifier === 'destination') {
            let valArr: ValidatorFn[] = [];
            if (field.mandatory) {
                valArr.push(Validators.required);
            }

            this.arrFormControl['diffusionList'] = new FormControl({ value: null, disabled: false });

            this.arrFormControl['diffusionList'].setValidators(valArr);

            this.arrFormControl['diffusionList'].setValue([]);

        }
    }

    regexValidator(regex: RegExp, error: ValidationErrors): ValidatorFn {
        return (control: AbstractControl): { [key: string]: any } => {
            if (!control.value) {
                return null;
            }
            const valid = regex.test(control.value);
            return valid ? null : error;
        };
    }

    isValidForm() {
        if (!this.indexingFormGroup.valid) {
            Object.keys(this.indexingFormGroup.controls).forEach(key => {

                const controlErrors: ValidationErrors = this.indexingFormGroup.get(key).errors;
                if (controlErrors != null) {
                    this.indexingFormGroup.controls[key].markAsTouched();
                    /*Object.keys(controlErrors).forEach(keyError => {
                        console.log('Key control: ' + key + ', keyError: ' + keyError + ', err value: ', controlErrors[keyError]);
                    });*/
                }
            });
        }
        return this.indexingFormGroup.valid;
    }

    isEmptyField(field: any) {

        if (this.arrFormControl[field.identifier].value === null) {
            return true;

        } else if (Array.isArray(this.arrFormControl[field.identifier].value)) {
            if (this.arrFormControl[field.identifier].value.length > 0) {
                return false;
            } else {
                return true;
            }
        } else if (String(this.arrFormControl[field.identifier].value) !== '') {
            return false;
        } else {
            return true;
        }
    }

    getMinDate(id: string) {
        if (this.arrFormControl[id] !== undefined) {
            return this.arrFormControl[id].value;
        } else if (id === '_TODAY') {
            return new Date();
        } else {
            return '';
        }
    }

    getMaxDate(id: string) {
        if (this.arrFormControl[id] !== undefined) {
            return this.arrFormControl[id].value;
        } else if (id === '_TODAY') {
            return new Date();
        } else {
            return '';
        }
    }

    toggleTodayDate(field: any) {
        field.today = !field.today;
        if (field.today) {
            this.arrFormControl[field.identifier].disable();
            this.arrFormControl[field.identifier].setValue(new Date());
        } else {
            this.arrFormControl[field.identifier].setValue('');
            this.arrFormControl[field.identifier].enable();
        }
    }

    toggleMailTracking() {
        this.arrFormControl['mail­tracking'].setValue(!this.arrFormControl['mail­tracking'].value);
    }

    changeCategory(categoryId: string) {
        this.currentCategory = categoryId;
    }

    changeDestination(entityIds: number[], allowedEntities: number[]) {

        if (entityIds.indexOf(this.arrFormControl['destination'].value) === -1) {
            this.arrFormControl['destination'].setValue(entityIds[0]);
        }
    }

    launchEvent(value: any, field: any) {
        if (field.event !== undefined && value !== null && !this.adminMode) {
            this[field.event](field, value);
        }
    }

    calcLimitDate(field: any, value: any) {
        let limitDate: Date = null;
        if (this.arrFormControl['processLimitDate'] !== undefined) {
            this.http.get("../../rest/indexing/processLimitDate", { params: { "doctype": value } }).pipe(
                tap((data: any) => {
                    limitDate = new Date(data.processLimitDate);
                    this.arrFormControl['processLimitDate'].setValue(limitDate);
                }),
                filter(() => this.arrFormControl['priority'] !== undefined),
                exhaustMap(() => this.http.get('../../rest/indexing/priority', { params: { "processLimitDate": limitDate.toDateString() } })),
                tap((data: any) => {
                    this.arrFormControl['priority'].setValue(data.priority);
                    this.setPriorityColor(null, data.priority);

                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        }
    }

    calcLimitDateByPriority(field: any, value: any) {
        let limitDate: Date = null;

        if (this.arrFormControl['processLimitDate'] !== undefined) {
            this.http.get("../../rest/indexing/processLimitDate", { params: { "priority": value } }).pipe(
                tap((data: any) => {
                    limitDate = new Date(data.processLimitDate);
                    this.arrFormControl['processLimitDate'].setValue(limitDate);
                    this.setPriorityColor(field, value);
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else {
            this.setPriorityColor(field, value);
        }
    }

    setPriorityColor(field: any, value: any) {
        if (field !== null) {
            this.currentPriorityColor = field.values.filter((fieldVal: any) => fieldVal.id === value).map((fieldVal: any) => fieldVal.color)[0];
        } else {
            this.fieldCategories.forEach(element => {
                if (this['indexingModels_' + element].filter((field: any) => field.identifier === 'priority').length > 0) {
                    this.currentPriorityColor = this['indexingModels_' + element].filter((field: any) => field.identifier === 'priority')[0].values.filter((fieldVal: any) => fieldVal.id === value).map((fieldVal: any) => fieldVal.color)[0]
                }
            });
        }

    }

    setPriorityColorByLimitDate(field: any, value: any) {

        const limitDate = new Date(value.value);

        this.http.get("../../rest/indexing/priority", { params: { "processLimitDate": limitDate.toDateString() } }).pipe(
            tap((data: any) => {
                this.arrFormControl['priority'].setValue(data.priority);
                this.setPriorityColor(null, data.priority);
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();

    }

    loadDiffusionList(field: any, value: any) {
        if (!this.functions.empty(this.appDiffusionsList)) {
            this.appDiffusionsList.loadListModel(value);
        }
    }
}

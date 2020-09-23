import { Component, OnInit, Input, Output, EventEmitter } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '../../../../service/notification/notification.service';
import { FormControl } from '@angular/forms';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';
import { startWith, map, tap, catchError } from 'rxjs/operators';
import { Observable } from 'rxjs/internal/Observable';
import { of } from 'rxjs/internal/observable/of';

declare var $: any;

@Component({
    selector: 'list-administration',
    templateUrl: 'list-administration.component.html',
    styleUrls: ['list-administration.component.scss'],
})
export class ListAdministrationComponent implements OnInit {


    loading: boolean = false;

    displayedMainData: any = [
        {
            'value': 'chronoNumberShort',
            'label': this.translate.instant('lang.chronoNumberShort'),
            'sample': 'MAARCH/2019A/1',
            'cssClasses': ['align_centerData', 'normalData'],
            'icon': ''
        },
        {
            'value': 'object',
            'label': this.translate.instant('lang.object'),
            'sample': this.translate.instant('lang.objectSample'),
            'cssClasses': ['longData'],
            'icon': ''
        }
    ];

    availableData: any = [
        {
            'value': 'getPriority',
            'label': this.translate.instant('lang.getPriority'),
            'sample': this.translate.instant('lang.getPrioritySample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-traffic-light'
        },
        {
            'value': 'getCategory',
            'label': this.translate.instant('lang.getCategory'),
            'sample': this.translate.instant('lang.incoming'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-exchange-alt'
        },
        {
            'value': 'getDoctype',
            'label': this.translate.instant('lang.getDoctype'),
            'sample': this.translate.instant('lang.getDoctypeSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-suitcase'
        },
        {
            'value': 'getAssignee',
            'label': this.translate.instant('lang.getAssignee'),
            'sample': this.translate.instant('lang.getAssigneeSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-sitemap'
        },
        {
            'value': 'getRecipients',
            'label': this.translate.instant('lang.getRecipients'),
            'sample': 'Patricia PETIT',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-user'
        },
        {
            'value': 'getSenders',
            'label': this.translate.instant('lang.getSenders'),
            'sample': 'Alain DUBOIS (MAARCH)',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-book'
        },
        {
            'value': 'getCreationAndProcessLimitDates',
            'label': this.translate.instant('lang.getCreationAndProcessLimitDates'),
            'sample': this.translate.instant('lang.getCreationAndProcessLimitDatesSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-calendar'
        },
        {
            'value': 'getVisaWorkflow',
            'label': this.translate.instant('lang.getVisaWorkflow'),
            'sample': '<i color="accent" class="fa fa-check"></i> Barbara BAIN -> <i class="fa fa-hourglass-half"></i> <b>Bruno BOULE</b> -> <i class="fa fa-hourglass-half"></i> Patricia PETIT',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-list-ol'
        },
        {
            'value': 'getSignatories',
            'label': this.translate.instant('lang.getSignatories'),
            'sample': 'Denis DAULL, Patricia PETIT',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-certificate'
        },
        {
            'value': 'getModificationDate',
            'label': this.translate.instant('lang.getModificationDate'),
            'sample': '01-01-2019',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-calendar-check'
        },
        {
            'value': 'getOpinionLimitDate',
            'label': this.translate.instant('lang.getOpinionLimitDate'),
            'sample': '01-01-2019',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-stopwatch'
        },
        {
            'value': 'getParallelOpinionsNumber',
            'label': this.translate.instant('lang.getParallelOpinionsNumber'),
            'sample': this.translate.instant('lang.getParallelOpinionsNumberSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-comment-alt'
        },
        {
            'value': 'getFolders',
            'label': this.translate.instant('lang.getFolders'),
            'sample': this.translate.instant('lang.getFoldersSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-folder'
        },
        {
            'value': 'getResId',
            'label': this.translate.instant('lang.getResId'),
            'sample': this.translate.instant('lang.getResIdSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-envelope'
        }, {
            'value': 'getBarcode',
            'label': this.translate.instant('lang.getBarcode'),
            'sample': this.translate.instant('lang.getBarcodeSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-barcode'
        }, {
            'value': 'getRegisteredMailRecipient',
            'label': this.translate.instant('lang.registeredMailRecipient'),
            'sample': this.translate.instant('lang.registeredMailRecipientSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-user'
        }, {
            'value': 'getRegisteredMailReference',
            'label': this.translate.instant('lang.registeredMailReference'),
            'sample': this.translate.instant('lang.registeredMailReferenceSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-hashtag'
        }, {
            'value': 'getRegisteredMailIssuingSite',
            'label': this.translate.instant('lang.issuingSite'),
            'sample': this.translate.instant('lang.issuingSiteSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fas fa-warehouse'
        }
    ];
    availableDataClone: any = [];

    displayedSecondaryData: any = [];
    displayedSecondaryDataClone: any = [];

    displayMode: string = 'label';
    dataControl = new FormControl();
    filteredDataOptions: Observable<string[]>;
    listEvent: any[] = [
        {
            id: 'detailDoc',
            value: 'documentDetails'
        },
        {
            id: 'eventVisaMail',
            value: 'signatureBookAction'
        },
        {
            id: 'eventProcessDoc',
            value: 'processDocument'
        },
        {
            id: 'eventViewDoc',
            value: 'viewDoc'
        }
    ];

    templateDisplayedSecondaryData: number[] = [1, 2, 3, 4, 5, 6, 7];
    selectedTemplateDisplayedSecondaryData: number = 7;
    selectedTemplateDisplayedSecondaryDataClone: number = 7;

    selectedListEvent: string = null;
    selectedListEventClone: string = null;

    processTool: any[] = [
        {
            id: 'dashboard',
            icon: 'fas fa-columns',
            label: this.translate.instant('lang.newsFeed'),
        },
        {
            id: 'history',
            icon: 'fas fa-history',
            label: this.translate.instant('lang.history'),
        },
        {
            id: 'notes',
            icon: 'fas fa-pen-square',
            label: this.translate.instant('lang.notesAlt'),
        },
        {
            id: 'attachments',
            icon: 'fas fa-paperclip',
            label: this.translate.instant('lang.attachments'),
        },
        {
            id: 'linkedResources',
            icon: 'fas fa-link',
            label: this.translate.instant('lang.links'),
        },
        {
            id: 'diffusionList',
            icon: 'fas fa-share-alt',
            label: this.translate.instant('lang.diffusionList'),
        },
        {
            id: 'emails',
            icon: 'fas fa-envelope',
            label: this.translate.instant('lang.mailsSentAlt'),
        },
        {
            id: 'visaCircuit',
            icon: 'fas fa-list-ol',
            label: this.translate.instant('lang.visaWorkflow'),
        },
        {
            id: 'opinionCircuit',
            icon: 'fas fa-comment-alt',
            label: this.translate.instant('lang.avis'),
        },
        {
            id: 'info',
            icon: 'fas fa-info-circle',
            label: this.translate.instant('lang.informations'),
        }
    ];
    selectedProcessTool: any = {
        defaultTab: null,
        canUpdateData: false,
        canUpdateModel: false,
        canUpdateDocuments: false,
    };
    selectedProcessToolClone: string = null;

    @Input('currentBasketGroup') private basketGroup: any;
    @Output('refreshBasketGroup') refreshBasketGroup = new EventEmitter<any>();

    constructor(public translate: TranslateService, public http: HttpClient, private notify: NotificationService) { }

    async ngOnInit(): Promise<void> {
        await this.initCustomFields();
        this.filteredDataOptions = this.dataControl.valueChanges
            .pipe(
                startWith(''),
                map(value => this._filterData(value))
            );

        this.availableDataClone = JSON.parse(JSON.stringify(this.availableData));
        this.displayedSecondaryData = [];
        let indexData: number = 0;
        this.selectedTemplateDisplayedSecondaryData = this.basketGroup.list_display.templateColumns;
        this.selectedTemplateDisplayedSecondaryDataClone = this.selectedTemplateDisplayedSecondaryData;
        this.basketGroup.list_display.subInfos.forEach((element: any) => {
            indexData = this.availableData.map((e: any) => e.value).indexOf(element.value);
            this.availableData[indexData].cssClasses = element.cssClasses;
            this.displayedSecondaryData.push(this.availableData[indexData]);
            this.availableData.splice(indexData, 1);
        });
        this.selectedListEvent = this.basketGroup.list_event;
        this.selectedListEventClone = this.selectedListEvent;

        if (this.basketGroup.list_event === 'processDocument') {
            this.selectedProcessTool.defaultTab = this.basketGroup.list_event_data === null ? 'dashboard' : this.basketGroup.list_event_data.defaultTab;
            this.selectedProcessTool.canUpdateData = this.basketGroup.list_event_data === null ? false : this.basketGroup.list_event_data.canUpdateData;
            this.selectedProcessTool.canUpdateModel = this.basketGroup.list_event_data === null ? false : this.basketGroup.list_event_data.canUpdateModel;
        } else if (this.basketGroup.list_event === 'signatureBookAction') {
            this.selectedProcessTool.canUpdateDocuments = this.basketGroup.list_event_data === null ? false : this.basketGroup.list_event_data.canUpdateDocuments;
        }

        this.selectedProcessToolClone = JSON.parse(JSON.stringify(this.selectedProcessTool));
        this.displayedSecondaryDataClone = JSON.parse(JSON.stringify(this.displayedSecondaryData));
    }

    initCustomFields() {
        return new Promise((resolve, reject) => {

            this.http.get('../rest/customFields').pipe(
                map((data: any) => {
                    data.customFields = data.customFields.map((info: any) => {
                        return {
                            'value': 'indexingCustomField_' + info.id,
                            'label': info.label,
                            'sample': this.translate.instant('lang.customField') + info.id,
                            'cssClasses': ['align_leftData'],
                            'icon': 'fa-hashtag'
                        };
                    });
                    return data.customFields;
                }),
                tap((customs) => {
                    console.log(customs);
                    this.availableData = this.availableData.concat(customs);
                    resolve(true);

                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    toggleData() {
        this.dataControl.disabled ? this.dataControl.enable() : this.dataControl.disable();

        if (this.displayMode === 'label') {
            this.displayMode = 'sample';
        } else {
            this.displayMode = 'label';
        }

    }

    setStyle(item: any, value: string) {
        const typeFont = value.split('_');

        if (typeFont.length === 2) {
            item.cssClasses.forEach((element: any, it: number) => {
                if (element.includes(typeFont[0]) && element !== value) {
                    item.cssClasses.splice(it, 1);
                }
            });
        }

        const index = item.cssClasses.indexOf(value);

        if (index === -1) {
            item.cssClasses.push(value);
        } else {
            item.cssClasses.splice(index, 1);
        }
    }

    addData(event: any) {
        const i = this.availableData.map((e: any) => e.value).indexOf(event.option.value.value);
        this.displayedSecondaryData.push(event.option.value);
        this.availableData.splice(i, 1);
        $('#availableData').blur();
        this.dataControl.setValue('');
    }

    removeData(data: any, i: number) {
        this.availableData.push(data);
        this.displayedSecondaryData.splice(i, 1);
        this.dataControl.setValue('');
    }

    removeAllData() {
        this.availableData = this.availableData.concat(this.displayedSecondaryData);
        this.displayedSecondaryData = [];
        this.dataControl.setValue('');
    }

    drop(event: CdkDragDrop<string[]>) {
        if (event.previousContainer === event.container) {
            moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
        }
    }

    saveTemplate() {
        let objToSend = {};
        const template: any = [];
        this.displayedSecondaryData.forEach((element: any) => {
            template.push(
                {
                    'value': element.value,
                    'cssClasses': element.cssClasses,
                    'icon': element.icon,
                }
            );
        });

        objToSend = {
            templateColumns: this.selectedTemplateDisplayedSecondaryData,
            subInfos: template
        };

        this.http.put('../rest/baskets/' + this.basketGroup.basket_id + '/groups/' + this.basketGroup.group_id, { 'list_display': objToSend, 'list_event': this.selectedListEvent, 'list_event_data': this.selectedProcessTool })
            .subscribe(() => {
                this.displayedSecondaryDataClone = JSON.parse(JSON.stringify(this.displayedSecondaryData));
                this.basketGroup.list_display = template;
                this.basketGroup.list_event = this.selectedListEvent;
                this.selectedListEventClone = this.selectedListEvent;
                this.basketGroup.list_event_data = this.selectedProcessTool;
                this.selectedProcessToolClone = JSON.parse(JSON.stringify(this.selectedProcessTool));
                this.selectedTemplateDisplayedSecondaryDataClone = JSON.parse(JSON.stringify(this.selectedTemplateDisplayedSecondaryData));
                this.notify.success(this.translate.instant('lang.modificationsProcessed'));
                this.refreshBasketGroup.emit(this.basketGroup);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    private _filterData(value: any): string[] {
        let filterValue = '';

        if (typeof value === 'string') {
            filterValue = value.toLowerCase();
        } else if (value !== null) {
            filterValue = value.label.toLowerCase();
        }
        return this.availableData.filter((option: any) => option.label.toLowerCase().includes(filterValue));
    }

    checkModif() {
        if (JSON.stringify(this.displayedSecondaryData) === JSON.stringify(this.displayedSecondaryDataClone) && this.selectedListEvent === this.selectedListEventClone && JSON.stringify(this.selectedProcessTool) === JSON.stringify(this.selectedProcessToolClone) && JSON.stringify(this.selectedTemplateDisplayedSecondaryData) === JSON.stringify(this.selectedTemplateDisplayedSecondaryDataClone)) {
            return true;
        } else {
            return false;
        }
    }

    cancelModification() {
        this.displayedSecondaryData = JSON.parse(JSON.stringify(this.displayedSecondaryDataClone));
        this.selectedListEvent = this.selectedListEventClone;
        this.selectedProcessTool = JSON.parse(JSON.stringify(this.selectedProcessToolClone));
        this.availableData = JSON.parse(JSON.stringify(this.availableDataClone));
        this.selectedTemplateDisplayedSecondaryData = JSON.parse(JSON.stringify(this.selectedTemplateDisplayedSecondaryDataClone));

        let indexData: number = 0;
        this.displayedSecondaryData.forEach((element: any) => {
            indexData = this.availableData.map((e: any) => e.value).indexOf(element.value);
            this.availableData.splice(indexData, 1);
        });
        this.dataControl.setValue('');
    }

    hasFolder() {
        if (this.displayedSecondaryData.map((data: any) => data.value).indexOf('getFolders') > -1) {
            return true;
        } else {
            return false;
        }
    }

    changeEventList(ev: any) {
        if (ev.value === 'processDocument') {
            this.selectedProcessTool = {
                defaultTab: 'dashboard'
            };
        } else {
            this.selectedProcessTool = {};
        }
    }

    toggleCanUpdate(state: boolean) {
        if (!state) {
            this.selectedProcessTool.canUpdateModel = state;
        }
    }
}

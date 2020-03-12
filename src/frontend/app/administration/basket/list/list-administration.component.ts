import { Component, OnInit, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../../translate.component';
import { NotificationService } from '../../../notification.service';
import { Observable } from 'rxjs';
import { FormControl } from '@angular/forms';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';
import { startWith, map } from 'rxjs/operators';

declare function $j(selector: any): any;
@Component({
    selector: 'list-administration',
    templateUrl: "list-administration.component.html",
    styleUrls: ['list-administration.component.scss'],
})
export class ListAdministrationComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;

    displayedMainData: any = [
        {
            'value': 'chronoNumberShort',
            'label': this.lang.chronoNumberShort,
            'sample': 'MAARCH/2019A/1',
            'cssClasses': ['align_centerData', 'normalData'],
            'icon': ''
        },
        {
            'value': 'object',
            'label': this.lang.object,
            'sample': this.lang.objectSample,
            'cssClasses': ['longData'],
            'icon': ''
        },];

    availableData: any = [
        {
            'value': 'getPriority',
            'label': this.lang.getPriority,
            'sample': this.lang.getPrioritySample,
            'cssClasses': ['align_leftData'],
            'icon': 'fa-traffic-light'
        },
        {
            'value': 'getCategory',
            'label': this.lang.getCategory,
            'sample': this.lang.incoming,
            'cssClasses': ['align_leftData'],
            'icon': 'fa-exchange-alt'
        },
        {
            'value': 'getDoctype',
            'label': this.lang.getDoctype,
            'sample': this.lang.getDoctypeSample,
            'cssClasses': ['align_leftData'],
            'icon': 'fa-suitcase'
        },
        {
            'value': 'getAssignee',
            'label': this.lang.getAssignee,
            'sample': this.lang.getAssigneeSample,
            'cssClasses': ['align_leftData'],
            'icon': 'fa-sitemap'
        },
        {
            'value': 'getRecipients',
            'label': this.lang.getRecipients,
            'sample': 'Patricia PETIT',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-user'
        },
        {
            'value': 'getSenders',
            'label': this.lang.getSenders,
            'sample': 'Alain DUBOIS (MAARCH)',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-book'
        },
        {
            'value': 'getCreationAndProcessLimitDates',
            'label': this.lang.getCreationAndProcessLimitDates,
            'sample': this.lang.getCreationAndProcessLimitDatesSample,
            'cssClasses': ['align_leftData'],
            'icon': 'fa-calendar'
        },
        {
            'value': 'getVisaWorkflow',
            'label': this.lang.getVisaWorkflow,
            'sample': '<i color="accent" class="fa fa-check"></i> Barbara BAIN -> <i class="fa fa-hourglass-half"></i> <b>Bruno BOULE</b> -> <i class="fa fa-hourglass-half"></i> Patricia PETIT',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-list-ol'
        },
        {
            'value': 'getSignatories',
            'label': this.lang.getSignatories,
            'sample': 'Denis DAULL, Patricia PETIT',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-certificate'
        },
        {
            'value': 'getModificationDate',
            'label': this.lang.getModificationDate,
            'sample': '01-01-2019',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-calendar-check'
        },
        {
            'value': 'getOpinionLimitDate',
            'label': this.lang.getOpinionLimitDate,
            'sample': '01-01-2019',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-stopwatch'
        },
        {
            'value': 'getParallelOpinionsNumber',
            'label': this.lang.getParallelOpinionsNumber,
            'sample': this.lang.getParallelOpinionsNumberSample,
            'cssClasses': ['align_leftData'],
            'icon': 'fa-comment-alt'
        },
        {
            'value': 'getFolders',
            'label': this.lang.getFolders,
            'sample': this.lang.getFoldersSample,
            'cssClasses': ['align_leftData'],
            'icon': 'fa-folder'
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
    selectedListEvent: string = null;
    selectedListEventClone: string = null;

    processTool: any[] = [
        {
            id: 'dashboard',
            icon: 'fas fa-columns',
            label: this.lang.newsFeed,
        },
        {
            id: 'history',
            icon: 'fas fa-history',
            label: this.lang.history,
        },
        {
            id: 'notes',
            icon: 'fas fa-pen-square',
            label: this.lang.notesAlt,
        },
        {
            id: 'attachments',
            icon: 'fas fa-paperclip',
            label: this.lang.attachments,
        },
        {
            id: 'linkedResources',
            icon: 'fas fa-link',
            label: this.lang.links,
        },
        {
            id: 'diffusionList',
            icon: 'fas fa-share-alt',
            label: this.lang.diffusionList,
        },
        {
            id: 'emails',
            icon: 'fas fa-envelope',
            label: this.lang.mailsSentAlt,
        },
        {
            id: 'visaCircuit',
            icon: 'fas fa-list-ol',
            label: this.lang.visaWorkflow,
        },
        {
            id: 'opinionCircuit',
            icon: 'fas fa-comment-alt',
            label: this.lang.avis,
        },
        {
            id: 'info',
            icon: 'fas fa-info-circle',
            label: this.lang.informations,
        }
    ];
    selectedProcessTool: any = {
        defaultTab : null,
        canUpdate: false, 
    };
    selectedProcessToolClone: string = null;

    @Input('currentBasketGroup') private basketGroup: any;

    constructor(public http: HttpClient, private notify: NotificationService) { }

    ngOnInit(): void {
        this.filteredDataOptions = this.dataControl.valueChanges
            .pipe(
                startWith(''),
                map(value => this._filterData(value))
            );

        this.availableDataClone = JSON.parse(JSON.stringify(this.availableData));
        this.displayedSecondaryData = [];
        let indexData: number = 0;
        this.basketGroup.list_display.forEach((element: any) => {
            indexData = this.availableData.map((e: any) => { return e.value; }).indexOf(element.value);
            this.availableData[indexData].cssClasses = element.cssClasses;
            this.displayedSecondaryData.push(this.availableData[indexData]);
            this.availableData.splice(indexData, 1);
        });
        this.selectedListEvent = this.basketGroup.list_event === null ? 'documentDetails' : this.basketGroup.list_event;
        this.selectedListEventClone = this.selectedListEvent;

        if (this.basketGroup.list_event === 'processDocument') {
            this.selectedProcessTool.defaultTab = this.basketGroup.list_event_data === null ? 'dashboard' : this.basketGroup.list_event_data.defaultTab;
            this.selectedProcessTool.canUpdate = this.basketGroup.list_event_data === null ? false : this.basketGroup.list_event_data.canUpdate;
        }
       
        this.selectedProcessToolClone = JSON.parse(JSON.stringify(this.selectedProcessTool));
        this.displayedSecondaryDataClone = JSON.parse(JSON.stringify(this.displayedSecondaryData));
    }

    toggleData() {
        this.dataControl.disabled ? this.dataControl.enable() : this.dataControl.disable();

        if (this.displayMode == 'label') {
            this.displayMode = 'sample';
        } else {
            this.displayMode = 'label';
        }

    }

    setStyle(item: any, value: string) {
        let typeFont = value.split('_');

        if(typeFont.length == 2) {
            item.cssClasses.forEach((element: any, index: number) => {
                if (element.includes(typeFont[0]) && element != value) {
                    item.cssClasses.splice(index, 1);
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
        if (this.displayedSecondaryData.filter((data: any) => data.value !== 'getFolders').length >= 7 && event.option.value.value !== 'getFolders') {
            this.dataControl.setValue('');
            alert(this.lang.warnMaxDataList);
        } else {
            let i = this.availableData.map((e: any) => { return e.value; }).indexOf(event.option.value.value);
            this.displayedSecondaryData.push(event.option.value);
            this.availableData.splice(i, 1);
            $j('#availableData').blur();
            this.dataControl.setValue('');
        }
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
        this.selectedListEvent = this.selectedListEvent === 'documentDetails' ? null : this.selectedListEvent;
        let template: any = [];
        this.displayedSecondaryData.forEach((element: any) => {
            template.push(
                {
                    'value': element.value,
                    'cssClasses': element.cssClasses,
                    'icon': element.icon,
                }
            );
        });

        this.http.put("../../rest/baskets/" + this.basketGroup.basket_id + "/groups/" + this.basketGroup.group_id, { 'list_display': template, 'list_event': this.selectedListEvent, 'list_event_data': this.selectedProcessTool })
            .subscribe(() => {
                this.displayedSecondaryDataClone = JSON.parse(JSON.stringify(this.displayedSecondaryData));
                this.basketGroup.list_display = template;
                this.selectedListEvent = this.selectedListEvent === null ? 'documentDetails' : this.selectedListEvent;
                this.basketGroup.list_event = this.selectedListEvent;
                this.selectedListEventClone = this.selectedListEvent;
                this.selectedProcessToolClone = JSON.parse(JSON.stringify(this.selectedProcessTool));
                this.notify.success(this.lang.resultPageUpdated);
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
        if (JSON.stringify(this.displayedSecondaryData) === JSON.stringify(this.displayedSecondaryDataClone) && this.selectedListEvent === this.selectedListEventClone && JSON.stringify(this.selectedProcessTool) === JSON.stringify(this.selectedProcessToolClone)) {
            return true 
        } else {
           return false;
        }
    }

    cancelModification() {
        this.displayedSecondaryData = JSON.parse(JSON.stringify(this.displayedSecondaryDataClone));
        this.selectedListEvent = this.selectedListEventClone;
        this.selectedProcessTool = this.selectedProcessToolClone;
        this.availableData = JSON.parse(JSON.stringify(this.availableDataClone));
        
        let indexData: number = 0;
        this.displayedSecondaryData.forEach((element: any) => {
            indexData = this.availableData.map((e: any) => { return e.value; }).indexOf(element.value);
            this.availableData.splice(indexData, 1);
        });
        this.dataControl.setValue('');
    }

    hasFolder() {
        if (this.displayedSecondaryData.map((data: any) => data.value).indexOf('getFolders') > -1) {
            return true
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
            this.selectedProcessTool = null;
        }
    }
}

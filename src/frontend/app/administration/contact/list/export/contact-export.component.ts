import { Component, OnInit, ViewChild, Inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../../../translate.component';
import { NotificationService } from '../../../../notification.service';
import { CdkDragDrop, moveItemInArray, transferArrayItem } from '@angular/cdk/drag-drop';
import { MAT_DIALOG_DATA } from '@angular/material/dialog';
import { SortPipe } from '../../../../../plugins/sorting.pipe';
import { catchError, map, tap, finalize, exhaustMap } from 'rxjs/operators';
import { of } from 'rxjs/internal/observable/of';

declare var $: any;

@Component({
    templateUrl: 'contact-export.component.html',
    styleUrls: ['contact-export.component.scss'],
    providers: [SortPipe],
})
export class ContactExportComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;
    loadingExport: boolean = false;

    delimiters = [';', ',', 'TAB'];
    formats = ['csv'];

    exportModel: any = {
        delimiter: ';',
        format: 'csv',
        data: [],
    };

    exportModelList: any;

    dataAvailable: any[] = [
        {
            value: 'id',
            label: this.lang.id
        },
        {
            value: 'externalId',
            label: 'External Id'
        },
        {
            value: 'enabled',
            label: this.lang.status
        },
        {
            value: 'communicationMeans',
            label: this.lang.communicationMean
        }
    ];

    @ViewChild('listFilter', { static: true }) private listFilter: any;


    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        @Inject(MAT_DIALOG_DATA) public data: any,
        private sortPipe: SortPipe
    ) { }

    async ngOnInit(): Promise<void> {
        await this.getContactFields();
    }

    getContactFields() {
        return new Promise((resolve, reject) => {
            this.http.get('../../rest/contactsParameters').pipe(
                map((data: any) => {
                    const regex = /contactCustomField_[.]*/g;
                    data.contactsParameters = data.contactsParameters.filter((field: any) => field.identifier.match(regex) === null).map((field: any) => {
                        return {
                            value : field.identifier,
                            label: this.lang['contactsParameters_' + field.identifier]
                        };
                    });
                    return data.contactsParameters;
                }),
                tap((fields: any) => {
                    this.dataAvailable = this.dataAvailable.concat(fields);
                }),
                exhaustMap(() => this.http.get('../../rest/contactsCustomFields')),
                map((data: any) => {
                    data.customFields = data.customFields.map((field: any) => {
                        return {
                            value: `contactCustomField_${field.id}`,
                            label: field.label
                        };
                    });
                    return data.customFields;
                }),
                tap((fields: any) => {
                    this.dataAvailable = this.dataAvailable.concat(fields);
                    this.dataAvailable = this.sortPipe.transform(this.dataAvailable, 'label');
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    drop(event: CdkDragDrop<string[]>) {
        if (event.previousContainer === event.container) {
            moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
        } else {
            let realIndex = event.previousIndex;
            if (event.container.id === 'selectedElements') {
                realIndex = 0;
                if ($('.available-data .columns')[event.previousIndex] !== undefined) {
                    const fakeIndex = $('.available-data .columns')[event.previousIndex].id;
                    realIndex = this.dataAvailable.map((dataAv: any) => (dataAv.value)).indexOf(fakeIndex);
                }
            }

            transferArrayItem(event.previousContainer.data,
                event.container.data,
                realIndex,
                event.currentIndex);
            const curFilter = this.listFilter.nativeElement.value;
            this.listFilter.nativeElement.value = '';
            setTimeout(() => {
                this.listFilter.nativeElement.value = curFilter;
            }, 10);

        }
    }

    exportData() {
        this.loadingExport = true;
        this.http.put('../../rest/exportContacts', this.exportModel, { responseType: 'blob' }).pipe(
            tap((data: any) => {
                if (data.type !== 'text/html') {
                    const downloadLink = document.createElement('a');
                    downloadLink.href = window.URL.createObjectURL(data);
                    let today: any;
                    let dd: any;
                    let mm: any;
                    let yyyy: any;

                    today = new Date();
                    dd = today.getDate();
                    mm = today.getMonth() + 1;
                    yyyy = today.getFullYear();

                    if (dd < 10) {
                        dd = '0' + dd;
                    }
                    if (mm < 10) {
                        mm = '0' + mm;
                    }
                    today = dd + '-' + mm + '-' + yyyy;
                    downloadLink.setAttribute('download', 'export_contact_maarch_' + today + '.' + this.exportModel.format.toLowerCase());
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                } else {
                    alert(this.lang.tooMuchDatas);
                }
            }),
            finalize(() => this.loadingExport = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    addData(item: any) {
        let realIndex = 0;

        this.dataAvailable.forEach((value: any, index: number) => {
            if (value.value === item.value) {
                realIndex = index;
            }
        });

        transferArrayItem(this.dataAvailable, this.exportModel.data, realIndex, this.exportModel.data.length);
        const curFilter = this.listFilter.nativeElement.value;
        this.listFilter.nativeElement.value = '';
        setTimeout(() => {
            this.listFilter.nativeElement.value = curFilter;
        }, 10);
    }

    removeData(i: number) {
        transferArrayItem(this.exportModel.data, this.dataAvailable, i, this.dataAvailable.length);
        this.sortPipe.transform(this.dataAvailable, 'label');
    }

    removeAllData() {
        this.dataAvailable = this.dataAvailable.concat(this.exportModel.data);
        this.exportModel.data = [];
    }

    addAllData() {
        this.exportModel.data = this.exportModel.data.concat(this.dataAvailable);
        while (this.dataAvailable.length > 0) {
            this.dataAvailable.pop();
        }
        this.listFilter.nativeElement.value = '';
    }
}

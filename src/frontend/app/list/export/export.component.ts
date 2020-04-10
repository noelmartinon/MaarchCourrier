import { Component, OnInit, ViewChild, Inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { CdkDragDrop, moveItemInArray, transferArrayItem } from '@angular/cdk/drag-drop';
import { MAT_DIALOG_DATA } from '@angular/material/dialog';
import { SortPipe } from '../../../plugins/sorting.pipe';
import { catchError, map, tap, finalize } from 'rxjs/operators';
import { of } from 'rxjs/internal/observable/of';

declare var $: any;

@Component({
    templateUrl: 'export.component.html',
    styleUrls: ['export.component.scss'],
    providers: [SortPipe],
})
export class ExportComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;
    loadingExport: boolean = false;

    delimiters = [';', ',', 'TAB'];
    formats = ['csv', 'pdf'];

    exportModel: any = {
        delimiter: ';',
        format: 'csv',
        data: [],
        resources: []
    };

    exportModelList: any;

    dataAvailable: any[] = [
        {
            value: 'res_id',
            label: this.lang.resId,
            isFunction: false
        },
        {
            value: 'type_label',
            label: this.lang.doctype,
            isFunction: false
        },
        {
            value: 'doctypes_first_level_label',
            label: this.lang.firstLevelDoctype,
            isFunction: false
        },
        {
            value: 'doctypes_second_level_label',
            label: this.lang.secondLevelDoctype,
            isFunction: false
        },
        {
            value: 'format',
            label: this.lang.format,
            isFunction: false
        },
        {
            value: 'doc_date',
            label: this.lang.docDate,
            isFunction: false
        },
        {
            value: 'departure_date',
            label: this.lang.departureDate,
            isFunction: false
        },
        {
            value: 'barcode',
            label: this.lang.barcode,
            isFunction: false
        },
        {
            value: 'getFolder',
            label: this.lang.folderName,
            isFunction: true
        },
        {
            value: 'confidentiality',
            label: this.lang.confidentiality,
            isFunction: false
        },
        {
            value: 'alt_identifier',
            label: this.lang.chronoNumber,
            isFunction: false
        },
        {
            value: 'admission_date',
            label: this.lang.admissionDate,
            isFunction: false
        },
        {
            value: 'process_limit_date',
            label: this.lang.processLimitDate,
            isFunction: false
        },
        {
            value: 'opinion_limit_date',
            label: this.lang.getOpinionLimitDate,
            isFunction: false
        },
        {
            value: 'closing_date',
            label: this.lang.closingDate,
            isFunction: false
        },
        {
            value: 'subject',
            label: this.lang.subject,
            isFunction: false
        },
        {
            value: 'getStatus',
            label: this.lang.status,
            isFunction: true
        },
        {
            value: 'getPriority',
            label: this.lang.priority,
            isFunction: true
        },
        {
            value: 'getCopies',
            label: this.lang.copyUsersEntities,
            isFunction: true
        },
        {
            value: 'getDetailLink',
            label: this.lang.detailLink,
            isFunction: true
        },
        {
            value: 'getParentFolder',
            label: this.lang.parentFolder,
            isFunction: true
        },
        {
            value: 'getCategory',
            label: this.lang.category_id,
            isFunction: true
        },
        {
            value: 'getInitiatorEntity',
            label: this.lang.initiatorEntity,
            isFunction: true
        },
        {
            value: 'getDestinationEntity',
            label: this.lang.destinationEntity,
            isFunction: true
        },
        {
            value: 'getDestinationEntityType',
            label: this.lang.destinationEntityType,
            isFunction: true
        },
        {
            value: 'getSenders',
            label: this.lang.getSenders,
            isFunction: true
        },
        {
            value: 'getRecipients',
            label: this.lang.getRecipients,
            isFunction: true
        },
        {
            value: 'getTypist',
            label: this.lang.typist,
            isFunction: true
        },
        {
            value: 'getAssignee',
            label: this.lang.dest_user,
            isFunction: true
        },
        {
            value: 'getTags',
            label: this.lang.tags,
            isFunction: true
        },
        {
            value: 'getSignatories',
            label: this.lang.signUser,
            isFunction: true
        },
        {
            value: 'getSignatureDates',
            label: this.lang.signatureDate,
            isFunction: true
        },
        {
            value: 'getDepartment',
            label: this.lang.department,
            isFunction: true
        },
        {
            value: 'getAcknowledgementSendDate',
            label: this.lang.acknowledgementSendDate,
            isFunction: true
        },
        {
            value: '',
            label: this.lang.comment,
            isFunction: true
        }
    ];
    dataAvailableClone: any[] = [];

    @ViewChild('listFilter', { static: true }) private listFilter: any;


    constructor(public http: HttpClient, private notify: NotificationService, @Inject(MAT_DIALOG_DATA) public data: any, private sortPipe: SortPipe) { }

    ngOnInit(): void {
        this.dataAvailableClone = JSON.parse(JSON.stringify(this.dataAvailable));

        this.http.get('../rest/resourcesList/exportTemplate')
            .subscribe((data: any) => {
                this.exportModel.resources = this.data.selectedRes;

                this.exportModelList = data.templates;

                this.exportModel.data = data.templates.csv.data;
                this.exportModel.data.forEach((value: any) => {
                    this.dataAvailable.forEach((availableValue: any, index: number) => {
                        if (value.value === availableValue.value) {
                            this.dataAvailable.splice(index, 1);
                        }
                    });
                });
                this.loading = false;
            }, (err: any) => {
                this.notify.handleErrors(err);
            });

        this.http.get('../rest/customFields').pipe(
            map((data: any) => {
                data.customFields = data.customFields.map((custom: any) => {
                    return {
                        value: 'custom_' + custom.id,
                        label: custom.label,
                        isFunction: true
                    };
                });
                return data;
            }),
            tap((data: any) => {
                this.dataAvailable = this.dataAvailable.concat(data.customFields);
                this.dataAvailableClone = this.dataAvailableClone.concat(data.customFields);
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
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
        this.http.put('../rest/resourcesList/users/' + this.data.ownerId + '/groups/' + this.data.groupId + '/baskets/' + this.data.basketId + '/exports', this.exportModel, { responseType: 'blob' }).pipe(
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
                    downloadLink.setAttribute('download', 'export_maarch_' + today + '.' + this.exportModel.format.toLowerCase());
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    this.exportModelList[this.exportModel.format.toLowerCase()].data = this.exportModel.data;
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

    changeTemplate(event: any) {
        this.dataAvailable = JSON.parse(JSON.stringify(this.dataAvailableClone));
        this.exportModel.format = event.value;
        this.exportModel.data = this.exportModelList[event.value].data;
        this.exportModel.data.forEach((value: any) => {
            this.dataAvailable.forEach((availableValue: any, index: number) => {
                if (value.value === availableValue.value) {
                    this.dataAvailable.splice(index, 1);
                }
            });
        });
    }
}

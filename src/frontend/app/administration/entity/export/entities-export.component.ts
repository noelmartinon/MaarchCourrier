import { Component, OnInit, ViewChild, Inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { NotificationService } from '@service/notification/notification.service';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { catchError, tap, finalize } from 'rxjs/operators';
import { of } from 'rxjs';
import { LocalStorageService } from '@service/local-storage.service';
import { HeaderService } from '@service/header.service';
import { TranslateService } from '@ngx-translate/core';
import { CdkDragDrop, moveItemInArray, transferArrayItem } from '@angular/cdk/drag-drop';
import { SortPipe } from '@plugins/sorting.pipe';

@Component({
    templateUrl: 'entities-export.component.html',
    styleUrls: ['entities-export.component.scss'],
    providers: [SortPipe],
})
export class EntitiesExportComponent implements OnInit {

    @ViewChild('listFilter', { static: true }) private listFilter: any;

    loading: boolean = false;
    loadingExport: boolean = false;

    delimiters = [';', ',', 'TAB'];
    formats = ['csv'];

    exportModel: any = {
        delimiter: ';',
        format: 'csv',
        data: []
    };

    exportModelList: any;

    canModifyHeaders: boolean = false;

    dataAvailable: any[] = [
        {
            value: 'id',
            label: this.translate.instant('lang.entitiesParameters_id')
        },
        {
            value: 'entity_id',
            label: this.translate.instant('lang.entitiesParameters_entity_id')
        },
        {
            value: 'entity_label',
            label: this.translate.instant('lang.entitiesParameters_entity_label')
        },
        {
            value: 'short_label',
            label: this.translate.instant('lang.entitiesParameters_short_label')
        },
        {
            value: 'entity_full_name',
            label: this.translate.instant('lang.entitiesParameters_entity_full_name')
        },
        {
            value: 'enabled',
            label: this.translate.instant('lang.entitiesParameters_enabled')
        },
        {
            value: 'address_number',
            label: this.translate.instant('lang.entitiesParameters_address_number')
        },
        {
            value: 'address_street',
            label: this.translate.instant('lang.entitiesParameters_address_street')
        },
        {
            value: 'address_additional1',
            label: this.translate.instant('lang.entitiesParameters_address_additional1')
        },
        {
            value: 'address_additional2',
            label: this.translate.instant('lang.entitiesParameters_address_additional2')
        },
        {
            value: 'address_postcode',
            label: this.translate.instant('lang.entitiesParameters_address_postcode')
        },
        {
            value: 'address_town',
            label: this.translate.instant('lang.entitiesParameters_address_town')
        },
        {
            value: 'address_country',
            label: this.translate.instant('lang.entitiesParameters_address_country')
        },
        {
            value: 'email',
            label: this.translate.instant('lang.entitiesParameters_email')
        },
        {
            value: 'parent_entity_id',
            label: this.translate.instant('lang.entitiesParameters_parent_entity_id')
        },
        {
            value: 'entity_type',
            label: this.translate.instant('lang.entitiesParameters_entity_type')
        },
        {
            value: 'business_id',
            label: this.translate.instant('lang.entitiesParameters_business_id')
        },
        {
            value: 'folder_import',
            label: this.translate.instant('lang.entitiesParameters_folder_import')
        },
        {
            value: 'producer_service',
            label: this.translate.instant('lang.entitiesParameters_producer_service')
        },
        {
            value: 'diffusionList',
            label: this.translate.instant('lang.entitiesParameters_diffusionList')
        },

        {
            value: 'visaCircuit',
            label: this.translate.instant('lang.entitiesParameters_visaCircuit')
        },
        {
            value: 'opinionCircuit',
            label: this.translate.instant('lang.entitiesParameters_opinionCircuit')
        },
        {
            value: 'users',
            label: this.translate.instant('lang.entitiesParameters_users')
        },
        {
            value: 'templates',
            label: this.translate.instant('lang.entitiesParameters_templates')
        }
    ];

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        public dialogRef: MatDialogRef<EntitiesExportComponent>,
        @Inject(MAT_DIALOG_DATA) public data: any,
        private localStorage: LocalStorageService,
        private headerService: HeaderService,
        private sortPipe: SortPipe,
    ) { }

    ngOnInit(): void {
        if (this.localStorage.get(`exportEntities_${this.headerService.user.id}`) !== null) {
            this.canModifyHeaders = JSON.parse(this.localStorage.get(`exportEntities_${this.headerService.user.id}`)).data.length > 0 ? true : false;
        } else {
            this.canModifyHeaders = false;
        }
        this.setConfiguration();
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

    setValues() {
        this.canModifyHeaders = !this.canModifyHeaders;
        if (!this.canModifyHeaders) {
            this.exportModel.data = [];
        }
    }

    exportData() {
        this.localStorage.save(`exportEntities_${this.headerService.user.id}`, JSON.stringify(this.exportModel));
        this.loadingExport = true;
        this.http.put('../rest/entities/export', this.exportModel, { responseType: 'blob' }).pipe(
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
                    downloadLink.setAttribute('download', 'export_entities_maarch_' + today + '.' + this.exportModel.format.toLowerCase());
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    this.dialogRef.close();
                } else {
                    alert(this.translate.instant('lang.tooMuchDatas'));
                }
            }),
            finalize(() => this.loadingExport = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    setConfiguration() {
        if (this.localStorage.get(`exportEntities_${this.headerService.user.id}`) !== null) {
            JSON.parse(this.localStorage.get(`exportEntities_${this.headerService.user.id}`)).data.forEach((element: any) => {
                this.addData(element);
            });
            this.exportModel.delimiter = JSON.parse(this.localStorage.get(`exportEntities_${this.headerService.user.id}`)).delimiter;
        }
    }
}

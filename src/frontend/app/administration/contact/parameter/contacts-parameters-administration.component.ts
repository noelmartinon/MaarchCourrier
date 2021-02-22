import { Component, OnInit, ViewChild, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { HeaderService } from '@service/header.service';
import { AppService } from '@service/app.service';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { FunctionsService } from '@service/functions.service';
import { ContactService } from '@service/contact.service';

@Component({
    templateUrl: 'contacts-parameters-administration.component.html',
    styleUrls: ['contacts-parameters-administration.component.scss'],
    providers: [ContactService]
})
export class ContactsParametersAdministrationComponent implements OnInit {

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    contactsFilling: any = {
        'enable': false,
        'first_threshold': '33',
        'second_threshold': '66',
    };

    contactsParameters: any = [];

    arrRatingColumns: String[] = [];
    fillingColor = {
        'first_threshold': '#E81C2B',
        'second_threshold': '#F4891E',
        'third_threshold': '#0AA34F',
    };

    loading: boolean = false;

    dataSource = new MatTableDataSource(this.contactsParameters);
    displayedColumns = ['label', 'mandatory', 'filling', 'searchable', 'displayable'];

    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService,
        public functionsService: FunctionsService,
        public contactService: ContactService,
        private viewContainerRef: ViewContainerRef
    ) { }

    ngOnInit(): void {

        this.loading = true;

        this.headerService.setHeader(this.translate.instant('lang.contactsParameters'));
        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');

        this.http.get('../rest/contactsParameters')
            .subscribe((data: any) => {
                this.contactsFilling = data.contactsFilling;
                this.contactsParameters = data.contactsParameters.map((item: any) => {
                    return {
                        ...item,
                        label : this.functionsService.empty(item.label) ? this.translate.instant('lang.contactsParameters_' + item.identifier) : item.label
                    };
                });

                this.loading = false;
                setTimeout(() => {
                    this.dataSource = new MatTableDataSource(this.contactsParameters);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sort = this.sort;
                }, 0);
            });
    }

    addCriteria(event: any, criteria: any, type: string) {
        this.contactsParameters.forEach((col: any, i: number) => {
            if (col.id == criteria.id) {
                this.contactsParameters[i][type] = event.checked;
            }
        });

        this.onSubmit();
    }

    onSubmit() {
        if (this.contactsFilling.first_threshold >= this.contactsFilling.second_threshold) {
            this.contactsFilling.second_threshold = this.contactsFilling.first_threshold + 1;
        }
        this.http.put('../rest/contactsParameters', { 'contactsFilling': this.contactsFilling, 'contactsParameters': this.contactsParameters })
            .subscribe(() => {
                this.notify.success(this.translate.instant('lang.parameterUpdated'));

            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    toggleFillingContact() {
        this.contactsFilling.enable === true ? this.contactsFilling.enable = false : this.contactsFilling.enable = true;
        this.onSubmit();
    }
}

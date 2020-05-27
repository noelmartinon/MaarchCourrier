import { Component, ViewChild, OnInit, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../../translate.component';
import { NotificationService } from '../../../notification.service';
import { HeaderService } from '../../../../service/header.service';
import { MatPaginator } from '@angular/material/paginator';
import { MatSidenav } from '@angular/material/sidenav';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { AppService } from '../../../../service/app.service';
import { FunctionsService } from '../../../../service/functions.service';

declare function $j(selector: any): any;

@Component({
    templateUrl: 'contacts-groups-administration.component.html',
    styleUrls: [
        'contacts-groups-administration.component.scss'
    ],
    providers: [AppService]
})

export class ContactsGroupsAdministrationComponent implements OnInit {

    @ViewChild('snav2', { static: true }) public sidenavRight: MatSidenav;
    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    lang: any = LANG;
    search: string = null;

    contactsGroups: any[] = [];
    titles: any[] = [];

    loading: boolean = false;

    subMenus: any[] = [
        {
            icon: 'fas fa-magic',
            route: '/administration/contacts/duplicates',
            label: this.lang.duplicatesContactsAdmin,
            current: false
        },
        {
            icon: 'fa fa-book',
            route: '/administration/contacts/list',
            label: this.lang.contactsList,
            current: false
        },
        {
            icon: 'fa fa-code',
            route: '/administration/contacts/contactsCustomFields',
            label: this.lang.customFieldsAdmin,
            current: false
        },
        {
            icon: 'fa fa-cog',
            route: '/administration/contacts/contacts-parameters',
            label: this.lang.contactsParameters,
            current: false
        },
        {
            icon: 'fa fa-users',
            route: '/administration/contacts/contacts-groups',
            label: this.lang.contactsGroups,
            current: true
        },
    ];

    displayedColumns = ['label', 'description', 'nbContacts', 'public', 'owner', 'actions',];
    dataSource = new MatTableDataSource(this.contactsGroups);
    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;
    applyFilter(filterValue: string) {
        filterValue = filterValue.trim(); // Remove whitespace
        filterValue = filterValue.toLowerCase(); // MatTableDataSource defaults to lowercase matches
        this.dataSource.filter = filterValue;
        this.dataSource.filterPredicate = (template, filter: string) => {
            return this.functions.filterUnSensitive(template, filter, ['label', 'description']);
        };
    }

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService,
        public functions: FunctionsService,
        private viewContainerRef: ViewContainerRef
    ) {
        $j("link[href='merged_css.php']").remove();
    }

    ngOnInit(): void {
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.contactsGroups);
        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');

        this.loading = true;

        this.http.get('../../rest/contactsGroups')
            .subscribe((data) => {
                this.contactsGroups = data['contactsGroups'];
                this.loading = false;
                setTimeout(() => {
                    this.dataSource = new MatTableDataSource(this.contactsGroups);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sort = this.sort;
                }, 0);
            }, (err) => {
                this.notify.handleErrors(err);
            });
    }

    deleteContactsGroup(row: any) {
        const contactsGroup = this.contactsGroups[row];
        const r = confirm(this.lang.confirmAction + ' ' + this.lang.delete + ' « ' + contactsGroup.label + ' »');

        if (r) {
            this.http.delete('../../rest/contactsGroups/' + contactsGroup.id)
                .subscribe(() => {
                    const lastElement = this.contactsGroups.length - 1;
                    this.contactsGroups[row] = this.contactsGroups[lastElement];
                    this.contactsGroups[row].position = row;
                    this.contactsGroups.splice(lastElement, 1);

                    this.dataSource = new MatTableDataSource(this.contactsGroups);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sort = this.sort;
                    this.notify.success(this.lang.contactsGroupDeleted);

                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }
}

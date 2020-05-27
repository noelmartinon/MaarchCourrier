import { Component, OnInit, ViewChild, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../../translate.component';
import { HeaderService } from '../../../../service/header.service';
import { AppService } from '../../../../service/app.service';
import { MatDialog } from '@angular/material';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
    templateUrl: 'contacts-page-administration.component.html',
    styleUrls: ['contacts-page-administration.component.scss'],
    providers: [AppService]
})
export class ContactsPageAdministrationComponent implements OnInit {

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    lang: any = LANG;
    loading: boolean = false;

    creationMode: boolean = true;

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
            current: false
        },
    ];

    contactId: number = null;

    constructor(
        public http: HttpClient,
        private route: ActivatedRoute,
        private router: Router,
        private headerService: HeaderService,
        public appService: AppService,
        public dialog: MatDialog,
        private viewContainerRef: ViewContainerRef) { }

    ngOnInit(): void {

        this.loading = true;

        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');

        this.route.params.subscribe((params: any) => {

            if (typeof params['id'] === 'undefined') {
                this.headerService.setHeader(this.lang.contactCreation);
                this.creationMode = true;
                this.loading = false;

            } else {

                this.headerService.setHeader(this.lang.contactModification);

                this.creationMode = false;

                this.contactId = params['id'];

                this.loading = false;
            }
        });
    }

    goToList() {
        this.router.navigate(['/administration/contacts/list']);
    }
}

import { Component, OnInit, ViewChild, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { HeaderService } from '@service/header.service';
import { AppService } from '@service/app.service';
import { MatDialog } from '@angular/material/dialog';
import { environment } from '../../../environments/environment';

@Component({
    templateUrl: 'organization-email-signatures-administration.component.html',
    styleUrls: ['organization-email-signatures-administration.component.scss']
})
export class OrganizationEmailSignaturesAdministrationComponent implements OnInit {

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    loading: boolean = false;

    docUrl: string = `https://docs.maarch.org/gitbook/html/MaarchCourrier/${environment.VERSION.split('.')[0] + '.' + environment.VERSION.split('.')[1]}/guaf/guaf_organization_email_signatures/home.html`;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private headerService: HeaderService,
        public appService: AppService,
        public dialog: MatDialog,
        private viewContainerRef: ViewContainerRef
    ) { }

    ngOnInit(): void {
        this.headerService.setHeader(this.translate.instant('lang.organizationEmailSignatures'));

        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');

    }
}

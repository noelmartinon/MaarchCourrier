import { Component, ViewChild, OnInit, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { HeaderService } from '../../../service/header.service';
import { AppService } from '../../../service/app.service';
import { FunctionsService } from '../../../service/functions.service';

@Component({
    templateUrl: 'templates-administration.component.html',
    providers: [AppService]
})

export class TemplatesAdministrationComponent implements OnInit {

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    lang: any = LANG;
    search: string = null;

    templates: any[] = [];
    titles: any[] = [];

    loading: boolean = false;

    displayedColumns = ['template_label', 'template_comment', 'template_type', 'template_target', 'actions'];
    dataSource = new MatTableDataSource(this.templates);
    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;
    applyFilter(filterValue: string) {
        filterValue = filterValue.trim(); // Remove whitespace
        filterValue = filterValue.toLowerCase(); // MatTableDataSource defaults to lowercase matches
        this.dataSource.filter = filterValue;
        this.dataSource.filterPredicate = (template, filter: string) => {
            return this.functions.filterUnSensitive(template, filter, ['template_label', 'template_comment', 'template_type', 'template_target']);
        };
    }

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService,
        public functions: FunctionsService,
        private viewContainerRef: ViewContainerRef
    ) { }

    ngOnInit(): void {
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.templates);

        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');

        this.loading = true;

        this.http.get('../rest/templates')
            .subscribe((data) => {
                this.templates = data['templates'];
                this.loading = false;
                setTimeout(() => {
                    this.dataSource = new MatTableDataSource(this.templates);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sortingDataAccessor = this.functions.listSortingDataAccessor;
                    this.sort.active = 'template_label';
                    this.sort.direction = 'asc';
                    this.dataSource.sort = this.sort;
                }, 0);
            }, (err) => {
                this.notify.handleErrors(err);
            });
    }

    deleteTemplate(template: any) {
        const r = confirm(this.lang.confirmAction + ' ' + this.lang.delete + ' « ' + template.template_label + ' »');

        if (r) {
            this.http.delete('../rest/templates/' + template.template_id)
                .subscribe(() => {
                    for (const i in this.templates) {
                        if (this.templates[i].template_id === template.template_id) {
                            this.templates.splice(Number(i), 1);
                        }
                    }
                    this.dataSource = new MatTableDataSource(this.templates);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sort = this.sort;

                    this.notify.success(this.lang.templateDeleted);

                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }
}

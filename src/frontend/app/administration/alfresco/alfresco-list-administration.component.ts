import { Component, OnInit, ViewChild, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { HeaderService } from '../../../service/header.service';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { AppService } from '../../../service/app.service';
import { FunctionsService } from '../../../service/functions.service';
import { tap } from 'rxjs/internal/operators/tap';
import { catchError } from 'rxjs/internal/operators/catchError';
import { of } from 'rxjs/internal/observable/of';
import { ConfirmComponent } from '../../../plugins/modal/confirm.component';
import { MatDialogRef } from '@angular/material/dialog';
import { filter } from 'rxjs/internal/operators/filter';
import { exhaustMap } from 'rxjs/internal/operators/exhaustMap';
import { MatDialog } from '@angular/material/dialog';

@Component({
    templateUrl: 'alfresco-list-administration.component.html',
    styleUrls: ['./alfresco-list-administration.component.scss'],
    providers: [AppService]
})
export class AlfrescoListAdministrationComponent implements OnInit {

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    lang: any = LANG;

    alfrescoUrl: string = '';

    accounts: any[] = [];

    loading: boolean = false;

    displayedColumns = ['label', 'entitiesLabel', 'actions'];
    dataSource: any;
    dialogRef: MatDialogRef<any>;

    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;


    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService,
        private dialog: MatDialog,
        public functions: FunctionsService,
        private viewContainerRef: ViewContainerRef
    ) { }

    applyFilter(filterValue: string) {
        filterValue = filterValue.trim(); // Remove whitespace
        filterValue = filterValue.toLowerCase(); // MatTableDataSource defaults to lowercase matches
        this.dataSource.filter = filterValue;
        this.dataSource.filterPredicate = (template: any, filter: string) => {
            return this.functions.filterUnSensitive(template, filter, ['label', 'entitiesLabel']);
        };
    }

    ngOnInit(): void {
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.alfresco);

        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');

        this.loading = true;

        this.http.get('../rest/alfresco/configuration').pipe(
            filter((data: any) => !this.functions.empty(data.configuration)),
            tap((data: any) => {
                this.alfrescoUrl = data.configuration.uri;
            })
        ).subscribe();

        this.http.get('../rest/alfresco/accounts')
            .subscribe((data: any) => {
                this.accounts = data.accounts;

                setTimeout(() => {
                    this.dataSource = new MatTableDataSource(this.accounts);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sort = this.sort;
                }, 0);
                this.loading = false;
            });
    }

    deleteAccount(id: number) {

        this.dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.lang.delete, msg: this.lang.confirmAction } });

        this.dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.delete('../rest/alfresco/accounts/' + id)),
            tap(() => {
                this.accounts = this.accounts.filter((account: any) => account.id !== id);
                this.dataSource = new MatTableDataSource(this.accounts);
                this.dataSource.paginator = this.paginator;
                this.dataSource.sort = this.sort;
                this.notify.success(this.lang.accountDeleted);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    saveUrl() {
        this.http.put('../rest/alfresco/configuration', { uri: this.alfrescoUrl }).pipe(
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}

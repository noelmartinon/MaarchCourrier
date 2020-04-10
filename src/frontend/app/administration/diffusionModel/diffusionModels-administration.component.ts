import { Component, OnInit, ViewChild, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { HeaderService } from '../../../service/header.service';
import { MatDialog } from '@angular/material/dialog';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { AppService } from '../../../service/app.service';
import { tap } from 'rxjs/internal/operators/tap';
import { catchError, map, finalize, filter, exhaustMap } from 'rxjs/operators';
import { of } from 'rxjs';
import { ConfirmComponent } from '../../../plugins/modal/confirm.component';
import {FunctionsService} from "../../../service/functions.service";
import {LatinisePipe} from "ngx-pipes";

@Component({
    templateUrl: "diffusionModels-administration.component.html",
    providers: [AppService]
})
export class DiffusionModelsAdministrationComponent implements OnInit {

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    lang: any = LANG;
    loading: boolean = false;

    listTemplates: any[] = [];
    listTemplatesForAssign: any[] = [];

    displayedColumns = ['label', 'description', 'typeLabel', 'actions'];
    dataSource = new MatTableDataSource(this.listTemplates);


    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;
    applyFilter(filterValue: string) {
        filterValue = filterValue.trim();
        filterValue = filterValue.toLowerCase();
        this.dataSource.filter = filterValue;
        this.dataSource.filterPredicate = (template, filter: string) => {
            let filterReturn = false;
            filter = this.latinisePipe.transform(filter);
            this.displayedColumns.forEach((column:any) => {
                if (column === 'description' || column === 'typeLabel') {
                    filterReturn = filterReturn || this.latinisePipe.transform(template[column].toLowerCase()).includes(filter);
                } else if (column === 'label') {
                    filterReturn = filterReturn || this.latinisePipe.transform(template['title'].toLowerCase()).includes(filter);
                }
            });
            return filterReturn;
        };
    }

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        public dialog: MatDialog,
        private headerService: HeaderService,
        public appService: AppService,
        public functions: FunctionsService,
        private latinisePipe: LatinisePipe,
        private viewContainerRef: ViewContainerRef
    ) { }

    async ngOnInit(): Promise<void> {
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.workflowModels);

        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');

        this.loading = true;

        await this.getListemplates();

        this.loadList();

        this.loading = false;
    }

    getListemplates() {
        return new Promise((resolve, reject) => {
            this.http.get("../rest/listTemplates").pipe(
                map((data: any) => {
                    data.listTemplates = data.listTemplates.filter((template: any) => template.entityId === null && ['visaCircuit', 'opinionCircuit'].indexOf(template.type) > -1).map((template: any) => {
                        return {
                            ...template,
                            typeLabel: this.lang[template.type]
                        }
                    });
                    return data.listTemplates;
                }),
                tap((listTemplates: any) => {
                    this.listTemplates = listTemplates;
                    resolve(true);
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    loadList() {
        setTimeout(() => {
            this.dataSource = new MatTableDataSource(this.listTemplates);
            this.dataSource.paginator = this.paginator;
            this.dataSource.sortingDataAccessor = (data: any, sortHeaderId: any) => {
                if (sortHeaderId === 'description' || sortHeaderId === 'typeLabel') {
                    return data[sortHeaderId].toLocaleLowerCase();
                }
                if (sortHeaderId === 'label') {
                    return data['title'].toLocaleLowerCase();
                }
            };
            this.sort.active = 'label';
            this.sort.direction = 'asc';
            this.dataSource.sort = this.sort;
        }, 0);
    }

    delete(listTemplate: any) {
        const dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.lang.delete, msg: this.lang.confirmAction } });
        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.delete("../rest/listTemplates/" + listTemplate['id'])),
            tap(() => {
                this.listTemplates = this.listTemplates.filter((template: any) => template.id !== listTemplate.id);
                this.notify.success(this.lang.diffusionModelDeleted);
                this.loadList();
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}

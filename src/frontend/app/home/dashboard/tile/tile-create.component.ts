import { Component, Inject, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { DashboardService } from '../dashboard.service';
import { FunctionsService } from '@service/functions.service';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { catchError, tap } from 'rxjs/operators';
import { of } from 'rxjs';
import { NotificationService } from '@service/notification/notification.service';
import { HeaderService } from '@service/header.service';

@Component({
    templateUrl: 'tile-create.component.html',
    styleUrls: ['tile-create.component.scss'],
    providers: [DashboardService]
})
export class TileCreateComponent implements OnInit {

    loading: boolean = false;

    tileTypes: any[] = [];
    views: any[] = [];

    sequence: string = null;
    selectedTileType: string = null;
    selectedView: string = null;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialogRef: MatDialogRef<TileCreateComponent>,
        private dashboardService: DashboardService,
        private functionsService: FunctionsService,
        private notify: NotificationService,
        private headerService: HeaderService
    ) { }

    ngOnInit(): void {
        this.sequence = this.data.sequence;
        this.getTileTypes();
    }

    getTileTypes() {
        const tmpTileTypes = this.dashboardService.getTileTypes();
        this.tileTypes = tmpTileTypes.map((tileType: any) => {
            return {
                id : tileType,
                label: this.translate.instant('lang.' + tileType)
            };
        });
    }

    getViews() {
        const tmpViews = this.dashboardService.getViewsByTileType(this.selectedTileType);
        this.views = tmpViews.map((view: any) => {
            return {
                ...view,
                label: this.translate.instant('lang.' + view.id)
            };
        });
        this.selectedView = this.views.length > 0 ? this.views[0].id : null;
    }

    isValid() {
        return !this.functionsService.empty(this.sequence) && !this.functionsService.empty(this.selectedTileType) && ((this.views.length > 0 &&  !this.functionsService.empty(this.selectedView)) || this.views.length === 0);
    }

    formatData() {
        return {
            type : this.selectedTileType,
            view: this.selectedView,
            userId: this.headerService.user.id
        };
    }

    onSubmit() {
        const objToSend: any = this.formatData();
        this.http.post('../rest/tiles', objToSend).pipe(
            tap((data: any) => {
                objToSend.id = data;
                this.dialogRef.close({
                    id : data,
                    type: objToSend.type,
                    view: objToSend.view,
                });
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}

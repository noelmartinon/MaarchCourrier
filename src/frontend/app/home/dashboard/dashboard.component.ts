import { Component, OnInit, AfterViewInit, QueryList, ViewChildren } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { DashboardService } from './dashboard.service';
import { FunctionsService } from '@service/functions.service';
import { TileCreateComponent } from './tile/tile-create.component';
import { exhaustMap, filter, tap } from 'rxjs/operators';
import { MatDialog } from '@angular/material/dialog';
import { ConfirmComponent } from '@plugins/modal/confirm.component';

@Component({
    selector: 'app-dashboard',
    templateUrl: 'dashboard.component.html',
    styleUrls: ['dashboard.component.scss'],
    providers: [DashboardService]
})
export class DashboardComponent implements OnInit, AfterViewInit {

    tiles: any = [];
    hoveredTool: boolean = false;

    @ViewChildren('tileComponent') tileComponent: QueryList<any>;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private dashboardService: DashboardService,
        private functionsService: FunctionsService,
        public dialog: MatDialog,
    ) { }

    ngOnInit(): void {
        this.getDashboardConfig();
    }

    ngAfterViewInit(): void { }

    enterTile(tile: any, index: number) {
        this.hoveredTool = false;
        this.tiles.forEach((element: any, indexTile: number) => {
            element.editMode = indexTile === index ? true : false;
        });
    }

    leaveTile(tile: any) {
        if (!this.hoveredTool) {
            tile.editMode = false;
        }
    }

    getDashboardConfig() {
        const test: any = [
            {
                id: 1,
                type: 'myLastResources',
                view: 'resume',
                sequence: 0
            },
            {
                id: 1,
                type: 'myLastResources',
                view: 'list',
                sequence: 3
            },
            {
                id: 1,
                type: 'myLastResources',
                view: 'chart',
                sequence: 5
            }
        ];

        for (let index = 0; index < 6; index++) {
            const tmpTile = test.find((tile: any) => tile.sequence === index);
            if (!this.functionsService.empty(tmpTile)) {
                const objTile = {...this.dashboardService.getTile(tmpTile.type), ...tmpTile};
                this.tiles.push(objTile);
            } else {
                this.tiles.push({
                    id: null,
                    sequence: index,
                    editMode: false
                });
            }
        }
    }

    changeView(tile: any, view: string) {
        const indexTile = this.tiles.filter((tileItem: any) => tileItem.id !== null).map((tileItem: any) => tileItem.sequence).indexOf(tile.sequence);
        this.tileComponent.toArray()[indexTile].changeView(view);
        tile.view = view;
    }

    transferDataSuccess() {
        this.tiles.forEach((tile: any, index: number) => {
            tile.sequence = index;
        });
        // TO DO : SAVE IN BACK
    }

    addTilePrompt(tile: any) {
        const dialogRef = this.dialog.open(TileCreateComponent, { panelClass: 'maarch-modal', width: '450px', autoFocus: false, disableClose: true, data: { sequence: tile.sequence} });

        dialogRef.afterClosed().pipe(
            filter((data: string) => !this.functionsService.empty(data)),
            tap((data: any) => {
                tile = {...this.dashboardService.getTile(data.type), ...data};
            })
        ).subscribe();
    }

    launchAction(action: string, tile: any) {
        this[action](tile);
    }

    delete(tile: any) {
        const dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.translate.instant('lang.delete'), msg: this.translate.instant('lang.confirmAction') } });

        // TO DO: SAVE IN BACK
        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            tap(() => {
                this.tiles[tile.sequence] = {
                    id: null,
                    sequence: tile.sequence,
                    editMode: false
                };
            })
        ).subscribe();
    }
}

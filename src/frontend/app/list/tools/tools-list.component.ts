import { Component, OnInit, Input, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { MatSidenav, MatAutocompleteTrigger, MatDialog } from '@angular/material';
import { Observable } from 'rxjs';
import { ExportComponent } from '../export/export.component';
import { SummarySheetComponent } from '../summarySheet/summary-sheet.component';



export interface StateGroup {
    letter: string;
    names: any[];
}

@Component({
    selector: 'app-tools-list',
    templateUrl: 'tools-list.component.html',
    styleUrls: ['tools-list.component.scss'],
})
export class ToolsListComponent implements OnInit {

    lang: any = LANG;


    @ViewChild(MatAutocompleteTrigger) autocomplete: MatAutocompleteTrigger;

    priorities: any[] = [];
    categories: any[] = [];
    entitiesList: any[] = [];
    statuses: any[] = [];
    metaSearchInput: string = '';

    stateGroups: StateGroup[] = [];
    stateGroupOptions: Observable<StateGroup[]>;

    isLoading: boolean = false;

    
    @Input('listProperties') listProperties: any;
    @Input('currentBasketInfo') currentBasketInfo: any;

    @Input('snavR') sidenavRight: MatSidenav;
    @Input('selectedRes') selectedRes: any;
    @Input('totalRes') totalRes: number;

    constructor(public http: HttpClient, public dialog: MatDialog) { }

    ngOnInit(): void {

    }

    openExport(): void {
        this.dialog.open(ExportComponent, {
            width: '800px',
            data: {
                ownerId: this.currentBasketInfo.ownerId,
                groupId: this.currentBasketInfo.groupId,
                basketId: this.currentBasketInfo.basketId,
                selectedRes: this.selectedRes
            }
        });
    }

    openSummarySheet(): void {
        this.dialog.open(SummarySheetComponent, {
            panelClass: 'summary-sheet-dialog',
            width: '800px',
            data: {
                ownerId: this.currentBasketInfo.ownerId,
                groupId: this.currentBasketInfo.groupId,
                basketId: this.currentBasketInfo.basketId,
                selectedRes: this.selectedRes
            }
        });
    }
}

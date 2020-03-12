import { Component, OnInit, Input, ViewChild, Output, EventEmitter } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { MatDialog, MatDialogRef } from '@angular/material/dialog';
import { MatMenuTrigger } from '@angular/material/menu';

import { Router } from '@angular/router';
import { ConfirmComponent } from '../../../plugins/modal/confirm.component';
import { filter, exhaustMap, tap, map } from 'rxjs/operators';
import { HeaderService } from '../../../service/header.service';
import { FoldersService } from '../folders.service';

@Component({
    selector: 'app-folder-action-list',
    templateUrl: "folder-action-list.component.html",
    styleUrls: ['folder-action-list.component.scss'],
    providers: [NotificationService],
})
export class FolderActionListComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;

    @ViewChild(MatMenuTrigger, { static: false }) contextMenu: MatMenuTrigger;
    @Output() triggerEvent = new EventEmitter<string>();

    contextMenuPosition = { x: '0px', y: '0px' };
    contextMenuTitle = '';
    currentAction: any = {};
    basketInfo: any = {};
    contextResId = 0;
    currentLock: any = null;
    arrRes: any[] = [];

    actionsList: any[] = [];
    basketList: any = {
        groups: [],
        list: []
    };

    @Input('selectedRes') selectedRes: any;
    @Input('totalRes') totalRes: number;
    @Input('contextMode') contextMode: boolean;
    @Input('currentFolderInfo') currentFolderInfo: any;

    @Output('refreshEvent') refreshEvent = new EventEmitter<string>();
    @Output('refreshPanelFolders') refreshPanelFolders = new EventEmitter<string>();

    constructor(
        public http: HttpClient, 
        private notify: NotificationService, 
        public dialog: MatDialog, 
        private router: Router,
        private headerService: HeaderService,
        private foldersService: FoldersService
        ) { }

    dialogRef: MatDialogRef<any>;
    
    ngOnInit(): void { }

    open(x: number, y: number, row: any) {

        // Adjust the menu anchor position
        this.contextMenuPosition.x = x + 'px';
        this.contextMenuPosition.y = y + 'px';

        this.contextMenuTitle = row.chrono;
        this.contextResId = row.resId;

        // Opens the menu
        this.contextMenu.openMenu();

        // prevents default
        return false;
    }

    refreshFolders() {
        this.refreshPanelFolders.emit();  
    }

    refreshDaoAfterAction() {
        this.refreshEvent.emit();
    }

    unclassify() {
        this.dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.lang.delete, msg: 'Voulez-vous enlever <b>' + this.selectedRes.length + '</b> document(s) du classement ?' } });

        this.dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.request('DELETE', '../../rest/folders/' + this.currentFolderInfo.id + '/resources', { body: { resources: this.selectedRes } })),
            tap((data: any) => {
                this.notify.success(this.lang.removedFromFolder);
                this.refreshFolders();
                this.foldersService.getPinnedFolders();
                this.refreshDaoAfterAction();
            })
        ).subscribe();
    }

    getBaskets() {
        this.http.get('../../rest/folders/' + this.currentFolderInfo.id + '/resources/' + this.selectedRes + '/baskets').pipe(
            tap((data: any) => {
                this.basketList.groups = data.groupsBaskets.filter((x: any, i: any, a: any) => x && a.map((info: any) => info.groupId).indexOf(x.groupId) === i);
                this.basketList.list = data.groupsBaskets;
            })
        ).subscribe();
    }


    goTo(basket: any) {
        if (this.contextMenuTitle !== this.lang.undefined) {
            this.router.navigate(['/basketList/users/' + this.headerService.user.id + '/groups/' + basket.groupId + '/baskets/' + basket.basketId], { queryParams: { chrono: '"' + this.contextMenuTitle + '"' } });
        } else {
            this.router.navigate(['/basketList/users/' + this.headerService.user.id + '/groups/' + basket.groupId + '/baskets/' + basket.basketId]);
        }
    }
}

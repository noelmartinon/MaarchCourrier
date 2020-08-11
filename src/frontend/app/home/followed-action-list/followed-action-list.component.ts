import { Component, OnInit, Input, ViewChild, Output, EventEmitter } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '../../../service/notification/notification.service';
import { MatDialog, MatDialogRef } from '@angular/material/dialog';
import { MatMenuTrigger } from '@angular/material/menu';

import { Router } from '@angular/router';
import { ConfirmComponent } from '../../../plugins/modal/confirm.component';
import { filter, exhaustMap, tap, map, catchError } from 'rxjs/operators';
import { HeaderService } from '../../../service/header.service';
import { MenuShortcutComponent } from '../../menu/menu-shortcut.component';
import { of } from 'rxjs/internal/observable/of';

@Component({
    selector: 'app-followed-action-list',
    templateUrl: 'followed-action-list.component.html',
    styleUrls: ['followed-action-list.component.scss'],
})
export class FollowedActionListComponent implements OnInit {

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
    folderList: any[] = [];

    actionsList: any[] = [];
    basketList: any = {
        groups: [],
        list: []
    };

    @Input() selectedRes: any;
    @Input() totalRes: number;
    @Input() contextMode: boolean;
    @Input() currentFolderInfo: any;

    @Input() menuShortcut: MenuShortcutComponent;

    @Output() refreshEvent = new EventEmitter<string>();
    @Output() refreshPanelFolders = new EventEmitter<string>();

    constructor(
        private translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        public dialog: MatDialog,
        private router: Router,
        private headerService: HeaderService,
    ) { }

    dialogRef: MatDialogRef<any>;

    ngOnInit(): void { }

    open(x: number, y: number, row: any) {
        // Adjust the menu anchor position
        this.contextMenuPosition.x = x + 'px';
        this.contextMenuPosition.y = y + 'px';

        this.contextMenuTitle = row.chrono;
        this.contextResId = row.resId;

        this.folderList = row.folders !== undefined ? row.folders : [];
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

    unFollow() {
        this.dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.translate.instant('lang.delete'), msg: this.translate.instant('lang.stopFollowingAlert') } });

        this.dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.request('DELETE', '../rest/resources/unfollow', { body: { resources: this.selectedRes } })),
            tap((data: any) => {
                this.notify.success(this.translate.instant('lang.removedFromFolder'));
                this.headerService.nbResourcesFollowed -= data.unFollowed;
                this.refreshDaoAfterAction();
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    getBaskets() {
        this.http.get('../rest/followedResources/' + this.selectedRes + '/baskets').pipe(
            tap((data: any) => {
                this.basketList.groups = data.groupsBaskets.filter((x: any, i: any, a: any) => x && a.map((info: any) => info.groupId).indexOf(x.groupId) === i);
                this.basketList.list = data.groupsBaskets;
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    goTo(basket: any) {
        if (this.contextMenuTitle !== this.translate.instant('lang.undefined') && this.contextMenuTitle !== '') {
            this.router.navigate(['/basketList/users/' + this.headerService.user.id + '/groups/' + basket.groupId + '/baskets/' + basket.basketId], { queryParams: { chrono: '"' + this.contextMenuTitle + '"' } });
        } else {
            this.router.navigate(['/basketList/users/' + this.headerService.user.id + '/groups/' + basket.groupId + '/baskets/' + basket.basketId]);
        }
    }

    refreshList() {
        this.refreshEvent.emit();
    }

}

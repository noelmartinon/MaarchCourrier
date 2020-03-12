import { Component, OnInit, NgZone } from '@angular/core';
import { LANG } from '../translate.component';
import { MatDialog, MatDialogRef } from '@angular/material/dialog';
import { MatSidenav } from '@angular/material/sidenav';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { IndexingGroupModalComponent } from './menu-shortcut.component';
import { HeaderService } from '../../service/header.service';
import { AppService } from '../../service/app.service';
import { PrivilegeService } from '../../service/privileges.service';

declare function $j(selector: any): any;

@Component({
    selector: "main-header",
    templateUrl: "main-header.component.html",
    providers: [AppService],
})
export class MainHeaderComponent implements OnInit {

    lang: any = LANG;
    user: any = { firstname: "", lastname: "" };
    dialogRef: MatDialogRef<any>;
    config: any = {};
    titleHeader: string;
    router: any;
    menus: any = [];

    snav: MatSidenav;
    snav2: MatSidenav;


    constructor(
        public http: HttpClient,
        private zone: NgZone,
        private _router: Router,
        public headerService: HeaderService,
        public dialog: MatDialog,
        public appService: AppService,
        private privilegeService: PrivilegeService
    ) {
        this.router = _router;
        window['MainHeaderComponent'] = {
            refreshTitle: (value: string) => this.setTitle(value),
            setSnav: (value: MatSidenav) => this.getSnav(value),
            setSnavRight: (value: MatSidenav) => this.getSnavRight(value),
        };
    }

    ngOnInit(): void { }

    getMenus() {
        this.menus = this.privilegeService.getCurrentUserMenus();
    }

    setTitle(title: string) {
        this.zone.run(() => this.titleHeader = title);
    }

    getSnav(snav: MatSidenav) {
        this.zone.run(() => this.snav = snav);
    }

    getSnavRight(snav2: MatSidenav) {
        if (snav2 == null) {
            $j('#snav2Button').hide();
        } else {
            $j('#snav2Button').show();
            this.zone.run(() => this.snav2 = snav2);
        }
    }

    gotToMenu(shortcut: any) {
        if (shortcut.id == 'indexing' && shortcut.groups.length > 1) {
            this.config = { panelClass: 'maarch-modal', data: { indexingGroups: shortcut.groups, link: shortcut.route } };
            this.dialogRef = this.dialog.open(IndexingGroupModalComponent, this.config);
        } else if (shortcut.angular === true) {
            this.router.navigate([shortcut.route]);
        } else {
            location.href = shortcut.route;
        }
    }
}

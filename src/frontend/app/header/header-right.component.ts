import { Component, OnInit, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { HeaderService } from '../../service/header.service';
import { MatDialog, MatDialogRef } from '@angular/material/dialog';
import { MatInput } from '@angular/material/input';
import { IndexingGroupModalComponent } from '../menu/menu-shortcut.component';
import { Router } from '@angular/router';
import { AppService } from '../../service/app.service';
import { PrivilegeService } from '../../service/privileges.service';
import { FunctionsService } from '../../service/functions.service';

@Component({
    selector: 'header-right',
    styleUrls: ['header-right.component.scss'],
    templateUrl: "header-right.component.html",
})
export class HeaderRightComponent implements OnInit {

    lang: any = LANG;

    dialogRef: MatDialogRef<any>;
    config: any = {};
    menus: any = [];

    hideSearch: boolean = true;

    @ViewChild('searchInput', { static: false }) searchInput: MatInput;

    constructor(
        public http: HttpClient,
        private router: Router,
        public dialog: MatDialog,
        public appService: AppService,
        public headerService: HeaderService,
        public functions: FunctionsService,
        public privilegeService: PrivilegeService) { }

    ngOnInit(): void {
        this.menus = this.privilegeService.getCurrentUserMenus();
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

    showSearchInput() {
        this.hideSearch = !this.hideSearch;
        setTimeout(() => {
            this.searchInput.focus();
        }, 200);
    }
}

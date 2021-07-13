import { Component, OnInit, ViewChild }    from '@angular/core';
import { HttpClient }           from '@angular/common/http';
import { LANG }                 from '../translate.component';
import { HeaderService }        from '../../service/header.service';
import { MatDialog, MatDialogRef, MatInput } from '@angular/material';
import { IndexingGroupModalComponent } from '../menu/menu-shortcut.component';
import { Router } from '@angular/router';

declare function $j(selector: any): any;
declare var angularGlobals: any;

@Component({
    selector: 'header-right',
    styleUrls: ['header-right.component.scss'],
    templateUrl : "header-right.component.html",
})
export class HeaderRightComponent implements OnInit {

    lang        : any       = LANG;
    mobileMode  : boolean   = false;

    dialogRef   : MatDialogRef<any>;
    config      : any       = {};

    hideSearch : boolean = true;

    @ViewChild('searchInput') searchInput: MatInput;

    constructor(public http: HttpClient, private router: Router, public headerService: HeaderService, public dialog: MatDialog) {
        this.mobileMode = angularGlobals.mobileMode;
     }

    ngOnInit(): void {}

    gotToMenu(shortcut:any) {
        if (shortcut.id == 'index_mlb' && this.headerService.user.indexingGroups.length > 1) {
            this.config = { data: { indexingGroups:this.headerService.user.indexingGroups, link:shortcut.servicepage } };
            this.dialogRef = this.dialog.open(IndexingGroupModalComponent, this.config);
        } else if (shortcut.angular == 'true') {
            this.router.navigate([shortcut.servicepage]);
        } else {
            location.href = shortcut.servicepage;
        }
    }

    showSearchInput() {
        this.hideSearch = !this.hideSearch;
        setTimeout(() => {
            this.searchInput.focus(); 
        }, 200);
    }
}
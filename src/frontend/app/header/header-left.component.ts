import { Component, OnInit, Input } from '@angular/core';
import { LANG } from '../translate.component';
import { HeaderService } from '../../service/header.service';
import { MatDialogRef } from '@angular/material/dialog';
import { MatSidenav } from '@angular/material/sidenav';

@Component({
    selector: 'header-left',
    styleUrls: ['header-left.component.scss'],
    templateUrl: "header-left.component.html",
})
export class HeaderLeftComponent implements OnInit {

    lang: any = LANG;
    dialogRef: MatDialogRef<any>;
    config: any = {};

    @Input() snavLeft: MatSidenav;

    constructor(
        public headerService: HeaderService
    ) { }

    ngOnInit(): void { }
}
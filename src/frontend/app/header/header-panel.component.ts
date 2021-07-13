import { Component, OnInit, Input }    from '@angular/core';
import { LANG }                 from '../translate.component';
import { HeaderService }        from '../../service/header.service';
import { MatDialogRef, MatSidenav }         from '@angular/material';

@Component({
    selector: 'header-panel',
    styleUrls: ['header-panel.component.scss'],
    templateUrl : "header-panel.component.html",
})
export class HeaderPanelComponent implements OnInit {

    lang        : any       = LANG;
    mobileMode  : boolean   = false;

    dialogRef   : MatDialogRef<any>;
    config      : any       = {};

    @Input('snavLeft') snavLeft: MatSidenav;
    
    constructor(public headerService: HeaderService) { }

    ngOnInit(): void { }
}
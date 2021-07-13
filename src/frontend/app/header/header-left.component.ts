import { Component, OnInit, Input }    from '@angular/core';
import { LANG }                 from '../translate.component';
import { HeaderService }        from '../../service/header.service';
import { MatDialogRef, MatSidenav }         from '@angular/material';

@Component({
    selector: 'header-left',
    styleUrls: ['header-left.component.scss'],
    templateUrl : "header-left.component.html",
})
export class HeaderLeftComponent implements OnInit {

    lang        : any       = LANG;
    mobileMode  : boolean   = false;

    dialogRef   : MatDialogRef<any>;
    config      : any       = {};

    @Input('snavLeft') snavLeft: MatSidenav;
    
    constructor(public headerService: HeaderService) { }

    ngOnInit(): void { }
}
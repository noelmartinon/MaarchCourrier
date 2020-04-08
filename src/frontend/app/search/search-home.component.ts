import { Component, OnInit } from '@angular/core';
import { LANG } from '../translate.component';

@Component({
    selector: 'search-home',
    templateUrl: 'search-home.component.html',
})
export class SearchHomeComponent implements OnInit {

    lang: any = LANG;

    constructor() {
    }

    ngOnInit(): void { }

}
